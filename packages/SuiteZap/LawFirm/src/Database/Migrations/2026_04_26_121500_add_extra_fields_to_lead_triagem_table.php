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
        Schema::table('lead_triagem', function (Blueprint $table) {
            $table->string('risco')->nullable();
            $table->string('probabilidade')->nullable();
            $table->text('recomendacao')->nullable();
            $table->string('competencia')->nullable();
            $table->text('risco_operacional')->nullable();
            $table->text('informacoes_faltantes')->nullable();
            $table->text('perguntas_chave')->nullable();
            $table->string('tipo_atuacao')->nullable();
            $table->string('complexidade')->nullable();
            $table->string('modelo_honorarios')->nullable();
            $table->text('estrategia_cobranca')->nullable();
            $table->text('argumentacao_valor')->nullable();
            $table->text('abordagem_abertura')->nullable();
            $table->text('estrategia_objecoes')->nullable();
            $table->text('frase_fechamento')->nullable();
            $table->text('cta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_triagem', function (Blueprint $table) {
            $table->dropColumn([
                'risco',
                'probabilidade',
                'recomendacao',
                'competencia',
                'risco_operacional',
                'informacoes_faltantes',
                'perguntas_chave',
                'tipo_atuacao',
                'complexidade',
                'modelo_honorarios',
                'estrategia_cobranca',
                'argumentacao_valor',
                'abordagem_abertura',
                'estrategia_objecoes',
                'frase_fechamento',
                'cta',
            ]);
        });
    }
};
