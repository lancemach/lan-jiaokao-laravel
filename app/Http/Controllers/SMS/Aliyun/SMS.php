<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-02 16:49:35
 * @LastEditTime: 2021-07-01 11:45:40
 * @LastEditors: Please set LastEditors
 * @Description: 阿里云短信业务
 * @FilePath: .\app\Http\Controllers\Sms\Aliyun\SMS.php
 */

namespace App\Http\Controllers\Sms;

// require_once __DIR__ . "/SignatureHelper.php";

use Aliyun\DySDKLite\SignatureHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BasicInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SMS extends Controller
{
    private $accessKeyId;
    private $accessKeySecret;
    private $PhoneNumbers;
    private $SignName;
    private $TemplateCode;
    
    /**
     * Create a new SMS instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
    /**
     * 发送短信
     */
    public function sendSms(Request $request) {
 
        $fields = [
            'phone' => ['required', 'regex:' . REGEX['phone']],
            'scene' => 'required|string',
            'desired' => 'required|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        if (! $CODETODO = CODETODO[$scene] ?? false) {
            return response()->json(export_data(100400, null, '当前 使用场景(scene)不存在'));
        }
        if (! $SMSDYTEMP = SMSDYTEMP[$desired] ?? false) {
            return response()->json(export_data(100400, null, '当前 短信模板不存在'));
        }
        extract($SMSDYTEMP);

        $codeStr = SMSSTR . $phone;

        if ($isSend = Redis::get($codeStr)) {
            $time_diff = SYSTEM_TIME - (int)explode('_', $isSend)[0];
            if ($time_diff <= $expires_cycle) {
                return response()->json(export_data(100200, [
                    'expires_in' => ($expires_cycle - $time_diff) * 1000
                ], $time_diff . 's前验证码已发送'));
            }
            
        }
        
        $data = [];

        switch ($desired) {
            case 'code':
                $code = rand(100000, 999999);
                $serial = '0' . rand(1, 9);
                $param['code'] = $code;
                $data = [
                    'serial' => $serial,
                    'expires_in' => $expires_cycle * 1000
                ];

                break;
            
            default:
                # code...
                break;
        }

        // 发送短信
        $sendSms = $this->send($phone, $sign, $template_id, $param) ?: false;
        if ($sendSms !== 'OK') {
            return $sendSms;
        }

        if (empty(Redis::setex($codeStr, $expires_in, SYSTEM_TIME . '_' . $code))) {
            return response()->json(export_data(100400, null, '短信储存失败'));
        }

        return response()->json(export_data(100200, $data, '短信发送成功'));

    }

    /**
     * 核验短信是否存在(code)
     */
    public function verifySms($phone = 0, $check = false) {
        if (! $code = Redis::get(SMSSTR . $phone)) {
            return false;
        }
        return $check ? substr($code, strripos($code, "_") + 1) : true;
    }

    /**
     * 删除短信
     */
    public function delSms($phone = 0) {
        if (empty(Redis::del(SMSSTR . $phone))) {
            return false;
        }
        return true;
    }

    // 读取大鱼(密钥)配置
    protected function send ($phone = 0, $sign = '', $template_id = 0, $param = [], $fields = SECRET_KEY) {
        $bi = new BasicInterface;
        if (! $config = $bi->getAccessData('sms_dayu', 'sms', 'dayu')) {
            return response()->json(export_data(100400, null, '当前 appid 配置不存在或者为空'));
        }

        $config = [
            $fields['KeyId'] => $config['param_val'],
            $fields['KeySecret'] => $config['param_key']
        ];
 
        extract($config);

        $params = [];

        // *** 需用户填写部分 ***
        // fixme 必填：是否启用https
        $security = true;

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        // $accessKeyId = "";
        // $accessKeySecret = "";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $phone;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $sign;

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $template_id;

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = array_merge($param);

        // fixme 可选: 设置发送短信流水号
        // $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        // $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        try {
            // 此处可能会抛出异常，注意catch
            $content = $helper->request(
                $accessKeyId,
                $accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                    "RegionId" => "cn-hangzhou",
                    "Action" => "SendSms",
                    "Version" => "2017-05-25",
                )),
                $security
            );
        } catch (\Throwable $th) {
            return response()->json(export_data(100400, null, $th->getMessage()));
        }
        if ($content->Code !== 'OK') {
            return response()->json(export_data(100400, null, $content->Message));
        }

        return $content->Code;
    }

    // 读取大鱼(密钥)配置
    // protected function getAccessData () {

    //     if (! $sms_dayu = Redis::get('sms_dayu')) {
            
    //         $config = DB::table('configs')->where([['tags', $tags ?? 'sms'], ['param_id', $appid ?? 'dayu']])->first();
    //         if (empty($config->param_val) || empty($config->param_key)) {
    //             return false;
    //         }
    //         $access = [
    //             'accessKeyId' => $config->param_val,
    //             'accessKeySecret' => $config->param_key
    //         ];
    //         Redis::set('sms_dayu', serialize($access));
    //         return $access;
    //     }
    //     return unserialize($sms_dayu);
    // }

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
