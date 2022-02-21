<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-18 10:23:57
 * @LastEditTime: 2021-06-18 16:16:21
 * @LastEditors: Please set LastEditors
 * @Description: 会员卡
 * @FilePath: .\app\Models\CardVip.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardVip extends Model
{
    //管理的数据表
    public $table = MODULES[17][0];
    public $table_item = MODULES[17][0] . '_item';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 17;
    public $modules = MODULES[17];

    public $timestamps = false;
}
