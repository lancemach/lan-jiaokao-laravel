<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-02 11:51:55
 * @LastEditTime: 2021-05-24 20:14:38
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Models\Upload.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;
    //管理的数据表
    public $table = 'upload';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 8;
    public $modules = MODULES[8];

    public $timestamps = false;
}
