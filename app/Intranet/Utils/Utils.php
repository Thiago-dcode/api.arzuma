<?php

namespace App\Intranet\Utils;


class Utils
{


    public static function objectToArray($obj)
    {


        if (!is_object($obj) && is_array($obj)) return $obj;

        return json_decode(json_encode($obj), true);
    }
    public static function createEnvVar($key, $value)
    {
        if (env($key)) return;
        $envFile =   file_get_contents(Path::ROOT . 'api/.env');
        $newVar = "\n" . $key . '=' . $value . "\n";

        $newEnvFile = $envFile . $newVar;

        file_put_contents(Path::ROOT . 'api/.env', $newEnvFile);
    }

    public static function roundTo($n, $place) {
        $num = floatval($n);
    
        if (is_nan($num)) {
            return $num;
        }
    
        return round($num, $place);
    }
   public static  function percentageBtwNumbers($number1, $number2, $round = 2) {
        try {
            // Check if the input values are numbers
            if (!is_numeric((float)$number1) || !is_numeric((float)$number2)) {
                throw new Exception("Both input values must be numbers");
            }
            return self::roundTo(abs(($number1 - $number2) / $number1) * 100, $round);
        } catch (Exception $error) {
            return $error->getMessage();
        }
    }
}
