<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MothershipAssistantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Popula a tabela de templates no banco MotherShip com os assistentes do Roadmap Jurídico.
     */
    public function run(): void
    {
        // Limpa a tabela antes de inserir
        DB::connection('mothership')->table('lawfirm_assistant_templates')->truncate();

        $templates = [
            // ETAPA 1: TRIAGEM
            [
                'tenant_id' => null,
                'category' => 'triagem',
                'title' => 'Triagem e Classificação',
                'description' => 'Analisa o relato inicial do cliente, classifica a área do direito, urgência e documentos necessários.',
                'icon' => 'heroicon-funnel',
                'prompt_structure' => 'Analise o relato: "{{relato_cliente}}". Classifique a área do direito, urgência (0-10) e documentos necessários.',
                'n8n_webhook_url' => null,
                'required_module' => 'lawfirm_process',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ETAPA 2: PROCESSUAL
            [
                'tenant_id' => null,
                'category' => 'processual',
                'title' => 'Resumo de Decisão Judicial',
                'description' => 'Resume decisões judiciais, explicando o impacto para o cliente e próximos prazos.',
                'icon' => 'heroicon-scale',
                'prompt_structure' => 'Resuma a decisão: "{{texto_decisao}}". Explique o impacto para o cliente e próximos prazos.',
                'n8n_webhook_url' => null,
                'required_module' => 'lawfirm_process',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ETAPA 3: PEÇAS
            [
                'tenant_id' => null,
                'category' => 'pecas',
                'title' => 'Estruturação de Petição',
                'description' => 'Cria a estrutura inicial de petições com fundamentos legais atualizados.',
                'icon' => 'heroicon-document-text',
                'prompt_structure' => 'Crie estrutura para ação de "{{tipo_acao}}". Fatos: "{{fatos}}". Fundamente com lei brasileira atualizada.',
                'n8n_webhook_url' => null,
                'required_module' => 'lawfirm_ged',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ETAPA 4: GESTÃO
            [
                'tenant_id' => null,
                'category' => 'gestao',
                'title' => 'Gestão de Prazos',
                'description' => 'Analisa publicações e calcula datas fatais com sugestão de protocolo seguro.',
                'icon' => 'heroicon-calendar',
                'prompt_structure' => 'Analise a publicação: "{{publicacao}}". Identifique a data fatal e sugira data de protocolo segura.',
                'n8n_webhook_url' => null,
                'required_module' => 'lawfirm_deadlines',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ETAPA 5: COMPLIANCE
            [
                'tenant_id' => null,
                'category' => 'compliance',
                'title' => 'Mascaramento LGPD',
                'description' => 'Substitui dados pessoais por [PROTEGIDO] conforme a Lei Geral de Proteção de Dados.',
                'icon' => 'heroicon-shield-check',
                'prompt_structure' => 'Receba o texto: "{{texto_original}}". Substitua dados pessoais por [PROTEGIDO] conforme LGPD.',
                'n8n_webhook_url' => null,
                'required_module' => null, // Disponível para todos
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::connection('mothership')->table('lawfirm_assistant_templates')->insert($templates);
    }
}
