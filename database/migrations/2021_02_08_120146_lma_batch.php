<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_batch', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->bigIncrements('id')->nullable(false)->comment('主键id');
			$table->boolean('life')->nullable(false)->default(1)->comment('生命周期(公共常量 LIFE)');
			$table->boolean('squad')->nullable(false)->default(1)->comment('班种 1=> 白班， 3=> 夜班');
			$table->string('batch', 50)->nullable(false)->comment('批号');
			$table->unsignedInteger('updated_time')->nullable(false)->comment('更新时间');
			$table->unsignedInteger('created_time')->nullable(false)->comment('创建时间');
			$table->string('note', 255)->nullable()->default(null)->comment('备注说明');
			
        });

        DB::statement("alter table `lma_batch` comment '批号'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_batch');
    }
}
