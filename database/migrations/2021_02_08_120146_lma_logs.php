<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_logs', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->bigIncrements('id')->nullable(false)->comment('主键');
			$table->unsignedBigInteger('uid')->nullable(false)->default(1)->comment('操作用户id 1 => 系统超管');
			$table->unsignedBigInteger('tid')->nullable(false)->comment('操作数据id');
			$table->boolean('mid')->nullable(false)->comment('模块id');
			$table->text('details')->nullable()->comment('源数据');
			$table->unsignedInteger('ip')->nullable(false)->comment('登录ip  （INET_ATON 存入）');
			$table->boolean('type')->nullable(false)->comment('类型1=> 创建；2=> 更新；3=>删除');
			$table->unsignedInteger('created_time')->nullable(false)->comment('创建时间');
			$table->string('note', 255)->nullable()->default(null)->comment('说明备注');
			
        });

        DB::statement("alter table `lma_logs` comment '日志'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_logs');
    }
}
