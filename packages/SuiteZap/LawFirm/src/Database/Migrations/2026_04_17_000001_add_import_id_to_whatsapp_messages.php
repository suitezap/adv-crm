<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('law_processo_whatsapp_messages', 'import_id')) {
            return;
        }

        Schema::table('law_processo_whatsapp_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('import_id')->nullable()->after('processo_id');

            $table->foreign('import_id')
                ->references('id')
                ->on('law_whatsapp_imports')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('law_processo_whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropColumn('import_id');
        });
    }
};
