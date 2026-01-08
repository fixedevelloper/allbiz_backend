<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\+?[0-9]{8,15}$/', $value)) {
            $fail('Le numéro de téléphone est invalide.');
        }
    }
}
