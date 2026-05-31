<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class PreTriagemTemplateSeeder extends Seeder
{
    /**
     * Seed the pre-triagem template into MotherShip.
     */
    public function run()
    {
        $promptStructure = <<<'PROMPT'
Você é um assistente estratégico com sólido conhecimentos na área jurídica, em especial para área do Lead abaixo.

Avalie tecnicamente o caso abaixo:

Área: {{title}}
Resumo: {{description}}
Observações: {{observacoes}}

Produza:

1) Fundamento jurídico aplicável
2) Requisitos necessários para a ação
3) Provas indispensáveis
4) Pontos frágeis da demanda
5) Risco processual estimado (Baixo, Médio, Alto)
6) Probabilidade estratégica de êxito (Baixa, Moderada, Alta)
7) Competência provável (Juizado, Vara Cível, Vara de Família etc.)
8) Recomendação: Prosseguir / Ajustar Estratégia / Não Recomendado

**MANDATÓRIO:** A sua resposta FINAL DEVE ser formatada estritamente em **Markdown**, utilizando bem os itens (listas), subtítulos (`##`) e **negrito** nos pontos críticos para facilitar a leitura.
PROMPT;

        $variables = [
            [
                'key' => 'title',
                'label' => 'Área / Tipo de Ação',
                'type' => 'text',
                'placeholder' => 'Ex: Direito do Consumidor - Negativação Indevida',
            ],
            [
                'key' => 'description',
                'label' => 'Resumo do Caso',
                'type' => 'textarea',
                'rows' => 5,
                'placeholder' => 'Descreva os fatos e circunstâncias relevantes...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ],
            [
                'key' => 'observacoes',
                'label' => 'Observações',
                'type' => 'textarea',
                'rows' => 5,
                'placeholder' => 'Informações adicionais...',
            ],
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'pre-triagem-lead'],
            [
                'tenant_id' => null,
                'category' => 'leads',
                'area' => 'Geral',
                'title' => 'Análise de Viabilidade',
                'description' => 'Avaliação técnica do caso com fundamento jurídico, riscos e recomendação estratégica.',
                'icon' => '🧠',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lead_viabilidade',
                'required_module' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Análise de Viabilidade" (pre-triagem-lead) atualizado com webhook lead_viabilidade.');
    }
}
