<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_configs', function (Blueprint $table) {
             $table->engine = 'InnoDB';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->increments('id')->nullable(false)->comment('主键id');
			$table->string('name', 100)->nullable()->default(null)->comment('配置名称');
			$table->string('tags', 50)->nullable()->default(null)->comment('标签名称');
			$table->string('param_id', 200)->nullable()->default('')->comment('配置键名（唯一性）');
			$table->string('param_key', 200)->nullable()->default('')->comment('配置键名（唯一性）');
			$table->string('param_val', 500)->nullable()->default('')->comment('配置键值');
			$table->timestamp('created_at')->comment('创建时间');
			$table->timestamp('updated_at')->comment('更新时间');
			$table->string('note', 255)->nullable()->default(null)->comment('备注提示信息');
			
        });

        DB::statement("alter table `lma_configs` comment '系统配置'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_configs');
    }
}
