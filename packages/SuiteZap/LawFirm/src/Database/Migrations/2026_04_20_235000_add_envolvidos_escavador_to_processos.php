<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds the envolvidos_escavador text column to store
     * parties and lawyers imported from the Escavador API.
     */
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->text('envolvidos_escavador')->nullable()->after('opposing_party_document')
                ->comment('Partes e advogados importados da Capa do Processo via API Escavador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn('envolvidos_escavador');
        });
    }
};
