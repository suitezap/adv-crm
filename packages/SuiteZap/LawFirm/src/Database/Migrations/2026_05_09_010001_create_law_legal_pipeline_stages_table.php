<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('law_legal_pipeline_stages')) {
            return;
        }

        Schema::create('law_legal_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pipeline_id');
            $table->string('name', 50);
            $table->string('code', 50)->unique();
            $table->smallInteger('sort_order')->default(0);
            $table->string('color', 60)->nullable()->comment('Tailwind badge classes');
            $table->timestamps();

            $table->foreign('pipeline_id')
                ->references('id')->on('law_legal_pipelines')
                ->onDelete('cascade');

            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_legal_pipeline_stages');
    }
};
