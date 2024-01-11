<?php

namespace App\Intranet\Utils;



class Constants {

    
    static $constants =  [];

    


    public static function get($key){


        if(!isset(self::$constants[$key])) return;

        return self::$constants[$key];
        


    }

    
    public static function set($key,$value){

        if(!$key || !$value) return;
       
        self::$constants[$key] = $value;
        


    }
    public static function getAll(){

        return self::$constants;
    }



}