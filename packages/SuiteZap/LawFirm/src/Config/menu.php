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

    // Child 4: Dados do Escritório
    [
        'key' => 'lawfirm.settings',
        'name' => 'Dados do Escritório',
        'route' => 'admin.configuration.index',
        'params' => ['slug' => 'lawfirm', 'slug2' => 'settings'],
        'sort' => 20,
        'icon-class' => 'icon-setting',
        'permission' => 'lawfirm.settings.view',
    ],

    // Child 5: SaaS Dashboard (Minha Assinatura)
    [
        'key' => 'lawfirm.saas_dashboard',
        'name' => 'Minha Assinatura',
        'route' => 'admin.lawfirm.saas.index',
        'sort' => 50,
        'icon-class' => 'icon-settings',
    ],

    // Child 6: WhatsApp Integration
    [
        'key' => 'lawfirm.whatsapp',
        'name' => 'WhatsApp',
        'route' => 'admin.lawfirm.whatsapp.index',
        'sort' => 60,
        'icon-class' => 'icon-sales', // Using generic icon
    ],
];
