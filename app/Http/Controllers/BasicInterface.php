<?php
/*
 * @Author: your name
 * @Date: 2021-04-13 10:06:00
 * @LastEditTime: 2021-06-05 12:33:45
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \lma_clbd\app\Http\Controllers\BasicInterface.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BasicInterface extends Controller
{
    private $jisuapi_error = [
        'Throttled by USER Flow Control' => '因用户流控被限制',
        'Api Prov' => '因用户流控被限制',
        'Throttled by API Flow Control' => '因 API 流控被限制',
        'Throttled by DOMAIN Flow Control' => '因二级域名流控被限制，或因分组流控被限制',
        'Quota Exhausted' => '调用次数已用完	购买的次数已用完。',
        'Quota Expired' => '购买次数已过期	购买的次数已经过期。',
        'User Arrears' => '用户已欠费	请尽快充值续费。',
        'Specified access key is not found' => 'AccessKeyId 找不到	请检查调用时是否传入正确的 AccessKeyId',
        'A400AC' => '未找到AppCode'
    ];
    /**
     *    pagenum	STRING	必选	当前页数
     *    pagesize	STRING	必选	每页数量 默认1
     *    sort	STRING	必选	排序方式 正常排序normal 随机排序rand 默认normal
     *    subject	STRING	必选	科目类别 1为科目一 4为科目四 默认1
     *    type	STRING	必选	题目类型 分为A1,A3,B1、A2,B2、C1,C2,C3、D,E,F 默认C1
    */
    public function getStudyQuestionsData ($type = 'C1', $subject = 1, $param = ['party' => 'sujk', 'pagenum' => 1, 'pagesize' => 100]) 
    {
        extract($param);
        if (! $config = $this->getAccessData('question_' . $party, 'questionBank', $party, ['KeyId' => 'appcode', 'KeySecret' => 'secret'])) {
            return response()->json(export_data(100400, null, '当前 appid 配置不存在或者为空'));
        }
        extract($config);

        if (empty($appcode)) {
            return response()->json(export_data(100400, null, '当前 code 配置不存在或者为空'));
        }

        function resQuestions($appcode, $type, $subject, $pagenum, $pagesize, $jisuapi_error)
        {
            $response = Http::withHeaders([
                            'Authorization' => "APPCODE " . $appcode
                        ])
                        ->get("https://jisujiakao.market.alicloudapi.com/driverexam/query",
                            [
                                'pagenum' => $pagenum,
                                'pagesize' => $pagesize,
                                'sort' => 'normal',
                                'subject' => $subject,
                                'type' => $type
                            ]
                        );
            $content = $response->json();
            if (! $response->successful()) {
                $httpcode = $response->handlerStats()['http_code'];
                $errorMessage = $jisuapi_error[$response->headers()['X-Ca-Error-Message'][0]] ?? '接口数据异常';
                return $errorMessage;
            }
            if ($content['msg'] !== 'ok' || $content['status'] !== 0) {
                return $content['msg'];
            }

            extract($content['result']);
            $questions = [];
            foreach ($list as $v)
            {
                $v['subjects'] = $subject ?? 0;
                $v['skills'] = $v['explain'];
                unset($v['explain']);
                $answer = $v['answer'];
                if ((empty($v['option1']) && empty($v['option2']) && empty($v['option3']) && empty($v['option4'])) || in_array($answer, array_keys(QUESTION_BOOLE))) {
                    $v['option1'] = 'A、正确';
                    $v['option2'] = 'B、错误';
                    $v['answer'] = !empty(QUESTION_BOOLE[$answer]) ? QUESTION_BOOLE[$answer] : $answer;
                }

                $v['created_time'] = SYSTEM_TIME;
                $v['updated_time'] = SYSTEM_TIME;
                $questions[] = $v;
            }
    
            return [
                'total' => $total,
                'questions' => $questions
            ];
        }
                   
        $data = resQuestions($appcode, $type, $subject, $pagenum, $pagesize, $this->jisuapi_error);
        
        if (! is_array($data)) {
            return $data;
        }
 
        extract($data);
        if (empty($total) || count($questions) < 1) {
            return $data;
        }
        $list = $questions;
        $pages = ceil($total / $pagesize);
        if ($pages > $pagenum) {
            for ($i = $pagenum; $i < $pages + 1; $i++) {
                usleep(89);
                $moreQuestions = resQuestions($appcode, $type, $subject, $i, $pagesize, $this->jisuapi_error);
                if (empty($moreQuestions['total']) || count($moreQuestions['questions']) < 1) {
                    break;
                }
                $list = array_merge($list, $moreQuestions['questions']);
            }
        }
        return $list;
        
    }

    // 读取系统配置(密钥)配置
    public function getAccessData ($name = '', $tags, $appid, $fields = DEFAULT_SECRET_KEY) 
    {

        if (! $accessData = Redis::get($name)) {
            
            $config = DB::table('configs')->where([['tags', $tags], ['param_id', $appid]])->first();

            if (empty($config->param_val) || empty($config->param_key)) {
                return false;
            }
            $access = [
                $fields['KeyId'] => $config->param_val,
                $fields['KeySecret'] => $config->param_key
            ];
            Redis::set($name, serialize($access));
            return $access;
        }
        return unserialize($accessData);
    }

    // 设置系统配置(密钥)配置
    public function setAccessData ($name = '', $access)
    {
        if (! Redis::set($name, serialize($access))) {
            return $false;
        }
        return true;
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
