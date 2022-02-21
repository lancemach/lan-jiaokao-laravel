<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-17 19:00:29
 * @LastEditTime: 2021-06-24 21:10:07
 * @LastEditors: Please set LastEditors
 * @Description: 微信支付
 * @FilePath: .\app\Http\Controllers\Pay\WeChart.php
 */

namespace App\Http\Controllers\Pay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Pay\Pay as Payment;
use App\Http\Controllers\Order;

use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;

class WeChart extends Controller
{
    protected $config = [
        'appid' => '', // APP APPID
        'app_id' => '', // 公众号 APPID
        'miniapp_id' => 'wx960267360ddb20a5', // 小程序 APPID
        'mch_id' => '1552573041', // 14577xxxx
        'key' => 'u0j7VWPXrSDd9NO4c3UlFbyQvzLt6nxG',
        'notify_url' => '',
        'cert_client' => '', // optional，退款等情况时用到
        'cert_key' => '',// optional，退款等情况时用到
        'log' => [ // optional
            'file' => './logs/wechat.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [ // optional
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
        // 'mode' => 'dev', // optional, dev/hk; `dev`时为沙箱模式 当为 `hk` 时，为香港 gateway。
        'result_success_code' => 'SUCCESS'
    ];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->config['notify_url'] = 'https://' . request()->server('HTTP_HOST') . '/api/pay/wechat/notify';
    }

    public function index()
    {
        $order = [
            'out_trade_no' => SYSTEM_TIME,
            'total_fee' => 1, // **单位：分**
            'body' => 'test body - 测试',
            'openid' => 'onkVf1FjWS5SBIixxxxxxx',
        ];

        $pay = Pay::wechat($this->config)->mp($order);
        // $pay->appId
        // $pay->timeStamp
        // $pay->nonceStr
        // $pay->package
        // $pay->signType
    }

    public function miniapp($order)
    {
        $result = Pay::wechat($this->config)->miniapp($order);
        return response()->json(export_data(100200, $result, '小程序支付统一下单成功！'));
    }

    public function close($order)
    {
        $result = Pay::wechat($this->config)->close($order);
        return response()->json(export_data(100200, $result, '小程序支付订单关闭成功！'));
    }

    public function get(Request $request)
    {
        $fields = [
            'out_trade_no' => 'filled|string',
            'transaction_id' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $result = Pay::wechat($this->config)->find($validator);
        return response()->json(export_data(100200, $result, '小程序订单查询成功！'));
    }

    public function notify()
    {
        $pay = Pay::wechat($this->config);
        $payment = new Payment;
        $order = new Order;

        try{
            $data = json_decode($pay->verify(), true); // 是的，验签就这么简单！
            
            if ($data['result_code'] == $this->config['result_success_code']) {
                // file_put_contents(storage_path().'\logs\WeChat.txt', $data);
                $data['type'] = 1;
                $payment->create($data);
                $order->pay(['sn' => $data['out_trade_no'], 'status_pay' => 1]);
            }
            // Log::debug('Wechat notify', $data->all());
        } catch (\Exception $e) {
            // $e->getMessage();
        }

        return $pay->success();
        // return $pay->success()->send();// laravel 框架中请直接 `return $pay->success()`
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
