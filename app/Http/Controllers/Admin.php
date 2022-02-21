<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-13 17:13:51
 * @LastEditTime: 2021-06-04 17:11:24
 * @LastEditors: Please set LastEditors
 * @Description: 管理员
 * @FilePath: .\app\Http\Controllers\Admin.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin as AdminModel;

class Admin extends Controller
{
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
        $this->limit = 0;
        $this->less = 1;
        $this->action_entity = [[
            'action' => 'add',
            'describe' => '新增',
            'default_check' => false
          ],
          [
            'action' => 'query',
            'describe' => '查询',
            'default_check' => false
          ],
          [
            'action' => 'get',
            'describe' => '详情',
            'default_check' => false
          ],
          [
            'action' => 'update',
            'describe' => '修改',
            'default_check' => false
          ],
          [
            'action' => 'delete',
            'describe' => '删除',
            'default_check' => false
        ]];
    }

    /**
     * 查询数据
     */
    public function get(Request $request)
    {
        $fields = [
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'less' => 'filled|numeric'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            // extract($validator);
        }

        $data = $this->check($validator);
        if (isset($data[0]) && $data[0] === 0) {
            return response()->json(export_data($data[1], $data[2]));
        }

        return response()->json(export_data(100200, $this->less ? 
            $data : [
            'list' => [
                'total' => $total,
                'page' => $page,
                'pages' => $pages,
                'limit' => $limit,
                'admin' => $data
            ]
        ], '查询成功'));


    }

    /**
     * 查询数据(内部)
     */
    public function check($request = [])
    {
        extract($request);
        $admin = new AdminModel;
        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = $admin::whereRaw('id = ' . $id)->first();
            return $result;
        }

        $selectRaw = 'id, `group`, permission, note';
        $orderByRaw = 'created_time, updated_time DESC';
        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $admin::whereRaw($whereRaw)->count()) {
            return [];
        }
        
        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;
        $offset = ($page - 1) * $limit;

        if ($limit && $page > $pages) {
            return [0, 100200, '页数(page)大于最大页数'];
        }
        $data = $limit === 0 ?
                $admin::selectRaw($selectRaw)->whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $admin::selectRaw($selectRaw)->whereRaw($whereRaw)
                            ->orderByRaw($orderByRaw)
                            ->offset($offset)
                            ->limit($limit)
                            ->get();
        // return $data;
        return [
            'permissions' => [
                [
                    'role_id' => 'admin',
                    'permission_id' => 'dashboard',
                    'permission_name' => '仪表盘',
                    'action_entity_set' => $this->action_entity,
                    'action_list' => null,
                    'data_access' => null
                ],
                [
                    'role_id' => 'admin',
                    'permission_id' => 'studies',
                    'permission_name' => '题库管理',
                    'action_entity_set' => $this->action_entity,
                    'action_list' => null,
                    'data_access' => null
                ],
                [
                    'role_id' => 'admin',
                    'permission_id' => 'category',
                    'permission_name' => '分类管理',
                    'action_entity_set' => $this->action_entity,
                    'action_list' => null,
                    'data_access' => null
                ],
                [
                    'role_id' => 'admin',
                    'permission_id' => 'ad',
                    'permission_name' => '广告管理',
                    'action_entity_set' => $this->action_entity,
                    'action_list' => null,
                    'data_access' => null
                ],
                [
                    'role_id' => 'admin',
                    'permission_id' => 'pages',
                    'permission_name' => '单页管理',
                    'action_entity_set' => $this->action_entity,
                    'action_list' => null,
                    'data_access' => null
                ],
                [
                    'role_id' => 'admin',
                    'permission_id' => 'settings',
                    'permission_name' => '系统设置',
                    'action_entity_set' => $this->action_entity,
                    'action_list' => null,
                    'data_access' => null
                ]
            ]
        ];
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
