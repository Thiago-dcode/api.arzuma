<?php

namespace App\Models;

use App\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;
    protected $with = ['modules'];
    protected $guarded = [];
    public function modules()
    {
        return $this->belongsToMany(Module::class);
    }
}
