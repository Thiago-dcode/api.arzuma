<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Module;

class ModulesExists implements Rule
{
    public function passes($attribute, $value)
    {
        foreach ($value as $module) {
            if (!Module::where('id', $module)->exists()) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'One or more modules do not exist in the database.';
    }
}
