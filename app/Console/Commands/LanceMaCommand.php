<?php
/*
 * @Author: Lance Ma
 * @Date: 2021-05-12 15:55:26
 * @LastEditTime: 2021-05-12 16:04:43
 * @LastEditors: Please set LastEditors
 * @Description: 自定义 artisan 命令
 * @FilePath: .\app\Console\Commands\LanceMaCommand.php
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LanceMaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    // public function handle()
    // {
    //     return 0;
    // }

    /**
     * Lance Ma 定义命令执行 **
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'qweqweqwewq';
    }
}
