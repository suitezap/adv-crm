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

            // --- BLOCO 3: Endereço ---
            [
                'name' => 'address',
                'title' => 'Endereço Completo',
                'type' => 'textarea',
                'validation' => 'required', // Obrigatório para contratos
                'channel_based' => true,
                'info' => 'Rua, Número, Bairro, Cidade - UF, CEP.',
            ],
            [
                'name' => 'city',
                'title' => 'Cidade (para Data de Documentos)',
                'type' => 'text',
                'channel_based' => true,
                'info' => 'Ex: São Paulo. Usada na data de procurações e contratos.',
            ],
            // --- BLOCO 4: Integrações (WhatsApp) ---
            // Credenciais movidas para o .env por segurança
        ],
    ],
    [
        'key' => 'lawfirm.whatsapp_templates',
        'name' => 'Templates WhatsApp',
        'info' => 'Configure os textos das mensagens automáticas',
        'sort' => 2,
        'icon' => 'icon-speech-bubble',
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
                'channel_based' => true,
                'info' => 'Variáveis: {cliente_nome}, {processo_titulo}, {kit_nome}, {lista_documentos}.',
                'default' => "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que nos envie os seguintes documentos do kit {kit_nome}:\n{lista_documentos}\nPode enviar fotos legíveis por aqui mesmo.",
            ],
        ],
    ],
];
