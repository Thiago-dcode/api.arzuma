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

        if (isset($request['company']) && isset($request['modules'])) {

            foreach ($request['modules'] as $key => $module) {

                if (User::findModule($id, $request['company'], $module)) continue;

                ModuleUser::firstOrCreate([

                    'user_id' => $id,
                    'company' => $request['company'],
                    'module_id' => $module

                ]);
            }
        }

        return $this->success([
            'user' => $user,
            'modules' => User::allModulesByCompany($user->id, $request['company']),
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
                'is_admin'=> 0
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
