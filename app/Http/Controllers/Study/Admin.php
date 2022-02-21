<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-13 15:49:19
 * @LastEditTime: 2021-06-03 11:03:05
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Study\Admin.php
 */

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use App\Http\Controllers\BasicInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\Study;
use App\Http\Controllers\Upload;

class Admin extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
    }
    //
    public function updateQuestionBank()
    {
        $bi = new BasicInterface;
        $questions = $bi->getStudyQuestionsData();
        if (! is_array($questions)) {
            return response()->json(export_data(100400, null,  $questions));
        }

        $study = new Study;
        $insertOrUpdate = $study->insertOrUpdate($questions);
        return $insertOrUpdate;
    }

    // 查询考题
    public function get(Request $request)
	{
        $fields = [
            'subjects' => [
                'filled',
                'numeric',
                Rule::in([0, 1, 4]),
            ],
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'type' => 'filled|string',
            'rule' => 'filled|string',
            'vip'   => 'filled|numeric',
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
        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = $Study::whereRaw('id = ' . $id)->first();
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($result->pic) && ! preg_match($pattern, $result->pic)) {
                $result->pic = LOCAL_STORE . $result->pic;
            }
            return response()->json(export_data(100200, $result,  $Study->modules[1] . '查询成功'));
        }
        $orderByRaw = 'updated_time DESC, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ', updated_time DESC, created_time DESC';
        }
        
        // 是否随机取数
        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 关键词查询
        if (! empty($kw)) {
            $whereRaw .= isJointString($whereRaw) . " question like \"%$kw%\"";
        }

        // vip查询
        if (! empty($vip) && $vip == 1) {
            $whereRaw .= isJointString($whereRaw) . " vip = '" . $vip . "'";
        }

        // 驾照类型
        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type LIKE \"%$type%\"";
        }

        // 章节类型
        if (! empty($chapter)) {
            $whereRaw .= isJointString($whereRaw) . ($chapter === '_' ? " chapter = ''" : " chapter LIKE \"%$chapter%\"");
        }

        // 驾照科目
        if (! empty($subjects)) {
            $whereRaw .= isJointString($whereRaw) . " subjects = '" . $subjects . "'";
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

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                $Study::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $Study::whereRaw($whereRaw)
                                  ->orderByRaw($orderByRaw)
                                  ->offset($offset)
                                  ->limit($limit)
                                  ->get();

        return response()->json(export_data(100200, [
            'list' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => $pages,
                    'limit' => $limit,
                    'study' => $data
                ]
        ],  $Study->modules[1] . '查询成功'));

    }

    
     /**
     * 软删除数据
     */
    public function delete(Request $request)
    {
        $fields = [
            'id'   => 'required|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        // $ids = explode(',', $id);

        $Study = new Study;
        if ($del = $Study::whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $Study->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $Study->modules[1] . '数据删除失败'));
    }

    /**
     * 激活(恢复)数据
     */
    public function activate(Request $request)
    {
        $fields = [
            'id'   => 'required|numeric'
        ];
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $Study = new Study;
        if ($del = $Study::whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $Study->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $Study->modules[1] . '数据启用失败'));
    }

    /**
     * 更新数据
     */
    public function update(Request $request)
    {

        $fields = [
            'id' => 'required|numeric',
            'question' => 'filled|string',
            'option1' => 'filled|string',
            'option2' => 'filled|string',
            'option3' => 'filled|string',
            'option4' => 'filled|string',
            'answer' => 'filled|string',
            'skills' => 'filled|string',
            'technique' => 'nullable|string',
            'pic' => 'filled|string',
            'subjects' => [
                'filled',
                'numeric',
                Rule::in([1, 4]),
            ],
            'vip' => 'filled|numeric',
            'type' => 'filled|string',
            'chapter' => 'filled|string',
            'note' => 'nullable|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        if (!empty($option1)) {
            $validator['option1'] = substr($option1, 0, 2) === 'A、' ? $option1 : 'A、' . $option1;
        }
        if (!empty($option1)) {
            $validator['option2'] = substr($option1, 0, 2) === 'B、' ? $option1 : 'B、' . $option1;
        }
        if (!empty($option1)) {
            $validator['option3'] = substr($option1, 0, 2) === 'C、' ? $option1 : 'C、' . $option1;
        }
        if (!empty($option1)) {
            $validator['option4'] = substr($option1, 0, 2) === 'D、' ? $option1 : 'D、' . $option1;
        }

        $filled = $validator;
        unset($filled['id']);

        $Study = new Study;

        if (! $results = Study::where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $Study->modules[1] . '当前更新数据为空！'));
        }

        $whereRaw = "";

        if (! empty($question)) {
            $whereRaw .= isJointString($whereRaw) . " question = '" . $question . "'";
        }

        if (! empty($filled['option1'])) {
            $whereRaw .= isJointString($whereRaw) . " option1 = '" . $filled['option1'] . "'";
        }

        if (! empty($validator['option2'])) {
            $whereRaw .= isJointString($whereRaw) . " option2 = '" . $validator['option2'] . "'";
        }

        if (! empty($validator['option3'])) {
            $whereRaw .= isJointString($whereRaw) . " option3 = '" . $validator['option3'] . "'";
        }

        if (! empty($validator['option4'])) {
            $whereRaw .= isJointString($whereRaw) . " option4 = '" . $validator['option4'] . "'";
        }

        if (! empty($answer)) {
            $whereRaw .= isJointString($whereRaw) . " answer = '" . $answer . "'";
        }

        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type = '" . $type . "'";
        }

        if (! empty($subjects)) {
            $whereRaw .= isJointString($whereRaw) . " subjects = '" . $subjects . "'";
        }

        if (!empty($whereRaw) && $Study::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加考题！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $Study->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = Study::where('id', $id)->update($filled)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            return response()->json(export_data(100200, null,  $Study->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $Study->modules[1] . '数据更新失败'));
    }

    // 添加考题
    public function create(Request $request)
	{
        $fields = [
            'question' => 'required|string',
            'option1' => 'required|string',
            'option2' => 'required|string',
            'option3' => 'filled|string',
            'option4' => 'filled|string',
            'answer' => 'required|string',
            'skills' => 'required|string',
            'technique' => 'filled|string',
            'pic' => 'filled|string',
            'subjects' => [
                'filled',
                'numeric',
                Rule::in([1, 4]),
            ],
            'vip' => 'required|numeric',
            'type' => 'required|string',
            'chapter' => 'required|string',
            'note' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $option1 = substr($option1, 0, 2) === 'A、' ? $option1 : 'A、' . $option1;
        $option2 = substr($option2, 0, 2) === 'B、' ? $option2 : 'B、' . $option2;
        $option3 = !empty($option3) ? (substr($option3, 0, 2) === 'C、' ? $option3 : 'C、' . $option3) : '';
        $option4 = !empty($option4) ? (substr($option4, 0, 2) === 'D、' ? $option4 : 'D、' . $option4) : '';
        
        $data = [
            'question' => $question,
            'option1' => $option1,
            'option2' => $option2,
            'option3' => $option3,
            'option4' => $option4,
            'answer' => $answer,
            'skills' => $skills,
            'technique' => $technique ?? '',
            'pic' => $pic ?? null,
            'subjects' => $subjects,
            'vip' => $vip ?? 0,
            'type' => $type,
            'chapter' => $chapter,
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        $study = new Study;
        $whereRaw = "question = '" . $question . "' AND option1 = '" . $option1 . "' AND option2 = '"
                     . $option2 . "' AND option3 = '" . $option3 . "' AND option4 = '" . $option4 . "' AND answer = '" . $answer
                     . "' AND type = '" . $type . "' AND subjects = $subjects";
        if ($hasData = $study::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加考题！'));
        }
        if ($id = $study->insertGetId($data)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            
            return response()->json(export_data(100200, null, '考题添加成功！'));
         }
         return response()->json(export_data(100400, null, '考题添加失败！'));

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
