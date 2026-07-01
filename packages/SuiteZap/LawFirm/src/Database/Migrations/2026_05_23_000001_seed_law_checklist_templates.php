<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration idempotente para popular law_checklist_templates com os Kits de Documentos padrão.
 *
 * Motivação: O seeder DocumentChecklistTemplateSeeder não era chamado no boot do container
 * (entrypoint.sh chama apenas `artisan migrate`). Esta migration garante que os kits
 * estejam disponíveis em todo tenant provisionado, sem exigir seed manual.
 *
 * Idempotência: Usa INSERT IGNORE / updateOrCreate por 'name'.
 * Pode ser rodada múltiplas vezes com segurança.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Garante que a tabela existe antes de inserir
        if (! Schema::hasTable('law_checklist_templates')) {
            return;
        }

        $kits = [
            [
                'name'  => 'Kit Padrão Básico',
                'area'  => 'geral',
                'items' => json_encode([
                    'RG (Documento de Identidade)',
                    'CPF',
                    'Comprovante de Residência (atualizado)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Trabalhista — Demissão Sem Justa Causa',
                'area'  => 'trabalhista',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'CTPS (Carteira de Trabalho)',
                    'Termo Rescisório (TRCT)',
                    'Comprovante de Pagamento das Verbas Rescisórias',
                    'Extrato do FGTS (Caixa Econômica Federal)',
                    'Guia de Seguro-Desemprego',
                    'Holerites dos últimos 3 meses',
                    'Comprovante de Residência',
                    'Contrato de Trabalho (se houver)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Trabalhista — Acidente de Trabalho',
                'area'  => 'trabalhista',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'CTPS (Carteira de Trabalho)',
                    'CAT (Comunicação de Acidente de Trabalho)',
                    'Laudos e Atestados Médicos',
                    'Relatório de Alta Médica',
                    'Holerites dos últimos 3 meses',
                    'Comprovante de Residência',
                    'Fotos do Local do Acidente (se disponível)',
                    'Dados de Testemunhas (nome e telefone)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Trabalhista — Assédio Moral / Sexual',
                'area'  => 'trabalhista',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'CTPS (Carteira de Trabalho)',
                    'Prints de Mensagens ou E-mails (provas)',
                    'Laudos Psicológicos ou Médicos (se houver)',
                    'Holerites dos últimos 3 meses',
                    'Comprovante de Residência',
                    'Relato Cronológico dos Fatos (escrito pelo cliente)',
                    'Dados de Testemunhas (nome e telefone)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Família — Divórcio Consensual',
                'area'  => 'familia',
                'items' => json_encode([
                    'RG e CPF de ambos os cônjuges',
                    'Certidão de Casamento (atualizada — emitida nos últimos 90 dias)',
                    'Certidão de Nascimento dos filhos menores (se houver)',
                    'Comprovante de Residência de ambos',
                    'Documentos dos Bens a Partilhar (IPTU, DUT, extrato bancário etc.)',
                    'Escritura do Imóvel (se casados em regime com partilha de bens)',
                    'Declaração de IR do último exercício',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Família — Guarda e Alimentos',
                'area'  => 'familia',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'Certidão de Nascimento do(s) filho(s)',
                    'Comprovante de Residência',
                    'Comprovante de Renda (holerites, decore, extrato etc.)',
                    'Comprovante de Despesas com o Filho (escola, saúde, etc.)',
                    'Documentos do Outro Genitor (se disponível)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Família — Inventário / Herança',
                'area'  => 'familia',
                'items' => json_encode([
                    'Certidão de Óbito do falecido',
                    'RG e CPF de todos os herdeiros',
                    'Certidão de Nascimento dos herdeiros',
                    'Certidão de Casamento (do falecido e herdeiros casados)',
                    'Documentos de Todos os Bens (imóveis: IPTU; veículos: DUT; contas: extrato)',
                    'Declaração de IR do último exercício do falecido',
                    'Certidão Negativa de Débitos Estaduais e Federais',
                    'Testamento (se houver)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Cível — Dano Moral (Consumidor)',
                'area'  => 'civel',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'Comprovante de Residência',
                    'Contratos, Notas Fiscais ou Comprovantes de Compra',
                    'Prints ou Capturas de Tela das Mensagens',
                    'Protocolos de Atendimento e Reclamações',
                    'Boletim de Ocorrência (se aplicável)',
                    'Laudos Médicos ou Psicológicos (se houver)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Cível — Cobrança / Inadimplência',
                'area'  => 'civel',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF / CNPJ',
                    'Comprovante de Residência',
                    'Contrato Assinado',
                    'Comprovantes de Pagamento Realizados',
                    'Correspondências e E-mails Trocados',
                    'Notas Fiscais / Faturas em Aberto',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Criminal — Réu (Defesa)',
                'area'  => 'criminal',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'Comprovante de Residência',
                    'Comprovante de Endereço Fixo',
                    'Comprovante de Renda ou Trabalho',
                    'Atestado de Antecedentes Criminais',
                    'Certidão de Nascimento ou Casamento',
                    'Decisão / Mandado Judicial (se houver)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Criminal — Vítima (Representação)',
                'area'  => 'criminal',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'Comprovante de Residência',
                    'Boletim de Ocorrência',
                    'Laudos Médicos / Periciais',
                    'Fotos das Lesões ou Danos (com data)',
                    'Dados das Testemunhas',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Previdenciário — Aposentadoria / INSS',
                'area'  => 'previdenciario',
                'items' => json_encode([
                    'RG / CNH',
                    'CPF',
                    'Comprovante de Residência',
                    'Carteira de Trabalho (CTPS) — todas as páginas com vínculos',
                    'Extrato CNIS (Cadastro Nacional de Informações Sociais)',
                    'PPP (Perfil Profissiográfico Previdenciário — se atividade especial)',
                    'Laudos Médicos e Exames (se BPC/LOAS ou Auxílio-Doença)',
                    'Carta de Indeferimento do INSS (se houver)',
                    'Procuração Assinada',
                ]),
            ],
            [
                'name'  => 'Kit Empresarial — Constituição de Empresa',
                'area'  => 'empresarial',
                'items' => json_encode([
                    'RG e CPF de todos os sócios',
                    'Comprovante de Residência dos sócios',
                    'CNPJ (se já existir)',
                    'Contrato Social ou Estatuto (versão atual)',
                    'Ata da Última Assembleia (se S/A)',
                    'Certidões Negativas dos sócios (Federal, Estadual e Municipal)',
                    'Comprovante do Endereço Comercial (contrato de locação ou escritura)',
                    'Alvará de Funcionamento (se renovação)',
                    'Procuração Assinada',
                ]),
            ],
        ];

        $now = now();

        foreach ($kits as $kit) {
            // Idempotente: ignora se já existe pelo nome
            $exists = DB::table('law_checklist_templates')
                ->where('name', $kit['name'])
                ->exists();

            if (! $exists) {
                DB::table('law_checklist_templates')->insert([
                    'name'       => $kit['name'],
                    'area'       => $kit['area'],
                    'items'      => $kit['items'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove apenas os kits inseridos por esta migration (pelo nome exato)
        $names = [
            'Kit Padrão Básico',
            'Kit Trabalhista — Demissão Sem Justa Causa',
            'Kit Trabalhista — Acidente de Trabalho',
            'Kit Trabalhista — Assédio Moral / Sexual',
            'Kit Família — Divórcio Consensual',
            'Kit Família — Guarda e Alimentos',
            'Kit Família — Inventário / Herança',
            'Kit Cível — Dano Moral (Consumidor)',
            'Kit Cível — Cobrança / Inadimplência',
            'Kit Criminal — Réu (Defesa)',
            'Kit Criminal — Vítima (Representação)',
            'Kit Previdenciário — Aposentadoria / INSS',
            'Kit Empresarial — Constituição de Empresa',
        ];

        DB::table('law_checklist_templates')->whereIn('name', $names)->delete();
    }
};
