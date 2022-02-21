<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class LmaPasswordResets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lma_password_resets', function (Blueprint $table) {
             $table->engine = 'MyISAM';
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
            // CONTENT
            $table->string('email', 248)->nullable(false)->comment('');
			$table->string('token', 248)->nullable(false)->comment('');
			$table->timestamp('created_at')->comment('');
			$table->index('email', 'lma_password_resets_email_index');
			
        });

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lma_password_resets');
    }
}
