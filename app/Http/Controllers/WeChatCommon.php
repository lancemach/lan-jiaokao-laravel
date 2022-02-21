<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-01-30 09:57:05
 * @LastEditTime: 2021-06-23 16:43:49
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\WeChatCommon.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Logs;
use App\Http\Controllers\WeChatApplet;
use App\Http\Controllers\BasicInterface;

class WeChatCommon extends Controller
{
    public static $OK = 0;
	public static $IllegalAesKey = -41001;
	public static $IllegalIv = -41002;
	public static $IllegalBuffer = -41003;
	public static $DecodeBase64Error = -41004;
	
	private $frequency_time = 60 * 60 * 24 * 30;

    private $appid;
	private $sessionKey;
	public $prefix;
	private $expires_in = 7200;
    
	/**
	 * 构造函数
	 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
	 * @param $appid string 小程序的appid
	 */
    public function __construct()
    {
		$this->prefix = DB::getConfig('prefix');
    }

	/** 
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
     *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function encryptedData($param)
	{

		extract($param);
		$bi = new BasicInterface;
		$tag = $tags ?: 'clbd';
        if (! $config = $bi->getAccessData($tag . '_applet', $tag, 'applet')) {
            return response()->json(export_data(100400, null, '当前 appid 配置不存在或者为空'));
        }
		$this->appid = $config['param_val'];
		if (empty($code)) {
			$session_key = Redis::get('applet_session_key');
			if (empty($session_key)) {
				return response()->json(export_data(100400, null, '当前用户登录token为空'));
			}
		} else {
			$WeChatApplet = new WeChatApplet();
			
			$applet = $WeChatApplet->authCode2Session($this->appid, $config['param_key'], $code);
			if (empty($applet['session_key']) || !empty($applet['errcode'])|| !empty($applet['errmsg'])) {
				return response()->json(export_data(100400, null, '(errcode: '. $applet['errcode'] . '  errmsg:' . $applet['errmsg'] .')'));
			}

			$session_key = $applet['session_key'];
			if (empty(Redis::set('applet_session_key', $session_key))) {
				return response()->json(export_data(100400, null, '当前 appid session_key redis 储存失败'));
			}
		}
        
        if (empty($session_key)) {
            return response()->json(export_data(100400, null, '当前 appid session_key 获取失败'));
        }
		$this->sessionKey = $session_key;
		
		if (strlen($this->sessionKey) != 24) {
			return response()->json(export_data(100400, null, '当前 appid session_key 获取失败,' . self::$IllegalAesKey));
		}
		$aesKey = base64_decode($this->sessionKey);

		if (strlen($iv) != 24) {
			return response()->json(export_data(100400, null, '当前 appid 解密参数iv参数错误,' . self::$IllegalIv));
		}
		$aesIV = base64_decode($iv);

		$aesCipher = base64_decode($encryptedData);

		$result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

		$dataObj = json_decode($result);
		if( $dataObj  == NULL )
		{
			return response()->json(export_data(100400, null, '当前 appid 解密数据错误,' . self::$IllegalBuffer));
		}
		if( $dataObj->watermark->appid != $this->appid )
		{
			return response()->json(export_data(100400, null, '当前 appid 解密数据错误,' . self::$IllegalBuffer));
		}

		$param = ['from' =>  $from];
		if (!empty($update) && $update === 1) {
            $param['update'] = $update;
			$param['openId'] = $openId;

        }

		$weChartUser = $this->setWeChatUser(array_merge(json_decode($result, true), $param));
		if (empty($weChartUser)) {
			return response()->json(export_data(100400, null, '当前用户数据储存失败'));
		}

		return $weChartUser;
        // return response()->json(export_data(100200, $weChartUser, '微信用户信息解析成功！'));
	}

	/**
	 * 储存/更新微信用户信息.
	 */
	public function setWeChatUser($user = [])
	{
			$data = flatten($user);

			// DB::enableQueryLog();
			if ($data['unionId'] = !empty($data['unionId']) ? $data['unionId'] : '') {
				$results = DB::table('wechat')
							->where('openId', 'like', '%"' . $data['openId'] . '"%')
							->orWhere('unionId', $data['unionId'])
							->first();
			} else {
				$results = DB::table('wechat')
							->where('openId', 'like', '%"' . $data['openId'] . '"%')
							->first();
			}

			$params = array_intersect_key($data, [
				'openId' => '',
				'nickName' => '',
				'gender' => '',
				'language' => '',
				'city' => '',
				'province' => '',
				'country' => '',
				'avatarUrl' => '',
				'unionId' => ''
			]);
			$params['updated_time'] = SYSTEM_TIME;
			$openId = $params['openId'];
			if (empty($results)) {
				$params['openId'] = json_encode([[ 'key' => $data['appid'], 'val' => $params['openId'] ]]);
				$params['created_time'] = SYSTEM_TIME;
				$id = DB::table('wechat')->insertGetId($params);
			} else {
				$Logs = new Logs;
				$params['uid'] = $results->uid;
				$openId_list = json_decode($results->openId, true);
				if (deep_in_array($openId, $openId_list)) {
					
					if (SYSTEM_TIME - $results->updated_time > $this->frequency_time || (!empty($data['update']) && $data['update'] === 1)) {
						unset($params['openId']);
						$updated = DB::table('wechat')
									->where('openId', 'like', "%\"" . $openId . "\"%")
									->orWhere('unionId', $data['unionId'])
									->update($params);
					}
					$results->openId = $openId;
				} else {
					$params['openId'] = json_encode(array_merge($openId_list, [ 'key' => $data['appid'], 'val' => $params['openId'] ]));
					$updated = DB::table('wechat')
								->where('openId', 'like', "%\"" . $openId . "\"%")
								->orWhere('unionId', $data['unionId'])
								->update($params);
				}
				$params['openId'] = $openId;
				
				if ($data_changes = array_values_change($params, object_array($results, true))) {
					$Logs->create([
						'mid' => logs_data_details(MODULES, 'wechat', 0, 1),
						'tid' => $results->id,
						'details' => $data_changes,
						'type' =>  $data['from']
					]);
				}
				
			}
			
			$params['openId'] = $openId;
			$params['from'] = $data['from'];
			return $params;
	}

