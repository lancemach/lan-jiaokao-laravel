<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-03 12:13:36
 * @LastEditTime: 2021-06-03 12:18:56
 * @LastEditors: Please set LastEditors
 * @Description: 单页面
 * @FilePath: .\app\Models\Pages.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pages extends Model
{
    use HasFactory;
    //管理的数据表
    public $table = 'single_page';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 13;
    public $modules = MODULES[13];
    public $db_prefix;

    public $timestamps = false;

}
