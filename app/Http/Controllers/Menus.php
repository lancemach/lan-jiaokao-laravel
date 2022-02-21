<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-13 17:13:04
 * @LastEditTime: 2021-05-16 16:10:39
 * @LastEditors: Please set LastEditors
 * @Description: 菜单
 * @FilePath: .\app\Http\Controllers\Menus.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Menus as MenusModel;

class Menus extends Controller
{
    /**
     * Create a new Menus instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paginator = object_array(PAGINATOR);
        $this->limit = 0;
        $this->less = 1;
        $this->meta = ['title', 'icon', 'show', 'keepAlive', 'hiddenHeaderContent', 'permission'];
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
                'menus' => $data
            ]
        ], '查询成功'));


    }

    /**
     * 查询数据(内部)
     */
    public function check($request)
    {
        extract($request);
        $menus = new MenusModel;
        // 单条数据查询
        if (! empty($id) && COUNT($validator) === 1) {
            $result = $menus::whereRaw('id = ' . $id)->first();
            return $result;
        }

        $selectRaw = 'id,
                     parent_id,
                     name,
                     permission,
                     title,
                     icon,
                     redirect,
                     component,
                     keep_alive as keepAlive,
                     hidden_children as hiddenChildren,
                     hidden_header_content as hiddenHeaderContent,
                     hidden as `show`
                    ';
        $orderByRaw = '`order` ASC, updated_time DESC';
        $whereRaw = "life = " . (!empty($life) ? $life : 1);

        // 关键词查询
        if (! empty($kw)) {
            $whereRaw .= isJointString($whereRaw) . " title like \"%$kw%\"";
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) || empty($limit) == 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $menus::whereRaw($whereRaw)->count()) {
            return [];
        }
        
        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;
        $offset = ($page - 1) * $limit;

        if ($limit && $page > $pages) {
            return [0, 100200, '页数(page)大于最大页数'];
        }
        $data = $limit === 0 ?
                $menus::selectRaw($selectRaw)->whereRaw($whereRaw)->orderByRaw($orderByRaw)->get() :
                $menus::selectRaw($selectRaw)->whereRaw($whereRaw)
                            ->orderByRaw($orderByRaw)
                            ->offset($offset)
                            ->limit($limit)
                            ->get();
        $list = $limits = [];
        foreach($data as $i => $v)
        {
            foreach(object_array($v, true) as $k => $j)
            {
                if (in_array($k, $this->meta)) {
                    $list[$i]['meta'][$k] = $j;
                    if ($k === 'show') {
                        $list[$i]['meta']['show'] = $list[$i]['meta']['show'] === 1 ? false : true;
                    }
                    $list[$i]['meta']['permission'] = explode(',', $v['permission']);
                } else {
                    $list[$i][$k] = $j;
                }
            }
            if (!empty($list[$i]['component']) && $list[$i]['component'] === 'RouteView') {
                $list[$i]['redirect'] = '';
            }
            
        }
        return $list ?: $data;
    }

    // public function flat_array($data = [])
    // {
    //     $list = [];
    //     foreach ($data as $k => $v) {
    //         if ($v['son']){
    //             $list = array_merge($list, function flatten($v['son']));
    //         } else {
    //             $list[$k] = $v;
    //         }
    //     }
    //     return $list;
    // }
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
