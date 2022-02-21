<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-03-12 11:02:39
 * @LastEditTime: 2021-06-08 09:20:00
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Category.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category as CategoryModel;

use Illuminate\Support\Facades\Validator;

class Category extends Controller
{
    private $paginator;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->paginator = object_array(PAGINATOR);
    }
    /**
     * 查询分类
     */
    public function check(Request $request)
    {
        $fields = [
            'id' => ''
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

    
        return response()->json(export_data(100200, ['type' => WORK_STATION],  $this->modules[1] . '配置信息查询成功'));
    }

    public function list($pid = 0, $admin = 0, $page = 0, $limit = 0)
    {
        
        $orderByRaw = 'id asc';

        $whereRaw = '';
        if (empty($admin)) {
            $whereRaw .= 'life = 1';
        }

        if (! empty($pid)) {
            $whereRaw .= (!empty($whereRaw) ? ' AND' : '') . ' parent_id = ' . $pid;
        } 
   
        $category = new CategoryModel;
        if (! $total = $category::whereRaw($whereRaw)->count()) {
            return export_data(100200, null,  $category->modules[1] . '查询成功');
        }
        
        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) !== 0 ? intval($limit) : $this->paginator->limit;
        $pages = $limit ? ceil($total / $limit) : 1;
        $offset = ($page - 1) * $limit;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '(page)页（参）数大于最大页数'));
        }

        $data = $limit === 0 ? 
                $category::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $category::whereRaw($whereRaw)->orderByRaw($orderByRaw)->offset($offset)->limit($limit)->get();
        return $data;
    }

    // 查询分类
    public function get(Request $request)
	{
        $fields = [
            'id' => 'filled|numeric',
            'parent_id'   => 'filled|numeric',
            'group_id'   => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'type' => 'filled|string',
            'rule' => 'filled|string',
            'sort' => 'filled|string'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $category = new CategoryModel;
        
        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = $category::whereRaw('id = ' . $id)->first();
            return response()->json(export_data(100200, $result,  $category->modules[1] . '查询成功'));
        }
        $orderByRaw = 'updated_time DESC, created_time DESC';

        if (! empty($sort)) {
            $orderByRaw = $sort . ', updated_time DESC, created_time DESC';
        }
        
        // 是否随机取数
        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 分类(级)查询
        $whereRaw .= isJointString($whereRaw) . " parent_id = " . (empty($parent_id) ? 0 : $parent_id);

        // 菜单组
        if (! empty($group_id)) {
            $whereRaw .= isJointString($whereRaw) . " group_id = " .  $group_id;
        }

        // 关键词查询
        if (! empty($kw)) {
            $whereRaw .= isJointString($whereRaw) . " question like \"%$kw%\"";
        }

        // 信息类型
        if (! empty($type)) {
            $whereRaw .= isJointString($whereRaw) . " type  = " . $type;
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) || intval($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $category::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'cates' => []
                ]
            ],  $category->modules[1] . '查询成功'));
        }

        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);
        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }


        $data = $limit === 0 ?
                $category::whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $category::whereRaw($whereRaw)
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
                    'cates' => $data
                ]
        ],  $category->modules[1] . '查询成功'));

    }

    // 更新分类
    public function update(Request $request)
    {
        $fields = [
            'id' => 'required|numeric',
            'name' => 'filled|string',
            'parent_id'   => 'filled|numeric',
            'group_id'   => 'filled|numeric',
            'type'   => 'filled|numeric',
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

        $cate = new CategoryModel;

        if (! $results = $cate::where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $cate->modules[1] . '当前更新数据为空！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $cate->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = $cate::where('id', $id)->update($filled)) {
            return response()->json(export_data(100200, null,  $cate->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $cate->modules[1] . '数据更新失败'));

    }

    // 添加分类
    public function create(Request $request)
    {
        $fields = [
            'name' => 'required|string',
            'parent_id'   => 'filled|numeric',
            'group_id'   => 'filled|numeric',
            'type'   => 'required|numeric',
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
            'parent_id' => $parent_id ?? 0,
            'group_id' => $group_id ?? 0,
            'type' => $type,
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];
        // print_r($data);die;
        $cate = new CategoryModel;
        $whereRaw = "name = '" . $data['name'] . "' AND parent_id = " . $data['parent_id'] . " AND type = " . $data['type'];

        if ($hasData = $cate::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加分类！'));
        }
        if ($id = $cate->insertGetId($data)) {
            if (empty($group_id)) {
                $cate::whereRaw("id = $id")->update(['group_id' => $id]);
            }
            return response()->json(export_data(100200, null, '分类添加成功！'));
        }
        return response()->json(export_data(100400, null, '分类添加失败！'));

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

        // 检查删除项是否有子信息(待下个版本)

        $cate = new CategoryModel;
        if ($del = $cate::whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $cate->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $cate->modules[1] . '数据删除失败'));
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

        $cate = new CategoryModel;
        if ($del = $cate::whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $cate->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $cate->modules[1] . '数据启用失败'));
    }


    public function userGroup($gid = 0, $uid = 0)
    {
        $category = new CategoryModel;
        $group = $category::whereRaw("group_id = $gid")->get();
        // $data = treeMagicData(object_array($group, true), $uid, 'id', 'parent_id', ['order' => 'asc', 'flat' => 1]);
        
        // $userGroup = [];
        // foreach ($data as $v) {
        //     if ($v['type'] !== 1) {
        //         $userGroup[] = $v['name'];
        //     }
        // }
        // return $userGroup;
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
