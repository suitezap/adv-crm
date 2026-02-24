<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class GeradorQuesitosMedicosTemplateSeeder extends Seeder
{
    /**
     * Seed the gerador-quesitos-medicos template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Atue como um médico perito assistente. Idade do segurado: {{idade}}. O cliente tem a doença '{{doenca_cid}}' e trabalha como '{{profissao_habitual}}'. Elabore 10 quesitos técnicos e estratégicos para o perito judicial que evidenciem que essa patologia específica impede o exercício desta profissão, focando em limitações de movimento, esforço ou psíquicas.";

        $variables = [
            [
                'key' => 'doenca_cid',
                'label' => 'Doença (CID)',
                'type' => 'text',
                'placeholder' => 'Ex: M51.1 - Transtornos de discos lombares e de outros discos intervertebrais com radiculopatia',
            ],
            [
                'key' => 'profissao_habitual',
                'label' => 'Profissão Habitual',
                'type' => 'text',
                'placeholder' => 'Ex: Pedreiro, Motorista, Auxiliar Administrativo',
            ],
            [
                'key' => 'idade',
                'label' => 'Idade',
                'type' => 'text',
                'placeholder' => 'Ex: 55 anos',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'gerador-quesitos-medicos'],
            [
                'tenant_id' => null,
                'category' => 'pecas',
                'title' => 'Gerador de Quesitos Médicos',
                'description' => 'Gera requisitos técnicos para perícia médica evidenciando incapacidade para o trabalho.',
                'icon' => '🩺',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lawfirm-gerador-quesitos-medicos',
                'required_module' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Gerador de Quesitos Médicos" (gerador-quesitos-medicos) criado com sucesso.');
    }
}
