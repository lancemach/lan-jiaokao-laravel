<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-03-07 12:15:05
 * @LastEditTime: 2021-04-13 16:04:15
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Models\Products.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\BaseCommon;

class Products extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'number',
    //     'title',
    //     'station_no',
    //     'life',
    //     'critical',
    //     'capacity',
    //     'created_time',
    //     'updated_time',
    //     'note'
    // ];

    /**
     * 用来向表中插入数据的字段
     *
     * @var array
     */
    protected $tableColumns = [
        'number',
        'title',
        'station_no',
        'life',
        'created_time',
        'updated_time'
    ];
    
    protected $table = 'products';
    protected $db_prefix;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $db_prefix = DB::getConfig('prefix');
    }

    //主键
    public $primaryKey = 'id';
    public $moduleId = 10;
    public $modules = MODULES[10];

    public $timestamps = false;

    public function insertOrUpdate($data)
    {
         $baseCommon = new BaseCommon;
         $create = $baseCommon->batchInsertOrUpdate($data, $this->db_prefix . $this->table, $this->tableColumns);
         return $create;
    }

}
