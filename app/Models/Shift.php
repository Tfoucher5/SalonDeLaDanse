<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['edition_id', 'mission_id', 'time_slot_id', 'date', 'capacity'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * Le jour du creneau, stocke en base au format Y-m-d.
     *
     * Le cast date natif ecrirait un datetime a minuit : la colonne resterait
     * lisible, mais les exports et les recherches par jour deviendraient piegeux.
     *
     * @return Attribute<Carbon, string>
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): Carbon => Carbon::parse($value)->startOfDay(),
            set: fn (Carbon|string $value): string => Carbon::parse($value)->toDateString(),
        );
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /** @return BelongsTo<TimeSlot, $this> */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /** @return HasMany<Assignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Les benevoles inscrits sur ce creneau.
     *
     * Confidentialite : cette relation ne doit jamais alimenter une vue benevole,
     * elle est reservee au back-office.
     *
     * @return BelongsToMany<User, $this>
     */
    public function volunteers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assignments')
            ->withPivot('assigned_by_admin')
            ->withTimestamps();
    }

    /**
     * Nombre de reservations en cours.
     *
     * Compte par requete, jamais via un compteur denormalise : un compteur se
     * desynchronise.
     */
    public function takenPlaces(): int
    {
        if ($this->relationLoaded('assignments')) {
            return $this->assignments->count();
        }

        return $this->assignments_count ?? $this->assignments()->count();
    }

    /**
     * Places restantes sur le creneau.
     *
     * @return Attribute<int, never>
     */
    protected function remainingPlaces(): Attribute
    {
        return Attribute::get(fn (): int => max(0, $this->capacity - $this->takenPlaces()));
    }

    public function isFull(): bool
    {
        return $this->remaining_places === 0;
    }

    /**
     * Creneaux des missions encore proposees au benevole.
     *
     * Publique et active : une mission desactivee cesse d etre offerte, meme
     * si elle reste publique. L exclusion se fait par la requete, jamais a
     * l affichage.
     */
    #[Scope]
    protected function onBookableMissions(Builder $query): void
    {
        $query->whereHas('mission', fn (Builder $mission) => $mission->bookable());
    }
}
