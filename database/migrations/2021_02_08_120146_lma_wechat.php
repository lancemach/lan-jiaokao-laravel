<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaWechat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_wechat', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->bigIncrements('id')->nullable(false)->comment('主键id');
			$table->unsignedBigInteger('uid')->nullable(false)->default(0)->comment('用户id');
			$table->string('nickName', 255)->nullable()->default(null)->comment('用户昵称');
			$table->boolean('gender')->nullable()->default(null)->comment('性别 0：未知、1：男、2：女');
			$table->string('language', 20)->nullable()->default(null)->comment('语言标识');
			$table->string('city', 50)->nullable()->default(null)->comment('城市');
			$table->string('province', 50)->nullable()->default(null)->comment('省份');
			$table->string('country', 50)->nullable()->default(null)->comment('国家');
			$table->string('avatarUrl', 255)->nullable()->default(null)->comment('头像地址');
			$table->string('unionId', 50)->nullable()->default(null)->comment('');
			$table->string('openId', 255)->nullable(false)->comment('');
			$table->integer('updated_time')->nullable(false)->comment('更新时间');
			$table->integer('created_time')->nullable(false)->comment('创建时间');
			
        });

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_wechat');
    }
}
