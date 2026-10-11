<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HalfHour implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(?:[01][0-9]|2[0-3]):(?:00|30)$/', $value)) {
            $fail('Jam harus kelipatan 30 menit (:00 atau :30).');
        }
    }
}
