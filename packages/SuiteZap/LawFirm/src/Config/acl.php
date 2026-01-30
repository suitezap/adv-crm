<?php

return [
    // Parent: Jurídico
    [
        'key' => 'lawfirm',
        'name' => 'Jurídico',
        'route' => 'admin.processos.index',
        'sort' => 1,
    ],

    // Child 1: Processos
    [
        'key' => 'lawfirm.processos.view',
        'name' => 'Processos',
        'route' => 'admin.processos.index',
        'sort' => 1,
    ],

    // Child 2: Prazos
    [
        'key' => 'lawfirm.prazos.view',
        'name' => 'Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 2,
    ],

    // Child 3: Financeiro
    [
        'key' => 'lawfirm.financial.view',
        'name' => 'Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 3,
    ],

    // Child 4: Dados do Escritório
    [
        'key' => 'lawfirm.settings.view',
        'name' => 'Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort' => 4,
    ],
];
