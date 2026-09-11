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
        Schema::table('law_processo_whatsapp_messages', function (Blueprint $table) {
            // FK to the GED Anexo so we can use the secure proxy (admin.processos.download_attachment)
            $table->unsignedBigInteger('anexo_id')->nullable()->after('media_type');
            // Tag origin for the Anexo record in GED (e.g. 'whatsapp_media')
            $table->string('media_source')->nullable()->after('anexo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('law_processo_whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['anexo_id', 'media_source']);
        });
    }
};
