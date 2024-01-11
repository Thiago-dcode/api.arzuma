<?php

namespace App\Http\Controllers\Intranet\Modules;
use App\Models\Module;
       use Illuminate\Http\Request;

       use App\Traits\HttpResponses;

       use App\Http\Controllers\Controller;

       
       class CustomModuleController extends Controller

       {

            
               use HttpResponses;

               

               public function index($name, Request $request){

                $module = Module::where('name', 'customModule')->first();
                

                $hasCompany = $module->companies()->where('name', $name)->exists();
           
                    if(!($request->user()->company_active === $name && $hasCompany)){

                        return response("",401);
                    }

                 
                    //start your logic here
                    return response("CustomModule module for campany $name created successfully.");  
                }

       }