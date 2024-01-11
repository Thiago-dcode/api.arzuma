<?php
use App\Intranet\Utils\Utils;
use App\Http\Controllers\Intranet\Modules\ClientesController;
use App\Http\Controllers\Intranet\Modules\EansController;
use App\Http\Controllers\Intranet\Modules\ArticulosController;
use App\Http\Controllers\Intranet\Modules\GraficosController;
use App\Http\Controllers\Intranet\Modules\EmailController;
use App\Http\Controllers\Intranet\Modules\CustomModuleController;
use App\Http\Controllers\Intranet\Modules\CombinacionesController;
use App\Http\Controllers\Intranet\Modules\ImportArticulosController;
       
       function moduleRoutes($modules){
        
            foreach ($modules as $key => $module) {
              
                $module= Utils::objectToArray($module);
               
             switch ( $module["name"]) {
            case 'clientes':
           Route::get( '{company:name}/modules/clientes',[App\Http\Controllers\Intranet\Modules\ClientesController::class,'index']);
                 break;

            case 'eans':
           Route::get( '{company:name}/modules/eans',[App\Http\Controllers\Intranet\Modules\EansController::class,'index']);
                 break;

            case 'articulos':
           Route::get( '{company:name}/modules/articulos',[App\Http\Controllers\Intranet\Modules\ArticulosController::class,'index']);
                 break;

            case 'graficos':
           Route::get( '{company:name}/modules/graficos',[App\Http\Controllers\Intranet\Modules\GraficosController::class,'index']);
                 break;

            case 'email':
           Route::get( '{company:name}/modules/email',[App\Http\Controllers\Intranet\Modules\EmailController::class,'index']);
                 break;

            case 'customModule':
           Route::get( '{company:name}/modules/customModule',[App\Http\Controllers\Intranet\Modules\CustomModuleController::class,'index']);
                 break;

            case 'combinaciones':
           Route::get( '{company:name}/modules/combinaciones',[App\Http\Controllers\Intranet\Modules\CombinacionesController::class,'index']);
                 break;

            case 'importArticulos':
           Route::get( '{company:name}/modules/importArticulos',[App\Http\Controllers\Intranet\Modules\ImportArticulosController::class,'index']);
                 break;

            default:
             # code...
             break;
     }

     require_once __DIR__ . "/modulesCustomRoutes.php";
     customModulesRoutes($module["name"]);
     
    }
    
   

}