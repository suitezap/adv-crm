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
    [
        'key' => 'lawfirm.processos.create',
        'name' => 'Criar Processos',
        'route' => ['admin.processos.create', 'admin.processos.store'],
        'sort' => 1,
    ],
    [
        'key' => 'lawfirm.processos.edit',
        'name' => 'Editar Processos',
        'route' => ['admin.processos.edit', 'admin.processos.update'],
        'sort' => 2,
    ],
    [
        'key' => 'lawfirm.processos.delete',
        'name' => 'Deletar Processos',
        'route' => ['admin.processos.delete', 'admin.processos.mass_delete'],
        'sort' => 3,
    ],
    [
        'key' => 'lawfirm.processos.view',
        'name' => 'Visualizar Processos',
        'route' => 'admin.processos.index',
        'sort' => 4,
    ],

    // Child 2 Group: Prazos
    [
        'key' => 'lawfirm.prazos',
        'name' => 'Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 2,
    ],
    [
        'key' => 'lawfirm.prazos.create',
        'name' => 'Criar Prazos',
        'route' => ['admin.prazos.create', 'admin.prazos.store'],
        'sort' => 1,
    ],
    [
        'key' => 'lawfirm.prazos.edit',
        'name' => 'Editar Prazos',
        'route' => ['admin.prazos.edit', 'admin.prazos.update'],
        'sort' => 2,
    ],
    [
        'key' => 'lawfirm.prazos.delete',
        'name' => 'Deletar Prazos',
        'route' => ['admin.prazos.delete', 'admin.prazos.mass_delete'],
        'sort' => 3,
    ],
    [
        'key' => 'lawfirm.prazos.view',
        'name' => 'Visualizar Prazos',
        'route' => 'admin.prazos.index',
        'sort' => 4,
    ],

    // Child 3 Group: Financeiro
    [
        'key' => 'lawfirm.financial',
        'name' => 'Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 3,
    ],
    [
        'key' => 'lawfirm.financial.create',
        'name' => 'Criar Registro Financeiro',
        'route' => ['admin.lawfirm.financial.create', 'admin.lawfirm.financial.store'],
        'sort' => 1,
    ],
    [
        'key' => 'lawfirm.financial.edit',
        'name' => 'Editar Registro Financeiro',
        'route' => ['admin.lawfirm.financial.edit', 'admin.lawfirm.financial.update'],
        'sort' => 2,
    ],
    [
        'key' => 'lawfirm.financial.delete',
        'name' => 'Deletar Registro Financeiro',
        'route' => ['admin.lawfirm.financial.delete', 'admin.lawfirm.financial.mass_delete'],
        'sort' => 3,
    ],
    [
        'key' => 'lawfirm.financial.view',
        'name' => 'Visualizar Financeiro',
        'route' => 'admin.lawfirm.financial.index',
        'sort' => 4,
    ],

    // Child 4 Group: Dados do Escritório
    [
        'key' => 'lawfirm.settings',
        'name' => 'Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort' => 4,
    ],
    [
        'key' => 'lawfirm.settings.view',
        'name' => 'Visualizar Dados do Escritório',
        'route' => 'admin.configuration.index',
        'sort' => 1,
    ],

    // Child 5 Group: Assistentes IA
    [
        'key' => 'lawfirm.assistants',
        'name' => 'Assistentes IA e Histórico',
        'route' => 'lawfirm.assistants.index',
        'sort' => 5,
    ],
    [
        'key' => 'lawfirm.assistants.execute',
        'name' => 'Executar Assistentes',
        'route' => ['lawfirm.assistants.execute', 'lawfirm.assistants.generate'],
        'sort' => 1,
    ],
    [
        'key' => 'lawfirm.assistants.view',
        'name' => 'Ver Assistentes e Histórico',
        'route' => ['lawfirm.assistants.index', 'lawfirm.assistants.history.index'],
        'sort' => 2,
    ],

    // Child 6 Group: Assistente Jurídico (Escavador)
    [
        'key' => 'lawfirm.escavador',
        'name' => 'Assistente Jurídico e Monitoramentos',
        'route' => 'lawfirm.escavador.index',
        'sort' => 6,
    ],
    [
        'key' => 'lawfirm.escavador.create',
        'name' => 'Criar Monitoramento / Executar Busca',
        'route' => ['lawfirm.escavador.monitoramentos.create', 'lawfirm.escavador.monitoramentos.store', 'lawfirm.escavador.buscar'],
        'sort' => 1,
    ],
    [
        'key' => 'lawfirm.escavador.view',
        'name' => 'Visualizar Monitoramentos / Histórico',
        'route' => ['lawfirm.escavador.index', 'lawfirm.escavador.monitoramentos.index', 'lawfirm.escavador.history'],
        'sort' => 2,
    ],

    // Child 6.1: Certificados
    [
        'key' => 'lawfirm.escavador.certs',
        'name' => 'Certificados Digitais (Jurídico)',
        'route' => 'lawfirm.escavador.certificados.view',
        'sort' => 3,
    ],
    [
        'key' => 'lawfirm.escavador.certs.manage',
        'name' => 'Gerenciar Certificados',
        'route' => 'lawfirm.escavador.certificados.store',
        'sort' => 1,
    ],

    // Child 7 Group: Assinatura SaaS
    [
        'key' => 'lawfirm.saas',
        'name' => 'Assinatura e Créditos SaaS',
        'route' => 'admin.lawfirm.saas.index',
        'sort' => 8,
    ],
    [
        'key' => 'lawfirm.saas.manage',
        'name' => 'Gerenciar Assinatura e Créditos',
        'route' => 'admin.lawfirm.saas.index',
        'sort' => 1,
    ],
];

