<?php

namespace App\Http\Controllers\Intranet\Modules;

use App\Intranet\Modules\Eans;
use Illuminate\Http\Request;

use App\Traits\HttpResponses;

use App\Http\Controllers\Controller;


class EansController extends Controller

{


        use HttpResponses;



        public function index($name, Request $request)
        {

                if ($request->user()->company_active !== $name) {

                        return $this->error([], '', 401);
                }

                $limit = isset($request['limit']) ? $request['limit'] : 50;
                $cod_articulo = isset($request['codarticulo']) ? $request['codarticulo'] : '';
                $id_proveedor = isset($request['proveedor']) ? $request['proveedor'] : '';

                $eans = Eans::getAll($request->user()->company_active, $limit, $cod_articulo, $id_proveedor);
                if (count($eans) > 0) {
                        return \response($eans);
                }
                return $this->error([
                        'isNotFound'=> true

                ], 'No se ha encontrado ningún artículo', 404);
        }
        public function proveedores(Request $request)
        {


                return \response(Eans::getProveedores($request->user()->company_active));
        }
        public function total(Request $request)
        {
                $cod_articulo = isset($request['codarticulo']) ? $request['codarticulo'] : '';
                $id_proveedor = isset($request['proveedor']) ? $request['proveedor'] : '';
                return \response(Eans::getTotalEans($request->user()->company_active, $cod_articulo, $id_proveedor));
        }

        public function update(Request $request)
        {

                $eansError = [];
                $eansWithoutNum = [];

                foreach ($request->toArray() as $article) {

                        $eanWithoutNum = [];

                        if (!isset($article['num'])) continue;


                        if (!isset($article['update'])) {

                                if (!Eans::validateEans($article[$article['num'] . '-CODBARRANUEVO'])) {
                                        array_push($eansError, $article);
                                }
                        }

                        foreach ($article as $key => $value) {
                                if ($key === 'num' || $key === 'update') continue;

                                $keyWithoutNum = explode('-', $key)[1];
                                $eanWithoutNum[$keyWithoutNum] = $value;
                        }
                        array_push($eansWithoutNum, $eanWithoutNum);
                }

                if ($eansError) {
                        return $this->error($eansError, "Algunos de los códigos de barras no cumple con EAN13.", 422);
                }
                $eanSucess = [];

                foreach ($eansWithoutNum as $ean) {

                        if (!Eans::updateCodeBar($request->user()->company_active, $ean)) {
                                return $this->error($ean, "Algo fue mal con ese ean.", 422);
                        };
                        array_push($eanSucess, $ean);
                }

                return $this->success($eanSucess, 'Los códigos de barras han sido actualizados correctamente');
        }
}
