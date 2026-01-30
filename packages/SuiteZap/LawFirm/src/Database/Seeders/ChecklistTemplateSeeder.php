<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChecklistTemplateSeeder extends Seeder
{
    public function run()
    {
        // Limpa a tabela para reiniciar a ordem (IDs)
        DB::table('law_checklist_templates')->truncate();

        $templates = [
            // 1. KIT BÁSICO (Padrão) - Solicitado como 1ª opção
            [
                'name' => '00. Kit Básico (Padrão)',
                'area' => 'Geral',
                'items' => json_encode([
                    ['name' => 'RG e CPF', 'required' => true],
                    ['name' => 'Comprovante de Residência (Atualizado)', 'required' => true],
                    ['name' => 'Procuração Assinada', 'required' => true],
                    ['name' => 'Declaração de Hipossuficiência', 'required' => false]
                ]),
            ],
            // 2. Kit Divórcio Consensual
            [
                'name' => 'Kit Divórcio Consensual',
                'area' => 'Família',
                'items' => json_encode([
                    ['name' => 'Certidão de Casamento Atualizada', 'required' => true],
                    ['name' => 'RG e CPF dos Cônjuges', 'required' => true],
                    ['name' => 'Comprovante de Residência', 'required' => true],
                    ['name' => 'Certidão de Nascimento dos Filhos', 'required' => false],
                ]),
            ],
            // 3. Kit Usucapião
            [
                'name' => 'Kit Usucapião',
                'area' => 'Civil',
                'items' => json_encode([
                    ['name' => 'Planta do Imóvel', 'required' => true],
                    ['name' => 'Memorial Descritivo', 'required' => true],
                    ['name' => 'Contratos de Compra e Venda Antigos', 'required' => true],
                    ['name' => 'Contas de Consumo (últimos 5 anos)', 'required' => true],
                ]),
            ],
            // 4. Kit Cível (Geral)
            [
                'name' => 'Kit Cível (Geral)',
                'area' => 'Civil',
                'items' => json_encode([
                    ['name' => 'Cópia do Contrato (Objeto da Ação)', 'required' => true],
                    ['name' => 'Troca de Mensagens (Prints/E-mails)', 'required' => false],
                    ['name' => 'Comprovantes de Pagamento', 'required' => true],
                    ['name' => 'Rol de Testemunhas (Nome/RG/Endereço)', 'required' => false]
                ]),
            ],
            // 5. Kit Penal
            [
                'name' => 'Kit Penal / Criminal',
                'area' => 'Penal',
                'items' => json_encode([
                    ['name' => 'Boletim de Ocorrência (B.O.)', 'required' => true],
                    ['name' => 'Cópia do Inquérito Policial', 'required' => false],
                    ['name' => 'Comprovante de Residência Atualizado', 'required' => true],
                    ['name' => 'Procuração Criminal Específica', 'required' => true]
                ]),
            ],
            // 6. Kit Consumidor
            [
                'name' => 'Kit Consumidor',
                'area' => 'Consumidor',
                'items' => json_encode([
                    ['name' => 'Nota Fiscal do Produto/Serviço', 'required' => true],
                    ['name' => 'Protocolos de Atendimento (SAC)', 'required' => false],
                    ['name' => 'Certificado de Garantia', 'required' => false],
                    ['name' => 'Fotos do Defeito/Produto', 'required' => true]
                ]),
            ],
            // 7. Kit Tributário
            [
                'name' => 'Kit Tributário',
                'area' => 'Tributário',
                'items' => json_encode([
                    ['name' => 'Notificação de Lançamento / Auto de Infração', 'required' => true],
                    ['name' => 'Cópia do Processo Administrativo (PAF)', 'required' => false],
                    ['name' => 'Certidão de Dívida Ativa (CDA)', 'required' => true],
                    ['name' => 'Comprovantes de Pagamento de Tributos', 'required' => true]
                ]),
            ]
        ];

        foreach ($templates as $data) {
            DB::table('law_checklist_templates')->insert(array_merge($data, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }
    }
}
