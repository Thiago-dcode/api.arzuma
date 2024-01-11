<?php
namespace App\Http\Controllers\Intranet\Modules;
use Illuminate\Http\Request;
use App\Models\Module;
use App\Intranet\Utils\Validate;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;

class ImportArticulosController extends Controller{

	use HttpResponses;

	public function index($companyName, Request $request){

		if(!Validate::module($request->user(),"importArticulos",$companyName)){

			return response("You are not authorized to access to ImportArticulos module",401);
		}

		 //start your logic here
		return response("ImportArticulos module for campany $companyName created successfully.");
	}

}