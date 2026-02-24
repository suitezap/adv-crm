<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class ArquitetoConvivenciaTemplateSeeder extends Seeder
{
    /**
     * Seed the arquiteto-convivencia template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Com base em uma Guarda {{tipo_guarda}}, na cidade do pai ({{cidade_pai}}) e na cidade da mãe ({{cidade_mae}}), redija uma cláusula detalhada de convivência (visitas). Inclua regras específicas para: 1. Alternância de Natal e Ano Novo (anos pares/ímpares); 2. Dia dos Pais/Mães; 3. Aniversário da criança (quem fica com ela?); 4. Feriados prolongados (emenda); 5. Tolerância máxima de atraso na retirada (ex: 30min). A linguagem deve ser clara para evitar interpretações dúbias.";

        $variables = [
            [
                'key' => 'tipo_guarda',
                'label' => 'Tipo de Guarda',
                'type' => 'text',
                'placeholder' => 'Ex: Compartilhada ou Unilateral',
            ],
            [
                'key' => 'cidade_pai',
                'label' => 'Cidade do Pai',
                'type' => 'text',
                'placeholder' => 'Ex: São Paulo - SP',
            ],
            [
                'key' => 'cidade_mae',
                'label' => 'Cidade da Mãe',
                'type' => 'text',
                'placeholder' => 'Ex: Campinas - SP',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'arquiteto-convivencia'],
            [
                'tenant_id' => null,
                'category' => 'pecas',
                'title' => 'Arquiteto de Convivência',
                'description' => 'Redige cláusulas detalhadas de visitas (plano parental) prevendo exceções e regras claras para evitar conflitos.',
                'icon' => '🤝',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'lawfirm-arquiteto-convivencia',
                'required_module' => null,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Arquiteto de Convivência" (arquiteto-convivencia) criado com sucesso.');
    }
}
