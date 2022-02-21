<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-02 11:51:55
 * @LastEditTime: 2021-05-28 12:08:43
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Models\Category.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    //管理的数据表
    public $table = 'category';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 6;
    public $modules = MODULES[6];

    public $timestamps = false;
}
