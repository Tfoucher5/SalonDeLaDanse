<?php

namespace App\Models;

use Database\Factories\MissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['edition_id', 'name', 'slug', 'is_public', 'is_active', 'default_capacity', 'instructions', 'position'])]
class Mission extends Model
{
    /** @use HasFactory<MissionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'default_capacity' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasMany<Shift, $this> */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * La mission est-elle encore proposee au benevole ?
     *
     * Une mission fermee garde ses inscrits : on cesse de l offrir, on ne
     * defait pas ce qui est deja pose.
     */
    public function isBookable(): bool
    {
        return $this->is_public && $this->is_active;
    }

    /**
     * Toutes les inscriptions portees par les creneaux de cette mission.
     *
     * @return HasManyThrough<Assignment, Shift, $this>
     */
    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(Assignment::class, Shift::class);
    }

    /**
     * Missions ouvertes a la reservation par les benevoles.
     */
    #[Scope]
    protected function public(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /**
     * Missions encore proposees : publiques et ouvertes.
     */
    #[Scope]
    protected function bookable(Builder $query): void
    {
        $query->where('is_public', true)->where('is_active', true);
    }

    /**
     * Missions sous restriction : Billetterie et Caisse, attribuees par l admin seul.
     */
    #[Scope]
    protected function restricted(Builder $query): void
    {
        $query->where('is_public', false);
    }
}
