<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-02-26 11:30:24
 * @LastEditTime: 2021-04-12 20:59:28
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Middleware\AuthInterface.php
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class AuthInterface
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // return response()->json(export_data(100200, null, '未查询到登录用户信息！'));
        if (! $this->Whitelist($request)) {
            return response()->json(export_data(100402, ['tips' => '可尝试将以下域名(' . $this->host($request) . ')添加到白名单中'], '当前客户端请求不在白名单中！'));
        }
        return $next($request);
    }

    protected function host($request)
    {
        return parse_url($request->server('HTTP_REFERER'), PHP_URL_HOST) ?: $request->server('HTTP_HOST');
    }

    protected function Whitelist($request)
    {

        if (! $whiteList = Redis::lrange('configs.whiteList', 0 , -1) ?: []) {
            if ($config_whiteList = DB::table('configs')
                                ->select('param_key')
                                ->where([['tags', 'whiteList'], ['param_val', 'domain_name']])
                                ->first()
                                ->param_key) {
                $whiteList = explode(",", $config_whiteList);
                Redis::rpush('configs.whiteList', $whiteList);
            }
        }
        return in_array(str_replace("www.", "", $this->host($request)), $whiteList);
    }
}
