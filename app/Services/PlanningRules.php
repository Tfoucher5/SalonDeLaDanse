<?php

namespace App\Services;

use App\Enums\BookingRule;
use App\Enums\PlanningState;
use App\Exceptions\BookingRuleException;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Le seul endroit où vivent les règles de réservation d'un créneau.
 *
 * La grille, le FormRequest et le back-office interrogent tous ce service :
 * une règle dupliquée finit par diverger. Le front n'est qu'un confort, l'état
 * serveur reste la référence — `book()` revérifie donc tout sous verrou, même
 * ce que le FormRequest vient de valider.
 */
class PlanningRules
{
    /**
     * Les créneaux déjà retenus par le bénévole, tranche horaire comprise.
     *
     * Chargés une seule fois puis passés à `violationFor()` : la grille compte
     * 135 créneaux, une requête par carte serait ruineuse.
     *
     * @return Collection<int, Shift>
     */
    public function bookedShifts(User $user): Collection
    {
        return $user->shifts()->with('timeSlot:id,position')->get();
    }

    /**
     * La première règle qui interdit ce créneau à ce bénévole, null si aucune.
     *
     * L'ordre des vérifications est l'ordre d'utilité du message : « votre
     * planning est validé » renseigne mieux que « ce créneau est complet ».
     *
     * @param  Collection<int, Shift>|null  $bookedShifts  créneaux déjà retenus, chargés par l'appelant
     */
    public function violationFor(User $user, Shift $shift, ?Collection $bookedShifts = null): ?BookingRule
    {
        $edition = $user->activeEdition();
        $state = PlanningState::for($user, $edition);

        if ($edition === null) {
            return BookingRule::PlanningClosed;
        }

        $locked = $this->lockedBy($state);

        if ($locked !== null) {
            return $locked;
        }

        $booked = $bookedShifts ?? $this->bookedShifts($user);

        if ($booked->contains(fn (Shift $retained): bool => $retained->id === $shift->id)) {
            return BookingRule::AlreadyBooked;
        }

        if ($shift->edition_id !== $edition->id) {
            return BookingRule::OutsideEdition;
        }

        if (! $shift->mission->is_public) {
            return BookingRule::RestrictedMission;
        }

        if ($booked->count() >= $edition->max_slots_per_volunteer) {
            return BookingRule::MaxSlotsReached;
        }

        if ($this->overlaps($booked, $shift)) {
            return BookingRule::OverlappingSlot;
        }

        if ($this->wouldChainThreeSlots($booked, $shift)) {
            return BookingRule::ThreeConsecutiveSlots;
        }

        if ($shift->isFull()) {
            return BookingRule::ShiftFull;
        }

        return null;
    }

    /**
     * Ajoute un créneau au planning en brouillon.
     *
     * @throws BookingRuleException
     */
    public function book(User $user, Shift $shift): Assignment
    {
        return DB::transaction(function () use ($user, $shift): Assignment {
            // Le créneau est relu depuis la base, verrou posé : le modèle reçu
            // vient de l'affichage de la grille et sa jauge peut avoir vieilli.
            $locked = Shift::query()->whereKey($shift->getKey())->lockForUpdate()->firstOrFail();

            $violation = $this->violationFor($user, $locked);

            if ($violation !== null) {
                throw BookingRuleException::make($violation, $user->activeEdition());
            }

            $assignment = Assignment::create([
                'user_id' => $user->getKey(),
                'shift_id' => $locked->getKey(),
                'assigned_by_admin' => false,
            ]);

            // SQLite ignore `lockForUpdate`. Ce recomptage après insertion est le
            // garde-fou qui fait échouer — et annuler — une jauge dépassée.
            if ($locked->assignments()->count() > $locked->capacity) {
                throw BookingRuleException::make(BookingRule::ShiftFull, $user->activeEdition());
            }

            return $assignment;
        });
    }

