<?php

return [
    [
        'key' => 'lawfirm',
        'name' => 'Jurídico',
        'info' => 'Configurações do módulo Jurídico',
        'sort' => 5,
    ],
    [
        'key' => 'lawfirm.settings',
        'name' => 'Personalização',
        'info' => 'Personalize a identidade visual e dados de contato',
        'sort' => 1,
        'icon' => 'icon-setting',
        'icon-class' => 'icon-setting',
    ],
    [
        'key' => 'lawfirm.settings.general',
        'name' => 'Identidade & Qualificação',
        'info' => 'Defina o nome, logo e rodapé dos documentos',
        'sort' => 1,
        'fields' => [
            // --- BLOCO 1: Identificação (Híbrido PF/PJ) ---
            [
                'name' => 'company_name',
                'title' => 'Nome do Escritório ou Advogado(a)',
                'type' => 'text',
                'validation' => 'required', // Obrigatório
                'channel_based' => true,
                'info' => 'Nome que aparecerá no cabeçalho dos documentos.',
            ],
            [
                'name' => 'document_id',
                'title' => 'CPF ou CNPJ',
                'type' => 'text',
                'validation' => 'required', // Vital para contratos
                'channel_based' => true,
                'info' => 'Documento fiscal para qualificação em contratos.',
            ],
            [
                'name' => 'oab_number',
                'title' => 'Registro OAB',
                'type' => 'text',
                'channel_based' => true,
                'info' => 'Ex: OAB/SP 123.456',
            ],
            [
                'name' => 'logo',
                'title' => 'Logo (Cabeçalho)',
                'type' => 'image',
                'validation' => 'mimes:jpeg,bmp,png,jpg',
                'channel_based' => true,
            ],

            // --- BLOCO 2: Contatos (Validados) ---
            [
                'name' => 'contact_whatsapp', // MANTIDO 'contact_whatsapp' para compatibilidade
                'title' => 'WhatsApp / Contato Principal',
                'type' => 'text',
                'validation' => 'required', // Obrigatório
                'channel_based' => true,
                'info' => 'Aparecerá no rodapé dos recibos.',
            ],
            [
                'name' => 'contact_email',
                'title' => 'E-mail Profissional',
                'type' => 'text',
                'validation' => 'required|email', // Validação estrita de e-mail
                'channel_based' => true,
            ],
            [
                'name' => 'website',
                'title' => 'Site / Redes Sociais',
                'type' => 'text',
                'channel_based' => true,
            ],

            // --- BLOCO 3: Dados de Endereço (Asaas-Compatible) ---
            [
                'name'         => 'address_cep',
                'title'        => 'CEP',
                'type'         => 'text',
                'channel_based'=> true,
                'info'         => 'CEP no formato 00000-000. O endereço será preenchido automaticamente.',
            ],
            [
                'name'         => 'address_street',
                'title'        => 'Logradouro (Rua/Avenida)',
                'type'         => 'text',
                'channel_based'=> true,
                'info'         => 'Ex: Rua das Flores. Preenchido automaticamente pelo CEP.',
            ],
            [
                'name'         => 'address_number',
                'title'        => 'Número',
                'type'         => 'text',
                'channel_based'=> true,
            ],
            [
                'name'         => 'address_complement',
                'title'        => 'Complemento',
                'type'         => 'text',
                'channel_based'=> true,
                'info'         => 'Ex: Sala 302, Andar 3, Casa B',
            ],
            [
                'name'         => 'address_province',
                'title'        => 'Bairro',
                'type'         => 'text',
                'channel_based'=> true,
            ],
            [
                'name'         => 'city',
                'title'        => 'Cidade',
                'type'         => 'text',
                'channel_based'=> true,
                'info'         => 'Ex: São Paulo. Usada em documentos e na cobrança do Asaas.',
            ],
            [
                'name'         => 'address_state',
                'title'        => 'Estado (UF)',
                'type'         => 'text',
                'channel_based'=> true,
                'info'         => 'Ex: SP, RJ, MG',
            ],
            // --- BLOCO 4: Integrações (WhatsApp) ---
            // Credenciais movidas para o .env por segurança
        ],
    ],
    [
        'key' => 'lawfirm.whatsapp',
        'name' => 'WhatsApp',
        'info' => 'Gerencie a conexão, QR Code e integração WhatsApp',
        'sort' => 2,
        'icon' => 'icon-setting',
        'route' => 'admin.lawfirm.whatsapp.index',
    ],
    [
        'key' => 'lawfirm.billing',
        'name' => 'Dados Faturamento',
        'info' => 'Configuração dos dados do comprador (SaaS Asaas)',
        'sort' => 3,
        'icon' => 'icon-user',
        'route' => 'admin.lawfirm.saas.billing-info.index',
    ],
    [
        'key' => 'lawfirm.whatsapp_templates',
        'name' => 'Templates WhatsApp',
        'info' => 'Configure os textos das mensagens automáticas',
        'sort' => 4,
        'icon' => 'icon-setting',
    ],
    [
        'key' => 'lawfirm.whatsapp_templates.messages',
        'name' => 'Mensagens Automáticas',
        'info' => 'Defina os modelos de mensagens',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'new_prazo_client',
                'title' => 'Novo Prazo (Notificação Cliente)',
                'type' => 'textarea',
                'validation' => 'required',
                'channel_based' => true,
                'info' => 'Variáveis disponíveis: {cliente_nome}, {prazo_titulo}, {prazo_data}, {prazo_descricao}.',
                'default' => 'Olá {cliente_nome}, informamos um novo prazo no seu processo: {prazo_titulo}. Data: {prazo_data}. {prazo_descricao}',
            ],
            [
                'name' => 'document_request',
                'title' => 'Solicitação de Documentos (Importação de Kit)',
                'type' => 'textarea',
                'default' => "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que nos envie os seguintes documentos do kit {kit_nome}:\n{lista_documentos}\nPode enviar fotos legíveis por aqui mesmo.",
            ],
            [
                'name' => 'financial_billing_due_today',
                'title' => 'Cobrança Financeira (No Prazo/Futuro)',
                'type' => 'textarea',
                'rows' => 4,
                'channel_based' => true,
                'info' => 'Variáveis: {cliente_nome}, {valor}, {descricao}, {data_vencimento}.',
                'default' => "Olá {cliente_nome}, lembrete amigável do vencimento de {descricao} no valor de {valor} para o dia {data_vencimento}.",
            ],
            [
                'name' => 'financial_billing_overdue',
                'title' => 'Cobrança Financeira (Em Atraso)',
                'type' => 'textarea',
                'rows' => 4,
                'channel_based' => true,
                'info' => 'Variáveis: {cliente_nome}, {valor}, {descricao}, {data_vencimento}.',
                'default' => "Olá {cliente_nome}, verificamos uma pendência de {valor} referente a {descricao}, vencida em {data_vencimento}. Podemos atualizar o boleto?",
            ],
            [
                'name' => 'escavador_monitoramento_update',
                'title' => 'Atualização de Monitoramento Jurídico (Escavador)',
                'type' => 'textarea',
                'rows' => 4,
                'channel_based' => true,
                'info' => 'Variáveis: {termo_monitorado}, {data_atualizacao}, {fonte}.',
                'default' => "Olá! Detectamos uma nova movimentação do seu monitoramento '{termo_monitorado}' em {fonte} na data de {data_atualizacao}. Acesse o CRM para verificar a íntegra.",
            ],
        ],
    ],
    [
        'key' => 'lawfirm.escavador_certs',
        'name' => 'Certificados Digitais',
        'info' => 'Gerencie os certificados digitais exigidos por tribunais judiciais',
        'sort' => 4,
        'icon' => 'icon-setting',
        'route' => 'lawfirm.escavador.certificados.view',
    ],
    [
        'key' => 'lawfirm.saas_dashboard',
        'name' => 'Minha Assinatura',
        'info' => 'Gerencie sua assinatura, recursos e limite de tokens',
        'sort' => 5,
        'icon' => 'icon-setting',
        'route' => 'admin.lawfirm.saas.index',
        'permission' => 'lawfirm.saas.manage',
    ],
    [
        'key' => 'lawfirm.saas_transactions',
        'name' => 'Uso de Créditos em Assistentes',
        'info' => 'Histórico financeiro e consumo de serviços pagos',
        'sort' => 6,
        'icon' => 'icon-setting',
        'route' => 'lawfirm.saas.transactions',
        'permission' => 'lawfirm.saas.manage',
    ],
];

