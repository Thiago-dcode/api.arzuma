<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Modules\Chart;
use App\Models\Module;
use App\Models\Company;
use App\Models\ModuleUser;
use App\Intranet\Utils\Utils;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    protected $with = ['companies'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }
    public function charts()
    {

        return $this->hasMany(Chart::class);
    }

    public static function allModules($userId)
    {

        $modulesIds = ModuleUser::where('user_id', $userId)->pluck('module_id')->toArray();
        return DB::table('modules')->whereIn('id', $modulesIds)->get();
    }
    public static function allModulesByCompany($userId, $companyName)
    {

        $modulesIds = ModuleUser::where('user_id', $userId)->where('company', $companyName)->pluck('module_id')->toArray();


        return  DB::table('modules')->whereIn('id', $modulesIds)->get();
    }
    public static function findModule($userId, $companyName, $moduleId)
    {
        $_module = null;
        foreach (static::allModulesByCompany($userId, $companyName) as $key => $module) {
            $module = Utils::objectToArray($module);
          
            if ($module['id'] !== (int)$moduleId) continue;
            $_module = $module;
        }
        return $_module;
    }
}
