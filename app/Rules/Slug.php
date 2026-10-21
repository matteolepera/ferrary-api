<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Slug implements ValidationRule
{
    /**
     * Solo minuscole, numeri e trattini singoli tra le parole (es. "812-superfast").
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            $fail('Lo slug può contenere solo lettere minuscole, numeri e trattini.');
        }
    }
}
