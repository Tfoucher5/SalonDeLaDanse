<?php

namespace App\Exceptions;

use App\Enums\BookingRule;
use App\Models\Edition;
use RuntimeException;

/**
 * Une règle métier a refusé l'écriture du planning.
 *
 * L'exception transporte la règle en plus du message : l'appelant peut ainsi
 * réagir au cas précis sans comparer des chaînes de caractères.
 */
class BookingRuleException extends RuntimeException
{
    private function __construct(public readonly BookingRule $rule, string $message)
    {
        parent::__construct($message);
    }

    public static function make(BookingRule $rule, ?Edition $edition = null): self
    {
        return new self($rule, $rule->message($edition));
    }
}
