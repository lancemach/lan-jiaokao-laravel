<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-03 09:32:21
 * @LastEditTime: 2021-06-07 16:22:06
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Study\Drives.php
 */

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

use App\Models\Drives AS DrivesMode;
use App\Http\Controllers\Upload;

class Drives extends Controller
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

    // 查询信息(简单)
    public function checkByType($type = '')
	{
        $drives = new DrivesMode;
        $whereRaw = 'life = 1';
        $orderByRaw = 'updated_time DESC, created_time DESC';
        $limit = 4;
        if (! $total = $drives::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'limit' => $limit,
                    'drives' => []
                ]
            ],  $drives->modules[1] . '查询成功'));
        }

        $data = $drives::whereRaw($whereRaw)
                        ->orderByRaw($orderByRaw)
                        ->offset(0)
                        ->limit($limit)
                        ->get();
        $list = [];
        foreach(object_array($data, true) as $v)
        {
            $v['pic'] = isLocalPath($v['pic']);
            $list[] = $v;
        }

        return response()->json(export_data(100200, [
            'list' => [
                    // 'total' => $total,
                    'limit' => $limit,
                    'drives' => $list ?: $data
                ]
        ],  $drives->modules[1] . '查询成功'));
    }
    // 查询信息
    public function get(Request $request)
	{
        $fields = [
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'type' => 'filled|string',
            'subjects' => 'filled|numeric',
            'vip'   => 'filled|numeric',
            'sort' => 'filled|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $drives = new DrivesMode;

        // 单条数据查询
        if (! empty($id)) {
            $result = $drives::whereRaw('id = ' . $id)->first();
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($result->pic) && ! preg_match($pattern, $result->pic)) {
                $result->pic = LOCAL_STORE . $result->pic;
            }
            return response()->json(export_data(100200, $result,  $drives->modules[1] . '查询成功'));
        }
        $orderByRaw = 'updated_time DESC, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ', updated_time DESC, created_time DESC';
        }
        
        // 是否随机取数
        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 关键词查询
        if (! empty($kw)) {
            $whereRaw .= isJointString($whereRaw) . " name like \"%$kw%\"";
        }

        // vip查询
        if (! empty($vip) && $vip == 1) {
            $whereRaw .= isJointString($whereRaw) . " vip = " . $vip;
        }

        // 驾照类型
        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type LIKE \"%$type%\"";
        }

        // 驾照科目
        if (! empty($subjects)) {
            $whereRaw .= isJointString($whereRaw) . " subjects = " . $subjects;
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $drives::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'drives' => []
                ]
            ],  $drives->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                $drives::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $drives::whereRaw($whereRaw)
                                  ->orderByRaw($orderByRaw)
                                  ->offset($offset)
                                  ->limit($limit)
                                  ->get();
        $list = [];
        foreach(object_array($data, true) as $v)
        {
            $v['pic'] = isLocalPath($v['pic']);
            $v['times'] = date("i:s", $v['times']);
            $list[] = $v;
        }

        return response()->json(export_data(100200, [
            'list' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => $pages,
                    'limit' => $limit,
                    'drives' => $list ?: $data
                ]
        ],  $drives->modules[1] . '查询成功'));

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

        $drives = new DrivesMode;
        if ($del = $drives::whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $drives->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $drives->modules[1] . '数据删除失败'));
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

        $drives = new DrivesMode;
        if ($del = $drives::whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $drives->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $drives->modules[1] . '数据启用失败'));
    }

    /**
     * 更新数据
     */
    public function update(Request $request)
    {

        $fields = [
            'id' => 'required|numeric',
            'name' => 'filled|string',
            'description' => 'filled|string',
            'pic' => 'filled|string',
            'subjects' => 'filled|numeric',
            'times' => 'filled|numeric',
            'vip' => 'filled|numeric',
            'type' => 'filled|string',
            'media' => 'filled|string',
            'note' => 'nullable|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $filled = $validator;
        unset($filled['id']);

        $drives = new DrivesMode;

        if (! $results = DrivesMode::where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $drives->modules[1] . '当前更新数据为空！'));
        }

        $whereRaw = "";

        if (! empty($name)) {
            $whereRaw .= isJointString($whereRaw) . " name = '" . $name . "'";
        }

        if (! empty($pic)) {
            $whereRaw .= isJointString($whereRaw) . " pic = '" . $pic . "'";
        }

        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type = '" . $type . "'";
        }

        if (! empty($subjects)) {
            $whereRaw .= isJointString($whereRaw) . " subjects = " . $subjects;
        }

        if (!empty($whereRaw) && $drives::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $drives->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = DrivesMode::where('id', $id)->update($filled)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            return response()->json(export_data(100200, null,  $drives->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $drives->modules[1] . '数据更新失败'));
    }

    // 添加信息
    public function create(Request $request)
	{
        $fields = [
            'name' => 'required|string',
            'description' => 'required|string',
            'pic' => 'required|string',
            'times' => 'required|numeric',
            'subjects' => 'required|numeric',
            'vip' => 'required|numeric',
            'type' => 'required|string',
            'media' => 'required|string',
            'note' => 'filled|string'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        
        $data = [
            'name' => $name,
            'pic' => $pic,
            'subjects' => $subjects,
            'vip' => $vip,
            'type' => $type,
            'media' => $media,
            'times' => $times,
            'description' => $description,
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        $drives = new DrivesMode;

        $whereRaw = "name = '" . $name . "' AND subjects = '" . $subjects . "' AND type = '" . $type . "' AND vip = " . $vip;
        
        if ($hasData = $drives::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        if ($id = $drives->insertGetId($data)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            
            return response()->json(export_data(100200, null, '信息添加成功！'));
         }
         return response()->json(export_data(100400, null, '信息添加失败！'));

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
