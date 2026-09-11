<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds 4 TEXT columns to store full AI assistant output per type,
     * enabling cross-assistant context injection (v3.50+).
     */
    public function up(): void
    {
        Schema::table('lead_triagem', function (Blueprint $table) {
            // Full raw AI output from "Análise de Viabilidade" (🧠)
            $table->text('viabilidade')->nullable()->after('objetivo');
            // Full raw AI output from "Qualificação Jurídica" (📋)
            $table->text('qualificacao')->nullable()->after('viabilidade');
            // Full raw AI output from "Sugestão de Proposta" (📄)
            $table->text('proposta')->nullable()->after('qualificacao');
            // Full raw AI output from "Negociação & Conversão" (💬)
            $table->text('negociacao')->nullable()->after('proposta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_triagem', function (Blueprint $table) {
            $table->dropColumn(['viabilidade', 'qualificacao', 'proposta', 'negociacao']);
        });
    }
};
