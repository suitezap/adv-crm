<?php

return [
    // Parent: Jurídico
    [
        'key' => 'lawfirm',
        'name' => 'Jurídico',
        'route' => 'admin.processos.index',
        'sort' => 1,
    ],

    // Child 1 Group: Processos
    [
        'key' => 'lawfirm.processos',
        'name' => 'Processos',
        'route' => 'admin.processos.index',
        'sort' => 1,
    ],
    // Child 1 Item: View Processos
    [
        'key' => 'lawfirm.processos.view',
        'name' => 'Visualizar Processos',
        'route' => 'admin.processos.index',
        'sort' => 1,
    ],

    // Child 2 Group: Prazos
    [
        'key' => 'lawfirm.prazos',
        'name' => 'Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 2,
    ],
    // Child 2 Item: View Prazos
    [
        'key' => 'lawfirm.prazos.view',
        'name' => 'Visualizar Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 1,
    ],

    // Child 3 Group: Financeiro
    [
        'key' => 'lawfirm.financial',
        'name' => 'Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 3,
    ],
    // Child 3 Item: View Financeiro
    [
        'key' => 'lawfirm.financial.view',
        'name' => 'Visualizar Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 1,
    ],

    // Child 4 Group: Dados do Escritório
    [
        'key' => 'lawfirm.settings',
        'name' => 'Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort' => 4,
    ],
    // Child 4 Item: View Dados do Escritório
    [
        'key' => 'lawfirm.settings.view',
        'name' => 'Visualizar Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort' => 1,
    ],
];
