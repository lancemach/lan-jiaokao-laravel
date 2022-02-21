<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-04-13 15:51:15
 * @LastEditTime: 2021-05-24 20:14:22
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Models\Study.php
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\BaseCommon;

class Study extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'option1',
        'option2',
        'option3',
        'option4',
        'answer',
        'skills',
        'technique',
        'pic',
        'subjects',
        'type',
        'chapter',
        'created_time',
        'updated_time',
        'note'
    ];

    //主键
    public $primaryKey = 'id';
    public $moduleId = 10;
    public $modules = MODULES[10];

    public $timestamps = false;

    public $table = 'study_questions';
    public $db_prefix;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->db_prefix = DB::getConfig('prefix');
    }

    public function insertOrUpdate($data)
    {
         $baseCommon = new BaseCommon;
         $create = $baseCommon->batchInsertOrUpdate($data, $this->db_prefix . $this->table, $this->fillable);
         return $create;
    }

    public function imageLocalization()
    {
         
    }
}
