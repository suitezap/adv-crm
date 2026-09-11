<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class NegociacaoTemplateSeeder extends Seeder
{
    /**
     * Seed the Negociação & Conversão (script-vendas) template.
     * Webhook: lead_negociacao
     */
    public function run()
    {
        $promptStructure = <<<'PROMPT'
Você é um especialista em conversão com sólidos conhecimentos na área jurídica, em especial para área do Lead do caso abaixo.

Crie um roteiro estratégico de conversa para o advogado utilizar no contato com o Lead abaixo:

Área: {{title}}
Resumo: {{description}}
Observações: {{observacoes}}

Estruture:

1) Abertura estratégica (gerar confiança)
2) Perguntas para aprofundamento
3) Demonstração de autoridade
4) Explicação clara da solução jurídica
5) Tratamento de possíveis objeções
6) Frase de fechamento
7) Chamada para ação
PROMPT;

        $variables = [
            [
                'key'         => 'title',
                'label'       => 'Área / Tipo de Ação',
                'type'        => 'text',
                'placeholder' => 'Ex: Direito do Consumidor - Negativação Indevida',
            ],
            [
                'key'         => 'description',
                'label'       => 'Resumo do Caso',
                'type'        => 'textarea',
                'rows'        => 5,
                'placeholder' => 'Descreva os fatos e circunstâncias relevantes...',
            ],
            [
                'key'         => 'tenant_id',
                'label'       => 'Tenant ID',
                'type'        => 'hidden',
                'placeholder' => '',
            ],
            [
                'key'         => 'observacoes',
                'label'       => 'Observações',
                'type'        => 'textarea',
                'rows'        => 5,
                'placeholder' => 'Informações adicionais...',
            ],
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'script-vendas'],
            [
                'tenant_id'        => null,
                'category'         => 'leads',
                'area'             => 'Geral',
                'title'            => 'Negociação & Conversão',
                'description'      => 'Roteiro estratégico de conversa para conversão do Lead em cliente.',
                'icon'             => '💬',
                'prompt_structure' => $promptStructure,
                'variables'        => $variables,
                'n8n_webhook_url'  => 'lead_negociacao',
                'required_module'  => null,
                'is_active'        => true,
            ]
        );

        $this->command->info('✅ Template "Negociação & Conversão" (script-vendas) criado/atualizado com webhook lead_negociacao.');
    }
}
