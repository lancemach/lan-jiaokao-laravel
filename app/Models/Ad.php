<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-04 15:57:52
 * @LastEditTime: 2021-06-05 15:20:45
 * @LastEditors: Please set LastEditors
 * @Description: 广告
 * @FilePath: .\app\Models\Ad.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Ad extends Model
{
    use HasFactory;

    //管理的数据表
    public $table = 'ad';
    public $table_son = 'ad_place';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 13;
    public $modules = MODULES[13];
    public $db_prefix;


    public $adType = [
        1 => '文本',
        2 => '图片',
        3 => '视频'
    ];

    public $timestamps = false;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
}
