<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-18 09:47:44
 * @LastEditTime: 2021-06-21 14:12:06
 * @LastEditors: Please set LastEditors
 * @Description: 订单支付
 * @FilePath: .\app\Http\Controllers\Pay\Pay.php
 */

namespace App\Http\Controllers\Pay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\Order AS OrderMode;
use App\Models\Pay AS PayMode;

class Pay extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
    }

    // 查询信息
    public function get(Request $request)
	{
        $fields = [
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'type' => 'filled|numeric',
            'sort' => 'filled|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $pay = new PayMode;

        // 单条数据查询
        if (! empty($id)) {
            $result = $pay::whereRaw('id = ' . $id)->first();
            return response()->json(export_data(100200, $result,  $pay->modules[1] . '查询成功'));
        }
        $payByRaw = 'updated_time DESC, created_time DESC';

        if (! empty($sort)) {
            $payByRaw = $sort . ', updated_time DESC, created_time DESC';
        }
        
        // 是否随机取数
        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 关键词查询
        if (! empty($kw)) {
            $whereRaw .= isJointString($whereRaw) . " name like \"%$kw%\"";
        }

        // 类型
        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type = " . $type;
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $pay::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'pages' => []
                ]
            ],  $pay->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                $pay::whereRaw($whereRaw)->orderByRaw($payByRaw)->get() :
                $pay::whereRaw($whereRaw)
                                  ->orderByRaw($payByRaw)
                                  ->offset($offset)
                                  ->limit($limit)
                                  ->get();
                       
        $list = [];

        return response()->json(export_data(100200, [
            'list' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => $pages,
                    'limit' => $limit,
                    'pages' => $list ?: $data
                ]
        ],  $pay->modules[1] . '查询成功'));

    }

    // 添加信息
    public function create($params = [])
	{

        extract($params);
        
        $order = new OrderMode;

        $order_pay = $order::whereRaw("sn = '" . $out_trade_no . "' AND status_pay = 0")->first() ?? 0;        
        $data = [
            'order_id' => $out_trade_no,
            'transaction_id' => $transaction_id,
            'uid' => $order_pay['uid'] ?? 0,
            'seller_id' => $order_pay['seller_id'] ?? 0,
            'price' => $total_fee,
            'time_end' => $time_end,
            'type' => $type ?? 1,
            'mode' => $mode ?? 1,
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];
        
        $pay = new PayMode;

        if ($id = $pay->insertGetId($data)) {
            return true;
         }
         return false;

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
