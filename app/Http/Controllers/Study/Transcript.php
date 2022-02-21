<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-29 15:06:49
 * @LastEditTime: 2021-05-10 19:30:24
 * @LastEditors: Please set LastEditors
 * @Description: 考试成绩
 * @FilePath: .\app\Http\Controllers\Study\Transcript.php
 */

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Category;

class Transcript extends Controller
{
    //
    private $table = 'study_transcript';
    private $paginator;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->paginator = object_array(PAGINATOR);
        // $this->limit = 100;
    }

    // 考题成绩查询
    public function check(Request $request)
    {

        $fields = [
            'id' => 'filled|numeric',
            'page' => 'filled|numeric',
            'limit' => 'filled|numeric',
            'uid' => 'filled|numeric'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $uid = $uid ?? auth('api')->user()->id;
         // 单条数据查询
         if (! empty($id)) {
            $result = DB::table($this->table)->whereRaw("id = $id AND uid = $uid")->first();
            $result->type_name = $this->category($result->note);
            return response()->json(export_data(100200, $result,  '查询成功'));
        }
        $orderByRaw = 'updated_time, created_time DESC';

        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 当前用户查询
        if (! empty($uid)) {
            $whereRaw .= isJointString($whereRaw) . " uid = $uid";
        }

        if (! empty($sort)) {
            $orderByRaw = $sort . ',updated_time, created_time DESC';
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = DB::table($this->table)->whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'performance' => []
                ]
            ], '查询成功'));
        }

        $pages = $limit ? ceil($total / $limit) : 1;
        $offset = ($page - 1) * $limit;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        // 用 orderByRaw 写原生的 sql
        $data = $limit === 0 ? 
                DB::table($this->table)->whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                DB::table($this->table)->whereRaw($whereRaw)
                                       ->orderByRaw($orderByRaw)
                                       ->offset($offset)
                                       ->limit($limit)
                                       ->get();
        $list = [];
        foreach ($data as $v)
        {
            if (!empty($v->type)) {
                $v->type_name = $this->category($v->type);
            }
            $list[] = $v;
        }
        return response()->json(export_data(100200, [
            'list' => [
                'total' => $total,
                'page' => $page,
                'offset' => $offset,
                'pages' => $pages,
                'limit' => $limit,
                'performance' => $list
            ]
        ], '查询成功'));
    }

    // 交卷
    public function examinationPaper(Request $request)
    {
        $fields = [
            'subjects' => 'required|string',
            'score' => 'required|int',
            'right' => 'required|int',
            'wrong' => 'required|int',
            'type' => 'required|string',
            'use_time' => 'required|string',
            'honor' => 'filled|string',
            'note' => 'filled|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $uid = auth('api')->user()->id;
        if ($paperId = DB::table($this->table)
                            ->insertGetId([
                                'uid' => $uid,
                                'honor' => $honor,
                                'type' => $type,
                                'subjects' => $subjects,
                                'right' => $right,
                                'wrong' => $wrong,
                                'score' => $score,
                                'use_time' => $use_time,
                                'note' => $note ?? '',
                                'updated_time' => SYSTEM_TIME,
                                'created_time' => SYSTEM_TIME
                            ])) {
            return response()->json(export_data(100200, null, '成绩单生成成功！'));
        }
        return response()->json(export_data(100400, null, '成绩单生成失败！'));
    }

    protected function category($note = 'none') 
    {
        $name = '';
        $category = new Category;
        $list = $category->list(1);
        foreach ($list as $v)
        {
            if (strstr($v['note'], $note) !== false) {
                $name = $v['name'];
                break;
            }
        }
        return $name;
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
