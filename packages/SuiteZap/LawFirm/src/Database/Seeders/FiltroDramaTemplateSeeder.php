<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

class FiltroDramaTemplateSeeder extends Seeder
{
    /**
     * Seed the filtro-drama template into MotherShip.
     */
    public function run()
    {
        $promptStructure = "Analise o relato informal do cliente abaixo. Ignore adjetivos, xingamentos e opiniões pessoais. Extraia e liste cronologicamente apenas os Fatos Jurídicos Relevantes para uma ação de {{tipo_acao}}, focando em: datas de separação de fato, quem saiu do lar, quem paga as contas atualmente, episódios de violência (se houver) e rotina atual das crianças.

Relato do cliente: 
{{relato_cliente}}";

        $variables = [
            [
                'key' => 'tipo_acao',
                'label' => 'Tipo de Ação Pretendida',
                'type' => 'text',
                'placeholder' => 'Ex: Divórcio, Guarda, Alimentos',
            ],
            [
                'key' => 'relato_cliente',
                'label' => 'Relato do Cliente',
                'type' => 'textarea',
                'rows' => 20,
                'placeholder' => 'Cole o relato completo, desabafo ou transcrição de áudio aqui...',
            ],
            [
                'key' => 'tenant_id',
                'label' => 'Tenant ID',
                'type' => 'hidden',
                'placeholder' => '',
            ]
        ];

        AssistantTemplate::updateOrCreate(
            ['slug' => 'filtro-drama'],
            [
                'tenant_id' => null,
                'category' => 'pecas',
                'area' => 'Família',
                'title' => 'Filtro de Drama',
                'description' => 'Estrutura relatos emocionais longos em uma linha do tempo objetiva de fatos jurídicos.',
                'icon' => '🎭',
                'prompt_structure' => $promptStructure,
                'variables' => $variables,
                'n8n_webhook_url' => 'ai-filtro-drama',
                'required_module' => 'IA-Familia',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Template "Filtro de Drama" (filtro-drama) criado com sucesso.');
    }
}
