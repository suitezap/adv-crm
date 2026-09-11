<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\Legal\Models\LegalPipeline;
use SuiteZap\LawFirm\Legal\Models\LegalPipelineStage;

class LegalPipelineSeeder extends Seeder
{
    /**
     * Seed the Legal Pipeline with 12 operational stages.
     * Idempotent — safe to run multiple times.
     */
    public function run(): void
    {
        $pipelineName = 'Kanban Jurídico';

        if (LegalPipeline::where('name', $pipelineName)->exists()) {
            return;
        }

        $pipeline = LegalPipeline::create(['name' => $pipelineName]);

        $stages = [
            ['sort_order' => 1,  'code' => 'novo_caso',         'name' => 'Novo Caso',              'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 2,  'code' => 'em_analise',         'name' => 'Em Análise',              'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 3,  'code' => 'aguard_cliente',     'name' => 'Aguardando Cliente',         'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 4,  'code' => 'em_prod_juridica',   'name' => 'Em Produção Jurídica',      'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 5,  'code' => 'protocolado',        'name' => 'Protocolado',             'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 6,  'code' => 'aguard_judiciario',  'name' => 'Aguardando Judiciário',     'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 7,  'code' => 'prazo_acao',         'name' => 'Prazo/Ação Necessária',  'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 8,  'code' => 'audiencia',          'name' => 'Audiência',              'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 9,  'code' => 'sentenca',           'name' => 'Sentença',               'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 10, 'code' => 'recurso',            'name' => 'Recurso',                 'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 11, 'code' => 'execucao',           'name' => 'Execução',               'color' => 'bg-gray-100 text-gray-600'],
            ['sort_order' => 12, 'code' => 'encerrado',          'name' => 'Encerrado',               'color' => 'bg-gray-100 text-gray-600'],
        ];

        foreach ($stages as $stage) {
            LegalPipelineStage::create(array_merge($stage, [
                'pipeline_id' => $pipeline->id,
            ]));
        }
    }
}
