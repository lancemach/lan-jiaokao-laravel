<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-03-19 10:21:18
 * @LastEditTime: 2021-03-28 15:54:42
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Imports\MaImports.php
 */

namespace App\Imports;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

// use Maatwebsite\Excel\Concerns\ToModel;
use App\Http\Controllers\Imports;

class MaImports implements ToCollection
{
    public $data;
    protected $header_row;

    /**
     *
     * @param $title integer 
     */
    public function __construct($parameters = [])
    {
        extract($parameters);
        $this->model = !empty($model) ? $model : '';
        $this->header_row = !empty($header_row) ? $header_row : 1;
    }

    // /**
    //  * @param array $row
    //  *
    //  * @return User|null
    //  */
    // public function model(array $row)
    // {
    //     return $row;
    // }

    /**
     * @param Collection $rows
     * $rows 是数组格式
     */
    public function collection(Collection $rows)
    {
        $data = object_array($rows);
        if ($array = array_splice($data, $this->header_row)) {
            $this->createData($array);
        }
        
    }

    public function createData($data)
    {

        $imports = new Imports;
        if (! $create = $imports->receive($data, $this->model)) {
            echo json_encode(export_data(100400, null, '导入失败！'));exit;
        }
        echo json_encode(export_data(100200, null, '导入成功！'));
    }

}
