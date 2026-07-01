<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class TransformadorRelatoTemplateSeeder extends Seeder
{
    /**
     * Seed the transformador-relato template into MotherShip.
     */
    public function run()
    {
        $promptStructure = <<<'PROMPT'
Você é um advogado trabalhista especialista na redação de Petições Iniciais.

Objetivo: transformar um relato informal do cliente em tópico estruturado de “Dos Fatos” e “Dos Fundamentos Jurídicos” para Reclamação Trabalhista.

Dados:

Relato do cliente:
{{ relato_cliente }}

Tese jurídica principal:
{{ tese_juridica }}

INSTRUÇÕES OBRIGATÓRIAS
- Reescreva o relato em linguagem jurídica formal.
- Organize em dois tópicos: I – Dos Fatos / II – Dos Fundamentos Jurídicos
- Estruture os fatos em ordem cronológica.
- Não invente fatos não mencionados.
- Não presuma informações inexistentes.
- Fundamente apenas com Artigos da CLT, Constituição Federal (quando pertinente), e Súmulas consolidadas do TST (somente se tiver alta segurança).
- Evite citar jurisprudência específica com número de processo.
- Faça subsunção clara entre fato e norma.
- Linguagem técnica, objetiva e própria de peça processual.
- Não inclua pedidos.
PROMPT;

        $variables = [
            [
                'key'         => 'tese_juridica',
                'label'       => 'Tese Jurídica',
                'type'        => 'text',
                'placeholder' => 'Ex: Dano Moral ou Insalubridade',
            ],
            [
                'key'         => 'relato_cliente',
                'label'       => 'Relato do Cliente (Bruto)',
                'type'        => 'textarea',
                'rows'        => 6,
                'placeholder' => 'Cole o texto sujo da entrevista ou WhatsApp',
            ],
            [
                'key'         => 'tenant_id',
                'label'       => 'Tenant ID',
                'type'        => 'hidden',
                'placeholder' => '',
            ],
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'transformador-relato'],
            [
                'tenant_id'        => null,
                'category'         => 'pecas',
                'area'             => 'Trabalhista',
                'title'            => 'Transformador de Relato',
                'description'      => 'Transforma relatos informais de clientes em tópicos formais para Petição Inicial Trabalhista.',
                'icon'             => '📝',
                'prompt_structure' => $promptStructure,
                'variables'        => $variables,
                'n8n_webhook_url'  => 'ai-transformador-relato',
                'required_module'  => 'IA-Trabalhista',
                'is_active'        => true,
            ]
        );

        $this->command->info('✅ Template "Transformador de Relato" (transformador-relato) atualizado com sucesso.');
    }
}
