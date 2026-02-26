<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class RoteirizadorAudienciaTemplateSeeder extends Seeder
{
    /**
     * Seed the pre-triagem template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Atue como um Juiz do Trabalho experiente. Para provar o objetivo '{{objetivo_prova}}' baseado nos fatos '{{resumo_fatos}}', liste 5 perguntas estratégicas e capciosas para fazer à testemunha da parte contrária e 5 perguntas para fazer ao preposto da empresa.";

        $variables = [
            [
                'key' => 'objetivo_prova',
                'label' => 'Objetivo da Prova',
                'type' => 'text',
                'placeholder' => 'Ex: Provar Vínculo de Emprego',
            ],
            [
                'key' => 'resumo_fatos',
                'label' => 'Resumo dos Fatos',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => 'Ex: O cliente trabalhava como PJ mas cumpria horário e tinha chefe.',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'roteirizador-audiencia'],
            [
                'tenant_id' => null,
                'category' => 'processual',
                'area' => 'Trabalhista',
                'title' => 'Roteirizador de Audiência',
                'description' => 'Gera um roteiro de perguntas estratégicas para testemunhas e prepostos baseado no resumo do caso e objetivo da prova.',
                'icon' => '🎤',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'ai-roteirizador-audiencia',
                'required_module' => 'IA-Trabalhista',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Roteirizador de Audiência" (roteirizador-audiencia) atualizado com sucesso.');
    }
}
