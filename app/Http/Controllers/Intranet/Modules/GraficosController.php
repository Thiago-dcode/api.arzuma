<?php




namespace App\Http\Controllers\Intranet\Modules;

use App\Intranet\Utils\Utils;
use App\Intranet\Modules\Graficos;
use App\Intranet\Utils\Validate;
use App\Models\Modules\Chart;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Traits\HttpResponses;

use App\Http\Controllers\Controller;


class GraficosController extends Controller

{


        use HttpResponses;



        public function index(Request $request)
        {

                $company = $request->user()->company_active;
                if (!$company) {

                        return $this->error([], 'User must has a company activated', 401);
                }

                $userChartsByCompany =  DB::table('charts')->where('user_id', $request->user()->id)->where('company_name', $company)->orderBy('created_at', 'desc')->get();

                $chartsWithData = [];

                foreach ($userChartsByCompany as $chart) {
                        $chart = Utils::objectToArray($chart);

                        $chartWithData = [...$chart];
                        Graficos::get($company, $chart['sql']);
                        $chartWithData['data'] =  Graficos::get($company, $chart['sql']);

                        array_push($chartsWithData, $chartWithData);
                }

                return $this->success($chartsWithData);
                //start your logic here

        }
        public function types(Request $request)
        {


                return $this->success(DB::table('chart_types')->get());
        }
        public function create(Request $request)
        {

                $errors = [];
                $user = $request->user();
                $fields = $request->validate([
                        'title' => 'required',
                        'sql' => 'required',
                        'type' => 'required',
                ]);

                //Handle sql error
                $sqlValidation = Validate::sql($user->company_active, $fields['sql']);
                if (!$sqlValidation['result']) {

                        array_push($errors, $sqlValidation['message']);
                }


                if (!DB::table('chart_types')->where('type',  $fields['type'])->exists()) {

                        array_push($errors, 'The chart type must be a available chart type');
                };

                if (!$errors) {



                        $newChart =  Chart::create([
                                'title' => $fields['title'],
                                'sql' => $fields['sql'],
                                'type' => $fields['type'],
                                'user_id' => $user->id,
                                'config' => '',
                                'company_name' => $user->company_active
                        ]);
                        $chart = Utils::objectToArray($newChart);

                        $chartWithData = [...$chart];
                        Graficos::get($user->company_active, $chart['sql']);
                        $chartWithData['data'] =  Graficos::get($user->company_active, $chart['sql']);

                        return $this->success($chartWithData, 'Chart create successfully');
                }

                return $this->error($errors, '', 422);
        }
        public function update($id, Request $request)
        {


                $errors = [];
                $user = $request->user();

                $fields = $request->validate([
                        'title' => 'required',
                        'sql' => 'required',
                        'type' => 'required',
                ]);

                //Handle sql error

                $sqlValidation = Validate::sql($user->company_active, $fields['sql']);
                if (!$sqlValidation['result']) {

                        array_push($errors, $sqlValidation['message']);
                }

                if (!DB::table('chart_types')->where('type',  $fields['type'])->exists()) {

                        array_push($errors, 'The chart type must be a available chart type');
                };

                if (!$errors) {

                        $chart = Chart::find($id);
                        $config = $chart->config;
                        if ($chart->type !== $fields['type']) {

                                $config = '';
                        }
                        $chartUpdated =  $chart->update([
                                'title' => $fields['title'],
                                'sql' => $fields['sql'],
                                'type' => $fields['type'],
                                'config' => isset($request['config']) ? $request['config'] : $config
                        ]);
                        $chart = Utils::objectToArray($chart);

                        $chartWithData = [...$chart];
                        Graficos::get($user->company_active, $chart['sql']);
                        $chartWithData['data'] =  Graficos::get($user->company_active, $chart['sql']);
                        return $this->success($chartWithData, 'Chart updated successfully');
                }

                return $this->error($errors, '', 422);
        }
        public function delete($id)
        {

                $chart = Chart::find($id);

                if (!$chart) {

                        return  $this->error([], "Trying to delete a chart that don't exist", 422);
                }

                $chart->delete();

                return $this->success([], 'Chart deleted successfully');
        }
}
