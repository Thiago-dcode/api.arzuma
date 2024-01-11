<?php

namespace App\Http\Controllers\Intranet;

use App\Models\User;
use App\Models\Module;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;
use App\Intranet\Utils\ModuleBuilder;
use Illuminate\Validation\ValidationException;

class ModuleController extends Controller

{
    use HttpResponses;
    public function index($name, Request $request)
    {
    }

    public function modules(Request $request)
    {

        if (!$request->user()->company_active) {

            return $this->error([], "From modules", 401);
        }

        $modules = User::allModulesByCompany($request->user()->id, $request->user()->company_active);

        return $this->success([

            'modules' => $modules,


        ], '');
    }


    public function create(Request $request)
    {

        try {
            $fields = $request->validate([
                'name' => 'required|max:255|unique:modules',
                'route' => 'required|unique:modules',
                'is_active' => 'boolean',
                'logo' => 'string',
            ]);
        } catch (ValidationException $e) {
            // Validation failed; return an error response with validation messages
            return $this->error($e->errors(), '', 422);
        }

        $module = Module::firstOrCreate($fields);

        if ($module->is_active) {
            ModuleBuilder::run();
        }

        return  $this->success($module, 'Module created successfully');
    }

    public function update($id, Request $request)
    {

        $user = $request->user();

        $fields = [];


        try {
            $fields = $request->validate([
                'name'  =>  'required|max:255|unique:modules,name,' . $id,
                'route'  =>  'required|max:255|unique:modules,route,' . $id,
                'is_active' => 'boolean',
                'logo' => 'string',
            ]);
        } catch (ValidationException $e) {
            // Validation failed; return an error response with validation messages

            return $this->error($e->errors(), '', 422);
        }
        $module = Module::find($id);


        $module->update($fields);

        if ($module->is_active) {

            ModuleBuilder::run();
        }

        return  $this->success($module, 'Module updated successfully');
    }
}
