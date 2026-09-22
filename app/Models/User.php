<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// `role`, `edition_id`, `profile_locked_at` et `planning_validated_at` sont
// volontairement hors du fillable : ils ne doivent jamais venir d une requete.
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'birth_date', 'password', 'photo_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
            'profile_locked_at' => 'datetime',
            'planning_validated_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isVolunteer(): bool
    {
        return $this->role === UserRole::Volunteer;
    }

    /**
     * Le planning a-t-il ete valide definitivement ?
     */
    public function planningIsValidated(): bool
    {
        return $this->planning_validated_at !== null;
    }

    /**
     * Le profil est verrouille des la creation du compte : seul un administrateur
     * peut encore modifier nom, prenom, e-mail et photo.
     */
    public function profileIsLocked(): bool
    {
        return $this->profile_locked_at !== null;
    }

    /**
     * L edition de rattachement du benevole.
     *
     * Les comptes crees avant le rattachement, et les comptes administrateurs,
     * n ont pas d edition propre : on retombe alors sur l edition courante.
     */
    public function activeEdition(): ?Edition
    {
        return $this->edition ?? Edition::current();
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasOne<InvitationCode, $this> */
    public function invitationCode(): HasOne
    {
        return $this->hasOne(InvitationCode::class);
    }

    /** @return HasMany<Assignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** @return BelongsToMany<Shift, $this> */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'assignments')
            ->withPivot('assigned_by_admin')
            ->withTimestamps();
    }

    #[Scope]
    protected function volunteers(Builder $query): void
    {
        $query->where('role', UserRole::Volunteer);
    }

    #[Scope]
    protected function admins(Builder $query): void
    {
        $query->where('role', UserRole::Admin);
    }
}
