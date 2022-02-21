<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

use App\Http\Controllers\SMS\Aliyun\SMS;
use App\Http\Controllers\Category;
use App\Http\Controllers\Logs;
use App\Http\Controllers\WeChatCommon;
use App\Http\Controllers\Admin;
use App\Http\Controllers\CardVip;

class Auth extends Controller
{
    private $Verify_user_field = [
        'username'   => 'required|string|min:6|max:20',
        'password'   => 'required|string|min:6|max:32',
    ];

    private $create_user_field = [
        'phone'   => 'required|int|min:11',
        'name'   => 'required|string|min:2|max:12',
        'group_id' => 'filled|numeric',
        'password'   => 'required|string|min:6|max:20',
    ];

    private $username;
    /**
     * Create a new Auth instance.
     *
     * @return void
     */
    public function __construct()
    {

        /* 这里额外注意了：官方文档样例中只除外了(login)
        // 这样的结果是，token 只能在有效期以内进行刷新，过期无法刷新
        // 如果把 refresh 也放进去，token 即使过期但仍在刷新期以内也可刷新
        // 不过刷新一次作废
        */
        $this->middleware('jwt.auth', ['except' => ['login', 'register', 'loginWeChat', 'loginSmsCode']]);
        // 另外关于上面的中间件，官方文档写的是(auth:api)
        // 但是我推荐用 (jwt.auth)，效果是一样的，但是有更加丰富的报错信息返回

        $this->username = 'lma_' . make_random_string(16, 16);
    }

    /**
     * 注册新用户
     */
    public function register(Request $request)
    {
        
        // 数据校验
        $validator = Validator::make($request->all(), $this->Verify_user_field);

        if ($validator->fails()) {
            return response()->json(export_data(100401, null, $validator->errors()->messages()));
        }

        $user = User::where('username', $request->username)->first();
        if (!empty($user->password) || !empty($user->id)) {
            return response()->json(export_data(100404, null, '用户('. $user->username .')已被注册请更换用户名！'));
        }

        if (empty($user = $this->save($request))) {
            return response()->json(export_data(100200, null, '用户('. $user->username .')注册失败'));
        }
        return response()->json(export_data(100200, null, '用户('. $user->username .')注册成功'));
    }


    /**
     * 注册新用户(生成)
     */
    public function save($request)
    {
        $user = new User();

        if ($phone = $request->phone) {
            $user->phone = $phone;
        }

        $user->username = $request->username;
        $user->secret_salt = make_random_string(12, 24, 'max');
        $user->password = bcrypt($request->password . $user->secret_salt);
        $user->updated_time = $user->created_time = SYSTEM_TIME;
        $user->save();
        return $user;
    }


