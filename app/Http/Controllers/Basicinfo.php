<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-07 15:30:27
 * @LastEditTime: 2021-06-23 13:44:05
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Basicinfo.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Settings;
use App\Http\Controllers\Category;
use App\Http\Controllers\Study\Index as Study;
use App\Http\Controllers\Study\Drives;
use App\Http\Controllers\CardVip;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

use App\Models\Study as StudyModel;
use App\Models\Ad;

class Basicinfo extends Controller
{
    //站点信息
    static function siteInfo () 
    {
        return [
            'name' => '车轮宝典',
            'logo' => 'https://wx.qlogo.cn/mmhead/Q3auHgzwzM7Pbia89eIR85sRrRU484Rvk58X85q8qnLI5VPqL3ciabjw/0',
            'logo_width' => '48rpx',
            'logo_height' => '48rpx',
            'logo_radius' => '50%'
        ];
    }

    //站点信息
    static function siteTabbar () 
    {
        $settings = new Settings;
        return $settings->AppletTabbar() ?? [];
    }

    //站点信息
    static function siteSettings () 
    {
        $settings = new Settings;
        return $settings->basic() ?? [];
    }

    // 页面基础信息
    public function pageInfo (Request $request) 
    {

        $data = [
            'site' => $this->siteInfo(),
            'tabbar' => $this->siteTabbar(),
            'settings' => $this->siteSettings()
        ];

        return response()->json(export_data(100200, $data, '查询成功'));
       
    }

    // 页面基础信息
    public function getAd (Request $request) 
    {

        $fields = [
            'parent_id' => 'required|numeric'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $ad = new Ad;

        $whereRaw = "life = 1";

        if (! empty($parent_id)) {
            $whereRaw .= isJointString($whereRaw) . " parent_id = '" . $parent_id . "'";
        }

        $data = DB::table($ad->table_son)->whereRaw($whereRaw)
                                  ->orderByRaw('updated_time DESC, created_time DESC')
                                  ->get();
        $list = [];
        foreach(object_array($data, true) as $v)
        {
            if (! empty($v['path'])) {
                $v['path'] = isLocalPath($v['path']);
            }

            $list[] = $v;
        }

        return response()->json(export_data(100200, ['slider' => $data], '查询成功'));
       
    }


    public function dataAnalysis (Request $request)
    {
        $fields = [
            'subjects' => 'required|numeric',
            'cid' => 'required|numeric'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $uid = auth('api')->user()->id ?? 0;

        $category = new Category;

        $list = $category->list(1);
        $study_cate = $data = [];
        
        foreach ($list as $v)
        {
            $type_str = explode('/', $v['note'])[0];
            if ($v['id'] == $cid) {
                $study_cate = [
                    'id' => $v['id'],
                    'type' => $type_str,
                    'name' => $v['name'],
                    'name' => $v['name'],
                    'note' => $v['note']
                ];
                break;
            }
        }
        $answered = $wrong = 0;
        if (!empty($uid) && !empty($subjects) && !empty($type_str)) {
            $collect = DB::table('study_collect')->select('answered', 'wrong')->whereRaw("subjects = $subjects AND uid = $uid AND type = '" . $type_str . "'")->first();
            $answered = !empty($collect->answered) ? COUNT(json_decode('[' . $collect->answered . ']', true)) : 0;
            $wrong = !empty($collect->wrong) ? COUNT(json_decode('[' . $collect->wrong . ']', true)) : 0;
        }

        $transcript = DB::table('study_transcript')->selectRaw('COUNT(id) as sum , round(AVG(score), 2) as score')->whereRaw("subjects Like '%" . strNatInt($subjects) . "%' AND uid = $uid AND type = '" . $type_str . "'")->first();
        $list = DB::table('study_transcript')->whereRaw("subjects Like '%" . strNatInt($subjects) . "%' AND uid = $uid AND type = '" . $type_str . "'")->limit(5)->orderByRaw('created_time DESC')->get();
        $data['time'] = $transcript->sum ?? 0;
        $data['score'] = $transcript->score ?? 0;
        $data['list'] = $list;
        $data['answered'] = $answered;
        $data['right'] = !empty($answered) ? round(($answered - $wrong) / $answered * 100, 2) : 0;
        $data['cate'] = $study_cate;

        return response()->json(export_data(100200, ['study' => $data], '查询成功'));
    }

    // 首页基础信息(客户端)
    public function pageIndex (Request $request) 
    {

        $fields = [
            'subjects' => 'required|numeric',
            'uid' => 'filled|numeric',
            'cid' => 'filled|numeric',
            'type' => 'required|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $category = new Category;
        $study = new Study;

        $studyData = $study->checkStatistical($type, $subjects);
        $list = $category->list(1);
        $study_cate = [];
        
        foreach ($list as $v)
        {
            $type_str = explode('/', $v['note'])[0];
            if ($v['id'] == $cid) {
                $study_cate = [
                    'id' => $v['id'],
                    'type' => $type_str,
                    'name' => $v['name'],
                    'note' => $v['note']
                ];
                break;
            }
        }
        $collect_answered = [];
        if (!empty($uid) && !empty($subjects) && !empty($type)) {
            $collect_answered = DB::table('study_collect')->whereRaw("subjects = $subjects AND uid = $uid AND type = '" . $type . "'")->value('answered');
            $collect_answered = json_decode('[' . $collect_answered . ']', true);
        }

        $data = [];

        if (!empty($subjects)) {
            if (in_array($subjects, [1, 4])) {
                $data = array_merge_recursive($data, $study_cate, ['question_total' => $studyData], ['collect_answered_sum' => COUNT($collect_answered)]);
            } else {
                $drives = new Drives;
                $request['limit'] = 4;
                $drivesData = object_array($drives->get($request), true)['original']['data']['list'] ?: [];
                $data = ['list' => $drivesData];
            }
        }

        $cardVip = new CardVip;

        return response()->json(export_data(100200, ['vip' => $cardVip->vip($uid), 'study' => $data], '查询成功'));
    }

    // 题库分类统计
    public function pageStudyCate (Request $request) 
    {
        
        $fields = [
            'subjects' => [
                'required',
                'numeric',
                Rule::in([1, 4]),
            ],
            'cate' => [
                'required',
                'numeric',
                Rule::in([1, 2]),
            ],
            'id' => 'filled|numeric',
            'type' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $orderByRaw = 'updated_time, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ',updated_time, created_time DESC';
        }

        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 驾照类型
        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type LIKE \"%$type%\"";
        }

        // 驾照科目
        if (! empty($subjects)) {
            $whereRaw .= isJointString($whereRaw) . " subjects = '" . $subjects . "'";
        }

        $study = new StudyModel;
        if (! $total = $study::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [],  '查询成功'));
        }

        if ($questions = object_array($study::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get(), true)) {
            $list = $chapter = [];
            foreach ($questions as $v)
            {
                $list[$v['chapter']]['num'][] = 1;
                $list[$v['chapter']][] = array_sum($v);
            }
            foreach ($list as $k => $v)
            {
                $chapter[] = ['title' => $k, 'sum' => array_sum($v['num'])];
            }
            return response()->json(export_data(100200, ['chapter' => $chapter], '查询成功'));
        }
        return response()->json(export_data(100200, [], '查询成功'));
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
