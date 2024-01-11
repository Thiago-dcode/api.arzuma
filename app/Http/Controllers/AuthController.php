<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Module;
use App\Models\Company;
use App\Models\ModuleUser;
use Illuminate\Http\Request;
use App\Intranet\Utils\Utils;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Intranet\Utils\ModuleBuilder;
use Dotenv\Exception\ValidationException;
use Illuminate\Console\Scheduling\Schedule;

class AuthController extends Controller
{
    use HttpResponses;

    public function login(Request $request, Schedule $schedule)
    {

  	
		
        $fields = $request->validate([
            'email' => 'required|max:255',
            'password' => 'required',
        ]);

        if (!Auth::attempt($fields)) {
            return $this->error('', 'Credenciales errónea', 422);
        }
        $user = Auth::user();
     
        DB::table('users')
        ->where('id', $user->id)
        ->update(['company_active' => null,"module_active"=> null]);

   $user = User::find($user->id);
        
        return $this->success([

            'user' => $user,
            'token' => $user->createToken('Api Token of ' . $user->name)->plainTextToken

        ], 'Inicio de sesión exitoso');
    }
    public function register(Request $resquest)
    {
    }
    public function logout(Request $request)
    {

        $user = User::find($request->user()->id)->update(['company_active' => '','module_active' => '']);

        Auth::guard('web')->logout();


        return response([
            'success' => true
        ]);
    }
    public function me(Request $request)
    {

 
        return $request->user();
    }

    public function activeCompany(Request $request)
    {


        if (!isset($request["company"])) {
            return $this->error('', '', 401);
        }
        
        if(!User::find($request['user_id'])->companies()->where('name', $request['company'])->first()){

            return $this->error('', '', 401);

        }
        User::where('id', $request['user_id'])->update(['company_active' => $request['company']]);


        return $this->success([
            'id' => $request['user_id'],
            'user' => User::find($request['user_id']),


        ], '');
    }
    public function activeModule(Request $request)
    {


        if (!isset($request["module"])) {

            return $this->error('', '', 401);
        }

   if(!User::findModule( $request->user()->id, $request->user()->company_active,$request['module'])){
    
    return $this->error('', 'not allowed', 401);
   };
       
        User::where('id', $request->user()->id)->update(['module_active' => $request['module']]);


        return $this->success([
            'id' => $request['user_id'],
            'user' => User::find($request['user_id']),


        ], '');
    }
    public function company(Request $request){

            $company = Company::where('name', $request->user()->company_active)->firstOrFail();

            if(!$company) return \response('',401);
            return \response($company);
    }

    public function modules(Request $request)
    {
    
        if(!$request->user()->company_active){

            return $this->error([],"From modules",401);
        }
        
        $modules = User::allModulesByCompany($request->user()->id,$request->user()->company_active);
        return $this->success([

            'modules' => $modules,


        ], '');
    }
}
