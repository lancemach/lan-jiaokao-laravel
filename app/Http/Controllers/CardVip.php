<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Settings;

use App\Http\Controllers\Auth;
use App\Http\Controllers\Order;
use App\Http\Controllers\Pay\WeChart as PayWeChart;
use App\Models\CardVip AS CardVipModel;

use App\Http\Controllers\Pay\Pay as Payment;

class CardVip extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
        $this->db_prefix = DB::getConfig('prefix');
        $this->module = new CardVipModel;
        $this->type = ['学车会员', '用车会员']; // 会员类型，请勿随意更改下标,
        $this->vip = [
            [
                'name' => '学车',
                'tips' => '考不过最高补偿100元',
                'title' => '全科稳过',
                'price' => 98.9,
                'save' => 0.27
            ],
            [
                'name' => '用车',
                'tips' => '',
                'title' => '洗车免费',
                'price' => 980,
                'save' => 0
            ]
        ];
    }

    protected function vipList($preview = 0) {
        $settings = new Settings;
        $vip = $settings->basic();
        $list = [];
        foreach ($this->vip as $k => $v)
        {   
            $v['price'] = $preview === 1 ? round(intval($vip['vip' . ($k + 1)]) / 100, 2) : $vip['vip' . ($k + 1)];
            $list[] = $v;
        }
        return $list;
    }

    // 查询信息
    public function get(Request $request)
	{
        $fields = [
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
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

        $db_prefix = $this->db_prefix;
        $tableLeft = $this->module->table;
        $tableLeftFull = $db_prefix . $this->module->table;
        $tableRight = $this->module->table_item;
        $tableRightFull = $db_prefix . $tableRight;

        $uid = auth('api')->user()['id'] ?? 0;

        if (empty($type)) {
            $type = 1;
        }

        // 单条数据查询
        if (! empty($uid)) {


            $leftTable = $this->module->table . " as a";
            $rightTable = $this->module->table_item . " as b";
            $threeTable = $this->module->table_item . " as c";
            $prefix = $this->db_prefix;

            $result = DB::table($leftTable)
                        ->selectRaw($prefix . "a.id, ". $prefix . "a.sn, ". $prefix . "b.`level` as le1, ". $prefix . "c.`level` as le2, ". $prefix . "b.end_time as et1, ". $prefix . "c.end_time as et2")
                        ->leftJoin($rightTable, function ($join) {
                            $join->on('b.pid', '=', 'a.id')
                                ->where('b.type', '=', 1);
                        })
                        ->leftJoin($threeTable, function ($join) {
                            $join->on('c.pid', '=', 'a.id')
                                ->where('c.type', '=', 2);
                        })
                        ->whereRaw($prefix . "a.uid = $uid")->first();

            // $whereRaw = $tableLeftFull . '.uid = ' . $uid . ' AND '. $tableRightFull .'.type = ' . $type;
            // $selectRaw = $tableLeftFull . '.*, ' . $tableRightFull . '.type,' . $tableRightFull . '.end_time,' . $tableRightFull . '.updated_time as start_time';
            // $result = DB::table($tableLeft)
            //             ->selectRaw($selectRaw)
            //             ->leftJoin($tableRight, $tableLeft . '.id', '=', $tableRight . '.pid')
            //             ->whereRaw($whereRaw)
            //             ->first();
            if (empty($result)) {
                $result = $this->create($request);
            }

            $data['combo'] = $this->vipList(1)[$type - 1];
            $data['vip'] = object_array($result, true);
            $end_time = $data['vip']['et' . $type];
            $data['vip']['life'] = !empty($end_time) && $end_time > SYSTEM_TIME ? 1 : 0;
            $data['vip']['mid'] = $this->module->moduleId;
            return response()->json(export_data(100200, $data,  $this->module->modules[1] . '查询成功'));
        }
        $orderByRaw = 'updated_time DESC, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ', updated_time DESC, created_time DESC';
        }
        
        // 是否随机取数
        $whereRaw = "";

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

        if (! $total = $this->module::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'pages' => []
                ]
            ],  $this->module->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                $this->module::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $this->module::whereRaw($whereRaw)
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
        ],  $this->module->modules[1] . '查询成功'));

    }

    /**
     * 更新数据
     */
    public function update(Request $request)
    {

        $fields = [
            'id' => 'required|numeric',
            'type' => 'filled|string',
            'note' => 'nullable|string'
        ];
        $uid = auth('api')->user()['id'] ?? 0;
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $filled = $validator;
        unset($filled['id']);

        if (! $results = $this->module::where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $this->module->modules[1] . '当前更新数据为空！'));
        }

        $whereRaw = "";


        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type = '" . $type . "'";
        }

        if (!empty($whereRaw) && $this->module::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $this->module->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = $this->module::where('id', $id)->update($filled)) {
            return response()->json(export_data(100200, null,  $this->module->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $this->module->modules[1] . '数据更新失败'));
    }

    // 添加信息
    public function create(Request $request)
	{
        $fields = [
            'type' => 'required|string',
            'city' => 'required|numeric',
            'note' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $uid = auth('api')->user()['id'] ?? 0;

        $sn = '00' . substr($city, 3, 4) . date("ymd") . mt_rand(1000, 9999);
        $data = [
            'uid' => $uid,
            'sn' => $sn,
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        $whereRaw = "uid = $uid";
        
        if ($hasData = $this->module::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        if ($id = $this->module::insertGetId($data)) {
            return $data;
        }
        return response()->json(export_data(100400, null, '信息添加失败！'));

    }

    // 会员信息
    public function vip($_uid = 0)
    {
        $user = auth('api')->user();
        $uid = $user->id ?? $_uid;

        if (empty($uid)) {
            return false;
        }

        $leftTable = $this->module->table . " as a";
        $rightTable = $this->module->table_item . " as b";
        $threeTable = $this->module->table_item . " as c";
        $prefix = $this->db_prefix;

        $hasData = DB::table($leftTable)
                    ->selectRaw($prefix . "a.id, ". $prefix . "a.sn, ". $prefix . "b.`level` as le1, ". $prefix . "c.`level` as le2, ". $prefix . "b.end_time as et1, ". $prefix . "c.end_time as et2")
                    ->leftJoin($rightTable, function ($join) {
                        $join->on('b.pid', '=', 'a.id')
                             ->where('b.type', '=', 1);
                    })
                    ->leftJoin($threeTable, function ($join) {
                        $join->on('c.pid', '=', 'a.id')
                             ->where('c.type', '=', 2);
                    })
                    ->whereRaw($prefix . "a.uid = $uid")->first();
        if (!empty($hasData->et1) && $hasData->et1 >= SYSTEM_TIME) {
            $hasData->ets1 = 1;
        }
        if (!empty($hasData->et2) && $hasData->et2 >= SYSTEM_TIME) {
            $hasData->ets2 = 1;
        }
        return object_array($hasData, true);
    }
    // 开通会员
    public function open(Request $request)
	{
        $fields = [
            'appid' => 'required|string',
            'title' => 'required|string',
            'goods_id' => 'required|numeric',
            'mid' => 'required|numeric',
            'seller_id' => 'required|numeric',
            'type' => 'required|string',
            'comboId' => 'required|numeric',
            // 'price_market' => 'required|numeric',
            // 'price_deal' => 'required|numeric',
            // 'price_total' => 'required|numeric',
            // 'price_derate' => 'filled|price_derate',
            'num' => 'required|numeric',
            'note' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $price = $type == 1 ? $this->vipList()[$comboId - 1]['price'] : $this->vipList()[0]['price'];
        $data = array_merge($validator, [
            'price_deal' => $price,
            'price_market' => $price,
            'price_derate' => 0,
            'price_total' => $price  * $num
        ]);

        $data['params'] = json_encode(['type' => $data['type']]);

        unset($data['comboId']);
        $order = new Order;
        $auth = new Auth;
        $user = $auth->info(['appid' => $appid]);

        if (!empty($appid)) {
            unset($data['appid']);
        }

        $data['goods_id'] = $user['vip']['id'] ?? 0;

        if ($isOrder = $order->create($data)) {
            extract($isOrder);
            $pay = new PayWeChart;
            $pay_miniapp = $pay->miniapp([
                'out_trade_no' => $sn,
                'body' => $title,
                'total_fee' => $price_total ,//* 100,
                'openid' => $user['openId'],
            ]);
            return $pay_miniapp;
        }
        return response()->json(export_data(100400, null, '会员订单创建失败！'));
    }
    /**
     * 制卡
     */
    public function make($params = [])
    {
        extract($params);
        if (empty($pid) || empty($type)) {
            return false;
        }

        if ($hasData = DB::table($this->module->table_item)->whereRaw("pid = $pid AND type = $type")->first()) {
            DB::table($this->module->table_item)->whereRaw("pid = $pid AND type = $type")->update(['end_time' => strtotime("+1year", $hasData->end_time)]);
            return true;
        }

        $data = [
            'pid' => $pid,
            'type' => $type,
            'end_time' => strtotime("+1year", mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1),
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        if ($id = DB::table($this->module->table_item)->insertGetId($data)) {
            return $id;
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
