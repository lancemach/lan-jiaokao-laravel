<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaLogin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_login', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->bigIncrements('id')->nullable(false)->comment('主键');
			$table->bigInteger('uid')->nullable(false)->comment('用户id');
			$table->unsignedInteger('created_time')->nullable(false)->comment('创建时间');
			$table->unsignedInteger('ip')->nullable(false)->comment('登录ip  （INET_ATON 存入）');
			$table->boolean('type')->nullable(false)->comment('登录类型 1=> 微信小程序');
			
        });

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_login');
    }
}
