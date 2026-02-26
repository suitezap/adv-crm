<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class AnalistaSubrogacaoTemplateSeeder extends Seeder
{
    /**
     * Seed the analista-subrogacao template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Atue como um especialista em Direito de Família. O regime é {{regime_bens}}, casamento em {{data_casamento}}. O bem '{{descricao_bem}}' foi adquirido em {{data_aquisicao}}. A origem do dinheiro foi '{{origem_recurso}}'. Analise juridicamente: Este bem deve ser partilhado (meação) ou é bem particular? Cite os artigos do Código Civil (ex: 1.659 ou 1.660) que justificam se há ou não sub-rogação.";

        $variables = [
            [
                'key' => 'regime_bens',
                'label' => 'Regime de Bens',
                'type' => 'text',
                'placeholder' => 'Ex: Comunhão Parcial, Total ou Separação',
            ],
            [
                'key' => 'data_casamento',
                'label' => 'Data do Casamento',
                'type' => 'text',
                'placeholder' => 'Ex: 15/04/2010',
            ],
            [
                'key' => 'descricao_bem',
                'label' => 'Descrição do Bem',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => 'Ex: Apartamento em SP, Veículo Hilux...',
            ],
            [
                'key' => 'data_aquisicao',
                'label' => 'Data da Aquisição',
                'type' => 'text',
                'placeholder' => 'Ex: 20/08/2018',
            ],
            [
                'key' => 'origem_recurso',
                'label' => 'Origem do Recurso',
                'type' => 'textarea',
                'rows' => 5,
                'placeholder' => 'Ex: Dinheiro recebido de herança da avó vendido...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'analista-subrogacao'],
            [
                'tenant_id' => null,
                'category' => 'processual',
                'area' => 'Família',
                'title' => 'Analista de Sub-rogação',
                'description' => 'Analisa à luz do Código Civil, de acordo com o regime, se um bem é partilhável (meação) ou particular (sub-rogação).',
                'icon' => '🧮',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'ai-analista-subrogacao',
                'required_module' => 'IA-Familia',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Analista de Sub-rogação" (analista-subrogacao) criado com sucesso.');
    }
}
