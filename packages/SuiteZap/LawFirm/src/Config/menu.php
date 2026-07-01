<?php

/**
 * LawFirm Menu Configuration
 *
 * Each menu item's 'permission' key MUST match an ACL key from acl.php.
 * Krayin's sidebar renderer checks bouncer()->hasPermission($permission)
 * before showing the menu item.
 *
 * Hierarchical keys MUST follow the pattern: parent.child.grandchild
 * to appear correctly nested in the sidebar.
 */

return [

    // ══════════════════════════════════════════════════════════
    // TOP-LEVEL: Jurídico
    // ══════════════════════════════════════════════════════════
    [
        'key'        => 'lawfirm',
        'name'       => 'Jurídico',
        'route'      => 'admin.processos.index',
        'sort'       => 2,
        'icon-class' => 'icon-note',
        'permission' => 'lawfirm',
    ],

    // ─── Kanban ──────────────────────────────────────────────
    [
        'key'        => 'lawfirm.kanban',
        'name'       => 'Kanban',
        'route'      => 'admin.lawfirm.legal.kanban.index',
        'sort'       => 1,
        'icon-class' => 'icon-pipeline',
        'permission' => 'lawfirm.kanban.view',
    ],

    // ─── Casos ────────────────────────────────────────────────
    [
        'key'        => 'lawfirm.casos',
        'name'       => 'Casos',
        'route'      => 'admin.lawfirm.casos.index',
        'sort'       => 2,
        'icon-class' => '',
        'permission' => 'lawfirm.casos.view',
    ],

    // ─── Processos ───────────────────────────────────────────
    [
        'key'        => 'lawfirm.processos',
        'name'       => 'Processos',
        'route'      => 'admin.processos.index',
        'sort'       => 3,
        'icon-class' => '',
        'permission' => 'lawfirm.processos.view',
    ],

    // ─── Tarefas ─────────────────────────────────────────────
    [
        'key'        => 'lawfirm.tarefas',
        'name'       => 'Tarefas',
        'route'      => 'admin.activities.index',
        'sort'       => 3.5,
        'icon-class' => 'icon-activity',
        'permission' => 'lawfirm.tarefas.view',
    ],

    // ─── Modelos de Documentos ──────────────────────────────
    [
        'key'        => 'lawfirm.modelos',
        'name'       => 'Modelos de Docs',
        'route'      => 'admin.modelos.index',
        'sort'       => 4.5,
        'icon-class' => '',
        'permission' => 'lawfirm.modelos.view',
    ],

    // ─── Prazos ──────────────────────────────────────────────
    [
        'key'        => 'lawfirm.prazos',
        'name'       => 'Prazos',
        'route'      => 'admin.prazos.index',
        'sort'       => 4,
        'icon-class' => '',
        'permission' => 'lawfirm.prazos.view',
    ],

    // ─── Agenda ──────────────────────────────────────────────
    [
        'key'        => 'lawfirm.agenda',
        'name'       => 'Agenda',
        'route'      => 'admin.lawfirm.agenda.index',
        'sort'       => 5,
        'icon-class' => 'icon-calendar',
        'permission' => 'lawfirm.agenda.view',
    ],

    // ═══════════════════════════════════════════════════════════
    // TOP-LEVEL: Financeiro
    // ═══════════════════════════════════════════════════════════
    [
        'key'        => 'financeiro',
        'name'       => 'Financeiro',
        'route'      => 'admin.lawfirm.financial.index',
        'sort'       => 5.1,
        'icon-class' => 'icon-quote text-2xl',
        'permission' => 'lawfirm.financeiro',
    ],

    // Sub-item: Dashboard
    [
        'key'        => 'financeiro.dashboard',
        'name'       => 'Dashboard',
        'route'      => 'admin.lawfirm.financial.index',
        'sort'       => 1,
        'icon-class' => 'icon-dashboard',
        'permission' => 'lawfirm.financeiro.view',
    ],

    // Sub-item: Cobranças
    [
        'key'        => 'financeiro.cobrancas',
        'name'       => 'Cobranças / Movimentações',
        'route'      => 'admin.lawfirm.tenant_finance.index',
        'sort'       => 2,
        'icon-class' => 'icon-revenue',
        'permission' => 'lawfirm.financeiro.cobrancas.view',
    ],

    // ═══════════════════════════════════════════════════════════
    // TOP-LEVEL: Assistentes de IA
    // ═══════════════════════════════════════════════════════════
    [
        'key'        => 'assistants',
        'name'       => 'Assistentes de IA',
        'route'      => 'lawfirm.assistants.index',
        'sort'       => 4,
        'icon-class' => 'icon-user',
        'permission' => 'lawfirm.assistants',
    ],

    // 0 ─── Assistentes IA (hub principal) ────────────────────
    [
        'key'        => 'assistants.ia',
        'name'       => 'Assistentes IA',
        'route'      => 'lawfirm.assistants.index',
        'sort'       => 0,
        'icon-class' => 'icon-dashboard',
        'permission' => 'lawfirm.assistants',
    ],

    // 1 ─── Assistentes Jurídicos ──────────────────────────────
    [
        'key'        => 'assistants.escavador',
        'name'       => 'Assistentes Jurídicos',
        'route'      => 'lawfirm.escavador.index',
        'sort'       => 1,
        'icon-class' => 'icon-note',
        'permission' => 'lawfirm.escavador',
    ],

    // 2 ─── Criar Monitoramentos ───────────────────────────────
    [
        'key'        => 'assistants.escavador_monitoramentos_create',
        'name'       => 'Criar Monitoramentos',
        'route'      => 'lawfirm.escavador.monitoramentos.create',
        'sort'       => 2,
        'icon-class' => '',
        'permission' => 'lawfirm.escavador.create',
    ],

    // 3 ─── EscavAI ────────────────────────────────────────────
    [
        'key'        => 'assistants.escavai',
        'name'       => 'EscavAI',
        'route'      => 'lawfirm.assistants.escavai',
        'sort'       => 3,
        'icon-class' => 'icon-user',
        'permission' => 'lawfirm.assistants.escavai',
    ],

    // ═══════════════════════════════════════════════════════════
    // TOP-LEVEL: SAC
    // ═══════════════════════════════════════════════════════════
    [
        'key'        => 'sac',
        'name'       => 'SAC',
        'route'      => 'lawfirm.assistants.chatwoot',
        'sort'       => 4.5,
        'icon-class' => 'icon-settings-webhooks',
        'permission' => 'sac',
    ],

    // 4 ─── Históricos Assist. IA ──────────────────────────────
    [
        'key'        => 'assistants.ai_history',
        'name'       => 'Históricos Assist. IA',
        'route'      => 'lawfirm.assistants.history.index',
        'sort'       => 4,
        'icon-class' => 'icon-dashboard',
        'permission' => 'lawfirm.assistants.view',
    ],

    // 5 ─── Históricos Assist. Jurídico ────────────────────────
    [
        'key'        => 'assistants.escavador_history',
        'name'       => 'Históricos Assist. Jurídico',
        'route'      => 'lawfirm.escavador.history',
        'sort'       => 5,
        'icon-class' => 'icon-clock',
        'permission' => 'lawfirm.escavador.view',
    ],

    // 6 ─── Monitoramentos / Robôs ─────────────────────────────
    [
        'key'        => 'assistants.escavador_monitoramentos',
        'name'       => 'Monitoramentos / Robôs',
        'route'      => 'lawfirm.escavador.monitoramentos.index',
        'sort'       => 6,
        'icon-class' => '',
        'permission' => 'lawfirm.escavador.view',
    ],
];
