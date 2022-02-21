<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-03-17 17:21:56
 * @LastEditTime: 2021-06-05 11:05:23
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Upload.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Upload AS UploadModel;


class Upload extends Controller
{
 
    private $error_extension;
    private $maxSize;
    public $allow_extensions;

    /**
     * Create a new Upload instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->error_extension = config('aetherupload.forbidden_extensions', []);
        $this->maxSize = 10241000;
        $this->allow_extensions;
    }


    // 图片上传 id => 使用图片表id
    public function uploadsImages(Request $request)
    {
        $fields = [
            'id'     => 'filled|numeric',
            'tid'     => 'filled|numeric'
        ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $params = [
            'name' => $name ?? '',
            'folder' => 'images',
            'id' => $id ?? '',
            // 'allow_type' => 'image/jpeg',
            'allow_extensions' => ['jpeg', 'png', 'jpg', 'gif']
        ];
        $upload = $this->uploadFile($params);

        if (!is_array($upload) || empty($upload['filePath'])) {
            return response()->json(export_data(100400, [], $upload));
        }
        return response()->json(export_data(100200, $upload['filePath'], '上传成功！'));
        
    }


    /**
	 * 上传文件
	 * @param $name string 文件名称
	 * @param $id string 系统配置参数名
     *
	 * @return 
	 */

    public function uploadFile($params)
    {

        extract($params);

        if (! $uploadFile = request()->file()) {
            return '上传文件未定义文件(键)名或者未上传文件';
        }

        $filePathList = $uploadList = $uploadValidate = [];

        foreach ($uploadFile as $file)
        {
            $extension = $file->extension();

            if ($this->filterExtension($extension)) {
                return '上传文件禁止类型('. $extension .')';
            }

            if (!empty($allow_extensions) && ! in_array($extension, $allow_extensions)) {
                return '上传文件只允许类型('. implode(',', $allow_extensions) .')';
            }

            if ($file->getSize() > $this->maxSize) {
                return '上传文件大小超出('. round((($this->maxSize / 1024) <= 999 ? $this->maxSize / 1024 : $this->maxSize / (1024 * 1024)), 2) .')大小限制';
            }
        }
        
        foreach ($uploadFile as $k => $file)
        {

            // $filePath = $file->getRealPath();
            $date = date('Ymd');
            $folder = $folder ?? '';
            $flatPath = [$folder ?? 'lancema', $date];
            if (!empty($folder)) {

                $fullPath = implode('/', $flatPath);
                if (! Storage::exists($fullPath)) {
                    // 是否存在文件夹
                    foreach ($flatPath as $k => $v)
                    {
                        $folderPath = implode('/', array_slice($flatPath, 0, $k + 1));

                        if (!Storage::exists($folderPath)) {
                            Storage::makeDirectory($folderPath);
                        } 
                    }
                }
                
                $filePath = Storage::putFile($fullPath, new File($file));
            } else {
                if (!Storage::exists($date)) {
                    Storage::makeDirectory($date);
                }
                $filePath = Storage::putFile($folder. '/' . $date , new File($file));
            }
            // $filePathList[] = [$k => $filePath];
            $filePathList[] = $filePath;
            $uploadList[] = [
                'title' => $k,
                'consumer' => $id,
                'path' => $filePath,
                'type' => DOCUMENT_TYPE[2][0],
                'network' => 1,
                'extends' => $file->extension(),
                'created_time' => SYSTEM_TIME,
                'updated_time' => SYSTEM_TIME,
                'note' => $note ?? ''
            ];
        }
        
        if (! $insert = $this->insert($uploadList)) {
            return '文件保存失败！';
        }
        // if (! request()->hasFile($name)) {
        //     return '文件('. $file->getClientOriginalName() .')上传失败';
        // }
        return ['status' => 0, 'filePath' => $filePathList];
    }

    protected function insert($data = [])
    {
        if (empty($data) || COUNT($data) < 1) {
            return '存入数据为空！';
        }

        $upload = new UploadModel;
        if ($insert = $upload::insert($data)) {
            return true;
        }
        return false;
    }

    /**
	 * 上传文件
	 * @param $id number 使用者id
	 * @param $tid number 表主键id
     *
	 * @return 
	 */
    public function add($data = [])
    {
        if (empty($data) || COUNT($data) < 1) {
            return '存入数据为空！';
        }
        extract($data);

        $upload = new UploadModel;
        if (! $target = $upload::where('id', $tid)->first()) {
            return '存入数据不在';
        }

        if ($id == $target->consumer || in_array($id, explode(',', $target->consumer))) {
            return '数据已存在';
        }

        if (! $upload::where('id', $tid)->update(['consumer' => empty($target->consumer) ? $id : $target->consumer . ',' . $id])) {
            return '数据删除失败';
        }
        return true;
    }

