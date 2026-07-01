<?php

/**
 * LawFirm ACL Configuration
 *
 * Follows Krayin's native ACL pattern:
 *   - Top-level key = sidebar menu group
 *   - Dot-notation children = CRUD permissions
 *   - 'route' maps route names to ACL keys for Bouncer middleware enforcement
 *
 * IMPORTANT: Every named route in the LawFirm package MUST appear here.
 * Routes not listed bypass Bouncer's authorization check.
 *
 * @see \Webkul\Core\Acl::getRoles()  — builds route→key map from this config
 * @see \Webkul\Admin\Http\Middleware\Bouncer::checkIfAuthorized() — enforces it
 */

return [

    // ══════════════════════════════════════════════════════════
    // TOP-LEVEL: Jurídico
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm',
        'name'  => 'Jurídico',
        'route' => 'admin.processos.index',
        'sort'  => 1,
    ],

    // ─── Processos ───────────────────────────────────────────
    [
        'key'   => 'lawfirm.processos',
        'name'  => 'Processos',
        'route' => 'admin.processos.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.processos.create',
        'name'  => 'Criar Processos',
        'route' => ['admin.processos.create', 'admin.processos.store'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.processos.edit',
        'name'  => 'Editar Processos',
        'route' => ['admin.processos.edit', 'admin.processos.update'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.processos.delete',
        'name'  => 'Deletar Processos',
        'route' => ['admin.processos.destroy', 'admin.processos.mass_delete', 'admin.processos.delete_attachment'],
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.processos.view',
        'name'  => 'Visualizar Processos',
        'route' => [
            'admin.processos.index',
            'admin.processos.show',
            'admin.processos.search_person',
            'admin.processos.search_lead',
            'admin.processos.download_attachment',
            'admin.leads.processos',
            'admin.contacts.persons.processos',
            'admin.contacts.organizations.processos',
        ],
        'sort'  => 4,
    ],

    // ─── Casos ───────────────────────────────────────────────
    [
        'key'   => 'lawfirm.casos',
        'name'  => 'Casos',
        'route' => 'admin.lawfirm.casos.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.casos.create',
        'name'  => 'Criar Casos',
        'route' => ['admin.lawfirm.casos.create', 'admin.lawfirm.casos.store'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.casos.edit',
        'name'  => 'Editar Casos',
        'route' => ['admin.lawfirm.casos.edit', 'admin.lawfirm.casos.update'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.casos.delete',
        'name'  => 'Deletar Casos',
        'route' => ['admin.lawfirm.casos.destroy', 'admin.lawfirm.casos.mass_delete'],
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.casos.view',
        'name'  => 'Visualizar Casos',
        'route' => ['admin.lawfirm.casos.index', 'admin.lawfirm.casos.show', 'admin.lawfirm.casos.search', 'admin.processos.search_caso'],
        'sort'  => 4,
    ],

    // ─── Tarefas ─────────────────────────────────────────────
    [
        'key'   => 'lawfirm.tarefas',
        'name'  => 'Tarefas (Em Atividades)',
        'route' => 'admin.activities.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.tarefas.view',
        'name'  => 'Visualizar Tarefas',
        'route' => 'admin.activities.index',
        'sort'  => 1,
    ],

    // ─── Kanban Jurídico ─────────────────────────────────────
    [
        'key'   => 'lawfirm.kanban',
        'name'  => 'Kanban Jurídico',
        'route' => 'admin.lawfirm.legal.kanban.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.kanban.view',
        'name'  => 'Visualizar Kanban',
        'route' => 'admin.lawfirm.legal.kanban.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.kanban.edit',
        'name'  => 'Mover Casos no Kanban',
        'route' => 'admin.lawfirm.legal.kanban.update',
        'sort'  => 2,
    ],

    // ─── Agenda Jurídica ─────────────────────────────────────
    [
        'key'   => 'lawfirm.agenda',
        'name'  => 'Agenda Jurídica',
        'route' => 'admin.lawfirm.agenda.index',
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.agenda.view',
        'name'  => 'Visualizar Agenda',
        'route' => ['admin.lawfirm.agenda.index', 'admin.lawfirm.agenda.events'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.agenda.create',
        'name'  => 'Criar Eventos na Agenda',
        'route' => 'admin.lawfirm.agenda.store',
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.agenda.edit',
        'name'  => 'Editar Agenda (Drag & Drop)',
        'route' => 'admin.lawfirm.agenda.update',
        'sort'  => 3,
    ],

    // ─── Modelos de Documentos ──────────────────────────────
    [
        'key'   => 'lawfirm.modelos',
        'name'  => 'Modelos de Documentos',
        'route' => 'admin.modelos.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'lawfirm.modelos.create',
        'name'  => 'Criar Modelos',
        'route' => ['admin.legal.modelos.create', 'admin.legal.modelos.store'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.modelos.edit',
        'name'  => 'Editar Modelos',
        'route' => ['admin.legal.modelos.edit', 'admin.legal.modelos.update'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.modelos.delete',
        'name'  => 'Deletar Modelos',
        'route' => ['admin.legal.modelos.destroy'],
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.modelos.view',
        'name'  => 'Visualizar Modelos',
        'route' => ['admin.modelos.index'],
        'sort'  => 4,
    ],

    // ─── Prazos ──────────────────────────────────────────────
    [
        'key'   => 'lawfirm.prazos',
        'name'  => 'Prazos',
        'route' => 'admin.prazos.index',
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.prazos.create',
        'name'  => 'Criar Prazos',
        'route' => [
            'admin.prazos.store',
            'admin.lawfirm.legal.deadlines.store',
            'admin.lawfirm.legal.prazos.store',
        ],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.prazos.edit',
        'name'  => 'Editar Prazos',
        'route' => [
            'admin.prazos.edit',
            'admin.prazos.update',
            'admin.prazos.concluir',
            'admin.prazos.toggle-notify',
            'admin.lawfirm.legal.deadlines.update',
            'admin.lawfirm.legal.deadlines.toggle',
        ],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.prazos.delete',
        'name'  => 'Deletar Prazos',
        'route' => [
            'admin.prazos.destroy',
            'admin.lawfirm.legal.deadlines.destroy',
            'admin.lawfirm.legal.prazos.destroy',
        ],
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.prazos.view',
        'name'  => 'Visualizar Prazos',
        'route' => ['admin.prazos.index', 'lawfirm.prazos.notify'],
        'sort'  => 4,
    ],

    // ══════════════════════════════════════════════════════════
    // CHILD GROUP: Financeiro (dentro de lawfirm)
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm.financeiro',
        'name'  => 'Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort'  => 4,
    ],

    // ─── Dashboard Financeiro ────────────────────────────────
    [
        'key'   => 'lawfirm.financeiro.dashboard',
        'name'  => 'Dashboard Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.financeiro.create',
        'name'  => 'Criar Lançamento Financeiro',
        'route' => ['admin.lawfirm.financial.process.store'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.financeiro.edit',
        'name'  => 'Editar / Pagar Lançamento',
        'route' => ['admin.lawfirm.financial.quick_pay', 'admin.lawfirm.financial.send_whatsapp'],
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.financeiro.view',
        'name'  => 'Visualizar Financeiro',
        'route' => ['admin.lawfirm.financial.index', 'admin.lawfirm.financial.receipt'],
        'sort'  => 4,
    ],

    // ─── Cobranças (TenantFinance / Asaas) ───────────────────
    [
        'key'   => 'lawfirm.financeiro.cobrancas',
        'name'  => 'Cobranças (Asaas)',
        'route' => 'admin.lawfirm.tenant_finance.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'lawfirm.financeiro.cobrancas.create',
        'name'  => 'Criar Cobranças',
        'route' => ['admin.lawfirm.tenant_finance.store'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.financeiro.cobrancas.view',
        'name'  => 'Ver Cobranças',
        'route' => ['admin.lawfirm.tenant_finance.index', 'admin.lawfirm.tenant_finance.show'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.financeiro.cobrancas.edit',
        'name'  => 'Editar / Reenviar Cobranças',
        'route' => ['admin.lawfirm.tenant_finance.resend'],
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.financeiro.cobrancas.delete',
        'name'  => 'Cancelar/Excluir Cobranças',
        'route' => ['admin.lawfirm.tenant_finance.cancel'],
        'sort'  => 4,
    ],
    [
        'key'   => 'lawfirm.financeiro.cobrancas.settings',
        'name'  => 'Configurar Asaas do Escritório',
        'route' => ['admin.lawfirm.tenant_finance.settings', 'admin.lawfirm.tenant_finance.settings.store'],
        'sort'  => 5,
    ],

    // ══════════════════════════════════════════════════════════
    // Dados do Escritório
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm.settings',
        'name'  => 'Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'lawfirm.settings.view',
        'name'  => 'Visualizar Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort'  => 1,
    ],

    // ══════════════════════════════════════════════════════════
    // Assistentes IA
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm.assistants',
        'name'  => 'Assistentes IA e Histórico',
        'route' => 'lawfirm.assistants.index',
        'sort'  => 6,
    ],
    [
        'key'   => 'lawfirm.assistants.execute',
        'name'  => 'Executar Assistentes',
        'route' => ['lawfirm.assistants.execute', 'lawfirm.assistants.generate'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.assistants.view',
        'name'  => 'Ver Assistentes e Histórico',
        'route' => ['lawfirm.assistants.index', 'lawfirm.assistants.history.index'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.assistants.escavai',
        'name'  => 'Acessar EscavAI',
        'route' => 'lawfirm.assistants.escavai',
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.assistants.chatwoot',
        'name'  => 'Acessar SAC / Chatwoot',
        'route' => 'lawfirm.assistants.chatwoot',
        'sort'  => 4,
    ],
    [
        'key'   => 'sac',
        'name'  => 'Acessar SAC',
        'route' => 'lawfirm.assistants.chatwoot',
        'sort'  => 5,
    ],

    // ══════════════════════════════════════════════════════════
    // Assistente Jurídico (Escavador)
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm.escavador',
        'name'  => 'Assistente Jurídico e Monitoramentos',
        'route' => 'lawfirm.escavador.index',
        'sort'  => 7,
    ],
    [
        'key'   => 'lawfirm.escavador.create',
        'name'  => 'Criar Monitoramento / Executar Busca',
        'route' => ['lawfirm.escavador.monitoramentos.create', 'lawfirm.escavador.monitoramentos.store', 'lawfirm.escavador.buscar'],
        'sort'  => 1,
    ],
    [
        'key'   => 'lawfirm.escavador.view',
        'name'  => 'Visualizar Monitoramentos / Histórico',
        'route' => ['lawfirm.escavador.index', 'lawfirm.escavador.monitoramentos.index', 'lawfirm.escavador.history'],
        'sort'  => 2,
    ],
    [
        'key'   => 'lawfirm.escavador.certs',
        'name'  => 'Certificados Digitais (Jurídico)',
        'route' => 'lawfirm.escavador.certificados.view',
        'sort'  => 3,
    ],
    [
        'key'   => 'lawfirm.escavador.certs.manage',
        'name'  => 'Gerenciar Certificados',
        'route' => 'lawfirm.escavador.certificados.store',
        'sort'  => 1,
    ],

    // ══════════════════════════════════════════════════════════
    // Assinatura SaaS
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm.saas',
        'name'  => 'Assinatura e Créditos SaaS',
        'route' => 'admin.lawfirm.saas.index',
        'sort'  => 8,
    ],
    [
        'key'   => 'lawfirm.saas.manage',
        'name'  => 'Gerenciar Assinatura e Créditos',
        'route' => 'admin.lawfirm.saas.index',
        'sort'  => 1,
    ],

    // ══════════════════════════════════════════════════════════
    // GED (Documentos)
    // ══════════════════════════════════════════════════════════
    [
        'key'   => 'lawfirm.documentos',
        'name'  => 'Documentos (GED)',
        'route' => 'admin.processos.store_documents',
        'sort'  => 9,
    ],
    [
        'key'   => 'lawfirm.documentos.create',
        'name'  => 'Enviar Documentos',
        'route' => 'admin.processos.store_documents',
        'sort'  => 1,
    ],
];
