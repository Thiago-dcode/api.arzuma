<?php

namespace App\Intranet\Modules;
use PDO;
use App\Intranet\Utils\Constants;
use App\Intranet\Pyme\PymeConnection;

class Eans
{


    public static function getAll($company,$limit = 50, $codArticulo = '', $proveedor = '')
    {

        $filter = '';
      

        if ($codArticulo) {

            $filter .= "AND cb.codarticulo LIKE " . "'%" . $codArticulo . "%'";
        }
        if ($proveedor) {

            $filter .= "AND a.PROVEEDDEFECTO = " . (int)$proveedor;
        }
      


        $sql = "SELECT  cb.codarticulo, a.nombre, p.nombrecomercial as Proveedor, cb.valorcaract, cb.codbarras, ce.stock1
FROM codbarra cb
RIGHT JOIN carexist ce ON cb.codarticulo = ce.codarticulo AND cb.valorcaract = ce.valor
JOIN articulo a ON cb.codarticulo = a.codigo
JOIN proveed p ON a.PROVEEDDEFECTO = p.codigo

WHERE cb.codbarras LIKE '20%'
        $filter
  AND ce.stock1 > 0
  and ce.codalmacen = 2
ORDER BY cb.codarticulo, cb.valorcaract
ROWS $limit-49 to $limit ;
              ";
			  
	;
       
        $stmt = PymeConnection::start(Constants::get($company))->prepare($sql);
        $stmt->execute([]);



        return  $stmt->fetchAll(PDO::FETCH_ASSOC);


       
    }
    public static function getTotalEans($company,$codArticulo = '', $proveedor = '')
    {

        $filter = '';


        if ($codArticulo) {

            $filter .= "AND cb.codarticulo = " . "'" . $codArticulo . "'";
        }
        if ($proveedor) {

            $filter .= "AND a.PROVEEDDEFECTO = " . (int)$proveedor;
        }

                $sql = "SELECT COUNT(*) AS total_rows
        FROM codbarra cb
        RIGHT JOIN carexist ce ON cb.codarticulo = ce.codarticulo AND cb.valorcaract = ce.valor
        JOIN articulo a ON cb.codarticulo = a.codigo
        JOIN proveed p ON a.PROVEEDDEFECTO = p.codigo

        WHERE cb.codbarras LIKE '20%'
        $filter
        AND ce.stock1 > 0
        and ce.codalmacen = 2";
                $stmt = PymeConnection::start(Constants::get($company))->prepare($sql);
                $stmt->execute();
                return  $stmt->fetchColumn();
    }

    public static function getProveedores($company){
        
        $sql = 'select nombrecomercial, codigo from proveed order by nombrecomercial';
        $stmt =  PymeConnection::start(Constants::get($company))->prepare($sql);
        $stmt->execute([]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $proveedores = [];
       return $result;

    }
    public static function validateEans($ean){
          // check to see if barcode is 13 digits long
          if (!preg_match("/^[0-9]{13}$/", $ean)) {
            return false;
        }

        $digits = $ean;

        // 1. Add the values of the digits in the 
        // even-numbered positions: 2, 4, 6, etc.
        $even_sum = $digits[1] + $digits[3] + $digits[5] +
            $digits[7] + $digits[9] + $digits[11];

        // 2. Multiply this result by 3.
        $even_sum_three = $even_sum * 3;

        // 3. Add the values of the digits in the 
        // odd-numbered positions: 1, 3, 5, etc.
        $odd_sum = $digits[0] + $digits[2] + $digits[4] +
            $digits[6] + $digits[8] + $digits[10];

        // 4. Sum the results of steps 2 and 3.
        $total_sum = $even_sum_three + $odd_sum;

        // 5. The check character is the smallest number which,
        // when added to the result in step 4, produces a multiple of 10.
        $next_ten = (ceil($total_sum / 10)) * 10;
        $check_digit = $next_ten - $total_sum;

        // if the check digit and the last digit of the 
        // barcode are OK return true;
        if ($check_digit == $digits[12]) {
            return true;
        }

        return false;

    }
    public static function updateCodeBar($company,$data)
    {   
       
        $result1 = false;
        $result2 = false;
        //update doclin
        $sql = "UPDATE doclin
                SET codbarras = :cod_barra_nuevo
                WHERE CODARTICULO = :codarticulo
                AND codbarras = :codbarras";
        try {
            $stmt =  PymeConnection::start(Constants::get($company))->prepare($sql);
            
            $params = [
                ':cod_barra_nuevo' => $data['CODBARRANUEVO'],
                ':codarticulo' => $data['CODARTICULO'],
                ':codbarras' => $data['CODBARRAS'],

            ];
           
           $result1 = $stmt->execute($params);
           

            //update codbarra

            $sql = "UPDATE codbarra
                SET codbarras = :cod_barra_nuevo
                WHERE CODARTICULO = :codarticulo
                AND VALORCARACT = :valorcaract
                AND codbarras = :codbarras";  

            $stmt =  PymeConnection::start(env('DBHOST_BERA_TEXTIL_PYME'))->prepare($sql);

            $params = [
                ':cod_barra_nuevo' => $data['CODBARRANUEVO'],
                ':codarticulo' => $data['CODARTICULO'],
                ':valorcaract' => $data['VALORCARACT'],
                ':codbarras' => $data['CODBARRAS'],
            ];

            $result2 = $stmt->execute($params);
        } catch (\Throwable $th) {
           
            throw new PDOException();
        } 
        
        finally {

            return $result1 && $result2;
        }
    }
    
    
}