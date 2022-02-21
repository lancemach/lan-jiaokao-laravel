<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-03-19 10:27:09
 * @LastEditTime: 2021-03-28 15:44:13
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Imports.php
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Upload;
use App\Http\Controllers\Products;

use App\Imports\MaImports;

use Excel;

class Imports extends Controller
{

    public function receive($data = [], $model = '') 
    {
        $products = new Products;
        return $products->imports($data);
    }
    //
    public function products(Request $request) 
    {

        $upload = new Upload;
        $uploadFile = $upload->uploadFile($request);
  
        if (is_array($uploadFile)) {
            if (!empty($uploadFile[env('NET_RESPONSE_KEYMSG', '')])) {
                return response()->json(export_data(100400, null, $uploadFile[env('NET_RESPONSE_KEYMSG', '')]));
            }
            
            extract($uploadFile);
        }
        if (empty($filePath) || empty($status) && $status !== 0) {
            return response()->json(export_data(100400, null, $uploadFile));
        }
        
        $excel = new MaImports(['model' => 'products']);
        $importExcel = Excel::import($excel, $filePath);
        exit;
    }
    
}
