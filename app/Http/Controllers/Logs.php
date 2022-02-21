<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-02-02 11:03:01
 * @LastEditTime: 2021-03-30 15:40:28
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: .\app\Http\Controllers\Logs.php
 */

namespace App\Http\Controllers;

use App\Models\Logs as LogsModel;
use Illuminate\Support\Facades\DB;

class Logs extends Controller
{
     /**
     * 创建操作日志
     */
    public function create($array)
    {
        extract($array);
        $Logs = new LogsModel;
        $Logs->uid = $uid ?? 1;
        $Logs->mid = $mid;
        $Logs->tid = $tid;
        $Logs->details = !empty($details) ? $details : '';
        $Logs->ip = DB::raw('INET_ATON("'. request()->ip() .'")');
        $Logs->created_time = SYSTEM_TIME;
        $Logs->type = $type;
        $Logs->note = !empty($note) ? $note : '';
        return $Logs->save();
    }
}
