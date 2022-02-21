<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-06-03 09:32:42
 * @LastEditTime: 2021-06-03 10:35:49
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Models\Drives.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Drives extends Model
{
    use HasFactory;
    //管理的数据表
    public $table = 'study_drives';
    //主键
    public $primaryKey = 'id';
    public $moduleId = 12;
    public $modules = MODULES[12];
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
