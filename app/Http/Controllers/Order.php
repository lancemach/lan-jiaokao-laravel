<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-18 09:48:11
 * @LastEditTime: 2021-06-24 22:15:30
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Order.php
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\CardVip;

use App\Models\Order AS OrderMode;


class Order extends Controller
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

        $order = new OrderMode;

        // 单条数据查询
        if (! empty($id)) {
            $result = $order::whereRaw('id = ' . $id)->first();
            return response()->json(export_data(100200, $result,  $order->modules[1] . '查询成功'));
        }
        $orderByRaw = 'updated_time DESC, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ', updated_time DESC, created_time DESC';
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

        if (! $total = $order::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'pages' => []
                ]
            ],  $order->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                $order::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $order::whereRaw($whereRaw)
                                  ->orderByRaw($orderByRaw)
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
        ],  $order->modules[1] . '查询成功'));

    }

    
     /**
     * 软删除数据
     */
    public function delete(Request $request)
    {
        $fields = [
            'id'   => 'required|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        // $ids = explode(',', $id);

        $order = new OrderMode;
        if ($del = $order::whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $order->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $order->modules[1] . '数据删除失败'));
    }

    /**
     * 激活(恢复)数据
     */
    public function activate(Request $request)
    {
        $fields = [
            'id'   => 'required|numeric'
        ];
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $order = new OrderMode;
        if ($del = $order::whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $order->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $order->modules[1] . '数据启用失败'));
    }

     /**
     * 支付订单
     */
    public function pay($params = [])
    {
        extract($params);

        $order = new OrderMode;
        if (! $pay_order = $order::selectRaw('mid, params, id, goods_id')->whereRaw("id = " . ($id ?? 0) . " OR sn = '". ($sn ?? 0) . "'")->first()) {
            return false;
        }

        extract(object_array($pay_order, true));
        if (!empty($params)) {
            extract(json_decode($params, true));

            $cardVip = new CardVip;
            // 开通会员卡
            if ($mid == $cardVip->module->moduleId) {
                if (!empty($type)) {
                    $cardVip->make(['pid' => $goods_id, 'type' => $type]);
                }
            }
        }

        if ($order::whereRaw("id = " . ($id ?? 0) . " OR sn = '". ($sn ?? 0) . "'")->update(['status_pay' => $status_pay ?? 1, 'updated_time' => SYSTEM_TIME])) {
            return true;
        }
        return false;
    }

    /**
     * 更新数据
     */
    public function update(Request $request)
    {

        $fields = [
            'id' => 'filled|numeric',
            'sn' => 'filled|string',
            'life' => 'filled|numeric',
            'num' => 'filled|numeric',
            'price_derate' => 'filled|numeric',
            'price_deal' => 'filled|numeric',
            'price_total' => 'filled|numeric',
            'status_pay' => 'filled|numeric',
            'status_express' => 'filled|numeric',
            'status_order' => 'filled|numeric',
            'note' => 'nullable|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $filled = $validator;
        if (!empty($filled['id'])) {
            unset($filled['id']);
        }
        if (!empty($filled['sn'])) {
            unset($filled['sn']);
        }
        

        $order = new OrderMode;
        
        if (! $results = OrderMode::whereRaw("id = $id OR sn = '". $sn . "'")->first()) {
            return response()->json(export_data(100400, null,  $order->modules[1] . '当前更新数据为空！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $order->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = OrderMode::where("id = $id OR sn = '". $sn . "'")->update($filled)) {
            return response()->json(export_data(100200, null,  $order->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $order->modules[1] . '数据更新失败'));
    }

    // 添加信息
    public function create($array)
	{
        extract($array);

        $uid = auth('api')->user()['id'] ?? 0;
        

        $goods_base = 10 ** 8;
        $sn = date("YmdHis") . (10 + intval($mid)) . ($goods_base > intval($goods_id) ? $goods_base + intval($goods_id) : intval($goods_id));

        $data = array_merge($array, [
            'sn' => $sn,
            'uid' => $uid,
            'life' => $life ?? 1,
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ]);

        $order = new OrderMode;

        $whereRaw = "goods_id = " . $goods_id . " AND mid = " . $mid . " AND status_pay = 0 AND uid = " . $uid . " AND seller_id = " . $seller_id . " AND status_order = 0 AND life = 1";

        if ($mid == 17) {
            $whereRaw .= " AND params LIKE '%\"type\":\"" . $type . "\"%'"; 
            unset($data['type']);
        }

        if ($hasData = $order::whereRaw($whereRaw)->first()) {
            if (SYSTEM_TIME - $hasData->created_time < 86400) {
                return object_array($hasData, true);
            }
            $order::whereRaw("id = $hasData->id AND life = 1")->update(['status_order' => 2, 'updated_time' => SYSTEM_TIME]); 
        }

        if ($id = $order->insertGetId($data)) {
            $data['id'] = $id;
            return object_array($data, true);
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
