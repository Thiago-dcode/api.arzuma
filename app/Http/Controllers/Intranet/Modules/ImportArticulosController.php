<?php

namespace App\Http\Controllers\Intranet\Modules;

use Illuminate\Http\Request;
use App\Models\Module;
use App\Intranet\Utils\Validate;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;

class ImportArticulosController extends Controller
{

	use HttpResponses;

	//POST REQUEST!
	public function index($companyName, Request $request)
	{

		if (!Validate::module($request->user(), "importArticulos", $companyName)) {

			return response("You are not authorized to access to importArticulos module", 401);
		}

		$request->validate([
			'file' => 'required|file|mimes:txt', // Ensure the 'file' parameter is a file and has a .txt extension
		]);

		// Retrieve the uploaded file
		$uploadedFile = $request->file('file');

		// Check if the file has a .txt extension
		if ($uploadedFile->getClientOriginalExtension() !== 'txt') {
			return $this->error(['error' => 'Only .txt files are allowed.'], "", 422);
		}

		// Store the file in the storage disk (public disk by default)
		$path = $uploadedFile->storeAs("uploads/$companyName/importArticulos", $uploadedFile->getClientOriginalName());

		// Now you can save the file path or do whatever you need with it
		// For example, you can store the file path in the database


		return $this->success(['path' => $path], 'Success');
	}
}
