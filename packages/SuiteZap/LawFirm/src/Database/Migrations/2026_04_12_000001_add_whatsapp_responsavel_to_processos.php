<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            if (!Schema::hasColumn('processos', 'whatsapp_responsavel')) {
                $table->string('whatsapp_responsavel', 20)->nullable()->after('advogado_responsavel_oab')
                    ->comment('WhatsApp do advogado interno responsável pelo processo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            if (Schema::hasColumn('processos', 'whatsapp_responsavel')) {
                $table->dropColumn('whatsapp_responsavel');
            }
        });
    }
};
