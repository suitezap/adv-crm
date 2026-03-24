<?php

return [
    // Parent Menu: Jurídico
    [
        'key' => 'lawfirm',
        'name' => 'Jurídico',
        'route' => 'admin.processos.index',
        'sort' => 2,
        'icon-class' => 'icon-note',
        'permission' => 'lawfirm', // Keep parent generic or update if needed, usually parent is just container
    ],

    // Child 1: Processos
    [
        'key' => 'lawfirm.processos',
        'name' => 'Processos',
        'route' => 'admin.processos.index',
        'sort' => 1,
        'icon-class' => '',
        'permission' => 'lawfirm.processos.view',
    ],

    // Child 2: Prazos
    [
        'key' => 'lawfirm.prazos',
        'name' => 'Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 2,
        'icon-class' => 'icon-calendar',
        'permission' => 'lawfirm.prazos.view',
    ],

    // Child 3: Financeiro
    [
        'key' => 'lawfirm.financial',
        'name' => 'Dashboard Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 3,
        'icon-class' => 'icon-dashboard',
        'permission' => 'lawfirm.financial.view',
    ],

    // Child 4: Assistentes IA
    [
        'key' => 'lawfirm.assistants',
        'name' => 'Assistentes IA',
        'route' => 'lawfirm.assistants.index',
        'sort' => 4,
        'icon-class' => 'icon-dashboard',
        'permission' => 'lawfirm.assistants',
    ],

    // Child 5: Histórico IA
    [
        'key' => 'lawfirm.ai_history',
        'name' => 'Histórico Assist. IA',
        'route' => 'lawfirm.assistants.history.index',
        'sort' => 5,
        'icon-class' => 'icon-dashboard', // Ensure it has a recognizable icon
        'permission' => 'lawfirm.assistants', // Shared permissions
    ],

    // Child 6: Assistente Jurídico (Escavador API)
    [
        'key' => 'lawfirm.escavador',
        'name' => 'Assistente Jurídico',
        'route' => 'lawfirm.escavador.index',
        'sort' => 6,
        'icon-class' => 'icon-note',
        'permission' => 'lawfirm.escavador',
    ],

    // Child: Histórico dos Assistentes Jurídicos
    [
        'key' => 'lawfirm.escavador_history',
        'name' => 'Histórico Assist. Jurídico',
        'route' => 'lawfirm.escavador.history',
        'sort' => 7,
        'icon-class' => 'icon-clock',
        'permission' => 'lawfirm.escavador',
    ],
    [
        'key' => 'lawfirm.escavador_monitoramentos',
        'name' => 'Monitoramentos / Robôs',
        'route' => 'lawfirm.escavador.monitoramentos.index',
        'sort' => 99,
        'icon-class' => '',
        'permission' => 'lawfirm.escavador',
    ],
    [
        'key' => 'lawfirm.escavador_monitoramentos_create',
        'name' => 'Criar Monitoramentos',
        'route' => 'lawfirm.escavador.monitoramentos.create',
        'sort' => 98,
        'icon-class' => '',
        'permission' => 'lawfirm.escavador',
    ],
];