	public function getUnlimited(Request $request)
	{

		$VerifyField = [
			'id'   => 'required|string|min:2|max:10',
			'tags'   => 'required|string|min:2|max:10'
		];
        // 数据校验
        $validator = Validator::make($request->all(), $VerifyField);

        if ($validator->fails()) {
            return response()->json(export_data(100401, null, $validator->errors()->messages()));
        }
		if (! $input = $request->only(array_keys($VerifyField))) {
			return response()->json(export_data(100400, null, '接口参数解析失败！'));
		}
		extract($input);

		$accessToken = $this->getAccessToken($tags, $id);
		$WXApplet = new WeChatApplet;
		$wxCodeUnLimit = $WXApplet->wxCodeGetUnlimited($accessToken, '345fdg35645', 'pages/login/index');
		if (!empty($wxCodeUnLimit['errcode'])) {
			// print_r($accessToken);
			return response()->json(export_data(100400, ['tips' => $wxCodeUnLimit['tips']], $wxCodeUnLimit['errmsg']));
		}

		// base64_encode => 接受二进制流数据
		return response()->json(export_data(100400, [ 'image' =>  'data:image/jpg;base64,' . base64_encode($wxCodeUnLimit)], '二维码生成成功！'));
	}
	/**
	 * 获取小程序全局唯一后台接口调用凭据（access_token）
	 * @param $tags string 系统配置标签名
	 * @param $id string 系统配置参数名
     *
	 * @return 
	 */
	public function getAccessToken($tags = '', $id = '')
	{

		if ($applet = DB::table('configs')
						->select(['param_key as secret', 'param_val as appid'])
						->where([['tags', $tags], ['param_id', $id]])
						->first()) {
			extract(object_array($applet, true));
		}

		if (empty($secret) || empty($appid)) {
			return [0, '当前app配置(appid、secret)为空或者不存在！'];
		}


		if (! $access_token = Redis::get($tags . '.access_token')) {
			$WXApplet = new WeChatApplet;
			$accessToken = $WXApplet->authGetAccessToken($appid, $secret);
			if (!empty($accessToken['errcode'])) {
				// print_r($accessToken);
				return response()->json(export_data(100400, ['tips' => $accessToken['tips']], $accessToken['errmsg']));
			}
			Redis::setex($tags . '.access_token', $accessToken['expires_in'] - 1, $accessToken['access_token']);
			return $accessToken['access_token'];
		}

		return $access_token;
	}

	/**
	 * 获取小程序码，适用于需要的码数量极多的业务场景.
	 * @param $access_token string
     *
	 * @return 
	 */
	public function getWXCodeGetUnlimited($access_token, $scene = '', $page = '', $width = 430)
	{

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
