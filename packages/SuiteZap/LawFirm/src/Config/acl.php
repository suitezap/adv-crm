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

    // Child 5 Group: Assistentes IA
    [
        'key' => 'lawfirm.assistants',
        'name' => 'Assistentes IA',
        'route' => 'lawfirm.assistants.index',
        'sort' => 5,
    ],

    // Child 6 Group: Assistente Jurídico
    [
        'key' => 'lawfirm.escavador',
        'name' => 'Assistente Jurídico',
        'route' => 'lawfirm.escavador.index',
        'sort' => 6,
    ],

    // Child 6.1 Group: Certificados Digitais
    [
        'key' => 'lawfirm.escavador_certs',
        'name' => 'Certificados Digitais (Jurídico)',
        'route' => 'lawfirm.escavador.certificados.view',
        'sort' => 7,
    ],

    // Child 7 Group: Assinatura SaaS
    [
        'key' => 'lawfirm.saas',
        'name' => 'Assinatura e Créditos SaaS',
        'route' => 'admin.lawfirm.saas.index',
        'sort' => 8,
    ],
    // Child 7 Item: Gerenciar Assinatura
    [
        'key' => 'lawfirm.saas.manage',
        'name' => 'Gerenciar Assinatura e Créditos',
        'route' => 'admin.lawfirm.saas.index',
        'sort' => 1,
    ],
];

