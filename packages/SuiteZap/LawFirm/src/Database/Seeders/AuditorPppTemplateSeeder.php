<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class AuditorPppTemplateSeeder extends Seeder
{
    /**
     * Seed the auditor-ppp template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Analise os dados do PPP: Período {{periodo}}, Agente {{agente_nocivo}}, Intensidade {{intensidade}}, EPI Eficaz {{epi}}. Verifique se, na legislação vigente neste período específico, esse agente gerava direito à contagem de tempo especial. Cite o Decreto aplicável (ex: Decreto 53.831/64 ou Decreto 3.048/99). Se houver menção a 'EPI Eficaz' como 'Sim', cite a tese do STF (ARE 664.335) sobre se isso afasta ou não a nocividade para este agente específico.";

        $variables = [
            [
                'key'         => 'periodo',
                'label'       => 'Período',
                'type'        => 'text',
                'placeholder' => 'Ex: 01/01/1990 a 31/12/1995',
            ],
            [
                'key'         => 'agente_nocivo',
                'label'       => 'Agente Nocivo',
                'type'        => 'text',
                'placeholder' => 'Ex: Ruído Químico, Calor',
            ],
            [
                'key'         => 'intensidade',
                'label'       => 'Intensidade / Concentração',
                'type'        => 'text',
                'placeholder' => 'Ex: 85 dB(A)',
            ],
            [
                'key'         => 'epi',
                'label'       => 'EPI Eficaz (S/N)',
                'type'        => 'text',
                'placeholder' => 'Ex: Sim / Não',
            ],
            [
                'key'         => 'tenant_id',
                'label'       => 'Tenant ID',
                'type'        => 'hidden',
                'placeholder' => '',
            ],
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'auditor-ppp'],
            [
                'tenant_id'        => null,
                'category'         => 'processual',
                'area'             => 'Previdenciário',
                'title'            => 'Auditor de PPP',
                'description'      => 'Cruza dados do Perfil Profissiográfico Previdenciário com a legislação da época para verificar direito a tempo especial.',
                'icon'             => '👷',
                'prompt_structure' => $promptStructure,
                'variables'        => $variables,
                'n8n_webhook_url'  => 'ai-auditor-ppp',
                'required_module'  => 'IA-Previdenciario',
                'is_active'        => true,
            ]
        );

        $this->command->info('✅ Template "Auditor de PPP" (auditor-ppp) criado com sucesso.');
    }
}
