<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-13 17:13:33
 * @LastEditTime: 2021-05-13 22:19:10
 * @LastEditors: Please set LastEditors
 * @Description: 菜单
 * @FilePath: .\app\Models\Menus.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menus extends Model
{
    use HasFactory;

    //管理的数据表
    protected $table = MODULES[3][0];
    //主键
    public $primaryKey = 'id';
    public $moduleId = 3;
    public $modules = MODULES[3];

    public $timestamps = false;
}