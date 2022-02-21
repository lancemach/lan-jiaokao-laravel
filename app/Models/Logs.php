<?php
/*
 * @Author: your name
 * @Date: 2021-02-02 16:14:12
 * @LastEditTime: 2021-02-02 16:19:59
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \laravel-lancema\app\Models\Logs.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    use HasFactory;

    //管理的数据表
    protected $table = 'logs';
    //主键
    public $primaryKey = 'id';

    public $timestamps = false;

}