    // tid => 表id, id => 使用者id
    public function deleteImage(Request $request)
    {
        $fields = !empty($request->image) ? [
                        'image'     => 'required|string',
                        'id'     => 'filled',
                        'mid'     => 'filled',
                        'field' => 'filled|string'
                    ] : [
                        'id'     => 'required',
                        'tid'     => 'required',
                        'field' => 'filled|string'
                    ];
        
        // 数据校验
        if ($validator = $this->validator($request, $fields)) {
            if (! is_array($validator)) {
                return $validator;
            }
            extract($validator);
        }

        $field = $field ?? 'pic';

        if (empty($image)) {
            $ids = explode(',', $id);
            $tids = explode(',', $tid);
            if (COUNT($ids) !== COUNT($tids)) {
                return response()->json(export_data(100400, null, '删除指定图片格式有误！'));
            }
            foreach ($ids as $k => $v)
            {
                $image[] = [
                    'id' => $v,
                    'tid' => $tids[$k]
                ];
            }
        }

        if (!empty($mid)) {
            if (! $table = MODULES[$mid][0] ?? '') {
                $id = 0;
            }
            if (!  DB::table($table)->whereRaw("id = $id")->value($field)) {
                $id = 0;
            }
        }

        $delete = ! is_array($image) ? 
                    $this->delete(explode(',', $image), !empty($id) ? $id : true, $mid ?? 0, $field) :
                    $this->delete($image, false, 0, $field);
    
        if ($delete === true) {
            return response()->json(export_data(100200, null, '删除成功！'));
        }
        return response()->json(export_data(100400, null, $delete));
    }

    // tid => 表id, id => 使用者id
    public function delete($data = [], $path = false, $mid = 0, $field = 'pic')
    {
        if (empty($data) || COUNT($data) < 1) {
            return '删除数据为空！';
        }

        $upload = new UploadModel;

        foreach ($data as $v)
        {
            
            if (!empty($path)) {
                
                if (! empty($path) && $path !== true) {
                    $id = $path;
                }
                $pattern = "/^(http|https):\/\/.*$/i";
                $whereRaw = "path = '" . (substr($v, 0, strlen(LOCAL_STORE)) === LOCAL_STORE && ! preg_match($pattern, $v) ? substr($v, strlen(LOCAL_STORE))  : $v) . "'";
                
                if (! empty($id)) {
                    $whereRaw .= isJointString($whereRaw) . " locate(" . $id . ", consumer)";

                    $removeCustomer = $this->removeCustomer($mid, $id, $field);
                    if ($removeCustomer !== true) {
                        return $removeCustomer;
                    }
                }

                if ($target = $upload::whereRaw($whereRaw)->first()) {

                    if (empty($target->consumer) || ! empty($id) && $target->consumer == $id) {
                        $upload::whereRaw($whereRaw)->delete();
                        if ($target->network == 1) {
                            Storage::delete($target['path']);
                        }
                    }
                    if (! empty($id) && $target->consumer != $id) {
                        $target_array = explode(',', $target->consumer);
                        foreach ($target_array as $k => $v)
                        {
                            if ($v== $id) unset($target_array[$k]);
                        }
                        $target = implode(',', $target_array);
                        if (! $upload::where('id', $target->id)->update(['consumer' => $target])) {
                            return '数据删除失败';
                        }
                    }
                }
                
            } else {

                $id = $v['id'] ?? 0;
                $tid = $v['tid'] ?? 0;

                if ($upload::whereRaw("locate(" . $id . ", consumer) AND " . $tid . " = id")->first()) {
                    $target = $upload::selectRaw('consumer, path, network')->whereRaw($tid . " = id")->first();
                    if ($target->consumer == $id) {
                        $upload::where('id', $tid)->delete();
                        if ($target->network == 1) {
                            Storage::delete($target['path']);
                        }
                    } else {
                        $target_array = explode(',', $target->consumer);
                        foreach ($target_array as $k => $v)
                        {
                            if ($v== $id) unset($target_array[$k]);
                        }
                        $target = implode(',', $target_array);
                        if (! $upload::where('id', $tid)->update(['consumer' => $target])) {
                            return '数据删除失败';
                        }
                    }
                } else {
                    return '删除数据不存在';
                }
            }
        }
        return true;
    }

    // mid 模块id , id 主键id
    protected function removeCustomer($mid, $id, $field)
    {
        if (! $table = MODULES[$mid][0] ?? '') {
            return '删除数据表不存在';
        }
        if ($data = DB::table($table)->whereRaw("id = $id")->first()) {
             if (! DB::table($table)->whereRaw("id = $id")->update([ $field => '' ])) {
                return '删除数据失败';
             }
        }
        return true;
    }

    public function checkPath($path = '')
    {
        $upload = new UploadModel;
        if ($data = $upload::whereRaw("path = '" . $path . "'")->first()) {
             return $data;
        }
        return false;
    }

    protected function filterExtension($extension = '')
    {
        return in_array($extension, $this->error_extension);
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
