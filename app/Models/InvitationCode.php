<?php

namespace App\Models;

use Database\Factories\InvitationCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['code', 'edition_id', 'used_at', 'user_id'])]
class InvitationCode extends Model
{
    /** @use HasFactory<InvitationCodeFactory> */
    use HasFactory;

    /**
     * Caracteres sans ambiguite visuelle : ni O/0 ni I/1/L.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    #[Scope]
    protected function available(Builder $query): void
    {
        $query->whereNull('used_at');
    }

    /**
     * Genere un code lisible du type SALON-4K9TQ2.
     */
    public static function generateCode(): string
    {
        $suffix = collect(range(1, 6))
            ->map(fn (): string => Str::substr(self::ALPHABET, random_int(0, Str::length(self::ALPHABET) - 1), 1))
            ->implode('');

        return 'SALON-'.$suffix;
    }
}
