<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class PropostaTemplateSeeder extends Seeder
{
    /**
     * Seed the Sugestão de Proposta (gerador-proposta) template.
     * Webhook: lead_proposta
     */
    public function run()
    {
        $promptStructure = <<<'PROMPT'
Você é um assistente estratégico com sólido conhecimentos na área jurídica, em especial para área do Lead do caso abaixo.

Com base no caso abaixo, elabore:

1) Tipo de atuação sugerida (Extrajudicial, Judicial, Ambos)
2) Complexidade estimada (Baixa, Média, Alta)
3) Modelo sugerido de honorários (Fixo, Êxito, Misto)
4) Estrutura recomendada de cobrança
5) Argumentação para justificar o valor ao cliente
6) Próximos passos formais (documentos, contrato, prazo)

Caso:

Área: {{title}}
Resumo: {{description}}
Observações: {{observacoes}}

**OBRIGATÓRIO:** Formate toda a sua resposta em **Markdown**, usando `##` para subtítulos de cada item, **negrito** nos termos técnicos e listas (`-`) para enumerar pontos. Não retorne texto simples.
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
            ['slug' => 'gerador-proposta'],
            [
                'tenant_id' => null,
                'category' => 'leads',
                'area' => 'Geral',
                'title' => 'Sugestão de Proposta',
                'description' => 'Elaboração de proposta de honorários com base na análise técnica do caso.',
                'icon' => '📄',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lead_proposta',
                'required_module' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Sugestão de Proposta" (gerador-proposta) criado/atualizado com webhook lead_proposta.');
    }
}
