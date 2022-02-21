<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_users', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->bigIncrements('id')->nullable(false)->comment('主键');
			$table->string('name', 12)->nullable()->default(null)->comment('姓名');
			$table->string('username', 24)->nullable(false)->comment('用户名');
			$table->boolean('group_id')->nullable()->default(0)->comment('管理组id');
			$table->boolean('squad_id')->nullable()->default(0)->comment('部门组id');
			$table->string('secret_salt', 24)->nullable(false)->comment('盐');
			$table->string('password', 248)->nullable(false)->comment('密码');
			$table->char('phone', 11)->nullable()->default(null)->comment('手机号');
			$table->integer('job_no')->nullable()->default(null)->comment('工号');
			$table->unsignedInteger('updated_time')->nullable(false)->comment('更新时间');
			$table->unsignedInteger('created_time')->nullable(false)->comment('创建时间');
			
        });

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_users');
    }
}
