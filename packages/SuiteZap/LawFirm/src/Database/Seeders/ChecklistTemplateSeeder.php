<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class ChecklistTemplateSeeder extends Seeder
{
    /**
     * Seed the Qualificação (pre-triagem-checklist) template.
     * Webhook: lead_qualificacao
     */
    public function run()
    {
        $promptStructure = <<<'PROMPT'
Você é um assistente estratégico especializado em triagem jurídica.
Analise os dados abaixo do lead e forneça um relatório preliminar de qualificação:

Título do Caso: {{title}}
Descrição do Caso: {{description}}
Tenant ID: {{tenant_id}}
Observações: {{observacoes}}

Forneça:
1. Classificação da Área Jurídica (Trabalhista, Cível, Família, etc.)
2. Viabilidade da Causa (0-10) com justificativa
3. Riscos Imediatos identificados
4. Recomendações de Abordagem
5. Documentos Sugeridos para Solicitar ao cliente
6. Estimativa de Complexidade (Baixa/Média/Alta)

Seja objetivo e profissional.
PROMPT;

        $variables = [
            [
                'key' => 'title',
                'label' => 'Título do Caso',
                'type' => 'text',
                'placeholder' => 'Ex: Reclamação Trabalhista',
            ],
            [
                'key' => 'description',
                'label' => 'Descrição do Caso',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => 'Descreva o relato do cliente...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'text',
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
            ['slug' => 'pre-triagem-checklist'],
            [
                'tenant_id' => null, // Global
                'category' => 'leads',
                'title' => 'Qualificação Jurídica',
                'description' => 'Triagem e qualificação jurídica do Lead com análise de viabilidade.',
                'icon' => '📋',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lead_qualificacao',
                'required_module' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Qualificação Jurídica" (pre-triagem-checklist) criado/atualizado com webhook lead_qualificacao.');
    }
}
