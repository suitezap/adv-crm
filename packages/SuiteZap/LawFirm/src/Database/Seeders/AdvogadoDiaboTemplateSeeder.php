<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class AdvogadoDiaboTemplateSeeder extends Seeder
{
    /**
     * Seed the advogado-diabo template into MotherShip.
     */
    public function run()
    {
        $promptStructure = 'Atue como o advogado da Parte Contrária. Eu vou colar abaixo a minha Petição Inicial (Ação: {{tipo_acao}}). Sua missão é encontrar todas as brechas. Liste: 1. Preliminares processuais que eu esqueci de blindar (Prescrição, Decadência, Incompetência); 2. Argumentos de mérito que você usaria para derrubar minha tese. Seja impiedoso e aponte onde minha prova está fraca.
  
Petição Inicial:
{{texto_peticao}}';

        $variables = [
            [
                'key'         => 'tipo_acao',
                'label'       => 'Tipo de Ação',
                'type'        => 'text',
                'placeholder' => 'Ex: Reintegração de Posse, Indenizatória...',
            ],
            [
                'key'         => 'texto_peticao',
                'label'       => 'Petição Inicial',
                'type'        => 'textarea',
                'rows'        => 20,
                'placeholder' => 'Cole a minuta da sua Petição Inicial aqui...',
            ],
            [
                'key'         => 'tenant_id',
                'label'       => 'Tenant ID',
                'type'        => 'hidden',
                'placeholder' => '',
            ],
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'advogado-diabo'],
            [
                'tenant_id'        => null,
                'category'         => 'pecas',
                'area'             => 'Geral',
                'title'            => 'Advogado do Diabo',
                'description'      => 'Simula a contestação da parte contrária apontando brechas, prescrição e fraqueza de provas na sua petição.',
                'icon'             => '😈',
                'prompt_structure' => $promptStructure,
                'variables'        => $variables,
                'n8n_webhook_url'  => 'ai-advogado-diabo',
                'required_module'  => null,
                'is_active'        => true,
            ]
        );

        $this->command->info('✅ Template "Advogado do Diabo" (advogado-diabo) criado com sucesso.');
    }
}
