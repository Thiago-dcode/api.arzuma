<?php

namespace App\Intranet\Modules;

use PDO;
use App\Intranet\Utils\Utils;
use App\Intranet\Utils\Constants;
use App\Intranet\Utils\TreeBuilder;
use App\Intranet\Pyme\PymeConnection;

class Combinaciones
{

    static $firebird = null;



    public static function get($company, $codArticulo, $proveedor, $limit = 10)
    {

        static::$firebird = PymeConnection::start(Constants::get($company));
        $firebird = static::$firebird;

        $result = [];



        $offset = 0;
        $page = 1;


        if ($proveedor) {
            // $sql = "select count(*) as total from compra WHERE codproveedor = :codproveedor";
            // $stmt = $firebird->prepare($sql);
            // // to uppercase
            // $stmt->execute([':codproveedor' => $proveedor]);
            // $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['TOTAL'];
            // if ($count == 0) {
            //     return new JsonResponse(
            //         [
            //             'status' => 'success',
            //             'data' => [],
            //             'count' => $count,
            //             'page' => $page,
            //         ],
            //         200,
            //         ["Content-Type" => "application/json"]
            //     );
            // }

            $sql = "SELECT  codarticulo as codigo FROM compra WHERE codproveedor = $proveedor";
            $stmt = $firebird->prepare($sql);

            $stmt->execute();

            $result = $stmt->fetchAll();
        } elseif ($codArticulo) {


            $codArticulo = strtoupper($codArticulo);

            $sql = "SELECT  codigo FROM articulo WHERE codigo LIKE '%$codArticulo%'";
            $stmt = $firebird->prepare($sql);
            // to uppercase
            $stmt->execute();
            $result = $stmt->fetchAll();
        }

        $data = [];

        $articulos = array_map(function ($item) {
            return $item['CODIGO'];
        }, $result);
        if (!$articulos) return [];
        $in = "";
        $i = 0;
        foreach ($articulos as $item) {
            $key = ":id" . $i++;
            $in .= ($in ? "," : "") . $key; // :id0,:id1,:id2
            $in_params[$key] = $item; // collecting values into a key-value array
        }


        $to = $limit - 9;
        $sql = "select articulo.codigo, articulo.nombre, articulo.preciocoste, articulo.precioventa, articulo.codmarca, articulo.codfamilia, articulo.proveeddefecto, articulo.metakeywords,
        carvalortemporada.valor as temporada, carvalorcoleccion.valor as coleccion, webgrupocategoriaarticulo.codgrupocategoria, webgrupocategoriaarticulo.codcategoriadefecto
        from articulo
        left join caract as caracttemporada on caracttemporada.codclase=2 and caracttemporada.nombre='Temporada'
        left join carvalor as carvalortemporada on carvalortemporada.codcaract = caracttemporada.codcaract and carvalortemporada.codobjeto= articulo.codigo
        left join caract as caractcoleccion on caractcoleccion.codclase=2 and caractcoleccion.nombre='Colección'
        left join carvalor as carvalorcoleccion on carvalorcoleccion.codcaract = caractcoleccion.codcaract and carvalorcoleccion.codobjeto= articulo.codigo
        left join webgrupocategoriaarticulo on webgrupocategoriaarticulo.codarticulo= articulo.codigo
        where articulo.codigo IN ($in)
        ROWS $to to $limit";

        $stmt = $firebird->prepare($sql);
        $stmt->execute($in_params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = $result;


        foreach ($data as $key => $articulo) {
            $sql = 'select carvalid.dimension, carvalid.orden, carvalid.valor, caract.nombre
            from carvalid
            left join caract on caract.codcaract = carvalid.codcaract and caract.dimension = carvalid.dimension
            where carvalid.codobjeto = :codigo
            order by carvalid.dimension, carvalid.orden';
            $stmt = $firebird->prepare($sql);
            $stmt->execute(['codigo' => $articulo['CODIGO']]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $color = [];
            $copa = [];
            $talla = [];
            foreach ($result as $value) {
                if (substr($value['NOMBRE'], 0, 5) == 'COLOR' || substr($value['NOMBRE'], 0, 5) == 'Color') {
                    $color[] = $value['VALOR'];
                } elseif (substr($value['NOMBRE'], 0, 4) == 'COPA' || substr($value['NOMBRE'], 0, 4) == 'Copa') {
                    $copa[] = $value['VALOR'];
                } elseif (substr($value['NOMBRE'], 0, 5) == 'TALLA' || substr($value['NOMBRE'], 0, 5) == 'Talla') {
                    $talla[] = $value['VALOR'];
                }
            }
            $data[$key]['COLOR'] = implode(',', $color);
            $data[$key]['COPA'] = implode(',', $copa);
            $data[$key]['TALLA'] = implode(',', $talla);



            $sql = 'select venta.valorcaract, venta.precio from venta where codarticulo = :codigo';
            $stmt = $firebird->prepare($sql);
            $stmt->execute(['codigo' => $articulo['CODIGO']]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data[$key]['VENTA'] = $result;

            $sql = 'select compra.valorcaract, compra.preciocoste, compra.codproveedor, compra.descuento from compra where codarticulo = :codigo';
            $stmt = $firebird->prepare($sql);
            $stmt->execute(['codigo' => $articulo['CODIGO']]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data[$key]['COMPRA'] = $result;

            $sql = 'select valorcaract from carvaliddeshabilitado where codobjeto = :codigo';
            $stmt = $firebird->prepare($sql);
            $stmt->execute(['codigo' => $articulo['CODIGO']]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data[$key]['DESHABILITADO'] = $result;

            $sql = 'select codbarra.valorcaract, codbarra.codbarras from codbarra where codarticulo = :codigo';
            $stmt = $firebird->prepare($sql);
            $stmt->execute(['codigo' => $articulo['CODIGO']]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data[$key]['CODBARRAS'] = $result;
        }




        return self::formatData($data);
    }


    private static function getMarca()
    {
        $sql = 'SELECT NOMBRE as nombre, CODIGO as codigo FROM MARCA';
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function getProveedor()
    {
        $sql = 'SELECT NOMBRECOMERCIAL as nombre, CODIGO as codigo  FROM PROVEED';
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function getTemporada()
    {
        $sql = 'select VALOR as nombre from carvalid where codclase = 2 and codcaract = 1 and codobjeto is null';
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function getColeccion()
    {
        $sql = ' select VALOR as nombre from carvalid where codclase = 2 and codcaract = 2 and codobjeto is null';
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function getWebGrupoCategoria()
    {
        $sql = 'SELECT * FROM WEBGRUPOCATEGORIA;';
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getFamilia()
    {
        $sql = 'SELECT CODIGO as codigo, DESCRIPCION as nombre,PADRE, ORDEN FROM TIPO WHERE TIPO = 13 ORDER BY ORDEN;';
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getWebCategoria($cod, $company = '')
    {
        if (!static::$firebird) {

            static::$firebird = PymeConnection::start(Constants::get($company));
        }

        try {
            $sql = 'SELECT CODGRUPOCATEGORIA, CODIGO as codigo, CODPADRE, NOMBRE as nombre, ORDEN FROM WEBCATEGORIA WHERE CODGRUPOCATEGORIA = ' . $cod . 'ORDER BY ORDEN;';
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Handle the exception, e.g., log the error or display an error message
            // echo 'Error: ' . $e->getMessage();
        }
    }
    private static function getPrecio($codart, $proveed)
    {


        try {
            $sql = "select preciotarifa from compra where codarticulo = '$codart' and codproveedor = $proveed";

            $stmt = static::$firebird->prepare($sql);

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC)['PRECIOTARIFA'];
        } catch (PDOException $e) {
            // Handle the exception, e.g., log the error or display an error message
            // echo 'Error: ' . $e->getMessage();
        }
    }

    public static function getTemplate($company)
    {
        if (!static::$firebird) {

            static::$firebird = PymeConnection::start(Constants::get($company));
        }
        $marca = self::getMarca();

        $proveedor = self::getProveedor();
        $temporada = self::getTemporada();
        $temporada =    array_map(fn ($temp) => [
            'NOMBRE' => $temp['NOMBRE'],
            'CODIGO' => $temp['NOMBRE'],
        ], $temporada);
        $coleccion = self::getColeccion();
        $coleccion =    array_map(fn ($colec) => [
            'NOMBRE' => $colec['NOMBRE'],
            'CODIGO' => $colec['NOMBRE'],
        ], $coleccion);
        $webGrupoCategoria = self::getWebGrupoCategoria();

        $familia = TreeBuilder::build(self::getFamilia(), 'CODIGO', 'PADRE', 'NOMBRE', -1);

        $dataFormated['S_COD'] = [
            'id' => '',
            'placeholder' => 'codigo',
            'data' =>  '',
            'readonly' => true
        ];
        $dataFormated['S_REF'] = [
            'id' => '',
            'placeholder' => 'reference',
            'data' =>  '',
            'readonly' => false
        ];

        $dataFormated['S_metakey'] = [
            'id' => '',
            'placeholder' => 'keywords',
            'data' =>  '',
            'readonly' => false
        ];

        $dataFormated['S_nom'] = [
            'id' => '',
            'placeholder' => 'nombre',
            'data' =>  '',
            'readonly' => false
        ];
        $dataFormated['A_marca'] = [
            'id' => '',
            'data' =>  $marca,
            'readonly' => false
        ];
        $dataFormated['A_proveed'] = [
            'id' => '',
            'data' =>  $proveedor,
            'readonly' => false
        ];
        $dataFormated['S_color'] = [
            'id' => '',
            'placeholder' => 'color1,color2,color3...',
            'data' =>  '',
            'readonly' => false
        ];
        $dataFormated['S_talla'] = [
            'id' => '',
            'placeholder' => 'talla1,talla2,talla3...',
            'data' =>  '',
            'readonly' => false
        ];
        $dataFormated['S_precio'] = [
            'id' => '',
            'placeholder' => '0',
            'data' =>   0,
            'readonly' => false,
        ];

        $dataFormated['S_des'] = [
            'id' => '',
            'placeholder' => '0',
            'data' =>  0,
            'readonly' => false,
        ];

        $dataFormated['S_coste'] = [
            'id' => '',
            'placeholder' => '0',
            'data' => 0,
            'readonly' => true,
        ];
        $dataFormated['S_marg'] = [
            'id' => '',
            'placeholder' => '0',
            'data' =>  0,
            'readonly' => false,
        ];
        $dataFormated['S_P.V.A'] = [
            'id' => '',
            'placeholder' => '0',
            'data' => 0,
            'readonly' => true,
        ];
        $dataFormated['A_temporada'] = [
            'id' => '',
            'data' =>  $temporada,
            'readonly' => false,

        ];
        $dataFormated['A_coleccion'] = [
            'id' => '',
            'data' =>  $coleccion,
            'readonly' => false,
        ];
        $dataFormated["A_hombre/mujer"] = [
            'id' => '',
            'data' =>  $webGrupoCategoria,
            'readonly' => false,
        ];

        $dataFormated['T_cat.web'] = [
            'id' => '',
            'data' => [],
            'readonly' => false,
        ];
        $dataFormated['T_familia'] = [
            'id' => '',
            'data' =>  $familia,
            'readonly' => false,
        ];
        $dataFormated['C_venta'] = [
            'id' => '',
            'data' =>  [],
            'readonly' => false
        ];
        $dataFormated['C_compra'] = [
            'id' => '',
            'data' =>  [],
            'readonly' => false
        ];
        $dataFormated['D_deshab'] = [
            'id' => '',
            'data' =>  [],
            'deshab' => [],
            'readonly' => false
        ];
        $dataFormated['C_codbar'] = [
            'id' => '',
            'data' =>  [],
            'readonly' => false
        ];
        $dataFormated['I_info'] = [
            'id' => '',
            'modified' => false,
            'isInsert' => true,
            'readonly' => true
        ];



        return [
            'count' => 1,
            'articulos' => [$dataFormated],
            'isTemplate' => true

        ];
    }



    private  static function formatData($data)
    {

        $marca = self::getMarca();

        $proveedor = self::getProveedor();

        $temporada = self::getTemporada();

        $temporada =    array_map(fn ($temp) => [
            'NOMBRE' => $temp['NOMBRE'],
            'CODIGO' => $temp['NOMBRE'],
        ], $temporada);
        $coleccion = self::getColeccion();
        $coleccion =    array_map(fn ($colec) => [
            'NOMBRE' => $colec['NOMBRE'],
            'CODIGO' => $colec['NOMBRE'],
        ], $coleccion);
        $webGrupoCategoria = self::getWebGrupoCategoria();

        $familia = TreeBuilder::build(self::getFamilia(), 'CODIGO', 'PADRE', 'NOMBRE', -1);


        function getComb($color, $talla)
        {


            $comb = [];
            foreach (explode(',', $color) as $key => $col) {

                foreach (explode(',', $talla) as $key => $tal) {

                    array_push($comb, "$col-$tal");
                }
            }

            return $comb;
        }

        function getCompraVenta($arr, $codBarra, $priceKeyName = 'PRECIOCOSTE', $articulo = [])
        {
            try {

                return array_map(function ($cod) use ($arr, $priceKeyName) {


                    foreach ($arr as $key => $value) {

                        if ($value["VALORCARACT"] !== $cod["valorcaract"]) continue;
                        return [

                            "valorcaract" => $value["VALORCARACT"],
                            'value' => Utils::roundTo((float)$value[$priceKeyName], 2)

                        ];
                    }
                    return [
                        "VALORCARACT" => $cod["valorcaract"],
                        'value' => null
                    ];
                }, $codBarra);
            } catch (\Throwable $th) {
            }
        }


        // all keys from the api start with  S,A,T,OR C 
        // S: The value of that key is a simple string or number.
        // A: The value is an Array.
        // T: The value is a Tree.
        // C: The value depends on combinations.


        $dataOfdataFormated = [];

        foreach ($data as  $d) {

            if (isset($d['COPA']) && $d['COPA'])  continue;

            $comb = getComb($d['COLOR'], $d['TALLA']);
            $codBarra = array_map(function ($cod) use ($comb) {

                foreach ($comb as $key => $value) {

                    if ($cod["VALORCARACT"] !== $value) {

                        continue;
                    };

                    return [
                        "valorcaract" => $cod["VALORCARACT"],
                        "value" => $cod["CODBARRAS"],
                    ];
                }
            }, $d['CODBARRAS']);


            $codBarra =   array_filter($codBarra, fn ($cod) => is_array($cod));

            $descuento = '';
            foreach ($d['COMPRA'] as $key => $val) {

                if ($val['CODPROVEEDOR'] !== $d['PROVEEDDEFECTO']) continue;
                $descuento =  $val['DESCUENTO'];
                break;
            }

            $precio = self::getPrecio($d['CODIGO'], $d['PROVEEDDEFECTO']);

            $pCompra = array_filter(getCompraVenta($d['COMPRA'], $codBarra, 'PRECIOCOSTE', $d), fn ($cod) => is_array($cod));

            $pVenta = array_filter(getCompraVenta($d['VENTA'], $codBarra, 'PRECIO'), fn ($cod) => is_array($cod));

            $dataFormated = [];

            $dataFormated['S_COD'] = [
                'id' => '',
                'placeholder' => 'codigo',
                'data' =>  $d['CODIGO'],
                'readonly' => true
            ];
            $dataFormated['S_REF'] = [
                'id' => '',
                'placeholder' => 'reference',
                'data' =>  $d['CODIGO'],
                'readonly' => true
            ];


            $dataFormated['S_nom'] = [
                'id' => '',
                'placeholder' => 'nombre',
                'data' =>  $d['NOMBRE'],
                'readonly' => true
            ];
            $dataFormated['S_metakey'] = [
                'id' => '',
                'placeholder' => 'keywords',
                'data' =>  $d['METAKEYWORDS'],
                'readonly' => false
            ];
            $dataFormated['A_marca'] = [
                'id' => $d['CODMARCA'],
                'data' =>  $marca,
                'readonly' => false
            ];
            $dataFormated['A_proveed'] = [
                'id' => $d['PROVEEDDEFECTO'],
                'data' =>  $proveedor,
                'readonly' => true
            ];
            $dataFormated['S_color'] = [
                'id' => '',
                'placeholder' => 'color1,color2,color3...',
                'data' =>  $d['COLOR'],
                'readonly' => true
            ];
            $dataFormated['S_talla'] = [
                'id' => '',
                'placeholder' => 'talla1,talla2,talla3...',
                'data' =>  $d['TALLA'],
                'readonly' => true
            ];
            $dataFormated['S_precio'] = [
                'id' => '',
                'placeholder' => '0',
                'data' =>   Utils::roundTo($precio, 10),
                'readonly' => false,
            ];

            $dataFormated['S_des'] = [
                'id' => '',
                'placeholder' => '0',
                'data' =>  $descuento,
                'readonly' => false,
            ];

            $dataFormated['S_coste'] = [
                'id' => '',
                'placeholder' => '0',
                'data' => Utils::roundTo($d['PRECIOCOSTE'], 4),
                'readonly' => true,
            ];
            $dataFormated['S_marg'] = [
                'id' => '',
                'placeholder' => '0',
                'data' =>  Utils::percentageBtwNumbers($d['PRECIOCOSTE'], $d['PRECIOVENTA']),
                'readonly' => false,
            ];
            $dataFormated['S_P.V.A'] = [
                'id' => '',
                'placeholder' => '0',
                'data' =>  Utils::roundTo($d['PRECIOVENTA'], 4),
                'readonly' => true,
            ];
            $dataFormated['A_temporada'] = [
                'id' => $d['TEMPORADA'],
                'data' =>  $temporada,
                'readonly' => false,

            ];
            $dataFormated['A_coleccion'] = [
                'id' => $d['COLECCION'],
                'data' =>  $coleccion,
                'readonly' => false,
            ];
            $dataFormated["A_hombre/mujer"] = [
                'id' => $d['CODGRUPOCATEGORIA'],
                'data' =>  $webGrupoCategoria,
                'readonly' => false,
            ];

            $dataFormated['T_cat.web'] = [
                'id' => $d["CODCATEGORIADEFECTO"],

                'data' => TreeBuilder::build(self::getWebCategoria(((int)$d['CODGRUPOCATEGORIA'])), 'CODIGO', 'CODPADRE', 'NOMBRE'),
                'readonly' => false,
            ];
            $dataFormated['T_familia'] = [
                'id' => $d['CODFAMILIA'],
                'data' =>  $familia,
                'readonly' => false,
            ];
            $dataFormated['C_venta'] = [
                'id' => '',
                'data' =>  $pVenta ?? [],
                'readonly' => false
            ];
            $dataFormated['C_compra'] = [
                'id' => '',
                'data' =>  $pCompra ?? [],
                'readonly' => false
            ];
            $dataFormated['D_deshab'] = [
                'id' => '',
                'data' =>  $comb,
                'deshab' => array_map(fn ($des) => $des['VALORCARACT'], $d['DESHABILITADO']),
                'readonly' => false
            ];
            $dataFormated['C_codbar'] = [
                'id' => '',
                'data' =>  $codBarra,
                'readonly' => false
            ];
            $dataFormated['I_info'] = [
                'id' => '',
                'modified' => false,
                'isInsert' => false,
                'readonly' => true
            ];

            array_push($dataOfdataFormated, $dataFormated);
        }

        return [
            'count' => count($data),
            'articulos' => $dataOfdataFormated,
            'isTemplate' => false

        ];
    }

    private static function getHombreMujer($articulo)
    {   return $articulo;
        $hombreMujer = $articulo["hombre/mujer"];
     
        $sql = "select codigo, nombre from webgrupocategoria where codigo = $hombreMujer";
        $stmt =  static::$firebird->prepare($sql);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result[0]['NOMBRE'];
    }
    private static function getCategoriaWeb($articulo)
    {
        $codgrupo = $articulo["hombre/mujer"];
        $codigo = $articulo["cat.web"];

        if (!$codgrupo || !$codigo) return '';
        $sql = "select codgrupocategoria, codigo, codpadre, nombre, orden from webcategoria
        where codgrupocategoria = $codgrupo
        and codigo = $codigo";
        $stmt =  static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categoriaWeb = $result[0]['NOMBRE'];

        while ($result[0]['CODPADRE'] != 0) {
            $sql = 'select codgrupocategoria, codigo, codpadre, nombre, orden from webcategoria
            where codgrupocategoria = ' . $articulo["hombre/mujer"] . '
            and codigo = ' . $result[0]['CODPADRE'];
            $stmt =  static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $categoriaWeb = $result[0]['NOMBRE'] . '/' . $categoriaWeb;
        }
        return $categoriaWeb;
    }
    private static function getCodCaract($articulo)
    {
        try {

            $sql = "select * from carvalid
        left join caract on caract.codcaract = carvalid.codcaract and caract.dimension = carvalid.dimension
        where codobjeto = '" . $articulo['COD'] . "'
        and caract.nombre like 'COLOR%'";

            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $codcaract = '';
            if (sizeof($result) > 0) {
                $codcaract =  $result[0]['CODCARACT'];
            }
            return $codcaract;
        } catch (\Throwable $th) {
            return $articulo;
        }
    }
    public static function getCodigo($articulo)
    {

        $codigo = 'P' . $articulo['proveed'] . $articulo['REF'];
        $codigo = mb_strtoupper($codigo);
        return $codigo;
    }


    private static function updateArticulos($articulo)
    {
        
        $hombreMujer = self::getHombreMujer($articulo);
        return $hombreMujer;
        $categoriaWeb = self::getCategoriaWeb($articulo);

        $vars = [
            'codmarca' => (int) $articulo['marca'],
            'descripcion' => (string) utf8_decode($articulo['nom'] .
                '<br><br>' .
                $articulo['metakey'] ? $articulo['metakey'] : '' .
                ',' .
                $hombreMujer .
                ',' .
                $categoriaWeb),
            'preciocoste' => (float) $articulo['coste'],
            'precioventa' => (float) $articulo['P.V.A'],
            'codfamilia' => (float) $articulo['familia'],
            'metakeywords' => $articulo['metakey'],
            'codigo' => $articulo['COD']
        ];


        $sql = "update articulo set
        codmarca = " . $vars["codmarca"] . ",
        descripcion = '" . $vars["descripcion"] . "',
        preciocoste ='" . $vars["preciocoste"] . "',
        precioventa =" . $vars["precioventa"] . ",
        codfamilia =" . $vars["codfamilia"] . ",
        metakeywords ='" . $vars["metakeywords"] . "'
        where codigo ='" . $vars["codigo"] . "'";

        $stmt = static::$firebird->prepare($sql);



        return $stmt->execute();
    }
    private static function updateCompra($articulo)
    {
        $vars = [
            'codarticulo' => (string) $articulo['COD'],
            'codproveedor' => (int) $articulo['proveed'],
            'preciocoste' => (float) $articulo['coste'],
            'preciotarifa' => (float) $articulo['precio'],
            'descuento' => (float) $articulo['des'],
        ];


        $sql = 'update compra 
        set preciocoste = ' . (float) $vars['preciocoste'] . ', 
            preciotarifa = ' . (float) $vars['preciotarifa'] . ', 
            descuento = ' . (float) $vars['descuento'] . ' 
        WHERE codarticulo = \'' . $vars['codarticulo'] . '\' 
        AND codproveedor = ' . (int) $vars['codproveedor'];
        $stmt = static::$firebird->prepare($sql);


        return $stmt->execute();
        // $combinacionesLogger->debug('Compra actualizada', $vars);
    }

    private static function updateCategoriaWeb($articulo)
    {

        $vars = [
            'codarticulo' => $articulo['COD'],
            'codgrupocategoria' => (int) $articulo["hombre/mujer"],
            'codcategoria' => (int) $articulo["cat.web"]
        ];

        $sql = 'UPDATE webcategoriaarticulo 
                SET codgrupocategoria = ' . (int)$vars['codgrupocategoria'] . ', 
                    codcategoria = ' . (int)$vars['codcategoria'] . ' 
                WHERE codarticulo = \'' . $vars['codarticulo'] . '\'';

        $stmt = static::$firebird->prepare($sql);

        return $stmt->execute();
        // $combinacionesLogger->debug('Categoriasweb actualizada', $vars);
    }

    private static function getCodCaractCarValor($nombre)
    {
        $codcaract = '';
        $sql = "select codcaract from caract where codclase=2 and nombre='$nombre'";
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (sizeof($result) > 0) {
            $codcaract = $result[0]['CODCARACT'];
        }
        return  $codcaract;
    }

    private static function updateCarValor($articulo)
    {
        $codcaractTemporada = self::getCodCaractCarValor('Temporada');
        $codcaractColeccion = self::getCodCaractCarValor('Colección');



        $result = true;

        //temporada

        $vars = [
            'codclase' => 2,
            'codobjeto' => $articulo['COD'],
            'codcaract' => $codcaractTemporada,
            'valor' => $articulo['temporada'],
        ];
        $vars2 = [
            'codclase' => 2,
            'codobjeto' => $articulo['COD'],
            'codcaract' => $codcaractColeccion,
            'valor' => $articulo['coleccion'],
        ];

        $sql = 'UPDATE carvalor 
                SET valor = \'' . $vars['valor'] . '\'
                WHERE codobjeto = \'' . $vars['codobjeto'] . '\' 
                AND codclase = ' . $vars['codclase'] . '
                AND codcaract = ' . $vars['codcaract'];

        $sql2 = 'UPDATE carvalor 
                SET valor = \'' . $vars2['valor'] . '\'
                WHERE codobjeto = \'' . $vars2['codobjeto'] . '\' 
                AND codclase = ' . $vars2['codclase'] . '
                AND codcaract = ' . $vars2['codcaract'];

        $stmt =  static::$firebird->prepare($sql);

        if ($articulo['temporada']) {
            $result = $stmt->execute();
        }

        //coleccion
        $stmt2 = static::$firebird->prepare($sql2);

        if ($articulo['coleccion']) {
            $result =  $stmt2->execute();
        }
        // $combinacionesLogger->debug('Carvalor Temporada actualizada', $vars);

        return $result;
    }

    private static function updatePrecioVenta($articulo, $codcaract)
    {


        $result = true;

        foreach ($articulo['venta'] as $key => $precioVenta) {
            try {
                $vars = [
                    'codarticulo' => $articulo['COD'],
                    'codcaract' => $codcaract,
                    'valorcaract' => $precioVenta['valorcaract'],
                    'precio' => (float) $precioVenta['value'],
                ];

                $sql = 'UPDATE venta 
                    SET precio = ' . (float) $vars['precio'] . '
                    WHERE codarticulo = \'' . $vars['codarticulo'] . '\' 
                    AND codcaract = \'' . $vars['codcaract'] . '\' 
                    AND valorcaract = \'' . $vars['valorcaract'] . '\'';
                $stmt = static::$firebird->prepare($sql);

                $stmt->execute();
            } catch (\Throwable $th) {
                //throw $th;
            }
            

        }
        return $result;
    }


    private static function updatePrecioCompra($articulo, $codcaract)
    {
        $result = true;

        foreach ($articulo['compra'] as $key => $precioCompra) {
            try {
                $vars = [
                    'codarticulo' => $articulo['COD'],
                    'codcaract' => $codcaract,
                    'valorcaract' => $precioCompra['valorcaract'],
                    'preciocoste' => (float) $precioCompra['value'],
                ];

                $sql = 'UPDATE compra 
                        SET preciocoste = ' . (float) $vars['preciocoste'] . '
                        WHERE codarticulo = \'' . $vars['codarticulo'] . '\' 
                        AND codcaract = \'' . $vars['codcaract'] . '\' 
                        AND valorcaract = \'' . $vars['valorcaract'] . '\'';
                $stmt = static::$firebird->prepare($sql);

                $result =  $stmt->execute();
            } catch (\Throwable $th) {
                
            }
            // $combinacionesLogger->debug('compra actualizada', $vars);


        }
        return $result;
    }
    private static function updateCodBar($articulo, $codcaract)
    {
        $result = false;

        foreach ($articulo['codbar'] as $key => $codigoBarras) {
            try {
                if (!is_int((int) $codigoBarras['value'])) continue;
                $vars = [
                    'codarticulo' => $articulo['COD'],
                    'codcaract' => $codcaract,
                    'valorcaract' => $codigoBarras['valorcaract'],
                    'codbarras' => (int) $codigoBarras['value'],
                ];


                $sql = 'UPDATE codbarra 
                    SET codbarras = ' . (int) $vars['codbarras'] . '
                    WHERE codarticulo = \'' . $vars['codarticulo'] . '\' 
                    AND codcaract = \'' . $vars['codcaract'] . '\' 
                    AND valorcaract = \'' . $vars['valorcaract'] . '\'';
                $stmt = static::$firebird->prepare($sql);

                $result = $stmt->execute();
            } catch (\Throwable $th) {
                //throw $th;
            }
            // $combinacionesLogger->debug('codigo de barras actualizado', $vars);


        }
        return $result;
    }

    private static function updateInhabilitar($articulo, $codcaract)
    {

        $success = false;
        $codarticulo = $articulo['COD'];

        $sql = 'SELECT valorcaract, excluirweb FROM carvaliddeshabilitado 
        WHERE codobjeto = \'' . $codarticulo . '\' 
        AND codcaract = \'' . $codcaract . '\'';

        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $excluirwebs = [];

        foreach ($result as $row) {
            $excluirwebs[$row['VALORCARACT']] = $row['EXCLUIRWEB'];
        }

        foreach ($articulo['deshab'] as $key => $inhabilitar) {
            if (isset($excluirwebs[$inhabilitar])) {

                try {
                    $vars = [
                        'codarticulo' => $articulo['COD'],
                        'codcaract' => $codcaract,
                        'valorcaract' => $inhabilitar,
                        'excluirweb' => 'T',
                    ];

                    $sql = 'UPDATE carvaliddeshabilitado 
                            SET excluirweb = \'' . $vars['excluirweb'] . '\' 
                            WHERE codobjeto = \'' . $vars['codarticulo'] . '\' 
                            AND codcaract = \'' . $vars['codcaract'] . '\' 
                            AND valorcaract = \'' . $vars['valorcaract'] . '\'';
                    $stmt = static::$firebird->prepare($sql);


                    $success = $stmt->execute();
                } catch (\Throwable $th) {
                    //throw $th;
                }
                // $combinacionesLogger->debug('carvaliddeshabilitado actualizada', $vars);
                unset($excluirwebs[$inhabilitar]);
            } else {
                try {
                    $vars = [
                        'codclase' => 0,
                        'codobjeto' => $articulo['COD'],
                        'codcaract' => $codcaract,
                        'valorcaract' => $inhabilitar,
                        'deshabilitado' => 'F',
                        'excluirweb' => 'T',
                    ];

                    $sql = 'INSERT INTO carvaliddeshabilitado 
                            (codclase, codobjeto, codcaract, valorcaract, deshabilitado, excluirweb)
                            VALUES
                            (' . (int)$vars['codclase'] . ', 
                            \'' . $vars['codobjeto'] . '\', 
                            \'' . $vars['codcaract'] . '\', 
                            \'' . $vars['valorcaract'] . '\', 
                            \'' . $vars['deshabilitado'] . '\', 
                            \'' . $vars['excluirweb'] . '\')';
                    $stmt = static::$firebird->prepare($sql);

                    $success = $stmt->execute();
                } catch (\Throwable $th) {
                    //throw $th;
                }
                // $combinacionesLogger->debug('carvaliddeshabilitado insertada', $vars);
            }
        }
        foreach ($excluirwebs as $key => $excluirweb) {
            try {
                $vars = [
                    'codarticulo' => $articulo['COD'],
                    'codcaract' => $codcaract,
                    'valorcaract' => $key,
                ];

                $sql = 'DELETE FROM carvaliddeshabilitado 
                    WHERE codobjeto = \'' . $vars['codarticulo'] . '\' 
                    AND codcaract = \'' . $vars['codcaract'] . '\' 
                    AND valorcaract = \'' . $vars['valorcaract'] . '\'';
                $stmt = static::$firebird->prepare($sql);

                $success = $stmt->execute();
            } catch (\Throwable $th) {
                //throw $th;
            }
            // $combinacionesLogger->debug('carvaliddeshabilitado eliminada', $vars);
        }
        return $success;
    }
    public static function update($company, $articulo)
    {

        if (!static::$firebird) {

            static::$firebird = PymeConnection::start(Constants::get($company));
        }

        $codcaract =   self::getCodCaract($articulo);
         
        $result = false;

        $result = self::updateArticulos($articulo);
        return $result;
        $result =  self::updateCompra($articulo);

        $result =  self::updateCarValor($articulo);

        if ($articulo['hombre/mujer'] && $articulo['cat.web']) {

            $result = self::updateCategoriaWeb($articulo);
        };


        if (isset($articulo['venta']) && $articulo['venta'] && $codcaract) {

            $result = self::updatePrecioVenta($articulo, $codcaract);
        };

        if (isset($articulo['compra']) && $articulo['compra'] && $codcaract) {

            $result = self::updatePrecioCompra($articulo, $codcaract);
        };

        if (isset($articulo['codbar']) && $articulo['codbar'] && $codcaract) {

            $result =  self::updateCodBar($articulo, $codcaract);
        };

        if (isset($articulo['deshab']) && $codcaract) {

            $result = self::updateInhabilitar($articulo, $codcaract);
        };
        return true;
    }
    public static function insert($company, $articulo)
    {
        if (!static::$firebird) {

            static::$firebird = PymeConnection::start(Constants::get($company));
        }



        $articulo['COD'] = self::getCodigo($articulo);
        $codcaract = self::getCodCaract($articulo);
        $result = false;

        $result = self::insertArticulos($articulo);

        $result = self::insertCompra($articulo);

        $result = self::insertCarValor($articulo);

        if ($articulo['hombre/mujer'] && $articulo['cat.web']) {

            $result =  self::insertCategoriaWeb($articulo);
        };

        if ((isset($articulo['talla']) && $articulo['talla']) || (isset($articulo['color']) && $articulo['color'])) {
            $codcaract = self::insertCombinacion($articulo);
        }

        if (isset($articulo['venta']) && $articulo['venta'] && $codcaract) {

            $result = self::insertPrecioVenta($articulo, $codcaract);
        };

        if (isset($articulo['compra']) && $articulo['compra']) {

            $result = self::insertPrecioCompra($articulo, $codcaract);
        };

        if (isset($articulo['codbar']) && $articulo['codbar'] && $codcaract) {

            $result =  self::insertCodBar($articulo, $codcaract);
        };

        if (isset($articulo['deshab'])) {

            $result = self::insertDeshab($articulo, $codcaract);
        };
        return $result;
    }

    private static function insertArticulos($articulo)
    {
        try {
            $hombreMujer = self::getHombreMujer($articulo);

            $categoriaWeb = self::getCategoriaWeb($articulo);
            $vars = [
                'codigo' => (string) $articulo['COD'],
                'codmarca' => (int) $articulo['marca'],
                'nombre' => (string) $articulo['nom'],
                'descripcion' => (string) utf8_decode($articulo['nom'] . '<br><br>' . $articulo['metakey'] . ',' . $hombreMujer . ',' . $categoriaWeb),
                'preciocoste' => (float) $articulo['coste'],
                'precioventa' => (float) $articulo['P.V.A'],
                'codfamilia' => (float) $articulo['familia'],
                'baja' => 'F',
                'tipoactualizacion' => 0,
                'tipoiva' => 0,
                'tipoivareducido' => 1,
                'tipoivacompra' => 0,
                'tipoivacomprareducido' => 1,
                'controlstock' => 1,
                'unidaddecimales' => 0,
                'preciodecimales' => 2,
                'costedecimales' => 2,
                'stockfactor' => 1,
                'etiquetasegununidadmedida' => 0,
                'proveeddefecto' => (int) $articulo['proveed'],
                'ubicacion' => 0,
                'descripcioncorta' => $articulo['nom'],
                'formatodesccorta' => 0,
                'formatodescripcion' => 2,
                'metakeywords' => $articulo['metakey'],
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
                (\'' . $vars['codigo'] . '\', ' . (int)$vars['codmarca'] . ', \'' . $vars['nombre'] . '\', ' . (float)$vars['codfamilia'] . ', 
                \'' . $vars['baja'] . '\', \'' . $vars['descripcion'] . '\', ' . (float)$vars['preciocoste'] . ', ' . (float)$vars['precioventa'] . ', 
                ' . (int)$vars['tipoactualizacion'] . ', ' . (int)$vars['tipoiva'] . ', ' . (int)$vars['tipoivareducido'] . ', ' . (int)$vars['tipoivacompra'] . ', 
                ' . (int)$vars['tipoivacomprareducido'] . ', ' . (int)$vars['controlstock'] . ', ' . (int)$vars['unidaddecimales'] . ', 
                ' . (int)$vars['preciodecimales'] . ', ' . (int)$vars['costedecimales'] . ', ' . (int)$vars['stockfactor'] . ', 
                ' . (int)$vars['etiquetasegununidadmedida'] . ', ' . (int)$vars['proveeddefecto'] . ', ' . (int)$vars['ubicacion'] . ', 
                \'' . $vars['descripcioncorta'] . '\', ' . (int)$vars['formatodesccorta'] . ', ' . (int)$vars['formatodescripcion'] . ', 
                \'' . $vars['metakeywords'] . '\', ' . (int)$vars['aplicarinvsujetopasivo'] . ', ' . (int)$vars['tipobc3'] . ', 
                ' . (int)$vars['unidadcontrolcarubicstock'] . ', \'' . $vars['excluirweb'] . '\')';
            $stmt = static::$firebird->prepare($sql);

            return $stmt->execute();
        } catch (\Throwable $th) {
            return $th->getMessage() . ' insertARTICULO';
        }
    }
    private static function insertCompra($articulo)
    {

        try {
            $vars = [
                'codarticulo' => (string) $articulo['COD'],
                'codproveedor' => (int) $articulo['proveed'],
                'preciocoste' => (float) $articulo['coste'],
                'preciotarifa' => (float) $articulo['precio'],
                'descuento' => (float) $articulo['des'],
            ];

            $sql = 'INSERT INTO compra 
                (codarticulo, codproveedor, preciocoste, preciotarifa, descuento) 
                VALUES 
                (\'' . $vars['codarticulo'] . '\', ' . (int)$vars['codproveedor'] . ', ' . (float)$vars['preciocoste'] . ', ' . (float)$vars['preciotarifa'] . ', ' . (float)$vars['descuento'] . ')';

            $stmt = static::$firebird->prepare($sql);

            return $stmt->execute();
        } catch (\Throwable $th) {
            return $th->getMessage() . ' insertCompra';
        }
    }
    private static function insertCategoriaweb($articulo)
    {
        try {
            $vars = [
                'codarticulo' => $articulo['COD'],
                'codgrupocategoria' => (int) $articulo["hombre/mujer"],
                'codcategoria' => (int) $articulo["cat.web"],
            ];

            $sql = 'INSERT INTO webcategoriaarticulo 
                (codarticulo, codgrupocategoria, codcategoria) 
                VALUES 
                (\'' . $vars['codarticulo'] . '\', ' . (int)$vars['codgrupocategoria'] . ', ' . (int)$vars['codcategoria'] . ')';

            $stmt = static::$firebird->prepare($sql);

            return $stmt->execute();
        } catch (\Throwable $th) {
            return $th->getMessage() . ' insertCompra';
        }
    }
    private static function insertCarValor($articulo)
    {
        try {
            $codcaractTemporada = self::getCodCaractCarValor('Temporada');
            $codcaractColeccion = self::getCodCaractCarValor('Colección');



            $result = true;



            $vars = [
                'codclase' => 2,
                'codobjeto' => $articulo['COD'],
                'codcaract' => $codcaractTemporada,
                'valor' => $articulo['temporada'],
            ];
            $vars2 = [
                'codclase' => 2,
                'codobjeto' => $articulo['COD'],
                'codcaract' => $codcaractColeccion,
                'valor' => $articulo['coleccion'],
            ];

            $sql = 'INSERT INTO carvalor 
        (codclase, codobjeto, codcaract, valor) 
        VALUES 
        (' . (int)$vars['codclase'] . ', \'' . $vars['codobjeto'] . '\', \'' . $vars['codcaract'] . '\', \'' . $vars['valor'] . '\')';

            $sql2 =  'INSERT INTO carvalor 
        (codclase, codobjeto, codcaract, valor) 
        VALUES 
        (' . (int)$vars2['codclase'] . ', \'' . $vars2['codobjeto'] . '\', \'' . $vars2['codcaract'] . '\', \'' . $vars2['valor'] . '\')';

            $stmt =  static::$firebird->prepare($sql);

            if ($articulo['temporada']) {
                $result = $stmt->execute();
            }
            $stmt2 = static::$firebird->prepare($sql2);

            if ($articulo['coleccion']) {
                $result =  $stmt2->execute();
            }


            return $result;
        } catch (\Throwable $th) {
            return $th->getMessage() . ' insertCompra';
        }
    }
    private static function insertPrecioVenta($articulo, $codcaract)
    {

        try {
            $codigo = $articulo['COD'];
            foreach ($articulo['venta'] as $key => $venta) {

                $valorcaract = $venta['valorcaract'];
                $precio = $venta['value'];

                $sql = "INSERT INTO venta
                (codarticulo, codcaract, valorcaract, precio)
                VALUES
                ('$codigo',$codcaract, '$valorcaract', '$precio')";
                $stmt = static::$firebird->prepare($sql);
                $stmt->execute();
            }
        } catch (\Throwable $th) {
            return $th->getMessage() . ' insertPrecioVenta';
        }
        return true;
    }
    private static function insertCombinacion($articulo)
    {
        $codcaract = '';

        $codigo = $articulo['COD'];
        $sql = 'SELECT MAX(c1.codcaract) FROM caract c1
        LEFT JOIN caract c2 ON c1.codcaract=c2.codcaract
        WHERE c1.codclase=0 AND c1.nombre LIKE \'TALLA%\' AND c1.dimension = 2
        AND c2.codclase=0 AND c2.nombre LIKE \'COLOR%\' AND c2.dimension = 1';

        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $codcaract = $result[0]['MAX'];
        $sql = "select count(*) from carvalid where codcaract = $codcaract and codclase=0 and codobjeto is Null";
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = $result[0]['COUNT'];
        if ($count > 90) {

            $sql = "SELECT nombre FROM caract 
        WHERE codcaract = $codcaract AND codclase = 0 AND nombre LIKE 'TALLA%'";
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $talla = $result[0]['NOMBRE'];
            $numero = (int)substr($talla, 5);
            $numero = $numero + 1;
            $sql = 'select max(codcaract) from caract where codclase=0';
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $codcaract = $result[0]['MAX'] + 1;
            $sql = "INSERT INTO caract 
            (codclase, codcaract, dimension, orden, nombre, tipo, documentos, stock, requerido, duplicados, duplicadosunidadmedida, modoentrada, anadirautomatico, costecaract, anadircodbarras, dimensionseparador) 
            VALUES 
            (0, $codcaract, 1, NULL, 'COLOR$numero', 0, 2047, 'T', 'F', 1, 0, 3, 'F', 'F', 'F', '-')";


            $stmt = static::$firebird->prepare($sql);
            try {
                $stmt->execute();
                $sql = "INSERT INTO caract 
            (codclase, codcaract, dimension, orden, nombre, tipo, stock, requerido, duplicados, duplicadosunidadmedida, modoentrada, anadirautomatico, costecaract, anadircodbarras, dimensionseparador) 
            VALUES 
            (0, $codcaract, 2, NULL, 'TALLA$numero', 0, 'T', 'F', 1, 0, 3, 'F', 'F', 'F', '-')";

                $stmt = static::$firebird->prepare($sql);

                $stmt->execute();
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        //tallas
        $tallas = explode(',', $articulo['talla']);
        $sql = "select max(orden) from carvalid where codclase=0 and codcaract = $codcaract and dimension = 2";
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $orden = $result[0]['MAX'] + 1;
        $ordenobjeto = 1;

        try {
            $sql = "insert into carvalid (codcaract, codclase, codobjeto, orden) values ($codcaract, 0,'$codigo', 0)";

            $stmt = static::$firebird->prepare($sql);

            $stmt->execute();
        } catch (\Throwable $th) {
            //throw $th;
        }

        foreach ($tallas as $talla) {

            $sql = "SELECT * FROM carvalid WHERE codobjeto IS NULL AND codcaract = $codcaract AND codclase = 0 AND dimension = 2 AND valor = '$talla'";
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            try {
                if (sizeof($result) == 0) {

                    $sql = "INSERT INTO carvalid
                    (codclase, codobjeto, codcaract, dimension, orden, valor, color, foto, deshabilitado, excluirweb)
                VALUES
                    (0, NULL, $codcaract, 2, $orden, '$talla', NULL, NULL, 'F', 'F')";

                    static::$firebird->query($sql);


                    $stmt->execute();
                    $orden++;
                }
                $sql = "INSERT INTO carvalid
                    (codclase, codobjeto, codcaract, dimension, orden, valor, color, foto, deshabilitado, excluirweb)
                VALUES
                    (0, '$codigo', $codcaract, 2, $ordenobjeto, '$talla', NULL, NULL, 'F', 'F')";

                $stmt = static::$firebird->prepare($sql);

                $stmt->execute();
            } catch (\Throwable $th) {
                //throw $th;
            }
            $ordenobjeto++;
        }
        //colores

        $colores = explode(',', $articulo['color']);
        $sql = "SELECT MAX(orden) FROM carvalid WHERE codclase = 0 AND codcaract = $codcaract AND dimension = 1";
        $stmt = static::$firebird->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $orden = $result[0]['MAX'] + 1;
        $ordenobjeto = 1;

        foreach ($colores as $color) {


            $sql = "select * from carvalid where codobjeto is Null and codcaract = $codcaract
                    and codclase=0 and dimension=1 and valor = '$color'";
            $stmt = static::$firebird->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            try {
                if (sizeof($result) == 0) {

                    $sql = "INSERT INTO carvalid 
                        (codclase, codobjeto, codcaract, dimension, orden, valor, color, foto, deshabilitado, excluirweb) 
                        VALUES 
                        (0, null, $codcaract, 1, $orden, '$color', null, null, 'F', 'F')";
                    $stmt = static::$firebird->prepare($sql);

                    $stmt->execute();
                    $orden++;
                }
                $sql = "INSERT INTO carvalid
                    (codclase, codobjeto, codcaract, dimension, orden, valor, color, foto, deshabilitado, excluirweb)
                    VALUES
                    (0, '$codigo', $codcaract, 1, $ordenobjeto, '$color', NULL, NULL, 'F', 'F')";
                $stmt = static::$firebird->prepare($sql);

                $stmt->execute();
            } catch (\Throwable $th) {
                //throw $th;
            }
            $ordenobjeto++;
        }


        return $codcaract;
    }
    private static function insertPrecioCompra($articulo, $codcaract)
    {
        foreach ($articulo['compra'] as $key => $compra) {
            try {

                $codigo = $articulo['COD'];
                $valorcaract = $compra['valorcaract'];
                $precio = $compra['value'];

                $sql = "INSERT INTO compra
                (codarticulo, codcaract, valorcaract, preciocoste)
                VALUES
                ('$codigo',$codcaract, '$valorcaract', '$precio')";

                $stmt = static::$firebird->prepare($sql);

                $stmt->execute();
            } catch (\Throwable $th) {
                // echo $th->getMessage() . ' insertCombinaciones';
            }
        }
        return true;
    }
    private static function insertCodBar($articulo, $codcaract)
    {

        foreach ($articulo['codbar'] as $key => $codbar) {
            try {
                $sql = "INSERT INTO codbarra
                (codarticulo, codcaract, valorcaract, codbarras)
                VALUES
                ('{$articulo['COD']}', '$codcaract', '{$codbar['valorcaract']}', '{$codbar['value']}')";

                $stmt = static::$firebird->prepare($sql);
                $stmt->execute();
            } catch (\Throwable $th) {
                // echo $th->getMessage() . ' insertCodBar';
            }
        }
        return true;
    }
    private static function insertDeshab($articulo, $codcaract)
    {
        foreach ($articulo['deshab'] as $key => $inhabilitar) {
            try {


                $sql = "INSERT INTO carvaliddeshabilitado
        (codclase, codobjeto, codcaract, valorcaract, deshabilitado, excluirweb)
        VALUES
        (0, '{$articulo['COD']}', $codcaract, '{$inhabilitar}', 'F', 'T')";

                $stmt = static::$firebird->prepare($sql);
                $stmt->execute();
            } catch (\Throwable $th) {
                // echo $th->getMessage() . ' insertDeshab';
            }
        }
        return true;
    }
}
