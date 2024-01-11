<?php

namespace App\Http\Controllers\Intranet;

use App\Models\User;
use App\Models\Module;
use App\Models\ModuleUser;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;
use App\Intranet\Utils\ModuleBuilder;
use Illuminate\Validation\ValidationException;

class UserController extends Controller

{

    use HttpResponses;


    public function update($id, Request $request)
    {

        $user = User::find($id);

        if ($request->has('companies') && is_array($request->companies)) {
            $companiesToAtach = [];
            foreach ($request->companies as $company) {
                $company = (int) $company;
                if (is_int($company) && $user->companies()->where('company_id', $company)->exists()) {
                    continue;
                }
                array_push($companiesToAtach, $company);
                // Attach the company to the user

            }
          
            $user->companies()->attach($companiesToAtach);
         
        }


        if (isset($request['modules']) && is_array($request['modules']) && count(array_filter(array_keys($request['modules']), 'is_string')) > 0) {
            foreach ($request['modules'] as $company => $modules) {
              
                // Check if $company is a string and $modules is an array
                if (!is_string($company) || !is_array($modules)) continue;
                // Check if the user is attached to the specified company
                if (!$user->companies()->where('name', $company)->exists())  continue;
                foreach ($modules as $module) {
                    // Check if the user already has the module for the specified company
                    if (User::findModule($id, $company, $module)) {
                        continue;
                    }
                    
                    // Create a new record in the ModuleUser pivot table
                    ModuleUser::firstOrCreate([
                        'user_id' => $id,
                        'company' => $company,
                        'module_id' => $module,
                    ]);
                   
                }
            }
        }

        return $this->success([
            'user' => User::find($id),
            'modules' => User::allModules($user->id),
        ]);
    }
    public function create(Request $request)
    {
        try {
            $fields = $request->validate([
                'name' => 'required|max:255|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'is_active' => 'required|boolean'
            ]);

            // Create a new user
            $user = User::create([
                'name' => $fields['name'],
                'email' => $fields['email'],
                'password' => bcrypt($fields['password']), // Hash the password
                'is_active' => $fields['is_active'],
                'is_admin' => 0
            ]);

            // Retrieve the created user and return it in JSON
            $createdUser = User::find($user->id);
            return response()->json(['user' => $createdUser], 201); // Return the created user in JSON with status code 201 (created)
        } catch (ValidationException $e) {
            // Validation failed; return an error response with validation messages
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions (e.g., database errors) and return an error response
            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }
}
