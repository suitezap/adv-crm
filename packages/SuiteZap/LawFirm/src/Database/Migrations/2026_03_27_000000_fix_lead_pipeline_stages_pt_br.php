<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correção de dados: Traduz nomes dos estágios do funil para pt_BR.
 *
 * Problema: O PipelineSeeder gravou os estágios usando locale incorreto,
 * resultando em names como "installer::app.seeders.lead.pipeline.pipeline-stages.new".
 *
 * Solução: Atualiza os nomes baseado na coluna `code` (que é imutável e em inglês),
 * sobrescrevendo apenas os registros que estão com o valor bruto de chave de tradução
 * ou que ainda estão em inglês.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Só executa se a tabela existir para garantir idempotência em ambientes novos
        if (! Schema::hasTable('lead_pipeline_stages')) {
            return;
        }

        $stageNames = [
            'new'         => 'Novo',
            'follow-up'   => 'Acompanhamento',
            'prospect'    => 'Qualificado',
            'negotiation' => 'Negociação',
            'won'         => 'Ganho',
            'lost'        => 'Perdido',
        ];

        foreach ($stageNames as $code => $name) {
            DB::table('lead_pipeline_stages')
                ->where('code', $code)
                ->update(['name' => $name]);
        }

        // Corrige também o nome do pipeline padrão
        if (Schema::hasTable('lead_pipelines')) {
            DB::table('lead_pipelines')
                ->where('is_default', 1)
                ->update(['name' => 'Funil Padrão']);
        }
    }

    public function down(): void
    {
        // Reversão opcional (para inglês)
        if (! Schema::hasTable('lead_pipeline_stages')) {
            return;
        }

        $stageNamesEn = [
            'new'         => 'New',
            'follow-up'   => 'Follow Up',
            'prospect'    => 'Prospect',
            'negotiation' => 'Negotiation',
            'won'         => 'Won',
            'lost'        => 'Lost',
        ];

        foreach ($stageNamesEn as $code => $name) {
            DB::table('lead_pipeline_stages')
                ->where('code', $code)
                ->update(['name' => $name]);
        }
    }
};
