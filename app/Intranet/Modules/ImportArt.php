<?php

namespace App\Intranet\Modules;

use PDO;
use App\Models\Module;
use App\Intranet\Utils\Path;
use App\Intranet\Utils\Utils;
use App\Intranet\Utils\Constants;
use App\Intranet\Pyme\PymeConnection;
use PDOException;
use Symfony\Component\HttpFoundation\Request;


class ImportArt
{
    static $firebird = null;
    private static function connect($company = 'bera-textil')
    {
        //'185.226.177.3:3055:C:\Distrito\PYME\DATABASE\ROJASDIS\2023.FDB'
        try {
            static::$firebird = PymeConnection::start(Constants::get('bera-textil'));
        } catch (\Throwable $th) {

            throw new PDOException("Pyme ($company) connection error: " . $th->getMessage(), 1);
        }
    }

    public static function getArticulo($company, $codigo)
    {

        try {
            if (!static::$firebird) self::connect($company);
            $sql = "select CODIGO as codigo, NOMBRE as nombre, PRECIOCOSTE as precio from articulo where codigo=:codigo";

            $stmt = static::$firebird->prepare($sql);
            $stmt->execute([
                'codigo' => 'P1010764'
            ]);
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
    public static function getTipoIva($iva)
    {
        try {
            if (!static::$firebird) self::connect();
            $sql = "select * from empresa";
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            foreach ($result as $key => $value) {
                $key = strtolower($key);
                $ivaStr = substr($key, 0, strlen($key) - 1);
                if ($ivaStr  !== 'iva') continue;
                if ((int)$value !== (int)$iva) continue;
                $tipoIva = (int) str_replace($ivaStr, "", $key);
                if (!is_int($tipoIva)) continue;
                return $tipoIva;
            }
            return 0;
        } catch (\Throwable $th) {
            throw new PDOException($th->getMessage());
        }
    }
    public static function update($codigo, $articulo)
    {

        try {
            $result1 = self::updateArticulo($codigo, $articulo);
            $result2 = self::updateCodBar($codigo, $articulo);
            $result3 = self::updateCompra($codigo, $articulo);
            // $result4 = self::updateCantidad($codigo, $articulo);
            if ($result1 && $result2  && $result3) {
                return [
                    'status' => true,
                    'data' => [],
                ];
            }
            return [
                'status' => false,
                'data' => [],
                'error' => 'Hubo errores insertando'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data' => [],
                'error' => $th->getMessage()
            ];
        }
    }
    public static function insert($codigo, $articulo)
    {
        try {
            $result1 = self::insertArticulo($codigo, $articulo);
            $result2 = self::insertCodbar($codigo, $articulo);
            $result3 = self::insertCompra($codigo, $articulo);

            if ($result1 && $result2  && $result3) {
                return [
                    'status' => true,
                    'data' => [],
                ];
            }
            return [
                'status' => false,
                'data' => [],
                'error' => 'No se ha podido insertar'
            ];
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
        $tipoiva = self::getTipoIva($articulo['iva']);
        $sql = "UPDATE articulo SET PRECIOCOSTE=:precio, tipoiva=:tipoiva  where codigo=:codigo";

        $stmt = static::$firebird->prepare($sql);
        $result = $stmt->execute([
            'precio' => (float)$articulo['precio'],
            'tipoiva' => $tipoiva,
            'codigo' => $codigo
        ]);

        return $result;
    }

    private static function insertArticulo($codigo, $articulo)
    {
        $vars = [
            'codigo' => (string) $codigo,
            'codmarca' => null,
            'nombre' => (string) $articulo['nombre'],
            'descripcion' => (string) utf8_decode($articulo['nombre'] . '<br><br>'  . ',' . '' . ',' . ''),
            'preciocoste' => (float) $articulo['precio'],
            'precioventa' => null,
            'codfamilia' => null,
            'baja' => 'F',
            'tipoactualizacion' => 0,
            'tipoiva' => $articulo['iva'],
            'tipoivareducido' => 1,
            'tipoivacompra' => 0,
            'tipoivacomprareducido' => 1,
            'controlstock' => 1,
            'unidaddecimales' => 0,
            'preciodecimales' => 2,
            'costedecimales' => 2,
            'stockfactor' => 1,
            'etiquetasegununidadmedida' => 0,
            'proveeddefecto' => null,
            'ubicacion' => 0,
            'descripcioncorta' => $articulo['nombre'],
            'formatodesccorta' => 0,
            'formatodescripcion' => 2,
            'metakeywords' => null,
            'aplicarinvsujetopasivo' => 0,
            'tipobc3' => 20,
            'unidadcontrolcarubicstock' => 0,
            'excluirweb' => 'T',
        ];
        $sql = 'INSERT INTO articulo 
        (codigo, codmarca, nombre, codfamilia, baja, descripcion, preciocoste, precioventa, 
        tipoactualizacion, tipoiva, tipoivareducido, tipoivacompra, tipoivacomprareducido, controlstock, 
        unidaddecimales, preciodecimales, costedecimales, stockfactor, etiquetasegununidadmedida, proveeddefecto, 
        ubicacion, descripcioncorta, formatodesccorta, formatodescripcion, metakeywords, aplicarinvsujetopasivo, 
        tipobc3, unidadcontrolcarubicstock, excluirweb) 
        VALUES 
        (:codigo, :codmarca, :nombre, :codfamilia, :baja, :descripcion, :preciocoste, :precioventa, 
        :tipoactualizacion, :tipoiva, :tipoivareducido, :tipoivacompra, :tipoivacomprareducido, :controlstock, 
        :unidaddecimales, :preciodecimales, :costedecimales, :stockfactor, :etiquetasegununidadmedida, :proveeddefecto, 
        :ubicacion, :descripcioncorta, :formatodesccorta, :formatodescripcion, :metakeywords, :aplicarinvsujetopasivo, 
        :tipobc3, :unidadcontrolcarubicstock, :excluirweb)';

        $stmt = $firebird->prepare($sql);

        // Execute the query with parameters
        return $stmt->execute($vars);
    }
    private static function updateCodBar($codigo, $articulo)
    {
       
        $sql = 'UPDATE codbarra 
        SET codbarras = :codbarras
        WHERE codarticulo = :codarticulo
        AND codcaract = :codcaract
        AND valorcaract = :valorcaract';

        $stmt = $pdo->prepare($sql);

        // Execute the query with parameters
        $stmt->execute([
            ':codbarras' => $articulo['codbar'],
            ':codarticulo' => $codigo,
            ':codcaract' => null,
            ':valorcaract' => null,
        ]);

        return $result;
    }
    private static function insertCodbar($codigo, $articulo)
    {

        $sql = "INSERT INTO codbarra
        (codarticulo, codcaract, valorcaract, codbarras)
        VALUES
        (:codarticulo, :codcaract, :valorcaract, :codbarras)";

        $stmt = $firebird->prepare($sql);

        // Execute the query with parameters
        $result = $stmt->execute([
            ':codbarras' => $articulo['codbar'],
            ':codarticulo' => $codigo,
            ':codcaract' => null,
            ':valorcaract' => null,
        ]);

        return $result;
    }
    private static function updateCompra($codigo, $articulo)
    {

        $sql = 'UPDATE compra 
                SET preciocoste = :preciocoste
                WHERE codarticulo = :codarticulo
                AND codcaract = :codcaract
                AND valorcaract = :valorcaract';

        $stmt = $firebird->prepare($sql);


        $result = $stmt->execute([
            ':preciocoste' => (float)$articulo['precio'],
            ':codarticulo' => $codigo,
            ':codcaract' => null,
            ':valorcaract' => null,
        ]);

        return $result;
    }


    private static function insertCompra($codigo, $articulo)
    {

        $sql = 'INSERT INTO compra 
        (codarticulo, codproveedor, preciocoste, preciotarifa, descuento) 
        VALUES 
        (:codarticulo, :codproveedor, :preciocoste)';

        $stmt = $firebird->prepare($sql);

        // Execute the query with parameters
        $result = $stmt->execute([
            ':preciocoste' => (float)$articulo['precio'],
            ':codarticulo' => $codigo,
            ':codproveedor' => null
        ]);
        return $result;
    }
    private static function updateCantidad($codigo, $articulo)
    {
        $sql = "UPDATE EXISTENC SET STOCK1=:cantidad where codigo=:codigo";

        $stmt = static::$firebird->prepare($sql);
        $result = $stmt->execute([
            'cantidad' => $articulo['cantidad'],
            'codigo' => $codigo
        ]);

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
