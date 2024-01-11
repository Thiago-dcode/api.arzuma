<?php

                    namespace App\Http\Controllers\Intranet\Modules;

       
       use Illuminate\Http\Request;

       use App\Traits\HttpResponses;

       use App\Http\Controllers\Controller;

       
       class EmailController extends Controller

       {

            
               use HttpResponses;

               

               public function index($name, Request $request){

                
                
                    if($request->user()->company_active !== $name){

                                return response("",401);
                        }

                    return response("Email module for campany $name created successfully.");
                    //start your logic here
        
                }

       }