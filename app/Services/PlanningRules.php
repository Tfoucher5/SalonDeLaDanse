<?php

namespace App\Services;

use App\Enums\BookingRule;
use App\Enums\PlanningState;
use App\Exceptions\BookingRuleException;
use App\Models\Assignment;
use App\Models\Edition;
use App\Models\Shift;
use App\Models\User;
use Closure;
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

        return $this->shiftViolationFor($user, $shift, $edition, $bookedShifts);
    }

    /**
     * La règle que l'administrateur s'apprête à outrepasser, null si aucune.
     *
     * L'état du planning n'entre pas en compte : le back-office écrit sur un
     * planning verrouillé par construction, ce n'est pas un contournement mais
     * sa raison d'être. Cette méthode sert à annoncer ce qui est forcé — « ce
     * créneau était complet » — pas à l'empêcher.
     */
    public function overriddenRuleFor(User $user, Shift $shift): ?BookingRule
    {
        $edition = $user->activeEdition();

        if ($edition === null) {
            return BookingRule::PlanningClosed;
        }

        return $this->shiftViolationFor($user, $shift, $edition);
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
     * Attribue un créneau au nom de l'équipe organisatrice.
     *
     * C'est la main de l'administrateur : quota, chevauchement, tranches
     * consécutives, jauge pleine, mission restreinte et planning verrouillé
     * sont tous outrepassés. Ne subsistent que les deux refus qui ne sont pas
     * des règles métier mais des incohérences — un créneau déjà retenu, et un
     * créneau d'une autre édition.
     *
     * L'attribution porte `assigned_by_admin` : elle verrouille le planning du
     * bénévole, qui ne doit pas pouvoir défaire un poste sensible.
     *
     * @throws BookingRuleException
     */
    public function assignAsAdmin(User $user, Shift $shift): Assignment
    {
        return DB::transaction(function () use ($user, $shift): Assignment {
            $locked = Shift::query()->whereKey($shift->getKey())->lockForUpdate()->firstOrFail();

            $blocker = $this->adminBlockerFor($user, $locked);

            if ($blocker !== null) {
                throw BookingRuleException::make($blocker, $user->activeEdition());
            }

            return Assignment::create([
                'user_id' => $user->getKey(),
                'shift_id' => $locked->getKey(),
                'assigned_by_admin' => true,
            ]);
        });
    }

    /**
     * Retire un créneau au nom de l'équipe organisatrice.
     *
     * Contrairement à `release()`, l'état du planning n'est pas consulté :
     * validé ou verrouillé, l'administrateur reprend ce qu'il a posé.
     *
     * @throws BookingRuleException
     */
    public function revokeAsAdmin(User $user, Shift $shift): void
    {
        $deleted = $user->assignments()->where('shift_id', $shift->getKey())->delete();

        if ($deleted === 0) {
            throw BookingRuleException::make(BookingRule::NotBooked, $user->activeEdition());
        }
    }

    /**
     * Rend au bénévole la main sur son planning validé.
     *
     * Seule la validation définitive est levée. Une attribution faite par
     * l'équipe organisatrice continue de verrouiller le planning : la lever
     * ici laisserait le bénévole retirer une mission restreinte qu'il ne
     * pourrait jamais reprendre. Pour cela, on retire le créneau lui-même.
     */
    public function unlock(User $user): void
    {
        DB::transaction(function () use ($user): void {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $locked->planning_validated_at = null;
            $locked->save();

            $user->planning_validated_at = null;
        });
    }

    /**
     * Le planning de ce bénévole reste-t-il verrouillé après `unlock()` ?
     *
     * Répond à la seule question utile à l'écran : le bénévole a-t-il vraiment
     * repris la main, ou une attribution forcée le retient-elle encore ?
     */
    public function isLockedByForcedAssignment(User $user): bool
    {
        return $user->assignments()->where('assigned_by_admin', true)->exists();
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
     * La règle qui empêche l'équipe organisatrice de figer ce planning.
     *
     * Deux refus de `validationViolationFor()` tombent ici, et c'est voulu.
     * La fenêtre d'inscription fermée n'en est pas un : l'organisation valide
     * précisément une fois les inscriptions closes. Le verrouillage par
     * attribution forcée non plus : l'administrateur ne peut pas être bloqué
     * par ce qu'il vient lui-même de poser.
     *
     * Le quota minimum, lui, tient. Un planning sous le quota n'est pas
     * validable — mais l'administrateur peut y attribuer un créneau, puis
     * valider.
     */
    public function validationViolationForAdmin(User $user): ?BookingRule
    {
        $edition = $user->activeEdition();

        if ($edition === null) {
            return BookingRule::PlanningClosed;
        }

        if ($user->planningIsValidated()) {
            return BookingRule::PlanningValidated;
        }

        if ($user->assignments()->count() < $edition->min_slots_per_volunteer) {
            return BookingRule::MinimumSlotsNotReached;
        }

        return null;
    }

    /**
     * Fige le planning d'un bénévole : il passe en lecture seule.
     *
     * @throws BookingRuleException
     */
    public function validate(User $user): void
    {
        $this->freeze($user, fn (User $locked): ?BookingRule => $this->validationViolationFor($locked));
    }

    /**
     * Fige le planning au nom de l'équipe organisatrice.
     *
     * C'est le chemin réellement emprunté par l'application : la validation
     * définitive est une décision de l'organisation, prise depuis le
     * back-office, pas un bouton du bénévole.
     *
     * @throws BookingRuleException
     */
    public function validateAsAdmin(User $user): void
    {
        $this->freeze($user, fn (User $locked): ?BookingRule => $this->validationViolationForAdmin($locked));
    }

    /**
     * L'écriture de la validation, commune aux deux chemins.
     *
     * Le bénévole est relu sous verrou avant que la règle ne soit évaluée :
     * deux validations envoyées coup sur coup ne doivent en produire qu'une.
     *
     * @param  Closure(User): ?BookingRule  $violationFor
     *
     * @throws BookingRuleException
     */
    private function freeze(User $user, Closure $violationFor): void
    {
        DB::transaction(function () use ($user, $violationFor): void {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $violation = $violationFor($locked);

            if ($violation !== null) {
                throw BookingRuleException::make($violation, $locked->activeEdition());
            }

            $locked->planning_validated_at = now();
            $locked->save();

            $user->planning_validated_at = $locked->planning_validated_at;
        });
    }

    /**
     * Ce que même l'administrateur ne peut pas forcer.
     *
     * Ni l'un ni l'autre n'est une règle du Salon : ce sont deux incohérences
     * de données qu'aucune décision d'organisation ne justifie.
     */
    private function adminBlockerFor(User $user, Shift $shift): ?BookingRule
    {
        $edition = $user->activeEdition();

        if ($edition === null) {
            return BookingRule::PlanningClosed;
        }

        if ($user->assignments()->where('shift_id', $shift->getKey())->exists()) {
            return BookingRule::AlreadyBooked;
        }

        if ($shift->edition_id !== $edition->id) {
            return BookingRule::OutsideEdition;
        }

        return null;
    }

    /**
     * Les règles qui portent sur le créneau lui-même, état du planning mis à
     * part.
     *
     * Le bénévole les subit toutes ; l'administrateur les lit pour savoir ce
     * qu'il force. Les deux passent par ici, il n'existe qu'une définition de
     * « ce créneau est complet » ou de « cette mission est restreinte ».
     *
     * @param  Collection<int, Shift>|null  $bookedShifts
     */
    private function shiftViolationFor(User $user, Shift $shift, Edition $edition, ?Collection $bookedShifts = null): ?BookingRule
    {
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

        if (! $shift->mission->is_active) {
            return BookingRule::ClosedMission;
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
