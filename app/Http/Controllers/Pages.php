<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-03 12:13:49
 * @LastEditTime: 2021-06-17 20:10:55
 * @LastEditors: Please set LastEditors
 * @Description: 单页面
 * @FilePath: .\app\Http\Controllers\Pages.php
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Pages AS PagesMode;

class Pages extends Controller
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

    // 查询信息
    public function get(Request $request)
	{
        $fields = [
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'type' => 'filled|numeric',
            'sort' => 'filled|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $pages = new PagesMode;

        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = $pages::whereRaw('id = ' . $id)->first();
            return response()->json(export_data(100200, $result,  $pages->modules[1] . '查询成功'));
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

        // 类型
        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type = " . $type;
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $pages::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'pages' => []
                ]
            ],  $pages->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $_pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $_pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $data = $limit === 0 ?
                $pages::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $pages::whereRaw($whereRaw)
                                  ->orderByRaw($orderByRaw)
                                  ->offset($offset)
                                  ->limit($limit)
                                  ->get();
                       
        $list = [];

        return response()->json(export_data(100200, [
            'list' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => $_pages,
                    'limit' => $limit,
                    'pages' => $list ?: $data
                ]
        ],  $pages->modules[1] . '查询成功'));

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

        $pages = new PagesMode;
        if ($del = $pages::whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $pages->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $pages->modules[1] . '数据删除失败'));
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

        $pages = new PagesMode;
        if ($del = $pages::whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $pages->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $pages->modules[1] . '数据启用失败'));
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
            'type' => 'filled|string',
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

        $pages = new PagesMode;

        if (! $results = PagesMode::where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $pages->modules[1] . '当前更新数据为空！'));
        }

        $whereRaw = "";

        if (! empty($name)) {
            $whereRaw .= isJointString($whereRaw) . " name = '" . $name . "'";
        }

        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type = '" . $type . "'";
        }

        if (!empty($whereRaw) && $pages::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $pages->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = PagesMode::where('id', $id)->update($filled)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            return response()->json(export_data(100200, null,  $pages->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $pages->modules[1] . '数据更新失败'));
    }

    // 添加信息
    public function create(Request $request)
	{
        $fields = [
            'name' => 'required|string',
            'description' => 'filled|string',
            'type' => 'filled|string',
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
            'type' => $type ?? 2,
            'description' => $description ?? '',
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        $pages = new PagesMode;

        $whereRaw = "name = '" . $name . "'";
        
        if ($hasData = $pages::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加信息！'));
        }

        if ($id = $pages->insertGetId($data)) {
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
