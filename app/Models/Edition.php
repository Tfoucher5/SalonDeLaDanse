<?php

namespace App\Models;

use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

#[Fillable([
    'name',
    'starts_on',
    'ends_on',
    'registration_opens_at',
    'registration_closes_at',
    'is_locked',
    'min_slots_per_volunteer',
    'max_slots_per_volunteer',
    'is_active',
])]
class Edition extends Model
{
    /** @use HasFactory<EditionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'is_locked' => 'boolean',
            'is_active' => 'boolean',
            'min_slots_per_volunteer' => 'integer',
            'max_slots_per_volunteer' => 'integer',
        ];
    }

    /**
     * La composition du planning est-elle ouverte ?
     *
     * Le verrouillage manuel global prime sur les dates. Une borne nulle signifie
     * qu il n y a pas de limite de ce cote.
     */
    public function registrationIsOpen(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $now = now();

        if ($this->registration_opens_at !== null && $now->lessThan($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at !== null && $now->greaterThan($this->registration_closes_at)) {
            return false;
        }

        return true;
    }

    /**
     * Les jours de l evenement, du premier au dernier inclus.
     *
     * @return Collection<int, Carbon>
     */
    public function days()
    {
        $days = collect();

        for ($day = $this->starts_on->copy(); $day->lessThanOrEqualTo($this->ends_on); $day->addDay()) {
            $days->push($day->copy());
        }

        return $days;
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<InvitationCode, $this> */
    public function invitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class);
    }

    /** @return HasMany<TimeSlot, $this> */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class)->orderBy('position');
    }

    /** @return HasMany<Mission, $this> */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class)->orderBy('position');
    }

    /** @return HasMany<Shift, $this> */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * L edition courante du MVP. Une seule ligne active en base.
     */
    public static function current(): ?self
    {
        return static::query()->active()->orderBy('id')->first();
    }
}
