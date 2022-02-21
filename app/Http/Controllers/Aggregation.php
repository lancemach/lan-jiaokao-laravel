<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-03-14 15:32:45
 * @LastEditTime: 2021-03-29 16:39:39
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Aggregation.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Batch;
use App\Models\Products;
use App\Models\WorkStation;
use App\Models\Report;

use App\Http\Controllers\Category;

use App\Http\Controllers\Logs;

class Aggregation extends Controller
{
    private $paginator;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->paginator = object_array(PAGINATOR);
    }

    public function checkReportRelation(Request $request)
    {

        $fields = [
            'work.id' => ''
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $where = 'life = 1';

        // $batch = Batch::whereRaw($where)->selectRaw('id, squad, batch as text')->get();
        // $batch_list = [];
        // foreach ($batch as $v) {
        //     $atWork = ATWORK[$v['squad']];
        //     $v['text'] = $v['text'] . $atWork[0];
        //     $v['squad_text'] = $atWork[1] . '班';
        //     $batch_list[] = $v;
        // }
        $batch = [];
        foreach (ATWORK as $k => $v) {
            $batch[] = ['id' => $k, 'text' => $v[1] . '班', 'sign' => $v[0]];
        }

        // $products = Products::whereRaw($where)->selectRaw('id, number as text, station_id as sid')->get();
        $workStation = WorkStation::whereRaw($where)->selectRaw('id, number as text')->get();

        $category = new Category;
        return response()->json(export_data(100200, [
            // 'batch' => $batch,
            'batch' => $batch,
            // 'products' => $products,
            'station' => $workStation,
            'work_type' => object_array($category->list(0, 3), true)
        ],  '查询成功'));
    }

    public function checkWorkBigData(Request $request)
    {

        $category = new Category;

        $where = 'life = 1';
        $WSTotal = WorkStation::whereRaw($where)->count();
        $PTotal = Products::whereRaw($where)->count();
        $BTotal = Batch::whereRaw($where)->count();
        $STotal = object_array($category->list(0, 11), true);


        $Report = Report::whereRaw($where)->get();
        $work_type = object_array($category->list(0, 3), true);
        $report_list = $report_list_today = $report_list_all = $report = [];
        $report_count = [];
        $todayStart = strtotime(date('Y-m-d 00:00:00', SYSTEM_TIME));
        $todayEnd = strtotime(date('Y-m-d 23:59:59', SYSTEM_TIME));
        foreach (object_array($Report, true) as $v) {
            $report_list_all[$v['work_tid']]['output'][] = $v['output'];
            if ($v['created_time'] >= $todayStart && $v['created_time'] < $todayEnd) {
                $report_list_today[$v['work_tid']]['output'][] = $v['output'];
            }
        }
       
        foreach ($report_list_all as $k => $v) {
            $report_list[$k]['man'] = count($v['output']);
            $report_list[$k]['output'] = array_sum($v['output']);
        }

        foreach ($report_list_today as $k => $v) {
            $report_list[$k]['today'] = [
                'man' => count($v['output']),
                'output' => array_sum($v['output'])
            ];
        }

        return response()->json(export_data(100200, [
            'batch' => $BTotal,
            'products' => $PTotal,
            'station' => $WSTotal,
            'squad' => COUNT($STotal),
            'report' => $report_list
        ],  '查询成功'));
    }

    /**
     * 数据校验
     */
    protected function validator($request, $fields = [])
    {
        $validator = Validator::make($request->all(), $fields);

        if ($validator->fails()) {
            return response()->json(export_data(100400, null, $validator->errors()->messages()));
        }
        return $request->only(array_keys($fields));
    }
}
