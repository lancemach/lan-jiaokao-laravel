<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-27 21:54:43
 * @LastEditTime: 2021-05-28 12:05:20
 * @LastEditors: Please set LastEditors
 * @Description: 交规图标
 * @FilePath: .\app\Models\Symbol.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Symbol extends Model
{
    use HasFactory;
    //管理的数据表
    public $table = 'study_symbol';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 11;
    public $modules = MODULES[11];
    public $db_prefix;

    public $timestamps = false;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->db_prefix = DB::getConfig('prefix');
    }
}
