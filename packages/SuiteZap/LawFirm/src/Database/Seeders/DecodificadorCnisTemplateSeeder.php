<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class DecodificadorCnisTemplateSeeder extends Seeder
{
    /**
     * Seed the decodificador-cnis template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Analise o trecho do CNIS abaixo. Identifique todas as siglas de indicadores de pendência (ex: AEXT-VI, PEXT, PREC). Para cada uma, explique o significado jurídico e, principalmente, qual documento o advogado deve juntar para sanar o erro (ex: Carteira de Trabalho, Guias de Recolhimento).\n\nTexto extraído do CNIS:\n\n{{texto_cnis}}";

        $variables = [
            [
                'key' => 'texto_cnis',
                'label' => 'Texto CNIS',
                'type' => 'textarea',
                'rows' => 20,
                'placeholder' => 'Cole o conteúdo do CNIS ou indicadores aqui...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'decodificador-cnis'],
            [
                'tenant_id' => null,
                'category' => 'processual',
                'title' => 'Decodificador de CNIS',
                'description' => 'Identifica pendências no CNIS, explica os indicadores e orienta como saná-los antes do protocolo.',
                'icon' => '🔍',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lawfirm-decodificador-cnis',
                'required_module' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Decodificador de CNIS" (decodificador-cnis) atualizado com sucesso.');
    }
}