    /**
     * 创建新用户
     */
    public function create(Request $request)
    {
        // 数据校验
        $validator = Validator::make($request->all(), $this->create_user_field);
        if ($validator->fails()) {
            return response()->json(export_data(100401, null, $validator->errors()->messages()));
        }

        $request = object_array($request->only(array_keys($this->create_user_field)));
        $user = User::where('phone', $request->phone)
                ->orWhere('name', $request->name)
                ->first();

        if (!empty($user->password) || !empty($user->id)) {
            return response()->json(export_data(100404, null, '用户已被注册请检查手机、姓名等信息！'));
        }

        $user = new User();
        $user->phone = $request->phone;
        $user->name = $request->name;
        $user->username = $this->username;
        $user->secret_salt = make_random_string(12, 24, 'max');
        $user->password = bcrypt($request->password . $user->secret_salt);
        $user->updated_time = $user->created_time = SYSTEM_TIME;
        $user->save();
        $Logs = new Logs;
        $Logs->create([
            'uid' => auth('api')->user()->id,
            'mid' => logs_data_details(MODULES, 'users', 0, 1),
            'tid' => $user->id,
            'type' => 1
        ]);
        return response()->json(export_data(100200, null, '用户('. $request->name .')注册成功'));
    }



    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), $this->Verify_user_field);

        if ($validator->fails()) {
            return response()->json(export_data(100401, null, $validator->errors()->messages()));
        }

        $input = $request->only('username', 'password');
        $user = User::whereRaw("username = '" . $input['username'] ."' AND life = 1")->first();
        if (empty($user)) {
            return response()->json(export_data(100400, null, '用户名或者密码不正确！'));
        }

        $input['password'] = $input['password'] . $user['secret_salt'];
        $jwt_token = null;

        if (empty($user['id']) || !$jwt_token = JWTAuth::attempt($input)) {
            return response()->json(export_data(100400, null, '用户名或者密码不正确！'));
        }
        $this->loginLogs($user->id);
        return $this->respondWithToken($jwt_token, object_array($user, true));
    }

    /**
     * 短信验证码（快捷）登录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginSmsCode(Request $request)
    {
       
        $field = [
            'login' => 'required|array',
			'login.phone' => ['required', 'regex:' . REGEX['phone']],
            'login.code' => ['required', 'regex:' . REGEX['code']],
            'from' => 'filled|numeric',
            'appid' => 'required|string',
            'openId'   => '',
            'unionId'   => '',
            
		]; 

		// 数据校验
        $validator = Validator::make($request->all(), $field, [
            // 'code.required' => '验证码',
            'phone.regex' => '验证码(:attribute)格式是无效的',
            'code.regex' => '验证码(:attribute)格式是无效的'
        ]);

        if ($validator->fails()) {
            return response()->json(export_data(100401, null, $validator->errors()->messages()));
        }
        $input = $request->only(array_keys($field));
        extract($input);

        $phone = $login['phone'];
        $sms = new SMS;
        if (! $sendCodeStr = $sms->verifySms($phone, true)) {
            return response()->json(export_data(100400, null, '短信验证码已过期'));
        }
        if ($login['code'] !== $sendCodeStr) {
            return response()->json(export_data(100400, null, '短信验证码错误'));
        }

        if (! $user = User::whereRaw("phone = '" . $phone ."' AND life = 1")->first()) {
            $guest = new User;
            $guest->phone = $phone;
            $guest->username = $this->username;
            $user = $this->save($guest);
        }

        $jwt_token = null;
        
        if (!$jwt_token = auth('api')->login($user)) {
            return response()->json(export_data(100400, null, '手机短信快捷登录失败！'));
        }

        $isBindWx = !empty($unionId) ?
                    DB::table('wechat')->where('openId', 'like', '%"' . $openId . '"%')->orWhere('unionId', $unionId)->first() :
                    DB::table('wechat')->where('openId', 'like', '%"' . $openId . '"%')->first();
        if (! empty($isBindWx)) {
            $wechat = !empty($unionId) ?
                       DB::table('wechat')->where('openId', 'like', '%"' . $openId . '"%')->orWhere('unionId', $unionId)
                            ->update(['uid' => $user->id]) :
                       DB::table('wechat')->where('openId', 'like', '%"' . $openId . '"%')
                            ->update(['uid' => $user->id]);
            $Logs = new Logs;

            $Logs->create([
                'mid' => logs_data_details(MODULES, 'wechat', 0, 1),
                'tid' => $user->id,
                'details' => 'uid:'. '' .'=>' . $user->id,
                'type' => $from
            ]);
        }

        // 删除短信在验证码(校验成功)
        $sms->delSms($phone);

        $this->loginLogs($user->id, $from);
        $user = array_merge(object_array($user, true), object_array($isBindWx, true));

        return $this->respondWithToken($jwt_token, array_merge($user, ['openId' => deep_in_array($appid, json_decode($user['openId'], true), false)['val']]));
    }

    /**
     * 微信小程序（一键）登录
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginAKeyWeChat($we = [])
    {
        if (empty($we) || ! is_array($we)) {
            return;
        }

        extract($we);

        $user = User::find($uid);
        $jwt_token = null;
        if (!$jwt_token = auth('api')->login($user)) {
            return response()->json(export_data(100400, null, '用户名或者密码不正确！'));
        }

        $this->loginLogs($uid, $from);
        return $this->respondWithToken($jwt_token, array_merge(object_array($user, true), $this->info($we)));
    }

    /**
     * 修改密码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_password(Request $request)
    {
        $fields = [
            'username'   => 'required|string',
            'password'   => 'required|string',
            'new_password'   => 'required|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $whereRaw = "username = '" . $username . "' AND life = 1";
 
        if (! $update = User::selectRaw("secret_salt, password, id")->whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100200, null,  '修改用户不存在!'));
        }
        $secret_salt = $update->secret_salt;
        $user = [
            'password' => $password . $secret_salt,
            'username' => $username
        ];
        
        $new_password = bcrypt($new_password . $secret_salt);
 
        // if ($update->password === $new_password) {
        //     return response()->json(export_data(100400, null,  '新密码不能与旧密码相同!'));
        // }

        $jwt_token = null;
        if (empty($update->id) || !$jwt_token = JWTAuth::attempt($user)) {
            return response()->json(export_data(100400, null, '用户名或者密码不正确！'));
        }

        if (! User::whereRaw($whereRaw)->update(['password' => $new_password])) {
            return response()->json(export_data(100400, null, '修改密码失败！'));
        }

        // $user['password'] = $new_password . $secret_salt;
        
        $this->loginLogs($update->id);
        return $this->respondWithToken($jwt_token, object_array($user, true), '密码修改成功！');
    }

    /**
     * 微信小程序登录
     * update => 主动更新用户信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginWeChat(Request $request)
    {

        $fields = [
            'encryptedData' => 'required|string',
            'code' => 'filled|string',
            'from' => 'required|numeric',
            'iv' => 'required|string',
            'tags' => 'filled|string',
            'appid' => 'required|string',
            'update' => 'filled|numeric',
            'openId' => 'filled|string',
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }
        $wechat =  new WeChatCommon;
        $param = [
            'tags' => $tags ?? '',
            'encryptedData' => $encryptedData,
            'iv' => $iv,
            'appid' => $appid,
            'from' => $from
        ];
        if (!empty($code)) {
            $param['code'] = $code;
        }

        if (!empty($update) && $update === 1 && !empty($openId)) {
            $param['update'] = $update;
            $param['openId'] = $openId;
        }
              
        $wechatLogin = $wechat->encryptedData($param);
 
        if (is_object($wechatLogin)) {
            return $wechatLogin;
        }
        // 更新用户信息(主动)
        if (!empty($update) && $update === 1) {
            return response()->json(export_data(100200, null, '用户微信信息更新成功！'));
        }
 
        $openId = $wechatLogin['openId'] ?? '';

        // 用户是否绑定微信(openid)，绑定可以一键登录
		if ($uid = DB::table('wechat')->whereRaw("openId LIKE '%" . $openId . "%'")->value('uid')) {

			$auth = new Auth;
			return $auth->loginAKeyWeChat(['uid' => $uid, 'from' => $from, 'appid' => $appid]);
        }

        return response()->json(export_data(100200, $wechatLogin, '微信登陆成功！'));
        // if (empty($user['id']) || !$jwt_token = JWTAuth::attempt($input)) {
        //     return response()->json(export_data(100400, null, '用户名或者密码不正确！'));
        // }

        // if ($userWechat = DB::table('wechat')->where('uid', '=', $user->id)->first()) {
        //     if (!empty($request->openId) && !empty($userWechat->openId)) {
        //         $userOpenId = unserialize($userWechat->openId);
        //         if (!empty($userOpenId[$request->appId]) && $userOpenId[$request->appId] !== $request->openId) {
        //             return response()->json(export_data(100400, null, '当前账户已绑定其它微信！'));
        //         }
        //     }
        // }


    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = auth('api')->user();
        // $wechat = DB::table('wechat')->where('uid', '=', $user['id'])->first();
        // if ($request->type) {
        //     $this->loginLogs($user->id, $request->type);
        // }
        $info = $this->info(['appid' => $request->appid ?? '']);
        return response()->json(export_data(100200, array_merge($info, ['expires_in' => auth('api')->factory()->getTTL()])
        , '用户信息查询成功！'));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function info($request = [])
    {
        $user = auth('api')->user();

        if (! empty($request)) {
            extract($request);
        }
        $uid = $id ?? $user->id;

        $cardVip = new CardVip;

        $user = [
            'vip' => $cardVip->vip($uid),
            'uid' =>  $uid,
            'name' => $user->name,
            'username' => $user->username,
            'group_id' => $user->group_id,
            'phone' => $user->phone
        ];
        if ($wechat = DB::table('wechat')->where('uid', '=',  $uid)->first()) {
            
            $openId = '';
            if (! empty($appid) && ! empty($wechat->openId)) { 
                $openId = deep_in_array($appid, json_decode($wechat->openId, true), true)['val'] ?? '';
            }
            
            $user = array_merge($user, [
                'nickName' => $wechat->nickName ?? '',
                'gender' => $wechat->gender ?? '',
                'sex' => GENDER[$wechat->gender ?? 0],
                'avatarUrl' => $wechat->avatarUrl ?? 0,
                'unionId' => $wechat->unionId ?? '',
                'openId' => $openId,
                'weuped' => $wechat->updated_time > $wechat->created_time ? 1 : 0,
                'updated_time' => $wechat->updated_time
            ]);
        }
        
        return $user;
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions()
    {
        $user = auth('api')->user();

        $info = $this->info();
        $admin = new Admin;
        if ($role = $admin->check()) {
            $info['role'] = $role;
        }
        
        return response()->json(export_data(100200, array_merge($info, ['expires_in' => auth('api')->factory()->getTTL()])
        , '用户信息查询成功！'));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(export_data(100200, [] , '用户退出登录成功！'));
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $user = auth('api')->user();

        if ($user->status == PreUsersModel::STATUS_OFF) {
            return response()->json(['message' => '帐号已禁用'], 500);
        }
        return $this->respondWithToken(auth('api')->refresh(), $user);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user, $message = '')
    {
        $user_info = array_merge([
                    'token' => $token,
                    'type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL()
                ], $user);
        return response()->json(export_data(100200, $user_info, $message ?: '用户登录成功！'));
    }

    /**
     * 用的登录日志
     *
     * @param  Array
     *
     * @return Boole
     */
    protected function loginLogs($uid, $type = 1)
    {
        // INET_NTOA
        return DB::table('login')->insertGetId([
            'uid' => $uid,
            'ip' => DB::raw('INET_ATON("'. request()->ip() .'")'),
            'created_time' => SYSTEM_TIME,
            'type' => $type
        ]);
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
