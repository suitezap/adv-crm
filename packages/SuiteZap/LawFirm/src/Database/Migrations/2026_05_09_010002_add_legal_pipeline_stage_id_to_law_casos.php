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
        if (Schema::hasColumn('law_casos', 'legal_pipeline_stage_id')) {
            return;
        }

        Schema::table('law_casos', function (Blueprint $table) {
            $table->unsignedBigInteger('legal_pipeline_stage_id')
                ->nullable()
                ->after('status');

            $table->foreign('legal_pipeline_stage_id')
                ->references('id')->on('law_legal_pipeline_stages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('law_casos', function (Blueprint $table) {
            $table->dropForeign(['legal_pipeline_stage_id']);
            $table->dropColumn('legal_pipeline_stage_id');
        });
    }
};
