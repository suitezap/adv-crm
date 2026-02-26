<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class CacadorClausulasLeoninasTemplateSeeder extends Seeder
{
    /**
     * Seed the cacador-clausulas-leoninas template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Atue como um advogado sênior especialista em Contratos. Analise o contrato (Tipo de Contrato: {{tipo_contratual}}) colado abaixo. Identifique e liste em tópicos: 1. Cláusulas Leoninas (Abusivas) que desfavorecem meu cliente (a parte {{parte_cliente}}); 2. Riscos de multas desproporcionais; 3. Ausência de cláusulas essenciais de rescisão ou garantia. Para cada ponto, sugira uma redação alternativa mais equilibrada.

Contrato:
{{texto_contrato}}";

        $variables = [
            [
                'key' => 'parte_cliente',
                'label' => 'Parte Representada pelo Cliente',
                'type' => 'text',
                'placeholder' => 'Ex: Locatário, Contratante, Comprador',
            ],
            [
                'key' => 'tipo_contratual',
                'label' => 'Tipo Contratual (opcional)',
                'type' => 'text',
                'placeholder' => 'Ex: Prestação de Serviços, Locação',
            ],
            [
                'key' => 'texto_contrato',
                'label' => 'Texto do Contrato',
                'type' => 'textarea',
                'rows' => 20,
                'placeholder' => 'Cole o texto integral do contrato aqui...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'cacador-clausulas-leoninas'],
            [
                'tenant_id' => null,
                'category' => 'contratos',
                'area' => 'Contratos',
                'title' => 'Caçador de Cláusulas Leoninas',
                'description' => 'Varre minutas de contratos complexos em busca de cláusulas abusivas, multas desproporcionais e ausência de garantias.',
                'icon' => '🔎',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'ai-cacador-clausulas',
                'required_module' => 'IA-Contratos',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Caçador de Cláusulas Leoninas" (cacador-clausulas-leoninas) criado com sucesso.');
    }
}
