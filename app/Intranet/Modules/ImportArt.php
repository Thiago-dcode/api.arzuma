<?php

namespace App\Intranet\Modules;

use PDO;
use App\Intranet\Utils\Constants;
use App\Intranet\Pyme\PymeConnection;
use PDOException;



class ImportArt
{
    static $firebird = null;
    public static function connect($company)
    {

        try {
            static::$firebird = PymeConnection::start(Constants::get($company));
        } catch (\Throwable $th) {

            throw new PDOException("Pyme ($company) connection error: " . $th->getMessage(), 1);
        }
    }
    public static function disconnect()
    {

        static::$firebird = null;
    }
    // public static function getMarca()
    // {

    //     try {

    //         $sql = "SELECT * FROM marca order by CODIGO DESC";
    //         $stmt = static::$firebird->prepare($sql);
    //         $stmt->execute();
    //         $result = $stmt->fetch(PDO::FETCH_ASSOC);
    //         return $result;
    //         $sql = "INSERT INTO marca
    //         (CODIGO, NOMBRE, foto, WEBINACTIVO)
    //         VALUES
    //         (1,'importarticulo' , null, null)";
    //         $stmt = static::$firebird->prepare($sql);
    //        $result =  $stmt->execute();

    //         if ($result) {
    //             return [
    //                 'status' => true,
    //                 'data' => $result[0],
    //             ];
    //         }
    //         return [
    //             'status' => false,
    //             'data' => [],
    //         ];
    //     } catch (\Throwable $th) {
    //         return [
    //             'status' => false,
    //             'data' => [],
    //             'error' => $th->getMessage()
    //         ];
    //     }
    // }
    public static function getCodBar($company, $articulo)
    {

        try {

            $sql = "select CODARTICULO from codbarra where codbarras={$articulo['codbar']}";

            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'status' => true,
                    'data' => $result[0],
                ];
            }
            return [
                'status' => false,
                'data' => [],
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data' => [],
                'error' => $th->getMessage()
            ];
        }
    }
    public static function getArticulo($company, $codigo)
    {

        try {
            $sql = "select  PRECIOCOSTE  from articulo where codigo='$codigo'";

            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'status' => true,
                    'data' => $result,
                ];
            }
            return [
                'status' => false,
                'data' => [],
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data' => [],
                'error' => $th->getMessage()
            ];
        }
    }
    public static function getTipoIva()
    {
        $tipoIvas = [];
        try {

            $sql = "select * from empresa";
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // dd($result);
            foreach ($result as $key => $value) {
                $key = strtolower($key);
                $ivaStr = substr($key, 0, strlen($key) - 1);
                if ($ivaStr  !== 'iva') continue;
                $tipoIva = (int) str_replace($ivaStr, "", $key);
                if (!is_int($tipoIva)) continue;
                $tipoIvas[(int)$value] = $tipoIva;
            }
            return $tipoIvas;
        } catch (\Throwable $th) {
            throw new PDOException($th->getMessage());
        }
    }
    public static function update($codigo, $articulo)
    {
        try {
            // $obj = [
            //     'status' => false,
            //     'data' => [],
            //     'error' => 'No se ha podido ACTUALIZAR en tabla ARTICULO'
            // ];
            // $result1 = self::updateArticulo($codigo, $articulo);
            // $result2 = self::updateCompra($codigo, $articulo);
            // if (!$result2) {
            //     $obj['error'] = 'No se ha podido ACTUALIZAR en tabla COMPRA';
            // }
            $result1 = self::insertDoclin($codigo, $articulo);

            if (!$result1) {

                $obj['error'] = 'No se ha podido ACTUALIZAR en tabla DOCLIN';
            }
            // $result4 = self::updateOrInsertExistenc($codigo, $articulo);

            // if (!$result4) {



            //     $obj['error'] = 'No se ha podido ACTUALIZAR en tabla EXISTENC';
            // }

            if (!($result1)) {
                return $obj;
            }
            $obj['status'] = true;
            $obj['error'] = '';
            return $obj;
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'error' => $th->getMessage()
            ];
        }
    }
    public static function insert($codigo, $articulo)
    {
        $obj = [
            'status' => false,
            'data' => [],
            'error' => 'No se ha podido INSERTAR en tabla ARTICULO'
        ];
        try {

            $result1 = self::insertArticulo($codigo, $articulo);

            if (!$result1) {
                $obj['error'] = 'No se ha podido INSERTAR en tabla ARTICULO';
            }

            $result2 = self::insertCodbar($codigo, $articulo);
            if (!$result2) {
                $obj['error'] = 'No se ha podido INSERTAR en tabla CODBARRA';
            }

            // $result3 = self::insertCompra($codigo, $articulo);
            // if (!$result3) {
            //     $obj['error'] = 'No se ha podido INSERTAR en tabla COMPRA';
            // }
            //cantidad
            $result3 = self::insertDoclin($codigo, $articulo);
            if (!$result3) {
                $obj['error'] = 'No se ha podido INSERTAR en tabla DOCLIN';
            }

            // $result5 = self::updateOrInsertExistenc($codigo, $articulo);
            // if (!$result5) {
            //     $obj['error'] = 'No se ha podido INSERTAR en tabla EXISTENC';
            // }
            if (!($result1  && $result2 && $result3)) {
                return $obj;
            }
            $obj['status'] = true;
            $obj['error'] = '';
            return $obj;
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data' => [],
                'error' => $th->getMessage()
            ];
        }
    }

    private static function updateArticulo($codigo, $articulo)
    {


        $vars = [
            'precio' => (float)$articulo['precio'],
            'tipoiva' => $articulo['tipoiva'],
            'codigo' => $codigo
        ];
        $sql = "UPDATE articulo SET PRECIOCOSTE={$vars['precio']}, tipoiva={$vars['tipoiva']}  where codigo='{$vars['codigo']}'";
        $stmt = static::$firebird->prepare($sql);


        $result = $stmt->execute();

        return $result;
    }

    // public static function insertImage($codigo)
    // {
    //     try {
    //         $image = file_get_contents(__DIR__ . '/image.png');
    //         $sql = "SELECT  * from FOTOGRAF where codarticulo = '$codigo' and codigo = 1 and orden =1";
    //         $stmt = static::$firebird->prepare($sql);
    //         $stmt->execute();
    //         $result = $stmt->fetch(PDO::FETCH_ASSOC);

    //         if (!$result) {
             
    //             $sql = "INSERT INTO FOTOGRAF (CODARTICULO,CODIGO,ORDEN,FOTO,EXCLUIRWEB) VALUES($codigo,1,1,?,'F')";
    //             $stmt = static::$firebird->prepare($sql);
    //             $stmt->bindParam(1, $image, PDO::PARAM_LOB);
    //             $result = $stmt->execute();
    //             return $result;
    //         } else {
    //             $sql = "UPDATE FOTOGRAF set FOTO = ? where CODARTICULO ='$codigo' and codigo = 1 and orden =1";
    //             $stmt = static::$firebird->prepare($sql);
    //             $stmt->bindParam(1, $image, PDO::PARAM_LOB);
    //             $result = $stmt->execute();
    //             return $result;
    //         }
    //     } catch (\Throwable $th) {
    //         dd($th->getMessage());
    //         return false;
    //     }
    // }

    private static function insertArticulo($codigo, $articulo)
    {
        try {
            $vars = [
                'codigo' => (string) $codigo,
                'codmarca' => 1,
                'nombre' => (string) mb_convert_encoding($articulo['nombre'], "UTF-8"),
                'descripcion' => (string) mb_convert_encoding($articulo['nombre'], "UTF-8"),
                'preciocoste' => (float)$articulo['precio'],
                'baja' => 'F',
                'tipoactualizacion' => 0,
                'tipoiva' => (int)$articulo['tipoiva'],
                'tipoivareducido' => 1,
                'tipoivacompra' => 0,
                'tipoivacomprareducido' => 1,
                'controlstock' => 1,
                'unidaddecimales' => 0,
                'preciodecimales' => 2,
                'costedecimales' => 2,
                'stockfactor' => 1,
                'etiquetasegununidadmedida' => 0,
                'ubicacion' => 0,
                'descripcioncorta' => (string) mb_convert_encoding($articulo['nombre'], "UTF-8"),
                'formatodesccorta' => 0,
                'formatodescripcion' => 2,
                'aplicarinvsujetopasivo' => 0,
                'tipobc3' => 20,
                'unidadcontrolcarubicstock' => 0,
                'excluirweb' => 'T',
            ];
            $fieldsToInsert =  implode(',', array_keys($vars));

            $sql = "INSERT INTO articulo 
        ($fieldsToInsert) 
        VALUES 
        ('{$vars['codigo']}',{$vars['codmarca']},'{$vars['nombre']}','{$vars['descripcion']}',{$vars['preciocoste']},'{$vars['baja']}',{$vars['tipoactualizacion']},{$vars['tipoiva']},{$vars['tipoivareducido']},{$vars['tipoivacompra']},{$vars['tipoivacomprareducido']},{$vars['controlstock']},{$vars['unidaddecimales']},{$vars['preciodecimales']},{$vars['costedecimales']},{$vars['stockfactor']},{$vars['etiquetasegununidadmedida']},{$vars['ubicacion']},'{$vars['descripcioncorta']}',{$vars['formatodesccorta']},{$vars['formatodescripcion']},{$vars['aplicarinvsujetopasivo']},{$vars['tipobc3']},{$vars['unidadcontrolcarubicstock']},'{$vars['excluirweb']}')";





            $stmt = static::$firebird->prepare($sql);

            // Execute the query with parameters
            return $stmt->execute();
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
    private static function insertCodbar($codigo, $articulo)
    {

        $sql = "INSERT INTO codbarra
        (codarticulo, codcaract, valorcaract, codbarras)
        VALUES
        (:codarticulo, :codcaract, :valorcaract, :codbarras)";

        $stmt = static::$firebird->prepare($sql);

        // Execute the query with parameters
        $result = $stmt->execute([
            ':codbarras' => $articulo['codbar'],
            ':codarticulo' => $codigo,
            ':codcaract' => null,
            ':valorcaract' => null,
        ]);

        return $result;
    }
    public static function updateOrInsertExistenc($codigo, $articulo)
    {

        try {
            date_default_timezone_set('Europe/Madrid');
            $sql = "select CODARTICULO, CODALMACEN, STOCK1 from EXISTENC where CODARTICULO = '$codigo'";
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {

                $sql = "UPDATE EXISTENC SET STOCK1 = '{$articulo['cantidad']}' where CODARTICULO = '$codigo'";
                $stmt = static::$firebird->prepare($sql);

                $result = $stmt->execute();

                return $result;
            }
            $sql = "INSERT INTO EXISTENC (CODARTICULO,CODALMACEN,STOCK1) VALUES('$codigo',1,'{$articulo['cantidad']}')";
            $stmt = static::$firebird->prepare($sql);

            $result = $stmt->execute();
            return $result;
            //insert
        } catch (\Throwable $th) {
            return false;
        }
    }
    public static function insertDoclin($codigo, $articulo)
    {
        try {
            date_default_timezone_set('Europe/Madrid');
            $sql = 'select first 1 * from doclin order by codigo desc ';

            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) return false;
            $result = array_filter($result, fn ($r) => $r !== null);

            $result['CODDOCUMENTO'] = $articulo['doccab'];
            $result['CODIGO']++;
            $result['CODARTICULO'] = $codigo;
            $result['CANTIDAD'] = $articulo['cantidad'];
            $result['TIPOIVA'] = $articulo['tipoiva'];
            $result['PRECIO'] = $articulo['precio'];
            $result['CODBARRAS'] = $articulo['codbar'];
            $fieldsToInsert =  implode(',', array_keys($result));

            do {
                $sql = "SELECT CODDOCUMENTO FROM doclin WHERE CODDOCUMENTO = {$result['CODIGO']}";
                $stmt = static::$firebird->prepare($sql);

                $stmt->execute();
                $resultExist = $stmt->fetch(PDO::FETCH_ASSOC);

                // Your logic inside the loop
                if ($resultExist) ++$result['CODIGO'];
                // Increment $codigo for the next iteration


            } while ($resultExist);

            $sql = "INSERT INTO doclin ($fieldsToInsert) VALUES (
                {$result['CODDOCUMENTO']},
                {$result['CODIGO']},
                {$result['NIVEL']},
                '{$result['CODARTICULO']}',
                {$result['CANTIDAD']},
                '{$result['PRECIO']}',
                '{$result['COSTE']}',
                '{$result['COSTEINDIRECTO']}',
                {$result['TRIBUTACIONIVA']},
                {$result['TIPOIVA']},
                {$result['TIPOIRPF']},
                {$result['TIPORECEQUIV']},
                '{$result['DESCUENTOS']}',
                {$result['CODALMACEN']},
                {$result['PORTES']},
                {$result['GASTOSVARIOS']},
                {$result['PORCENTAJECOMISION']},
                {$result['IMPORTEDESCUENTO']},
                {$result['CURSO']},
                {$result['CANTIDADPADRE']},
                {$result['CANTIDADCERTANT']},
                '{$result['IDARTICULOOBRA']}',
                {$result['TIPOBC3']},
                '{$result['CODIGOBC3']}',
                '{$result['PRECIOCALCULADO']}',
                {$result['TIPOLINEA']},
                '{$result['CODBARRAS']}')";
            $stmt = static::$firebird->prepare($sql);

            $resultInsert = $stmt->execute();

            return $resultInsert;
        } catch (\Throwable $th) {
            dd([$th->getMessage(), $codigo, $result]);
            return false;
        }
    }
    public static function insertDoccab()
    {
        try {
            date_default_timezone_set('Europe/Madrid');
            //get the last row in doccab
            $sql = 'select first 1 * from doccab order by CODIGO DESC ';

            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'status' => false,
                    'data' => null,
                    'error' => 'No se ha podido insertar en DOCCAB'
                ];
            }
            $result = array_filter($result, fn ($r) => $r !== null);

            //insert in doccab rehusing the same fields of the last row
            $fieldsToInsert =  implode(',', array_keys($result));

            //Apply minor changes to the values of the last row to insert correctly
            $result['CODIGO']++;
            $result['FECHA'] = date("Y-m-d");
            $result['HORA'] = date("H:i:s");
            $result['TIPO'] = 12;

            //build the sql with the data

            $sql = "INSERT INTO doccab (
       $fieldsToInsert
    ) VALUES (
    " . $result['CODIGO'] . ", " . $result['TIPO'] . ", '" . $result['SERIE'] . "', " . $result['NUMERO'] . ", " . $result['REVISION'] . ", '" . $result['FECHA'] . "', " .
                $result['CODALMACEN'] . ", " . $result['PORTESTIPO'] . ", '" . $result['ENEUROS'] . "', '" . $result['RECUENTOABSOLUTO'] . "', '" . $result['REGIMENIVA'] . "', '" . $result['CRITERIOIVACAJA'] . "', " .
                $result['PENDIENTEDEVENGO'] . ", '" . $result['AJUSTEBASE'] . "', '" . $result['AJUSTEIVA'] . "', '" . $result['IMPORTEBRUTO'] . "', '" . $result['IMPORTEDESCUENTO'] . "', '" . $result['IMPORTEBASE'] . "', '" . $result['IMPORTEIVA'] . "', " .
                "'" . $result['IMPORTERECEQUIV'] . "', '" . $result['IMPORTEIRPF'] . "', '" . $result['IMPORTETOTAL'] . "', '" . $result['HORA'] . "', " . $result['ESTADOPEND'] . ", " . $result['MODOCOMPUESTOS'] . ", " . $result['MODOLICITACION'] . ")";
            // $sql = "INSERT INTO doccab (
            //     $fieldsToInsert
            //  ) VALUES (:$valuesToInsert)";

            //error inserting with parameter biding

            $stmt = static::$firebird->prepare($sql);

            $insertResult = $stmt->execute();
            if ($insertResult) {

                return [

                    'status' => true,
                    'data' => $result['CODIGO']
                ];
            }
            return [
                'status' => false,
                'data' => null,
                'error' => 'No se ha podido insertar en DOCCAB'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data' => null,
                'error' =>  $th->getMessage()
            ];
        }
    }
    // private static function updateCompra($codigo, $articulo)
    // {
    //     $vars = [
    //         'preciocoste' => (float)$articulo['precio'],
    //         'codarticulo' => $codigo,
    //     ];

    //     $sql = "UPDATE compra 
    //             SET preciocoste = {$vars['preciocoste']}
    //             WHERE codarticulo = '{$vars['codarticulo']}'
    //             AND codcaract = null
    //             AND valorcaract = null";

    //     $stmt = self::$firebird->prepare($sql);



    //     $result = $stmt->execute();

    //     return $result;
    // }


    // private static function insertCompra($codigo, $articulo)
    // {
    //     $vars = [
    //         'preciocoste' => (float)$articulo['precio'],
    //         'codarticulo' => $codigo,
    //         'codproveedor' => null
    //     ];
    //     $sql = "INSERT INTO compra 
    //     (codarticulo, codproveedor, preciocoste) 
    //     VALUES 
    //     ('{$vars['codarticulo']}', null , {$vars['codarticulo']})";

    //     $stmt = static::$firebird->prepare($sql);

    //     // Execute the query with parameters
    //     $result = $stmt->execute();
    //     return $result;
    // }


    public static function getArticulosFromTxt($fileContent)
    {
        $tipoIvas = self::getTipoIva();
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
                    $articulos[$codigo]['tipoiva'] =  isset($tipoIvas[(int)$iva]) ? $tipoIvas[(int)$iva] : 0;


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
                dd('Error extracting articles from document given: ' . $th->getMessage());
            }
        }

        // Print the result for demonstration purposes
        if (!$hasArt) return [];
        return $articulos;
    }
}
