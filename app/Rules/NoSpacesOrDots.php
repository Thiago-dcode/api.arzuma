<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSpacesOrDots implements ValidationRule
{
       /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/[\s\.]/', $value)) {
            $fail('The :attribute field should not contain spaces or dots.');
        }
    }
}
