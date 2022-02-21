<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-18 09:48:41
 * @LastEditTime: 2021-06-18 09:55:41
 * @LastEditors: Please set LastEditors
 * @Description: 支付
 * @FilePath: .\lma_clbd\app\Models\Pay.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pay extends Model
{
    use HasFactory;

    //管理的数据表
    protected $table = MODULES[15][0];
    //主键
    public $primaryKey = 'id';
    public $moduleId = 15;
    public $modules = MODULES[15];

    public $timestamps = false;
}
