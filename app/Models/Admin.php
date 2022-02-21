<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-13 17:13:44
 * @LastEditTime: 2021-05-13 22:42:30
 * @LastEditors: Please set LastEditors
 * @Description: 管理员
 * @FilePath: .\app\Models\Admin.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    //管理的数据表
    protected $table = MODULES[2][0];
    //主键
    public $primaryKey = 'id';
    public $moduleId = 2;
    public $modules = MODULES[2];

    public $timestamps = false;
}

