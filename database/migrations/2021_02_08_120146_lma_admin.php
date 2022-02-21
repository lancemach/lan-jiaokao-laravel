<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_admin', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->increments('id')->nullable(false)->comment('主键');
			$table->unsignedInteger('updated_time')->nullable(false)->comment('更新时间');
			$table->unsignedInteger('created_time')->nullable(false)->comment('创建时间');
			
        });

        DB::statement("alter table `lma_admin` comment '管理员'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_admin');
    }
}
