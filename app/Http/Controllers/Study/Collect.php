<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-29 15:05:22
 * @LastEditTime: 2021-05-10 20:10:53
 * @LastEditors: Please set LastEditors
 * @Description: 考题收藏
 * @FilePath: .\app\Http\Controllers\Study\Collect.php
 */

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

class Collect extends Controller
{
    private $table = 'study_collect';
    // wrong => 答错收藏(自动)，favorite => 收藏(手动)， answered => 已答过收藏(自动)
    private $fields = ['', 'wrong', 'favorite', 'answered'];

    // 考题收藏(取消收藏)
    // 目前只要答错自动收藏，默认无法取消已答过的错题
    public function examinationQuestionsCollect(Request $request)
    {
        $fields = [
            'subjects' => [
                'required',
                'numeric',
                Rule::in([1, 4]),
            ],
            'tid' => [
                'required',
                'numeric',
                Rule::in([1, 2, 3]),
            ],
            'id' => 'required|numeric',
            'type' => 'required|string'
        ];
        $uid = auth('api')->user()->id;
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }
        $fields = $this->fields[$tid];

        $whereRaw = "subjects = $subjects AND uid = $uid AND type = '" . $type . "'";
        // AND FIND_IN_SET($id, $fields)
        if ($collect = DB::table($this->table)->whereRaw($whereRaw)->first()) {

            $collect_fields = $collect->$fields;
            $collect_array = explode(',', $collect_fields);
            
            if (in_array($id, $collect_array)) {
                if ($tid == 1) {
                    return response()->json(export_data(100200, null, '错题收藏不允许取消！'));
                }
                if ($collect_fields == $id && COUNT($collect_array) === 1) {
                    if (DB::table($this->table)->whereRaw($whereRaw)->update([$fields => ''])) {
                        return response()->json(export_data(100200, null, '取消收藏成功！'));
                    }
                }
                
                $collect_array = array_diff($collect_array, [$id]);
                $favorite_str = implode(',', $collect_array);
                if (DB::table($this->table)->whereRaw($whereRaw)->update([$fields => $favorite_str])) {
                    return response()->json(export_data(100200, null, '取消收藏成功！'));
                } 
            }

            if (!empty($collect_fields) && COUNT($collect_array) >= 1 && DB::table($this->table)->whereRaw($whereRaw)->update([$fields => $collect_fields . ',' . $id])) {
                return response()->json(export_data(100200, null, '收藏成功！'));
            }
            if (DB::table($this->table)->whereRaw($whereRaw)->update([$fields => $id])) {
                return response()->json(export_data(100200, null, '收藏成功！'));
            }

            return response()->json(export_data(100400, null, '收藏失败！'));
        }

        if ($collectId = DB::table($this->table)
                            ->insertGetId([
                                'uid' => $uid,
                                'type' => $type,
                                'subjects' => $subjects,
                                $fields => $id,
                                'updated_time' => SYSTEM_TIME,
                                'created_time' => SYSTEM_TIME
                            ])) {
            return response()->json(export_data(100200, null, '收藏成功！'));
        }
        return response()->json(export_data(100400, null, '收藏失败！'));
        
    }

    /**
     * 获取收藏id （Array）
     */
    public function collectIds($params = [])
    {
        extract($params);
        if ($collect = DB::table($this->table)->whereRaw("subjects = $subjects AND uid = $uid AND type = '" . $type . "'")->first()) 
        {
            $fields = $this->fields[$tid];
            if ($favorite = $collect->$fields) {
                return $favorite;
            }
        }
        return 0;
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
