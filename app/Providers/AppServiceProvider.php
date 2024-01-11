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
        foreach (Company::all(['name']) as  $company) {

            CompanyBuilder::generateHostConstant($company['name']);
        };
    }
}
