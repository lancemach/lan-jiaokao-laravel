<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-18 09:49:02
 * @LastEditTime: 2021-06-18 09:56:30
 * @LastEditors: Please set LastEditors
 * @Description: 订单
 * @FilePath: .\app\Models\Order.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //管理的数据表
    protected $table = MODULES[16][0];
    //主键
    public $primaryKey = 'id';
    public $moduleId = 16;
    public $modules = MODULES[16];

    public $timestamps = false;
}
