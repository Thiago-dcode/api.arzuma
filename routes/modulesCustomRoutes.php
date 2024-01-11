<?php


use App\Http\Controllers\Intranet\Modules\EansController;


//import your routes h


//Write your custom routes here.
//Index route is already created.

function customModulesRoutes($moduleName){
   

      
    switch ($moduleName) {
       
        case 'articulos':
            Route::get('/modules/articulos/example',[ App\Http\Controllers\Intranet\Modules\ArticulosController::class, 'example']); 
    
            break;
        case 'eans':
            Route::get('{company:name}/modules/eans/total',[ App\Http\Controllers\Intranet\Modules\EansController::class,'total']);
            Route::get('{company:name}/modules/eans/proveedores',[ App\Http\Controllers\Intranet\Modules\EansController::class,'proveedores']);
            Route::post('{company:name}/modules/eans/update',[ App\Http\Controllers\Intranet\Modules\EansController::class,'update']);
            break;
            case 'graficos':
                Route::get('/modules/graficos/types',[ App\Http\Controllers\Intranet\Modules\GraficosController::class,'types']);
                Route::post('/modules/graficos/new',[ App\Http\Controllers\Intranet\Modules\GraficosController::class,'create']);
                Route::patch('/modules/graficos/{id}',[ App\Http\Controllers\Intranet\Modules\GraficosController::class,'update']);
                Route::delete('/modules/graficos/{id}',[ App\Http\Controllers\Intranet\Modules\GraficosController::class,'delete']);
                break;
            case 'combinaciones':
               
                Route::get('{company:name}/modules/combinaciones/categoriaweb',[ App\Http\Controllers\Intranet\Modules\CombinacionesController::class,'categoriaWeb']);
				Route::get('{company:name}/modules/combinaciones/template',[ App\Http\Controllers\Intranet\Modules\CombinacionesController::class,'getInsertTemplate']);
                Route::post('{company:name}/modules/combinaciones/update',[ App\Http\Controllers\Intranet\Modules\CombinacionesController::class,'update']);
                Route::post('{company:name}/modules/combinaciones/insert',[ App\Http\Controllers\Intranet\Modules\CombinacionesController::class,'insert']);
                break;
        default:
            # code...
            break;
    }
    
    
    }



