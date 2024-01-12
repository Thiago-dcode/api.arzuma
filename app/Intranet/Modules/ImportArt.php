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

    private static function extractData($arr,$start,$end){


    }

    public static function getArticulos($fileContent){
        $articulos= [];

        $lines = explode(PHP_EOL, $fileContent);
    
        foreach ($lines as $key => $line) {
            $codigo = '';
            if (trim($line) === '*BASEARTI') {
                $nextLine = $lines[$key+1];
                // $nextLine =str_split($nextLine);
                // $codigo=  self::extractData($nextLine,0,14);
               
                $codigo = trim(substr($nextLine,0,14));
                $articulos[$codigo] = [];
                $nombre = trim(substr($nextLine,14,40));
                $articulos[$codigo]['nombre'] =$nombre;
                $precio = trim(substr($nextLine,122,9));
                $articulos[$codigo]['precio'] =$precio;
                $cantidad = trim(substr($nextLine,140,9));
               
                $articulos[$codigo]['cantidad'] =$cantidad;
                $iva = trim(substr($nextLine,101,6));
            
                $articulos[$codigo]['iva'] =$iva;
                $iva = trim(substr($nextLine,101,6));
               
                $articulos[$codigo]['iva'] =$iva;
                continue;
            }
             if (trim($line) === '*BASEEAN') {
                $nextLine = $lines[$key+1];
              //TODO: handle code bar
            	continue;
            }
            // $articulos[$position] = [
            // 	'art' => '',
            // 	'codbar' => []
            // ];
           
            // if (!$isCodebar) {
            // 	$articulos[$position]['art'] .= $line;
            // 	continue;
            // }
            
            // array_push($articulos[$position]['codbar'], $line);

            // if (strpos(trim($line), '*BASEARTI') !== false) {

            // 	$itemCode = trim(substr($line, 1, 14));
            // 	$amount = trim(substr($line, 140, 8));
            // 	$price = trim(substr($line, 122, 9));
            // 	$iva = trim(substr($line, 101, 6));

            // 	$result[] = [
            // 		'itemCode' => $itemCode,
            // 		'amount' => $amount,
            // 		'price' => $price,
            // 		'iva' => $iva,
            // 	];
            // }

            // // Extract data from *BASEEAN lines
            // if (strpos($line, '*BASEEAN') !== false) {
            // 	$barcodes = [];
            // 	while (($nextLine = next($lines)) !== false && strpos($nextLine, '*BASEARTI') === false) {
            // 		$barcode = trim(substr($nextLine, 0, 13));
            // 		$type = trim(substr($nextLine, 28, 1));

            // 		if ($type === '0') {
            // 			// Only import the first barcode of type = 0
            // 			$barcodes[] = $barcode;
            // 			break;
            // 		}
            // 	}

            // 	// Add barcodes to the last imported *BASEARTI entry
            // 	if (!empty($result)) {
            // 		$result[count($result) - 1]['barcodes'] = $barcodes;
            // 	}
            // }
        }

        // Print the result for demonstration purposes

        return $articulos;
    }

   


}