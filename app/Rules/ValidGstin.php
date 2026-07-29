<?php

namespace App\Rules;

use App\Support\Gstin;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidGstin implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value && ! Gstin::isValid($value)) {
            $fail('The :attribute is not a valid GSTIN (format or checksum failed).');
        }
    }
}
