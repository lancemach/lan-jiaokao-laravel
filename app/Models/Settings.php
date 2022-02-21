<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-16 17:21:08
 * @LastEditTime: 2021-05-18 14:35:36
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Models\Settings.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    //管理的数据表
    protected $table = MODULES[1][0];
    //主键
    public $primaryKey = 'id';
    public $moduleId = 1;
    public $modules = MODULES[1];

    public $timestamps = false;
}