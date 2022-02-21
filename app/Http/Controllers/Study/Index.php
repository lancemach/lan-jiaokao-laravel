<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-09 16:33:42
 * @LastEditTime: 2021-06-23 10:06:17
 * @LastEditors: Please set LastEditors
 * @Description: 考试时间为45分钟，答题过程中错12分(6道题)即终止本场考试
 * @FilePath: .\app\Http\Controllers\Study\Index.php
 */

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Category;
use App\Http\Controllers\Study\Collect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\CardVip;

use Illuminate\Support\Facades\DB;

use App\Models\Study;


class Index extends Controller
{
    private $paginator;
    private $limit;
    // private $seconds = 60 * 1000;
    private $expires_in = 45 * 60 * 1000;
    private $pass_borderline = 90;
    private $full_marks = 100;
    private $full_marks4 = 50;
    private $question_value4 = 2;
    private $question_value = 1;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
        $this->limit = 100;
    }
    //类型页面查询
    public function checkCategoryPage()
    {
        $category = new Category;
        $list = $category->list(1);
        return response()->json(export_data(100200, [
            'category' => $list,
            'dict' => [
                'sex' => GENDER
            ]
        ], '查询成功'));
    }

    public function checkStatistical($type = '', $subjects = 0)
    {
        $Study = new Study;
        $data = $Study::whereRaw("type LIKE \"%" . $type . "%\" AND subjects = " . $subjects)
                    ->count();
        return $data;
    }

    // 查询考题
    public function check(Request $request)
	{
        $fields = [
            'subjects' => [
                'filled',
                'numeric',
                Rule::in([1, 4]),
            ],
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'random' => 'filled|numeric',
            'type' => 'filled|string',
            'rule' => 'filled|string',
            'chapter' => 'filled|string',
            'sort' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $Study = new Study;
        $cardVip = new CardVip;

        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = $Study::whereRaw('id = ' . $id)->first();
            $result->vip = $cardVip->vip();
            return response()->json(export_data(100200, $result,  $Study->modules[1] . '查询成功'));
        }
        $orderByRaw = 'updated_time, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ',updated_time, created_time DESC';
        }
        
        // 是否随机取数
        $random = (int)$random ?? 0;
        $whereRaw = "life = " . (!empty($life) ? $life : 1) . ' AND vip =' . (! empty($rule) && $rule === 'vip' ? 1 : 0);

        // 关键词查询
        if (! empty($kw)) {
            $whereRaw .= $this->isWhereRaw($whereRaw) . "question like \"%$kw%\"";
        }

        // 驾照类型
        if (! empty($type)) {
            $whereRaw .= $this->isWhereRaw($whereRaw) . "type LIKE \"%$type%\"";
        }

        // 章节类型
        if (! empty($chapter)) {
            $whereRaw .= $this->isWhereRaw($whereRaw) . ($chapter === '_' ? "chapter = ''" : "chapter LIKE \"%$chapter%\"");
        }

        // 驾照科目
        if (! empty($subjects)) {
            $whereRaw .= $this->isWhereRaw($whereRaw) . "subjects = '" . $subjects . "'";
            if ($subjects == 4 && $random == 1) {
                $limit = 50;
            }
        }

        // 其它规则（错题/收藏）
        if (! empty($rule) && ! empty($subjects) && in_array($rule, QUESTION_RULE) && !empty(QUESTION_RULE[$rule])) {
            $uid = auth('api')->user()->id;
            $collect = new Collect;
            $collectIds = $collect->collectIds([
                'subjects' => $subjects,
                'type' => $type,
                'tid' => QUESTION_RULE[$rule],
                'uid' => $uid
            ]);

            $whereRaw .= $this->isWhereRaw($whereRaw) . ($rule === 'undone' ? "id NOT IN ($collectIds)" : "id IN ($collectIds)");
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $Study::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'study' => []
                ]
            ],  $Study->modules[1] . '查询成功'));
        }

        if ($random == 1) {
            $total = $limit;
        }

        $pages = $limit ? ceil($total / $limit) : 1;
        $offset = ($page - 1) * $limit;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }
        // 当前查询条件所有id
        $folder_id = [];
        // 用 orderByRaw 写原生的 sql
        if ($limit === 0) {
            $data = $Study::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get();
        } else {
            if ($random === 1) {
                if (!empty($subjects) && $subjects == 4) {
                    $data1 = $Study::whereRaw($whereRaw . " AND option1 = 'A、正确'")->orderByRaw('RAND()')->limit(20)->get();
                    $data2 = $Study::whereRaw($whereRaw . " AND length(answer) = 1 AND option1 <> 'A、正确'")->orderByRaw('RAND()')->limit(20)->get();
                    $data3 = $Study::whereRaw($whereRaw . " AND length(answer) > 1 AND option1 <> 'A、正确'")->orderByRaw('RAND()')->limit(10)->get();
                    $data = array_merge(object_array($data1), object_array($data2), object_array($data3));
                } else {
                    $data = $Study::whereRaw($whereRaw)->orderByRaw('RAND()')->limit($limit)->get();
                }

            } else { //  . " AND length(answer) > 1 AND option1 <> 'A、正确'"
                if (! empty($rule) && isset(QUESTION_RULE[$rule]) && QUESTION_RULE[$rule] == 0) {
                    $data = $Study::whereRaw($whereRaw)
                                ->orderByRaw('RAND()')
                                ->offset($offset)
                                ->limit($limit)
                                ->get();
                } else {
                    $folder_id = $Study::whereRaw($whereRaw)
                                ->orderByRaw($orderByRaw)
                                ->pluck('id');
                    if (isset($id) && $id == -1) {
                        $id = $folder_id[0];
                    }
                    
                    if (! empty($id)) {
                        $whereRaw .= $this->isWhereRaw($whereRaw) . "id = $id";
                        $data = $Study::whereRaw($whereRaw)
                                ->orderByRaw($orderByRaw)
                                ->limit($limit)
                                ->get();
                    } else {
                        $data = $Study::whereRaw($whereRaw)
                                ->orderByRaw($orderByRaw)
                                ->offset($offset)
                                ->limit($limit)
                                ->get();
                    }
                             
                }
                
            }
        }
        
        // if (strstr($type, ',')) {
        //     $type = str_replace(',', '/', $type);
        // }
        $name = DB::table('category')->where('note', 'LIKE', "%$type%")->value('name');
        $uid = auth('api')->user()->id;
        $collect = DB::table('study_collect')->whereRaw((!empty($subjects) ? "subjects = $subjects AND " : '') . "uid = $uid AND type = '" . $type . "'")->first();
        $favorite = !empty($collect->favorite) ? json_decode('[' . $collect->favorite . ']', true) : [];
        $wrong = !empty($collect->wrong) ? json_decode('[' . $collect->wrong . ']', true) : [];
        $answered = !empty($collect->answered) ? json_decode('[' . $collect->answered . ']', true) : [];
  
        if (!empty($collect) && !empty($data)) {
            $list = [];
            foreach ($data as $v)
            {
                $v['favorite'] = in_array($v['id'], $favorite);
                $v['isWrong'] = in_array($v['id'], $wrong);
                $v['isAnswered'] = in_array($v['id'], $answered);
                $list[] = $v;
            }
            // $data = $list;
        } else {
            $list = $data;
        }

        $folder_list = [];
        foreach ($folder_id as $v)
        {
            $folder_list[] = [
                'id' => $v,
                'wrong' => (in_array($v, $wrong) && empty($rule) || !empty($rule) && ! in_array($rule, array_keys(QUESTION_RULE))) ? 1 : 0,
                'answered' => (in_array($v, $answered) && empty($rule) || !empty($rule) && ! in_array($rule, array_keys(QUESTION_RULE))) ? 1 : 0
            ];
        }
        
        return response()->json(export_data(100200, [
            'timer_in' => $this->expires_in,
            'expires_in' => $this->expires_in,
            'vip' => $cardVip->vip(),
            'exam'=> [
                'title' => '科目' . (!empty($subjects) ? strNatInt($subjects) : ''),
                'name' => '科' . (!empty($subjects) ? strNatInt($subjects) : ''),
                'type_name' => $name,
                'type' => $type,
                'pass_borderline' => 90,
                'question_value' => !empty($subjects) && $subjects == 4 ? $this->question_value4 : $this->question_value,
                'full_marks' => !empty($subjects) && $subjects == 4 ? $this->full_marks4 : $this->full_marks
            ],
            'list' => [
                    'folder_list' => $folder_list,
                    'total' => $total,
                    'page' => $page,
                    'pages' => $pages,
                    'limit' => $limit,
                    'study' => $list
                ]
        ],  $Study->modules[1] . '查询成功'));

    }

    private function isWhereRaw($str = '')
    {
        return $str ? " AND " : ' ';
    }

    // 查询考题
    public function checkStudyList(Request $request)
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
