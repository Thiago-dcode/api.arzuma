<?php



namespace App\Intranet\Pyme;


use PDO;
use PDOException;

class PymeConnection
{




    static $connection = null;

    public static function start($host)
    {

        if (!self::$connection) {
            try {
            
                self::$connection = new PDO('firebird:dbname='. $host. ';charset=utf8', env('DBUSER_PYME'), ENV('DBPASSWORD_PYME'),  [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            } catch (PDOException $e) {
                die('<b>Errors:</b> ' . $e->getMessage());
            }
        }
        return self::$connection;
    }
}
