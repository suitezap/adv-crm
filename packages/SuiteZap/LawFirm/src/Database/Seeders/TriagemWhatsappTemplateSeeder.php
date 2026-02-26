<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class TriagemWhatsappTemplateSeeder extends Seeder
{
    /**
     * Seed the Assistente de Triagem WhatsApp template into MotherShip.
     */
    public function run()
    {
        $promptStructure = <<<'PROMPT'
Você é um assistente jurídico de triagem via WhatsApp.

Sua missão é realizar a pré-qualificação do Lead com base nas informações recebidas pelo canal WhatsApp.

Dados recebidos:
Nome: {{nome_lead}}
Mensagem inicial: {{mensagem_inicial}}
Área do direito (se informada): {{area_direito}}

Com base nas informações acima:
1. Identifique se o caso tem viabilidade jurídica aparente
2. Classifique a área do direito (Trabalhista, Cível, Família, Previdenciário, Contratos, Outro)
3. Indique o nível de urgência (Baixa, Média, Alta)
4. Liste os documentos essenciais a serem solicitados
5. Sugira próximos passos para o atendimento

Seja objetivo, profissional e empático na análise.
PROMPT;

        $variables = [
            [
                'key' => 'nome_lead',
                'label' => 'Nome do Lead',
                'type' => 'text',
                'placeholder' => 'Ex: João da Silva',
            ],
            [
                'key' => 'mensagem_inicial',
                'label' => 'Mensagem do WhatsApp',
                'type' => 'textarea',
                'rows' => 6,
                'placeholder' => 'Cole aqui a mensagem inicial enviada pelo lead via WhatsApp...',
            ],
            [
                'key' => 'area_direito',
                'label' => 'Área do Direito (opcional)',
                'type' => 'text',
                'placeholder' => 'Ex: Trabalhista, Cível, Família...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ],
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'triagem-whatsapp'],
            [
                'tenant_id' => null,
                'category' => 'whatsapp',
                'area' => 'Geral',
                'title' => 'Assistente de Triagem WhatsApp',
                'description' => 'Este assistente utiliza IA para realizar triagens, pré-qualificando os Leads pelo canal do WhatsApp. Criando automaticamente, novas oportunidades no painel de Leads.',
                'icon' => '💬',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lead_triag_whats',
                'required_module' => 'IA-WhatsApp',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Assistente de Triagem WhatsApp" (triagem-whatsapp) criado/atualizado com sucesso.');
    }
}
