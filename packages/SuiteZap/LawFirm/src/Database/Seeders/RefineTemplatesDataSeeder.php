<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RefineTemplatesDataSeeder extends Seeder
{
    /**
     * Refina os dados dos templates com webhooks específicos e variáveis de UI.
     */
    public function run(): void
    {
        $conn = DB::connection('mothership');

        // Template 1 - Triagem
        $conn->table('lawfirm_assistant_templates')
            ->updateOrInsert(
                ['category' => 'triagem'],
                [
                    'n8n_webhook_url' => 'lawfirm/triagem',
                    'variables' => json_encode([
                        ['key' => 'relato_cliente', 'label' => 'Relato do Cliente', 'type' => 'textarea', 'placeholder' => 'Descreva o relato...', 'rows' => 4],
                        ['key' => 'urgencia', 'label' => 'Nível de Urgência', 'type' => 'select', 'options' => ['Baixa', 'Média', 'Alta']]
                    ]),
                    'updated_at' => now(),
                ]
            );

        // Template 2 - Processual/Decisão
        $conn->table('lawfirm_assistant_templates')
            ->updateOrInsert(
                ['category' => 'processual'],
                [
                    'n8n_webhook_url' => 'lawfirm/resumo-decisao',
                    'variables' => json_encode([
                        ['key' => 'texto_decisao', 'label' => 'Texto da Decisão/Andamento', 'type' => 'textarea', 'rows' => 6]
                    ]),
                    'updated_at' => now(),
                ]
            );

        // Template 3 - Peças/Petição
        $conn->table('lawfirm_assistant_templates')
            ->updateOrInsert(
                ['category' => 'pecas'],
                [
                    'n8n_webhook_url' => 'lawfirm/peticao-inicial',
                    'variables' => json_encode([
                        ['key' => 'tipo_acao', 'label' => 'Tipo da Ação', 'type' => 'text', 'placeholder' => 'Ex: Indenizatória'],
                        ['key' => 'fatos', 'label' => 'Fatos do Caso', 'type' => 'textarea', 'rows' => 8]
                    ]),
                    'updated_at' => now(),
                ]
            );

        // Template 4 - Gestão/Prazos
        $conn->table('lawfirm_assistant_templates')
            ->updateOrInsert(
                ['category' => 'gestao'],
                [
                    'n8n_webhook_url' => 'lawfirm/calculo-prazo',
                    'variables' => json_encode([
                        ['key' => 'publicacao', 'label' => 'Teor da Publicação', 'type' => 'textarea', 'rows' => 5]
                    ]),
                    'updated_at' => now(),
                ]
            );

        // Template 5 - LGPD/Compliance
        $conn->table('lawfirm_assistant_templates')
            ->updateOrInsert(
                ['category' => 'compliance'],
                [
                    'n8n_webhook_url' => 'lawfirm/anonimizacao',
                    'variables' => json_encode([
                        ['key' => 'texto_original', 'label' => 'Texto para Anonimizar', 'type' => 'textarea', 'rows' => 10]
                    ]),
                    'updated_at' => now(),
                ]
            );

        $this->command->info('Templates refinados com webhooks e variáveis de UI.');
    }
}
