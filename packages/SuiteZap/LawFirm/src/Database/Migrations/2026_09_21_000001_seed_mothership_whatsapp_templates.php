<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * WA-TPL-001 — Semeia os templates padrão de WhatsApp na tabela mothership.
 *
 * Corresponde 1:1 com os templates definidos em Config/system.php (grupos
 * prazos, agendador_adv, financeiro, ged, juridico). Idempotente via
 * updateOrInsert com base na chave `name`.
 *
 * NOTA: Esta migration roda via `php artisan migrate` no ambiente do tenant
 * (que possui connection 'mothership' configurada). Ela NÃO cria dados no banco
 * local do tenant.
 */
return new class extends Migration
{
    protected $connection = 'mothership';

    private function templates(): array
    {
        return [
            // --- Grupo: prazos ---
            [
                'name'         => 'new_prazo_client',
                'title'        => '[Prazos] Notificação de Novo Prazo ao Cliente',
                'group'        => 'prazos',
                'info'         => 'Enviada manualmente ao acionar o botão de notificar. Variáveis: {cliente_nome}, {prazo_titulo}, {prazo_data}, {prazo_descricao}.',
                'rows'         => 4,
                'default_text' => 'Olá {cliente_nome}, informamos um novo prazo no seu processo: {prazo_titulo}. Data: {prazo_data}. {prazo_descricao}',
                'sort_order'   => 1,
            ],
            [
                'name'         => 'prazo_5dias_cliente',
                'title'        => '[Agendador] 5 Dias Antes — Para o Cliente',
                'group'        => 'prazos',
                'info'         => 'Variáveis: {cliente_nome}, {prazo_titulo}, {prazo_data}, {processo_cnj}, {processo_titulo}.',
                'rows'         => 4,
                'default_text' => 'Olá {cliente_nome}! 📅 Lembrando que o prazo *{prazo_titulo}* do processo *{processo_titulo}* (Nº {processo_cnj}) vence em 5 dias, em *{prazo_data}*. Dúvidas? Entre em contato.',
                'sort_order'   => 2,
            ],
            [
                'name'         => 'prazo_vespera_cliente',
                'title'        => '[Agendador] 1 Dia Antes (Véspera) — Para o Cliente',
                'group'        => 'prazos',
                'info'         => 'Variáveis: {cliente_nome}, {prazo_titulo}, {prazo_data}, {processo_cnj}, {processo_titulo}.',
                'rows'         => 4,
                'default_text' => 'Olá {cliente_nome}! ⚠️ O prazo *{prazo_titulo}* do processo *{processo_titulo}* (Nº {processo_cnj}) vence *amanhã, {prazo_data}*. Nosso escritório está acompanhando.',
                'sort_order'   => 3,
            ],
            [
                'name'         => 'prazo_hoje_cliente',
                'title'        => '[Agendador] No Dia do Vencimento — Para o Cliente',
                'group'        => 'prazos',
                'info'         => 'Variáveis: {cliente_nome}, {prazo_titulo}, {prazo_data}, {processo_cnj}, {processo_titulo}.',
                'rows'         => 4,
                'default_text' => 'Olá {cliente_nome}! 🔴 O prazo *{prazo_titulo}* do processo *{processo_titulo}* (Nº {processo_cnj}) vence *hoje, {prazo_data}*. Nosso escritório está acompanhando todos os procedimentos.',
                'sort_order'   => 4,
            ],

            // --- Grupo: agendador_adv ---
            [
                'name'         => 'prazo_5dias_advogado',
                'title'        => '[Agendador] 5 Dias Antes — Para o Advogado',
                'group'        => 'agendador_adv',
                'info'         => 'Variáveis: {advogado_nome}, {prazo_titulo}, {prazo_data}, {processo_cnj}, {processo_titulo}, {cliente_nome}.',
                'rows'         => 4,
                'default_text' => "📅 *Lembrete — 5 dias*\n\nDr(a). {advogado_nome}, o prazo *{prazo_titulo}* do processo *{processo_cnj} — {processo_titulo}* (Cliente: {cliente_nome}) vence em 5 dias: *{prazo_data}*.",
                'sort_order'   => 1,
            ],
            [
                'name'         => 'prazo_vespera_advogado',
                'title'        => '[Agendador] 1 Dia Antes (Véspera) — Para o Advogado',
                'group'        => 'agendador_adv',
                'info'         => 'Variáveis: {advogado_nome}, {prazo_titulo}, {prazo_data}, {processo_cnj}, {processo_titulo}, {cliente_nome}.',
                'rows'         => 4,
                'default_text' => "⚠️ *Prazo amanhã!*\n\nDr(a). {advogado_nome}, o prazo *{prazo_titulo}* do processo *{processo_cnj} — {processo_titulo}* (Cliente: {cliente_nome}) vence *amanhã, {prazo_data}*.",
                'sort_order'   => 2,
            ],
            [
                'name'         => 'prazo_hoje_advogado',
                'title'        => '[Agendador] No Dia do Vencimento — Para o Advogado',
                'group'        => 'agendador_adv',
                'info'         => 'Variáveis: {advogado_nome}, {prazo_titulo}, {prazo_data}, {processo_cnj}, {processo_titulo}, {cliente_nome}.',
                'rows'         => 4,
                'default_text' => "🔴 *Prazo vencendo HOJE!*\n\nDr(a). {advogado_nome}, o prazo *{prazo_titulo}* do processo *{processo_cnj} — {processo_titulo}* (Cliente: {cliente_nome}) vence *hoje, {prazo_data}*.",
                'sort_order'   => 3,
            ],
            [
                'name'         => 'prazo_resumo_diario',
                'title'        => '[Agendador] Resumo Diário de Compromissos (Advogado)',
                'group'        => 'agendador_adv',
                'info'         => 'Variáveis: {advogado_nome}, {data_hoje}, {lista_compromissos}. A lista é gerada automaticamente.',
                'rows'         => 5,
                'default_text' => "📋 *Resumo — {data_hoje}*\n\nBom dia, Dr(a). {advogado_nome}! Seus compromissos de hoje:\n\n{lista_compromissos}\n\nTenha um excelente dia!",
                'sort_order'   => 4,
            ],

            // --- Grupo: financeiro ---
            [
                'name'         => 'financial_billing_due_today',
                'title'        => '[Financeiro] Cobrança no Prazo / Futura',
                'group'        => 'financeiro',
                'info'         => 'Variáveis: {cliente_nome}, {valor}, {descricao}, {data_vencimento}.',
                'rows'         => 4,
                'default_text' => 'Olá {cliente_nome}, lembrete de vencimento ref. {descricao} no valor de {valor} para o dia {data_vencimento}.',
                'sort_order'   => 1,
            ],
            [
                'name'         => 'financial_billing_overdue',
                'title'        => '[Financeiro] Cobrança em Atraso',
                'group'        => 'financeiro',
                'info'         => 'Variáveis: {cliente_nome}, {valor}, {descricao}, {data_vencimento}.',
                'rows'         => 4,
                'default_text' => 'Olá {cliente_nome}, verificamos uma pendência de {valor} referente a {descricao}, vencida em {data_vencimento}. Podemos atualizar o boleto?',
                'sort_order'   => 2,
            ],

            // --- Grupo: ged ---
            [
                'name'         => 'document_request',
                'title'        => '[GED / Documentos] Solicitação de Kits/Documentos',
                'group'        => 'ged',
                'info'         => 'Enviada ao importar um checklist de documentos. Variáveis: {cliente_nome}, {processo_titulo}, {lista_documentos}, {link_portal}.',
                'rows'         => 4,
                'default_text' => "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que nos envie os seguintes documentos:\n{lista_documentos}\nPode enviar fotos legíveis por aqui mesmo.\nou pelo link : {link_portal}",
                'sort_order'   => 1,
            ],
            [
                'name'         => 'registration_request',
                'title'        => '[GED/Documentos] Cadastro e Atualização de Clientes',
                'group'        => 'ged',
                'info'         => 'Enviada via botão no processo para solicitar o preenchimento de dados. Variáveis: {cliente_nome}, {processo_titulo}, {link_portal}.',
                'rows'         => 4,
                'default_text' => "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que atualize suas informações cadastrais.\nUtilize o link {link_portal}",
                'sort_order'   => 2,
            ],

            // --- Grupo: juridico ---
            [
                'name'         => 'escavador_monitoramento_update',
                'title'        => '[Jurídico] Nova Movimentação Monitorada',
                'group'        => 'juridico',
                'info'         => 'Variáveis disponíveis: {termo_monitorado}, {data_atualizacao}, {fonte}.',
                'rows'         => 4,
                'default_text' => "Olá! Detectamos uma nova movimentação do seu processo '{termo_monitorado}' em {fonte} na data de {data_atualizacao}. Acesse o portal para verificar a íntegra.",
                'sort_order'   => 1,
            ],
        ];
    }

    public function up(): void
    {
        $now = now()->toDateTimeString();

        foreach ($this->templates() as $template) {
            DB::connection('mothership')
                ->table('lawfirm_whatsapp_templates')
                ->updateOrInsert(
                    ['name' => $template['name']],
                    array_merge($template, [
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
        }
    }

    public function down(): void
    {
        $names = array_column($this->templates(), 'name');

        DB::connection('mothership')
            ->table('lawfirm_whatsapp_templates')
            ->whereIn('name', $names)
            ->delete();
    }
};
