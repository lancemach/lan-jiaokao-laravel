<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-04 15:57:37
 * @LastEditTime: 2021-06-04 22:37:06
 * @LastEditors: Please set LastEditors
 * @Description: 广告
 * @FilePath: .\app\Http\Controllers\Ad.php
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Upload;
use App\Models\Ad AS AdMode;

class Ad extends Controller
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
        $this->ad = new AdMode;
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
            'sort' => 'filled|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $drives = !empty($request->route()->getAction('as')) && strpos($request->route()->getAction('as'), 'place') !== false ? $this->ad->table_son : $this->ad->table;

        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = DB::table($drives)->whereRaw('id = ' . $id)->first();
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($result->path) && ! preg_match($pattern, $result->path)) {
                $result->path = LOCAL_STORE . $result->path;
            }
            return response()->json(export_data(100200, $result,  $this->ad->modules[1] . '查询成功'));
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

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = DB::table($drives)->whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'ad' => []
                ]
            ],  $this->ad->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                DB::table($drives)->whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                DB::table($drives)->whereRaw($whereRaw)
                                  ->orderByRaw($orderByRaw)
                                  ->offset($offset)
                                  ->limit($limit)
                                  ->get();
        $list = [];
        foreach(object_array($data, true) as $v)
        {
            if (! empty($v['path'])) {
                $v['path'] = isLocalPath($v['path']);
            }
            
            if (! empty($v['type'])) {
                $v['type_name'] = $this->ad->adType[$v['type']];
            }

            $list[] = $v;
        }

        return response()->json(export_data(100200, [
            'list' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => $pages,
                    'limit' => $limit,
                    'ad' => $list ?: $data
                ]
        ],  $this->ad->modules[1] . '查询成功'));

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

        $drives = !empty($request->route()->getAction('as')) && strpos($request->route()->getAction('as'), 'place') !== false ? $this->ad->table_son : $this->ad->table;
        if ($del = DB::table($drives)->whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $this->ad->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $this->ad->modules[1] . '数据删除失败'));
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

        $drives = !empty($request->route()->getAction('as')) && strpos($request->route()->getAction('as'), 'place') !== false ? $this->ad->table_son : $this->ad->table;
        if ($del = DB::table($drives)->whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $this->ad->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $this->ad->modules[1] . '数据启用失败'));
    }

    /**
     * 更新数据
     */
    public function update(Request $request)
    {

        $fields = [
            'id' => 'required|numeric',
            'name' => 'filled|string',
            'path' => 'filled|string',
            'parent_id' => 'filled|numeric',
            'links' => 'nullable|string',
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

        $drives = !empty($request->route()->getAction('as')) && strpos($request->route()->getAction('as'), 'place') !== false ? $this->ad->table_son : $this->ad->table;

        if (! $results = DB::table($drives)->where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $this->ad->modules[1] . '当前更新数据为空！'));
        }

        $whereRaw = "";

        if (! empty($name)) {
            $whereRaw .= isJointString($whereRaw) . " name = '" . $name . "'";
        }

        if (!empty($whereRaw) && DB::table($drives)->whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $this->ad->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = DB::table($drives)->where('id', $id)->update($filled)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($path) && ! preg_match($pattern, $path)) {
                $upload = new Upload;
                $path = $upload->checkPath($path);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            return response()->json(export_data(100200, null,  $this->ad->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $this->ad->modules[1] . '数据更新失败'));
    }

    // 添加信息
    public function create(Request $request)
	{
        $fields = [
            'name' => 'required|string',
            'parent_id' => 'required|numeric',
            'links' => 'filled|string',
            'path' => 'required|string',
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
            'path' => $path,
            'parent_id' => $parent_id,
            'links' => $links ?? '',
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        $drives = !empty($request->route()->getAction('as')) && strpos($request->route()->getAction('as'), 'place') !== false ? $this->ad->table_son : $this->ad->table;

        $whereRaw = "name = '" . $name . "' AND parent_id = " . $parent_id;
        
        if ($hasData = DB::table($drives)->whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        if ($id = DB::table($drives)->insertGetId($data)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($path) && ! preg_match($pattern, $path)) {
                $upload = new Upload;
                $path = $upload->checkPath($path);
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
