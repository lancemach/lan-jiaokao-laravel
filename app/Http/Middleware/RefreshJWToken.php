<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-01-30 10:01:14
 * @LastEditTime: 2021-05-13 12:32:24
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Middleware\RefreshJWToken.php
 */

namespace App\Http\Middleware;

use Closure;
use Auth;
use JWTAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class RefreshJWToken extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {

        $token_auth = 'Authorization';
        $token_key = config('app.token_akn') ?: '';
        if ($tokenString = $request->header($token_key) ?: '') {
            $request->headers->set('Authorization', 'Bearer ' . $tokenString);
            $request->headers->set($token_key, 'lancema');
        }

        // 检查当前 Token(值)
        if (! $hasToken = $this->auth->parser()->setRequest($request)->hasToken()) {
            return response()->json(export_data(100403, null, '用户当前未登录！'));
        }

        if ($hasToken && $request->getRequestUri() === config('app.user_login')) {
            return $next($request);
        }

        $auth = JWTAuth::parseToken();

        if (! $token = $auth->setRequest($request)->getToken()) {
            return response()->json(export_data(100403, null, '用户未登录！'));
        }

        $newToken = null;
        // $auth->parseToken()->check()  检查当前 Token(合法性)
        
        try {
            if (! $user = $auth->authenticate($token)) {
                 return response()->json(export_data(100403, null, '未查询到登录用户信息！'));
            }
            // $request->headers->set('Authorization', 'Bearer ' . $token);
        } catch (TokenExpiredException $e) {
        
            try {
                $newToken = JWTAuth::refresh($token);
                $request->headers->set('Authorization', 'Bearer ' . $newToken);
            } catch (JWTException $exception) {
                // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
                // return response()->json(export_data(100403, null, $exception->getMessage()));
                return response()->json(export_data(100403, null, '用户登录已过期！'));
            }
        } catch (JWTException $e) {
            return response()->json(export_data(100403, null, '用户未登录！'));
        }

        // if ($request->getRequestUri() === config('app.user_login')) {
        //     return response()->json(export_data(100200, auth('api')->user(), '用户登录成功！'));
        // }

        return $newToken ? $next($request)->header($token_key, $newToken) : $next($request);

    }

    // protected function setJWTHeader($token = null)
    // {
    //     return response()->json([export_data(100200, null, '用户(TOKEN)刷新成功！')], 200, [config('app.token_akn') => $token]);
    // }
}
