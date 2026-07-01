<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LawFirmLaborPromptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'category'         => 'triagem',
                'title'            => 'Triagem Trabalhista (PF)',
                'description'      => 'Analisa caso do reclamante, identifica verbas e riscos iniciais.',
                'icon'             => 'heroicon-user',
                'n8n_webhook_url'  => 'lawfirm/triagem-pf',
                'required_module'  => 'lawfirm_labor',
                'prompt_structure' => "Dados do caso trabalhista (Pessoa Física):\n\nNome do trabalhador: {{nome}}\nEmpregador: {{empregador}}\nCNPJ do empregador (se houver): {{cnpj}}\nData de início do contrato: {{data_inicio}}\nData de fim do contrato: {{data_fim}}\nFunção exercida: {{funcao}}\nDescrição dos Fatos:\n{{descricao_fatos}}\n\nRealize a triagem inicial.",
                'variables'        => json_encode([
                    ['key' => 'nome', 'label' => 'Nome do Trabalhador', 'type' => 'text'],
                    ['key' => 'empregador', 'label' => 'Empregador', 'type' => 'text'],
                    ['key' => 'cnpj', 'label' => 'CNPJ (Opcional)', 'type' => 'text'],
                    ['key' => 'data_inicio', 'label' => 'Data Admissão', 'type' => 'date'],
                    ['key' => 'data_fim', 'label' => 'Data Demissão', 'type' => 'date'],
                    ['key' => 'funcao', 'label' => 'Função Exercida', 'type' => 'text'],
                    ['key' => 'descricao_fatos', 'label' => 'Descrição Detalhada dos Fatos', 'type' => 'textarea', 'rows' => 6],
                ]),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category'         => 'triagem',
                'title'            => 'Triagem Patronal (PJ)',
                'description'      => 'Analisa riscos de passivo trabalhista e compliance para empresas.',
                'icon'             => 'heroicon-building-office',
                'n8n_webhook_url'  => 'lawfirm/triagem-pj',
                'required_module'  => 'lawfirm_labor',
                'prompt_structure' => "Dados da empresa (Pessoa Jurídica):\n\nRazão social: {{razao_social}}\nCNPJ: {{cnpj}}\nRamo de atividade: {{ramo}}\nNúmero aproximado de empregados: {{numero_empregados}}\nTipo de contratação predominante: {{tipo_contrato}}\nDescrição do problema ou dúvida:\n{{descricao_fatos}}\n\nRealize a triagem de risco trabalhista.",
                'variables'        => json_encode([
                    ['key' => 'razao_social', 'label' => 'Razão Social', 'type' => 'text'],
                    ['key' => 'cnpj', 'label' => 'CNPJ', 'type' => 'text'],
                    ['key' => 'ramo', 'label' => 'Ramo de Atividade', 'type' => 'text'],
                    ['key' => 'numero_empregados', 'label' => 'Nº Aprox. Empregados', 'type' => 'number'],
                    ['key' => 'tipo_contrato', 'label' => 'Tipo de Contratação Predominante', 'type' => 'text'],
                    ['key' => 'descricao_fatos', 'label' => 'Problema ou Dúvida Jurídica', 'type' => 'textarea', 'rows' => 6],
                ]),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category'         => 'processual',
                'title'            => 'Gerador de Checklist',
                'description'      => 'Cria lista de documentos e verificações essenciais para o caso.',
                'icon'             => 'heroicon-clipboard-document-check',
                'n8n_webhook_url'  => 'lawfirm/checklist',
                'required_module'  => 'lawfirm_process',
                'prompt_structure' => "Contexto do caso:\n{{contexto}}\n\nTipo de cliente: {{tipo_cliente}} (PF ou PJ)\n\nGere um checklist jurídico para análise completa do caso.",
                'variables'        => json_encode([
                    ['key' => 'tipo_cliente', 'label' => 'Tipo de Cliente', 'type' => 'select', 'options' => ['PF', 'PJ']],
                    ['key' => 'contexto', 'label' => 'Resumo do Caso', 'type' => 'textarea', 'rows' => 4, 'placeholder' => 'Cole aqui o resumo da triagem...'],
                ]),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category'         => 'pecas',
                'title'            => 'Pré-Minuta Trabalhista',
                'description'      => 'Gera rascunho técnico de peça. Requer revisão obrigatória.',
                'icon'             => 'heroicon-pencil-square',
                'n8n_webhook_url'  => 'lawfirm/minuta-segura',
                'required_module'  => 'lawfirm_ged',
                'prompt_structure' => "Tipo de peça: {{tipo_peca}}\nResumo dos fatos:\n{{fatos}}\n\nPedidos pretendidos:\n{{pedidos}}\n\nBase legal sugerida:\n{{fundamentos}}\n\nElabore rascunho inicial técnico.",
                'variables'        => json_encode([
                    ['key' => 'tipo_peca', 'label' => 'Tipo de Peça', 'type' => 'text', 'placeholder' => 'Ex: Reclamação Trabalhista'],
                    ['key' => 'fatos', 'label' => 'Resumo dos Fatos', 'type' => 'textarea', 'rows' => 6],
                    ['key' => 'pedidos', 'label' => 'Pedidos Pretendidos', 'type' => 'textarea', 'rows' => 4],
                    ['key' => 'fundamentos', 'label' => 'Base Legal / Súmulas (Opcional)', 'type' => 'textarea', 'rows' => 3],
                ]),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::connection('mothership')->table('lawfirm_assistant_templates')->updateOrInsert(
                ['n8n_webhook_url' => $template['n8n_webhook_url']],
                $template
            );
        }
    }
}
