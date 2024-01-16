<?php

namespace App\Http\Controllers\Intranet\Modules;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Rules\TxtFileRule;
use App\Intranet\Utils\Validate;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;
use App\Intranet\Utils\Path;
use Illuminate\Support\Facades\Storage;
use App\Intranet\Modules\ImportArt;
use App\Intranet\Utils\Constants;
use Illuminate\Support\Facades\Log;
use PHPUnit\TextUI\Configuration\Constant;

class ImportArticulosController extends Controller
{

	use HttpResponses;

	//POST REQUEST!
	public function index($companyName, Request $request)
	{


		if (!Validate::module($request->user(), "importArticulos", $companyName)) {

			return response("You are not authorized to access to importArticulos module", 401);
		}
	
		try {

			$request->validate([
				'file' => ['required', new TxtFileRule], // Ensure the 'file' parameter is a file and has a .txt extension
			]);
			$uploadedFile = $request->file('file');

			$file = $uploadedFile->getClientOriginalName();
		
			$today = date('d-m-Y');
			$moduleName = $request->user()["module_active"];
			
			$desirePath = "uploads/$companyName/$today/";
			$path = $uploadedFile->storeAs($desirePath, $file);
			$fileContent = Storage::get($path);
		
			$articulos = ImportArt::getArticulosFromTxt($fileContent);
			
			if (!$articulos) {

				return $this->error(['errors' => [
					'file' => 'No article found inside the ' . $file
				]], "Validation error", 401);
			}
			
			config(['logging.channels.modules.path' => storage_path("logs/modules/$moduleName/" . $today . '.log')]);
			
			//check if exist articulo
			foreach ($articulos as $codigo => $articulo) {
				
				$pymeArticulo = ImportArt::getArticulo($companyName, $codigo);
				dd($pymeArticulo);
				if ($pymeArticulo) {
					//log debug if exist or not

					Log::channel('modules')->debug("Articulo con codigo: $codigo existe en PYME empezando UPDATE");
					// update the precio,cantidad, codbar, iva
					$update = true;

					if ($update['status']) {
						Log::channel('modules')->info("UPDATE de Articulo con codigo: $codigo, precio: {$pymeArticulo['precio']}->{$articulo['precio']} cantidad: {$pymeArticulo['cantidad']}->{$articulo['cantidad']} iva: {$pymeArticulo['iva']}->{$articulo['iva']} codbar: {$pymeArticulo['codbar']}->{$articulo['codbar']}");
						continue;
					}

					Log::channel('modules')->error("ERROR en UPDATE de Articulo con codigo: $codigo, {$update['error']}");
					//log info with the previus articulo data and the new one

					continue;
				}
				Log::channel('modules')->debug("Articulo con codigo: $codigo  NO existe en PYME empezando INSERT");
				//insert with codigo, nombre, precio, cantidad, codbar,iva
				//log info of the insert

				$insert = true;

				if ($insert['status']) {
					Log::channel('modules')->info("INSERT de Articulo con codigo: $codigo, nombre: {$articulo['nombre']}  precio: {$articulo['precio']} cantidad: {$articulo['cantidad']} iva: {$articulo['iva']} codbar: {$articulo['codbar']}");
					continue;
				}

				Log::channel('modules')->error("ERROR en INSERT de Articulo con codigo: $codigo, {$update['error']}");
				//log info with the previus articulo data and the new one

				continue;
			}







			return $this->success(['path' => $path], 'Success');
			//code...
		} catch (ValidationException $e) {
			// Handle validation errors

			return $this->error(['errors' => $e->errors()], "Validation error", 401);
		} catch (\Exception $e) {
			// Handle other exceptions (e.g., database errors)
			return $this->error([], $e->errors(), 401);
		}
	}
}
