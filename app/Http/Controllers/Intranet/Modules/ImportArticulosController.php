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
					'file' => 'No se ha encontrado artículos dentro del archivo ' . $file
				]], "Validation error", 400);
			}

			config(['logging.channels.modules.path' => storage_path("logs/modules/$moduleName/" . $today . '.log')]);

			$responses = [
				'total' => count($articulos),
				'error' => 0,
				'update' => 0,
				'insert' => 0,
				'messages' => []
			];
			
			foreach ($articulos as $codigo => $articulo) {
				 $result = ImportArt::getArticulo($companyName, $codigo);
					$result = [
					'error' => 'something went wrong'
				];
				if (isset($result['error'])) {

					Log::channel('modules')->error("ERROR en SELECT de Articulo con codigo: $codigo, {$result['error']}");
					array_push($responses['messages'], [
						'type' => 'error',
						'message' => "ERROR en SELECT de Articulo con codigo: $codigo, {$result['error']}"
					]);
					$responses['error']++;
					continue;
				}
				if ($result['status']) {


					Log::channel('modules')->debug("Articulo con codigo: $codigo existe en PYME empezando UPDATE");

					$update = ImportArt::update($codigo,$articulo);

					if ($update['status']) {

						// cantidad: {$result['data']['cantidad']}->{$articulo['cantidad']} iva: {$result['data']['iva']}->{$articulo['iva']} codbar: {$result['data']['codbar']}->{$articulo['codbar']}
					
						array_push($responses['messages'], [
							"type" => 'update',
							"message" => "UPDATE de Articulo con codigo: $codigo, precio: {$result['data']['PRECIO']} --> {$articulo['precio']}"

						]);
						$responses['update']++;
						Log::channel('modules')->info("UPDATE de Articulo con codigo: $codigo, precio: {$result['data']['PRECIO']} --> {$articulo['precio']}");
						continue;
					}
					array_push($responses['messages'], [
						"type" => 'error',
						'message' => "ERROR en UPDATE de Articulo con codigo: $codigo, {$update['error']}"
					]);
					$responses['error']++;
					Log::channel('modules')->error("ERROR en UPDATE de Articulo con codigo: $codigo, {$update['error']}");


					continue;
				}

				Log::channel('modules')->debug("Articulo con codigo: $codigo  NO existe en PYME empezando INSERT");

				$insert = [
					'status' => false,
					'error' => 'Error in insert'
				];

				if ($insert['status']) {

					array_push($responses['messages'], [
						'type' => 'insert',
						'message' => "INSERT de Articulo con codigo: $codigo, nombre: {$articulo['nombre']}  precio: {$articulo['precio']} cantidad: {$articulo['cantidad']} iva: {$articulo['iva']} codbar: {$articulo['codbar']}"
					]);
					$responses['insert']++;
					Log::channel('modules')->info("INSERT de Articulo con codigo: $codigo, nombre: {$articulo['nombre']}  precio: {$articulo['precio']} cantidad: {$articulo['cantidad']} iva: {$articulo['iva']} codbar: {$articulo['codbar']}");
					continue;
				}
				array_push($responses['messages'], [
					"type" => "error",
					'message' => "ERROR en INSERT de Articulo con codigo: $codigo, {$insert['error']}"
				]);
				$responses['error']++;
				Log::channel('modules')->error("ERROR en INSERT de Articulo con codigo: $codigo, {$insert['error']}");
				//log info with the previus articulo data and the new one

				continue;
			}
			return $this->success($responses, 'Success');
			//code...
		} catch (ValidationException $e) {
			// Handle validation errors

			return $this->error(['errors' => $e->errors()], "Validation error", 400);
		} catch (\Exception $e) {
			// Handle other exceptions (e.g., database errors)
			return $this->error(['errors' => []], $e->getMessage(), 500);
		}
	}
}
