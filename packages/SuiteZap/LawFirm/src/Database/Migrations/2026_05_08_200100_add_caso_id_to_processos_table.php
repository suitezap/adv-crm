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
        if (Schema::hasColumn('processos', 'caso_id')) {
            return;
        }

        Schema::table('processos', function (Blueprint $table) {
            $table->unsignedBigInteger('caso_id')->nullable()->after('id');

            $table->foreign('caso_id')
                ->references('id')->on('law_casos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('processos', 'caso_id')) {
            return;
        }

        Schema::table('processos', function (Blueprint $table) {
            $table->dropForeign(['caso_id']);
            $table->dropColumn('caso_id');
        });
    }
};
