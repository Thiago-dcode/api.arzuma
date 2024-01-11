<?php

namespace App\Intranet\Companies;

use App\Intranet\Utils\Utils;
use App\Intranet\Utils\Constants;


class CompanyBuilder
{


        public static function generateHostEnvVar($company,$host){

            $company = Utils::objectToArray($company);
            $envKey =static::generateHostEnvKey($company['name']);
            Utils::createEnvVar($envKey, $host);
          

        }
        public static function generateHostEnvKey($companyName){
            $companyNameEnv = strtoupper(str_replace('-', '_', $companyName));
            $envKey = 'DBHOST_' . $companyNameEnv . '_PYME';
            return $envKey;
        }

        public static function generateHostConstant($companyName){

            Constants::set($companyName,env(static::generateHostEnvKey($companyName)));
            
        }

}