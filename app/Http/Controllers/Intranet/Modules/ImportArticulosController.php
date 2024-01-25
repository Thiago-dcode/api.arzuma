<?php

namespace App\Http\Controllers\Intranet\Modules;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Rules\TxtFileRule;
use App\Intranet\Utils\Validate;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Intranet\Modules\ImportArt;
use Illuminate\Support\Facades\Log;


class ImportArticulosController extends Controller
{

    use HttpResponses;

    //POST REQUEST!
    public function index($companyName, Request $request)
    {
        $moduleName = $request->user()["module_active"];


        if (!Validate::module($request->user(), $moduleName, $companyName)) {

            return response("You are not authorized to access to importArticulos module", 401);
        }

        try {

            $request->validate([
                'file' => ['required', new TxtFileRule], // Ensure the 'file' parameter is a file and has a .txt extension
            ]);
            $uploadedFile = $request->file('file');

            $file = $uploadedFile->getClientOriginalName();

            $today = date('d-m-Y');

            $desirePath = "uploads/$companyName/$today/";
            $path = $uploadedFile->storeAs($desirePath, $file);
            $fileContent = Storage::get($path);
            ImportArt::connect('PRUEBA');
            $articulos = ImportArt::getArticulosFromTxt($fileContent);

            if (!$articulos) {

                return $this->error(['errors' => [
                    'file' => 'No se ha encontrado artículos dentro del archivo ' . $file
                ]], "Validation error", 400);
            }

            config(['logging.channels.modules.path' => storage_path("logs/$companyName/modules/$moduleName/" . $today . '.log')]);

            $responses = [
                'total' => count($articulos),
                'error' => 0,
                'warning' => 0,
                'update' => 0,
                'insert' => 0,
                'messages' => []
            ];
          
            $doccab = ImportArt::insertDoccab();
          
          
            if (!$doccab['status']) {

                return $this->error(['errors' => [
                    'file' => $doccab['error']
                ]], "Validation error", 400);
            }

            foreach ($articulos as $codigo => $articulo) {
                
                $articulo['doccab'] = $doccab['data'];
             
                //check if the codbar exist in codbarra table
                $result = ImportArt::getCodBar($companyName, $articulo);
             
                if (isset($result['error'])) {
                    array_push($responses['messages'], [
                        'type' => 'error',
                        'message' => "ERROR en SELECT de Articulo con codigo: $codigo, {$result['error']}"
                    ]);

                    $responses['error']++;
                    continue;
                }
                //exist?
                if ($result['status']) {
                    //get the codarticulo from codbarra SELECT result
                    $codarticulo = $result['data']['CODARTICULO'];
                    //get the old price from articulo table
                    $oldPrice = ImportArt::getArticulo($companyName, $codarticulo)['data']['PRECIOCOSTE'] ?? '';
                   
                    array_push($responses['messages'], [
                        'type' => 'debug',
                        'message' => "Articulo con codigo: $codarticulo y codbar {$articulo['codbar']} existe en PYME empezando UPDATE"
                    ]);


                    //update the articulo with the code from codbarra
                    $update = ImportArt::update($result['data']['CODARTICULO'], $articulo);
                   
                    //update success??
                    if ($update['status']) {

                        $price = (float)$articulo['precio'];

                        array_push($responses['messages'], [
                            "type" => 'update',
                            "message" => "UPDATE de Articulo con codigo: $codarticulo y codbar {$articulo['codbar']}, precio: $oldPrice --> $price"

                        ]);
                        $responses['update']++;

                        continue;
                    }
                    //update failed?
                    array_push($responses['messages'], [
                        "type" => 'error',
                        'message' => "ERROR en UPDATE de Articulo con codigo: $codarticulo y codbar {$articulo['codbar']}, {$update['error']}"
                    ]);
                    $responses['error']++;


                    continue;
                }

                //codbarra not exist?

                //check if the codarticulo from the .txt exist in articulo table
                $result = ImportArt::getArticulo($companyName, $codigo);
                //exist?
              
                if ($result['status']) {

                    array_push($responses['messages'], [
                        'type' => 'warning',
                        'message' => "Articulo con codigo: $codigo y codbar {$articulo['codbar']}  está dado de BAJA."
                    ]);
                    $responses['warning']++;

                    continue;
                }
                //start insert

                $insert = ImportArt::insert($codigo, $articulo);
              
                if ($insert['status']) {

                    array_push($responses['messages'], [
                        'type' => 'insert',
                        'message' => "INSERT de Articulo con codigo: $codigo, nombre: {$articulo['nombre']}  precio: {$articulo['precio']} cantidad: {$articulo['cantidad']} iva: {$articulo['tipoiva']} codbar: {$articulo['codbar']}"
                    ]);
                    $responses['insert']++;
                    continue;
                }
               
                array_push($responses['messages'], [
                    "type" => "error",
                    'message' => "ERROR en INSERT de Articulo con codigo: $codigo, {$insert['error']}"
                ]);
                $responses['error']++;

               

                continue;
            }
            ImportArt::disconnect();
            unset($articulos);
            //logging
            foreach ($responses['messages'] as $key => $message) {


                switch ($message['type']) {

                    case 'info':
                        Log::channel('modules')->info($message['message']);
                        break;
                    case 'debug':
                        Log::channel('modules')->debug($message['message']);
                        break;
                    case 'error':
                        Log::channel('modules')->error($message['message']);
                        break;
                    case 'warning':
                        Log::channel('modules')->warning($message['message']);
                        break;
                    default:
                        break;
                }
            }
            return $this->success($responses, 'Success');
           
        } catch (ValidationException $e) {
            // Handle validation errors

            return $this->error(['errors' => $e->errors()], "Validation error", 400);
        } catch (\Exception $e) {
            // Handle other exceptions (e.g., database errors)
            return $this->error(['errors' => [
                'error'=> $e->getMessage()
            ]], $e->getMessage(), 500);
        }
    }
}
