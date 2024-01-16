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
		dd($request->user());
		try {

			$request->validate([
				'file' => ['required', new TxtFileRule], // Ensure the 'file' parameter is a file and has a .txt extension
			]);
			$uploadedFile = $request->file('file');

			$file = $uploadedFile->getClientOriginalName();

			$subfolder = date('d-m-Y');

			$desirePath = "uploads/$companyName/$subfolder/";
			$path = $uploadedFile->storeAs($desirePath, $file);
			$fileContent = Storage::get($path);
			$articulos = ImportArt::getArticulosFromTxt($fileContent);
			if (!$articulos) {

				return $this->error(['errors' => [
					'file' => 'No article found inside the ' . $file
				]], "Validation error", 401);
			}
			
			config(['logging.channels.modules.path' => storage_path("logs/modules//" . $subfolder . '.log')]);
			//check if exist articulo
			foreach ($articulos as $codigo => $articulo) {

				$pymeArticulo = ImportArt::getArticulo($company, $codigo);

				if ($pymeArticulo) {
					//log debug if exist or not

					Log::channel('custom')->useFiles()->debug('');
					// update the precio,cantidad, codbar, iva
					//log info with the previus articulo data and the new one

					continue;
				}
				//log debug if exist or not
				//insert with codigo, nombre, precio, cantidad, codbar,iva
				//log info of the insert

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
