<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function PHPSTORM_META\type;

class TxtFileRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (gettype($value) !== "object") {
            $fail('The :attribute must be type file');
        } else if ($value->getClientOriginalExtension() !== 'txt') {
            $fail('The :attribute should be type .txt');
        }
    }
}
