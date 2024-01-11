<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidateTokenController extends Controller
{
    
    public function validateToken(Request $request){
       
        
        if($request->user('sanctum')){

            return 'auth';
        }
        

    
}
}
