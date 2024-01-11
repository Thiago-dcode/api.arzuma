<?php

namespace App\Intranet\Modules;
use PDO;
use App\Models\Module;
use App\Intranet\Utils\Path;
use App\Intranet\Utils\Utils;
use App\Intranet\Utils\Constants;
use App\Intranet\Pyme\PymeConnection;
use Symfony\Component\HttpFoundation\Request;


class Graficos
{



    public static function get($company,$sql){

       $stmt =  PymeConnection::start(Constants::get($company))->prepare($sql);
        $stmt->execute([]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $proveedores = [];
       return $result;


    }

   


}