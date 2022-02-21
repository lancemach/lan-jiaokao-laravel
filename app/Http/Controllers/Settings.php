<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-07 20:15:55
 * @LastEditTime: 2021-06-23 13:47:12
 * @LastEditors: Please set LastEditors
 * @Description: 设置基础信息
 * @FilePath: .\app\Http\Controllers\Settings.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;
use App\Models\Settings as SettingModel;
use App\Http\Controllers\BasicInterface;

class Settings extends Controller
{
    public function __construct()
    {
        $this->field = [
            1 => ''
        ];
    }
    
    // 更新
    public function update(Request $request)
    {
        $fields = $request->id == -1 ?
        [
            'id' => 'required|numeric',
            'param_id'   => 'required|string',
            'form'   => 'required|array'
        ] :
        [
            'id' => 'required|numeric',
            'param_key'   => 'required|string',
            'param_val'   => 'filled|string',
            'param_id'   => 'filled|string',
            'form'   => 'filled|array'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $settings = new SettingModel;

        if ($id == -1) {
            foreach ($form as $k => $v) {
                // echo $k . $v;
                $settings::whereRaw("param_id = '" . $param_id . "' AND tags = '" . $k . "'")->update(['param_key' => $v]);
            }
            return response()->json(export_data(100200, null,  '信息批量更新成功'));
        }

        if (! $update = $settings::whereRaw("id = $id")->first()) {
            return response()->json(export_data(100200, null,  '信息查询为空'));
        }

        if ($update->param_key === $param_key && (!empty($param_val) ? $update->param_val === $param_val : true) ) {
            return response()->json(export_data(100400, null,  '信息未改动，无需更新！'));
        }
        $update_fields = ['param_key' => $param_key];
        if (!empty($param_val)) {
            $update_fields = array_merge($update_fields, ['param_val' => $param_val]);
        }

        if (! $updated = $settings::whereRaw("id = $id")->update($update_fields)) {
            return response()->json(export_data(100400, null,  '更新失败'));
        }
        if ($update->tags === 'whiteList') {
            if (! $Whitelist = $this->Whitelist($param_key)) {
                return response()->json(export_data(100400, null,  '白名单(Redis)储存失败！'));
            }
        }
        if (!empty($param_id) && $param_id === 'interfaceKey') {
            $access = [
                DEFAULT_SECRET_KEY['KeyId'] => $param_val,
                DEFAULT_SECRET_KEY['KeySecret'] => $param_key
            ];
            $BasicInterface = new BasicInterface;
            if (! $interfaceKey = $BasicInterface->setAccessData($update->tags . '_' . $update->param_id, $access)) {
                return response()->json(export_data(100400, null,  '接口(密钥)储存失败！'));
            }
        }
        return response()->json(export_data(100200, null,  '信息更新成功'));
    }

    // 查询
    public function get(Request $request)
    {
  
        $fields = [
            'id' => 'filled|numeric',
            'param_id'   => 'filled|string',
            'tags'   => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $settings = new SettingModel;
        if (empty($tags)) {
            if (! $data = $settings::whereRaw("param_id = '" . ($param_id ?? 'basic') . "'")->get()) {
                return response()->json(export_data(100200, $data, '信息查询为空！'));
            }
        } else {
            if (! $data = $settings::whereRaw("tags = '" . ($tags ?? '') . "' AND param_id = '" . ($param_id ?? '') . "'")->first()) {
                return response()->json(export_data(100200, $data, '信息查询为空！'));
            }
        }
        
        return response()->json(export_data(100200, $data, '查询成功！'));
    }

    // 网站白名单
    protected function Whitelist($data = '')
    {
        $whiteList = explode(",", $data);
        if (Redis::ltrim('configs.whiteList', 1 , 0)) {
            if (Redis::rpush('configs.whiteList', $whiteList)) {
                return true;
            }
        }
        return false;
    }

    //
    public function basic()
    {
        $settings = new SettingModel;
        if (! $data = $settings::whereRaw("param_id = 'basic'")->get()) {
            return [];
        }
        $settings = [];
        foreach (object_array($data, true) as $k => $v) {
            $settings[$v['tags']] = $v['param_key'];
        }
        return $settings;
    }

    //
    public function AppletTabbar ()
    {
        return [
            [
                'title' => '学车',
                'icon' => 'study',
                'name' => 'index',
                'page' => '/pages/index/index'
            ],
            [
                'title' => '买车',
                'icon' => 'calculator',
                'name' => 'buys',
                'page' => '/pages/buys/index'
            ],
            [
                'title' => '用车',
                'icon' => 'guideboard',
                'name' => 'uses',
                'page' => '/pages/uses/index'
            ],
            [
                'title' => '我的',
                'icon' => 'mine',
                'name' => 'mine',
                'page' => '/pages/mine/index/index'
            ]
        ];
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
