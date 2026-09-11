<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add caso_id to law_processo_anexos for Zero-Copy document sharing.
     */
    public function up(): void
    {
        if (Schema::hasColumn('law_processo_anexos', 'caso_id')) {
            return;
        }

        Schema::table('law_processo_anexos', function (Blueprint $table) {
            $table->unsignedBigInteger('caso_id')->nullable()->after('processo_id');

            $table->foreign('caso_id')
                ->references('id')->on('law_casos')
                ->onDelete('set null');

            $table->index('caso_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('law_processo_anexos', 'caso_id')) {
            return;
        }

        Schema::table('law_processo_anexos', function (Blueprint $table) {
            $table->dropForeign(['caso_id']);
            $table->dropColumn('caso_id');
        });
    }
};
