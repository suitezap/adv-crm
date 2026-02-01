<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateTemplatesVariablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conn = DB::connection('mothership');

        // ID 1 - Triagem Inicial
        $conn->table('lawfirm_assistant_templates')
            ->where('id', 1)
            ->update([
                'variables' => json_encode([
                    ['key' => 'relato_cliente', 'label' => 'Relato do Cliente', 'type' => 'textarea', 'placeholder' => 'Descreva o caso...']
                ]),
                'n8n_webhook_url' => 'lawfirm/triagem',
            ]);

        // ID 2 - Resumo Decisão
        $conn->table('lawfirm_assistant_templates')
            ->where('id', 2)
            ->update([
                'variables' => json_encode([
                    ['key' => 'texto_decisao', 'label' => 'Texto da Decisão', 'type' => 'textarea', 'placeholder' => 'Cole a decisão aqui...']
                ]),
                'n8n_webhook_url' => 'lawfirm/resumo-decisao',
            ]);

        // ID 3 - Estruturação Petição
        $conn->table('lawfirm_assistant_templates')
            ->where('id', 3)
            ->update([
                'variables' => json_encode([
                    ['key' => 'tipo_acao', 'label' => 'Tipo de Ação', 'type' => 'text'],
                    ['key' => 'fatos', 'label' => 'Fatos do Caso', 'type' => 'textarea']
                ]),
                'n8n_webhook_url' => 'lawfirm/peticao-inicial',
            ]);

        // ID 4 - Gestão Prazos
        $conn->table('lawfirm_assistant_templates')
            ->where('id', 4)
            ->update([
                'variables' => json_encode([
                    ['key' => 'publicacao', 'label' => 'Teor da Publicação', 'type' => 'textarea']
                ]),
                'n8n_webhook_url' => 'lawfirm/calculo-prazo',
            ]);

        // ID 5 - LGPD (Anonimização)
        $conn->table('lawfirm_assistant_templates')
            ->where('id', 5)
            ->update([
                'variables' => json_encode([
                    ['key' => 'texto_original', 'label' => 'Texto Original', 'type' => 'textarea']
                ]),
                'n8n_webhook_url' => 'lawfirm/anonimizacao',
            ]);

        $this->command->info('Templates atualizados com variáveis e webhooks.');
    }
}
