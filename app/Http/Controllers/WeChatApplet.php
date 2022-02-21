<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-01-30 09:56:03
 * @LastEditTime: 2021-03-02 11:15:03
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\WeChatApplet.php
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class WeChatApplet extends Controller
{
    /**
	 * 小程序获取 (auth.code2Session)
     * @param
	 * appid	string		是	小程序 appId
     * secret	string		是	小程序 appSecret
     * js_code	string		是	登录时获取的 code
     * grant_type	string		是	授权类型，此处只需填写 authorization_code
     *
	 * @return Object
     *  openid	string	用户唯一标识
     *  session_key	string	会话密钥
     *  unionid	string	用户在开放平台的唯一标识符，在满足 UnionID 下发条件的情况下会返回
     * 
	 */
    public function authCode2Session($appid, $secret, $code)
    {
        $response = Http::get("https://api.weixin.qq.com/sns/jscode2session?appid=". $appid ."&secret=". $secret ."&js_code=". $code ."&grant_type=authorization_code");
        return $response->json();
    }

    /**
	 * 小程序获取 (auth.getAccessToken)
     * @param
	 * appid	string		是	小程序 appId
     * secret	string		是	小程序 appSecret
     * grant_type	string		是	填写 client_credential
     *
	 * @return Object
     *  access_token	string	获取到的凭证
     *  expires_in	number	凭证有效时间，单位：秒。目前是7200秒之内的值。
     *  errcode	number	错误码
     *  errmsg	string	错误信息
     * 
	 */
    public function authGetAccessToken($appid, $secret)
    {
        $dict = [
            '-1'    =>	'系统繁忙，此时请开发者稍候再试',
            '0'     => '请求成功',
            '40001' => 'AppSecret 错误或者 AppSecret 不属于这个小程序，请开发者确认 AppSecret 的正确性',
            '40002' => '请确保 grant_type 字段值为 client_credential',
            '40013' => '不合法的 AppID，请开发者检查 AppID 的正确性，避免异常字符，注意大小写',
            '40125' => '检查appid对应secret是否正确'
        ];

        $response = Http::get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=". $appid ."&secret=". $secret);
        if (!empty($response->json('errcode'))) {
            return [
                'errcode' => $response->json('errcode'),
                'errmsg' => $dict[$response->json('errcode')],
                'tips' => $response->json('errmsg')
            ];
        }
        return $response->json();
    }

    /**
	 * 小程序获取 (wxacode.getUnlimited)
     * @param
	 *  access_token	string		是	接口调用凭证
     *  scene	string		是	最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
     *  page	string	主页	否	必须是已经发布的小程序存在的页面（否则报错），例如 pages/index/index, 根路径前不要填加 /,不能携带参数（参数请放在scene字段里），如果不填写这个字段，默认跳主页面
     *  width	number	430	否	二维码的宽度，单位 px，最小 280px，最大 1280px
     *  auto_color	boolean	false	否	自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调，默认 false
     *  line_color	Object	{"r":0,"g":0,"b":0}	否	auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"} 十进制表示
     *  is_hyaline	boolean	false	否	是否需要透明底色，为 true 时，生成透明底色的小程序
     *
	 * @return Object
     *  返回的图片 Buffer
     * 
	 */
    public function wxCodeGetUnlimited($access_token, $scene = '', $page = '', $width = 430, $auto_color = false, $line_color = '', $is_hyaline = false)
    {
        $scene = $scene ?: make_random_string(15, 15);
        $dict = [
            45009    =>	'调用分钟频率受限(目前5000次/分钟，会调整)，如需大量小程序码，建议预生成。',
            41030    => '所传page页面不存在，或者小程序没有发布'
        ];
        $params = [];

        $params['scene'] = $scene ?: 'lancema';
        $params['width'] = $width ?: 430;

        if (!empty($page)) {
            $params['page'] = $page;
        }
        if (!empty($auto_color)) {
            $params['auto_color'] = true;
        }
        if (!empty($line_color)) {
            $params['line_color'] = $line_color;
        }
        if (!empty($is_hyaline)) {
            $params['is_hyaline'] = true;
        }

        $response = Http::post("https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token, $params);
        if (!empty($response->json('errcode'))) {
            return [
                'errcode' => $response->json('errcode'),
                'errmsg' => $dict[$response->json('errcode')],
                'tips' => $response->json('errmsg')
            ];
        }
        // $response => 二进制数据流数据
        return $response;
    }
}
