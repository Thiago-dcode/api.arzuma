<?php

use App\Models\User;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Intranet\UserController;
use App\Http\Controllers\Intranet\ModuleController;
use App\Http\Controllers\Intranet\CompanyController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware(['auth:sanctum', 'active'])->group(function () {


    $user = auth('sanctum')->user();
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/company', [AuthController::class, 'company']);
    Route::post('/active-company', [AuthController::class, 'activeCompany']);
    Route::post('/active-module', [AuthController::class, 'activeModule']);
    Route::get('/modules', [ModuleController::class, 'modules']);

    Route::group(['middleware' => 'admin'], function () {

        Route::post('/user', [UserController::class, 'create']);
        Route::patch('/user/{id}', [UserController::class, 'update']);
        Route::post('/company', [CompanyController::class, 'create']);
        Route::post('/modules', [ModuleController::class, 'create']);
        Route::patch('/company/{id}', [CompanyController::class, 'update']);
        Route::patch('/modules/{id}', [ModuleController::class, 'update']);
    });

    if ($user) {

        $modules = User::allModules($user->id);

        require_once __DIR__ . '/modules.php';

        moduleRoutes($modules->toArray());
    }
});

Route::post('/login', [AuthController::class, 'login']);