    /**
     * Retire un créneau du planning en brouillon.
     *
     * @throws BookingRuleException
     */
    public function release(User $user, Shift $shift): void
    {
        $edition = $user->activeEdition();
        $locked = $this->lockedBy(PlanningState::for($user, $edition));

        if ($locked !== null) {
            throw BookingRuleException::make($locked, $edition);
        }

        $deleted = $user->assignments()->where('shift_id', $shift->getKey())->delete();

        if ($deleted === 0) {
            throw BookingRuleException::make(BookingRule::NotBooked, $edition);
        }
    }

    /**
     * La règle qui empêche la validation définitive, null si elle est possible.
     *
     * La validation appartient à l'équipe organisatrice, pas au bénévole : ce
     * sont le back-office et ses tests qui appellent cette règle. Le quota
     * minimum ne se vérifie qu'ici — un planning en brouillon a le droit d'être
     * vide, c'est au moment de le figer qu'il doit tenir debout.
     */
    public function validationViolationFor(User $user): ?BookingRule
    {
        $edition = $user->activeEdition();

        if ($edition === null) {
            return BookingRule::PlanningClosed;
        }

        $locked = $this->lockedBy(PlanningState::for($user, $edition));

        if ($locked !== null) {
            return $locked;
        }

        if ($user->assignments()->count() < $edition->min_slots_per_volunteer) {
            return BookingRule::MinimumSlotsNotReached;
        }

        return null;
    }

    /**
     * Fige le planning d'un bénévole : il passe en lecture seule.
     *
     * Le bénévole est relu sous verrou avant d'écrire la date : deux
     * validations envoyées coup sur coup ne doivent en produire qu'une.
     *
     * @throws BookingRuleException
     */
    public function validate(User $user): void
    {
        DB::transaction(function () use ($user): void {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $violation = $this->validationViolationFor($locked);

            if ($violation !== null) {
                throw BookingRuleException::make($violation, $locked->activeEdition());
            }

            $locked->planning_validated_at = now();
            $locked->save();

            $user->planning_validated_at = $locked->planning_validated_at;
        });
    }

    /**
     * La règle qui ferme le planning à l'écriture, null s'il reste un brouillon.
     *
     * Ajout et retrait passent par cette même traduction : un planning fermé
     * l'est dans les deux sens.
     */
    private function lockedBy(PlanningState $state): ?BookingRule
    {
        return match ($state) {
            PlanningState::Draft => null,
            PlanningState::Validated => BookingRule::PlanningValidated,
            PlanningState::Locked => BookingRule::PlanningLockedByAdmin,
            PlanningState::Closed => BookingRule::PlanningClosed,
        };
    }

    /**
     * Deux missions sur la même tranche horaire d'une même journée.
     *
     * Deux jours différents ne se chevauchent jamais, même à la même heure.
     *
     * @param  Collection<int, Shift>  $booked
     */
    private function overlaps(Collection $booked, Shift $shift): bool
    {
        return $booked->contains(
            fn (Shift $retained): bool => $retained->time_slot_id === $shift->time_slot_id
                && $retained->date->isSameDay($shift->date)
        );
    }

    /**
     * Le créneau visé fermerait-il une série de trois tranches consécutives ?
     *
     * La consécutivité se lit sur `position`, l'ordre de la tranche dans la
     * journée, et ne vaut qu'à l'intérieur d'un même jour.
     *
     * @param  Collection<int, Shift>  $booked
     */
    private function wouldChainThreeSlots(Collection $booked, Shift $shift): bool
    {
        // `toBase()` : filtre puis map rendent une collection Eloquent tant qu'ils
        // ne voient aucun modele, et un entier n'y a pas sa place.
        $positions = $booked
            ->filter(fn (Shift $retained): bool => $retained->date->isSameDay($shift->date))
            ->map(fn (Shift $retained): int => $retained->timeSlot->position)
            ->toBase()
            ->push($shift->timeSlot->position)
            ->unique();

        return $positions->contains(
            fn (int $position): bool => $positions->contains($position + 1) && $positions->contains($position + 2)
        );
    }
}
