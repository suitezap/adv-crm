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
        Schema::table('law_document_templates', function (Blueprint $table) {
            // Marks a template as a layout (Cabeçalho or Rodapé) instead of a document body.
            // Layout templates are injected around the document when the user enables Cabeçalho/Rodapé.
            $table->boolean('is_layout')->default(false)->after('ativo')
                ->comment('true = layout template (cabecalho/rodape); false = document body template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('law_document_templates', function (Blueprint $table) {
            $table->dropColumn('is_layout');
        });
    }
};
