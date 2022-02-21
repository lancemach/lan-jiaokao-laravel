<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Models\Symbol AS SymbolMode;
use App\Models\Category;
use App\Http\Controllers\Upload;

class Symbol extends Controller
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

    // 查询图标
    public function get(Request $request)
	{
        $fields = [
            'id' => 'filled|numeric',
            'page'   => 'filled|numeric',
            'limit'   => 'filled|numeric',
            'life' => 'filled|numeric',
            'kw' => 'filled|string',
            'sort' => 'filled|string',
            'category_id' => 'filled|numeric',
            'group_id' => 'filled|numeric'
        ];

        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $symbol = new SymbolMode;

        $cate = new Category;

        $tableLeft = $symbol->table;
        $tableRight = $cate->table;
        $strReplace = $symbol->db_prefix . $symbol->table . '.';

        // 单条数据查询
        if (! empty($id)) {
            $result = DB::table($tableLeft)
                        ->selectRaw($symbol->db_prefix . $tableLeft . '.*, ' . $symbol->db_prefix . $tableRight . '.group_id,' . $symbol->db_prefix . $tableRight . '.parent_id')
                        ->whereRaw($symbol->db_prefix . $tableLeft . '.id = ' . $id)
                        ->join($tableRight, $tableLeft . '.category_id', '=', $tableRight . '.id')
                        ->first();
            $result->pic = isLocalPath($result->pic);
            return response()->json(export_data(100200, $result,  $symbol->modules[1] . '查询成功'));
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

        if (! empty($category_id)) {
            $whereRaw .= isJointString($whereRaw) . " category_id =" . $category_id;
        }

        $page = !empty($page) ? intval($page) : $this->paginator->page;
        $limit = !empty($limit) && intval($limit) > 0 || intval($limit) === 0 ? intval($limit) : ($this->limit ?? $this->paginator->limit);

        if (! $total = $symbol::whereRaw($whereRaw)->count()) {
            return response()->json(export_data(100200, [
                'list' => [
                    'total' => 0,
                    'page' => $page,
                    'pages' => 0,
                    'limit' => $limit,
                    'symbol' => []
                ]
            ],  $symbol->modules[1] . '查询成功'));
        }

        $offset = ($page - 1) * $limit;

        $pages = intval($limit) > 0 ? ceil($total / $limit) : 1;

        if ($limit && $page > $pages) {
            return response()->json(export_data(100200, null,  '页数(page)大于最大页数'));
        }

        $whereRaw = isHasStringReplace($whereRaw, $strReplace, ['life', 'name']);
        $orderByRaw = isHasStringReplace($orderByRaw, $strReplace, ['updated_time', 'created_time']);
        $selectRaw = $symbol->db_prefix . $tableLeft . '.*, ' . $symbol->db_prefix . $tableRight . '.name as category_name, ' . $symbol->db_prefix . $tableRight . '.group_id, ' . $symbol->db_prefix . $tableRight . '.parent_id';
        $data = $limit === 0 ?
                DB::table($tableLeft)->whereRaw($whereRaw)
                                  ->selectRaw($selectRaw)
                                  ->join($tableRight, $tableLeft . '.category_id', '=', $tableRight . '.id')
                                  ->orderByRaw($orderByRaw)->get() :
                DB::table($tableLeft)->whereRaw($whereRaw)
                                  ->selectRaw($selectRaw)
                                  ->join($tableRight, $tableLeft . '.category_id', '=', $tableRight . '.id')
                                  ->orderByRaw($orderByRaw)
                                  ->offset($offset)
                                  ->limit($limit)
                                  ->get();
        $data = object_array($data, true);                     
        $list = [];
 
        $cateList = DB::table($tableRight)->whereRaw('group_id = ' . $data[0]['group_id'])->get();
        // print_r($cateList);die;
        foreach($data as $k => $v)
        {
            $v['pic'] = isLocalPath($v['pic']);
            if (! empty($group_id)) {
                foreach(object_array($cateList, true) as $k)
                {
                    if ($k['id'] === $v['category_id'] && empty($list[$v['category_id']])) {
                        $list[$v['category_id']] = $k;
                    }
                }
                $list[$v['category_id']]['son'][] = $v;
            } else {
                $list[] = $v;
            }
            
        }

        $list = array_merge([], $list);

        return response()->json(export_data(100200, [
            'list' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => $pages,
                    'limit' => $limit,
                    'symbol' => $list ?: $data
                ]
        ],  $symbol->modules[1] . '查询成功'));

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

        $symbol = new SymbolMode;
        if ($del = $symbol::whereRaw("id IN ($id) AND life <> 6")->update(['life' => 6, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $symbol->modules[1] . '数据删除成功'));
        }
        return response()->json(export_data(100400, null,  $symbol->modules[1] . '数据删除失败'));
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

        $symbol = new SymbolMode;
        if ($del = $symbol::whereRaw("id = $id AND life = 6")->update(['life' => 1, 'updated_time' => SYSTEM_TIME])) {
            return response()->json(export_data(100200, null,  $symbol->modules[1] . '数据启用成功'));
        }
        return response()->json(export_data(100400, null,  $symbol->modules[1] . '数据启用失败'));
    }

    /**
     * 更新数据
     */
    public function update(Request $request)
    {

        $fields = [
            'id' => 'required|numeric',
            'name' => 'filled|string',
            'category_id' => 'filled|numeric',
            'description' => 'filled|string',
            'pic' => 'filled|string',
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

        $symbol = new SymbolMode;

        if (! $results = SymbolMode::where('id', $id)->first()) {
            return response()->json(export_data(100400, null,  $symbol->modules[1] . '当前更新数据为空！'));
        }

        $whereRaw = "";

        if (! empty($name)) {
            $whereRaw .= isJointString($whereRaw) . " name = '" . $name . "'";
        }

        if (! empty($pic)) {
            $whereRaw .= isJointString($whereRaw) . " pic = '" . $pic . "'";
        }

        if (! empty($category_id)) {
            $whereRaw .= isJointString($whereRaw) . " category_id = '" . $category_id . "'";
        }

        if (!empty($whereRaw) && $symbol::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加图标！'));
        }

        $data_changes = array_values_change($filled, object_array($results, true));
        
        if (empty($data_changes)) {
            return response()->json(export_data(100400, null,  $symbol->modules[1] . '数据未曾改变，无需更新'));
        }
        
        $filled['updated_time'] = SYSTEM_TIME;
        if ($update = SymbolMode::where('id', $id)->update($filled)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            return response()->json(export_data(100200, null,  $symbol->modules[1] . '数据更新成功'));
        }

        return response()->json(export_data(100400, null,  $symbol->modules[1] . '数据更新失败'));
    }

    // 添加图标
    public function create(Request $request)
	{
        $fields = [
            'name' => 'required|string',
            'category_id' => 'required|numeric',
            'description' => 'required|string',
            'pic' => 'required|string',
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
            'category_id' => $category_id,
            'description' => $description,
            'note' => $note ?? '',
            'created_time' => SYSTEM_TIME,
            'updated_time' => SYSTEM_TIME
        ];

        $symbol = new SymbolMode;

        $whereRaw = "name = '" . $name . "' AND pic = '" . $pic . "' AND category_id = " . $category_id;
        
        if ($hasData = $symbol::whereRaw($whereRaw)->first()) {
            return response()->json(export_data(100400, null, '请勿重复添加图标！'));
        }

        if ($id = $symbol->insertGetId($data)) {
            $pattern = "/^(http|https):\/\/.*$/i";
            if (!empty($pic) && ! preg_match($pattern, $pic)) {
                $upload = new Upload;
                $path = $upload->checkPath($pic);
                if (!empty($path->id)) {
                    $upload->add(['tid' => $path->id, 'id' => $id]);
                }
            }
            
            return response()->json(export_data(100200, null, '图标添加成功！'));
         }
         return response()->json(export_data(100400, null, '图标添加失败！'));

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
