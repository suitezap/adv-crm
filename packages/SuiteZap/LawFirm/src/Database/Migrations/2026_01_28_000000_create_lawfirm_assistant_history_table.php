<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('lawfirm_assistant_history')) {
            Schema::create('lawfirm_assistant_history', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable();
                $table->unsignedBigInteger('template_id');
                $table->json('input_data')->nullable();
                $table->longText('generated_content')->nullable();
                $table->string('execution_mode')->default('local');
                $table->string('status')->default('completed');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('lawfirm_assistant_history');
    }
};
