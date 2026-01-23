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
        'key' => 'lawfirm.processos',
        'name' => 'Processos',
        'route' => 'admin.processos.index',
        'sort' => 1,
    ],

    // Child 2: Prazos
    [
        'key' => 'lawfirm.prazos',
        'name' => 'Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 2,
    ],

    // Child 3: Financeiro
    [
        'key' => 'lawfirm.financial',
        'name' => 'Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 3,
    ],

    // Child 4: Dados do Escritório
    [
        'key' => 'lawfirm.settings',
        'name' => 'Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort' => 4,
    ],
];
