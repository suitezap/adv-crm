<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class DosimetroDanoMoralTemplateSeeder extends Seeder
{
    /**
     * Seed the dosimetro-dano-moral template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Com base na jurisprudência recente do STJ sobre Dano Moral na relação jurídica de '{{relacao_juridica}}', analise o caso: O fato é '{{tipo_dano}}'. O réu tem capacidade econômica '{{capacidade_reu}}'. A extensão do sofrimento/dano foi '{{extensao_dano}}'. Sugira uma faixa de valor indenizatório (Mínimo, Médio e Otimista) justificando com base nos critérios de: Punitive Damages (Caráter Punitivo) e Compensatory Damages (Reparação). Cite precedentes genéricos do STJ sobre casos similares.";

        $variables = [
            [
                'key' => 'tipo_dano',
                'label' => 'Tipo de Dano',
                'type' => 'text',
                'placeholder' => 'Ex: Morte, Dano Estético, Negativação...',
            ],
            [
                'key' => 'capacidade_reu',
                'label' => 'Capacidade Econômica do Réu',
                'type' => 'text',
                'placeholder' => 'Ex: Banco (Grande Porte), Pessoa Física...',
            ],
            [
                'key' => 'extensao_dano',
                'label' => 'Extensão do Dano',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => 'Ex: Cliente não pôde comprar a casa por causa do Serasa...',
            ],
            [
                'key' => 'relacao_juridica',
                'label' => 'Relação Jurídica',
                'type' => 'text',
                'placeholder' => 'Ex: Consumerista, Trabalhista, Civil...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'dosimetro-dano-moral'],
            [
                'tenant_id' => null,
                'category' => 'processual',
                'area' => 'Cível',
                'title' => 'Dosímetro de Dano Moral',
                'description' => 'Aplica Método Bifásico (STJ) na extensão do dano e sugere faixas de ganho (mínimo, médio e ótimo).',
                'icon' => '⚖️',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'ai-dosimetro-dano-moral',
                'required_module' => 'IA-Civil',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Dosímetro de Dano Moral" (dosimetro-dano-moral) criado com sucesso.');
    }
}
