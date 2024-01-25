<?php

namespace App\Providers;

use App\Models\Company;
use App\Intranet\Utils\Constants;
use Illuminate\Support\ServiceProvider;
use App\Intranet\Companies\CompanyBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Constants::set('PRUEBA', 'nube.arzuma.es/3050:C:\Distrito2\TPV\Database\PRUEBAS-\2024.FDB');
        foreach (Company::all(['name']) as  $company) {

            CompanyBuilder::generateHostConstant($company['name']);
        };
    }
}
