<?php

namespace App\Intranet\Modules;

use PDO;
use App\Models\Module;
use App\Intranet\Utils\Path;
use App\Intranet\Utils\Utils;
use App\Intranet\Utils\Constants;
use App\Intranet\Pyme\PymeConnection;
use Symfony\Component\HttpFoundation\Request;


class ImportArt
{
    static $firebird = null;
    private static function connect($company)
    {

        try {
            static::$firebird = PymeConnection::start(Constants::get($company));
        } catch (\Throwable $th) {
            
            echo $th->getMessage();
        }

      
    }

    public static function getArticulo($company, $codigo)
    {

        if (!static::$firebird) self::connect($company);
        $sql = "select * from articulo where codigo=:codigo";

        $stmt = static::$firebird->prepare($sql);
        $stmt->execute([
            'codigo' => $codigo
        ]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getArticulosFromTxt($fileContent)
    {

        $articulos = [];
        $hasArt = false;
        $lines = explode(PHP_EOL, $fileContent);
        $codigo = '';
        foreach ($lines as $key => $line) {
            try {

                if (trim($line) === '*BASEARTI') {
                    $hasArt = true;
                    if (!isset($lines[$key + 1])) break;
                    $nextLine = $lines[$key + 1];
                    // $nextLine =str_split($nextLine);
                    // $codigo=  self::extractData($nextLine,0,14);

                    $codigo = trim(substr($nextLine, 0, 14));
                    $articulos[$codigo] = [];
                    $nombre = trim(substr($nextLine, 14, 40));
                    $articulos[$codigo]['nombre'] = $nombre;
                    $precio = trim(substr($nextLine, 122, 9));
                    $articulos[$codigo]['precio'] = $precio;
                    $cantidad = trim(substr($nextLine, 140, 9));
                    $articulos[$codigo]['cantidad'] = $cantidad;
                    $iva = trim(substr($nextLine, 101, 6));
                    $articulos[$codigo]['iva'] = $iva;

                    continue;
                }
                if (trim($line) === '*BASEEAN') {
                    $count = 1;

                    if (!isset($lines[$key + $count])) break;
                    $nextLine = $lines[$key + $count];
                    while (trim($nextLine) !== '*BASEARTI') {
                        $count++;
                        $tipo = $nextLine[28];

                        $articulos[$codigo]['codbar'] = '';

                        if ($tipo == 0) {
                            $cod = trim(substr($nextLine, 0, 14));
                            $articulos[$codigo]['codbar'] = $cod;

                            break;
                        }
                        if (!isset($lines[$key + $count])) break;
                        $nextLine = $lines[$key + $count];
                    }

                    continue;
                }
            } catch (\Throwable $th) {
                dd($th->getMessage());
            }
        }

        // Print the result for demonstration purposes
        if (!$hasArt) return [];
        return $articulos;
    }
}
