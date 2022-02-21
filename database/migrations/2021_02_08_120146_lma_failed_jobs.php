<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaFailedJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_failed_jobs', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->bigIncrements('id')->nullable(false)->comment('');
			$table->string('uuid', 248)->nullable(false)->comment('');
			$table->text('connection')->nullable(false)->comment('');
			$table->text('queue')->nullable(false)->comment('');
			$table->longText('payload')->nullable(false)->comment('');
			$table->longText('exception')->nullable(false)->comment('');
			$table->timestamp('failed_at')->useCurrent()->comment('');
			$table->unique('uuid', 'lma_failed_jobs_uuid_unique');
			
        });

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_failed_jobs');
    }
}
