<?php

$csvFile = __DIR__ . '/ai_studio_code (atlz).csv';
$data = array_map('str_getcsv', file($csvFile));
$header = array_shift($data);

// Headers: "Versão","Menu do CRM","Subcategoria API","Título Exato da Requisição","Descrição da Requisição","HTTP Request","Custo"

$phpArray = [];
$jsInfo = [];

$apiMapFields = [
    // V1
    'Buscar por termo' => "[{ name: 'q', label: 'Termo de Busca', required: true }]",
    'Download do PDF da página do Diário Oficial' => "[{ name: 'id', label: 'ID da Publicação', required: true }, { name: 'pagina', label: 'Página', required: true }]",
    'Página do Diário Oficial' => "[{ name: 'id', label: 'ID da Página', required: true }]",
    'Obter Instituição' => "[{ name: 'instituicaoId', label: 'ID Instituição', required: true }]",
    'Pessoas de uma Instituição' => "[{ name: 'instituicaoId', label: 'ID da Instituição', required: true }]",
    'Processos de uma Instituição' => "[{ name: 'instituicaoId', label: 'ID da Instituição', required: true }]",
    'Busca por Jurisprudências' => "[{ name: 'q', label: 'Termo (Ex: Indenização)', required: true }]",
    'Documento de Jurisprudência' => "[{ name: 'id', label: 'ID Documento', required: true }]",
    'PDF de uma jurisprudência' => "[{ name: 'id', label: 'ID Documento', required: true }]",
    'Busca por Legislação' => "[{ name: 'q', label: 'Termo de Busca', required: true }]",
    'Documento de Legislação' => "[{ name: 'id', label: 'ID Documento', required: true }]",
    'Fragmentos do texto de uma Legislação' => "[{ name: 'id', label: 'ID Documento', required: true }]",
    'Retornar uma movimentação' => "[{ name: 'movimentaco', label: 'ID do Movimento', required: true }]",
    'Obter pessoa' => "[{ name: 'pessoaId', label: 'ID Pessoa', required: true }]",
    'Processos de uma Pessoa' => "[{ name: 'pessoaId', label: 'ID da Pessoa', required: true }]",
    'Autos de um Processo' => "[{ name: 'id', label: 'ID Processo', required: true }]", // assumption
    'Autos de um Processo - Documentos' => "[{ name: 'id', label: 'ID Processo', required: true }]",
    'Buscar processos dos Diários por OAB' => "[{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero', label: 'Número OAB', required: true }]",
    'Buscar processos dos Diários por número' => "[{ name: 'numero', label: 'Nº do Processo', required: true }]",
    'Envolvidos de um Processo' => "[{ name: 'processoId', label: 'ID Processo', required: true }]",
    'Movimentações de um processo (D.O.)' => "[{ name: 'processoId', label: 'ID Processo', required: true }]",
    'Processo no Diário Oficial' => "[{ name: 'id', label: 'ID Processo', required: true }]",
    'Pesquisar processo no tribunal' => "[{ name: 'numero', label: 'Nº do Processo CNJ', required: true }]",
    'Pesquisar processos por CPF ou CNPJ' => "[{ name: 'origem', label: 'Sigla Origem (Ex: tjsp)', required: true }, { name: 'documento', label: 'CPF ou CNPJ', required: true }]",
    'Pesquisar processos por OAB' => "[{ name: 'origem', label: 'Sigla Origem (Ex: tjsp)', required: true }, { name: 'oab', label: 'Número OAB', required: true }]",
    'Pesquisar processos por Nome' => "[{ name: 'origem', label: 'Sigla Origem (Ex: tjsp)', required: true }, { name: 'nome', label: 'Nome da Pessoa', required: true }]",
    
    // V2
    'Solicitar geração/atualização de Resumo IA' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Solicitar atualização de um processo' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Atualização baixando alguns documentos' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Atualização baixando autos inteiros' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Atualização baixando documentos públicos' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Processos de um advogado por OAB' => "[{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero_oab', label: 'Número OAB', required: true }]",
    'Processos do envolvido por CPF/CNPJ ou Nome' => "[{ name: 'cpf_cnpj', label: 'CPF/CNPJ ou Nome', required: true }]",
    'Resumo de processos por OAB' => "[{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero_oab', label: 'Número OAB', required: true }]",
    'Resumo de Processos do Envolvido' => "[{ name: 'cpf_cnpj', label: 'CPF/CNPJ ou Nome', required: true }]",
    'Autos do processo (públicos e restritos)' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Processo por numeração CNJ (Capa)' => "[{ name: 'numero', label: 'Número CNJ', placeholder: '0000000-00.0000.0.00.0000', required: true }]",
    'Documentos públicos de um processo' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Envolvidos de um processo' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Movimentações de um processo' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
    'Resumo Inteligente de um processo' => "[{ name: 'numero', label: 'Número CNJ', required: true }]",
];

$iconMap = [
    'Outro' => '⚙️',
    'Relatórios Jurídicos' => '📊',
    'Empresa' => '🏢',
    'Jurisprudência' => '⚖️',
    'Legislações' => '📜',
    'Processo' => '📋',
    'Pessoa' => '👤',
    'Advogado(a)' => '💼',
    'Pessoa / Empresa' => '👥',
];

$macroSlugs = [
    'Outro' => 'outro',
    'Relatórios Jurídicos' => 'relatorios',
    'Empresa' => 'empresa',
    'Jurisprudência' => 'jurisprudencia',
    'Legislações' => 'legislacao',
    'Processo' => 'processo',
    'Pessoa' => 'pessoa',
    'Advogado(a)' => 'advogado',
    'Pessoa / Empresa' => 'pessoa_empresa',
];

$phpOutput = "[\n";
$jsOutput = "    var SVC_INFO = {\n";

foreach ($data as $i => $row) {
    // 0:"Versão", 1:"Menu do CRM", 2:"Subcategoria API", 3:"Título Exato da Requisição", 4:"Descrição da Requisição", 5:"HTTP Request", 6:"Custo"
    $versao = strtolower($row[0]);
    $menuCRM = $row[1];
    $titulo = $row[3];
    $desc = str_replace("'", "\'", $row[4]);
    $req = $row[5];
    $custo = $row[6];
    
    // Ignorar endpoints de criar monitoramentos
    if (strpos($titulo, 'novo monitoramento') !== false || strpos($desc, 'Cadastra um termo ou processo para ser monitorado') !== false || strpos($titulo, 'Criar novo monitoramento') !== false) {
        continue;
    }
    
    $key = strtoupper('API_'.$versao.'_'.preg_replace('/[^a-zA-Z0-9]/', '', $titulo));
    $menuSlug = $macroSlugs[$menuCRM] ?? 'outro';
    $icon = $iconMap[$menuCRM] ?? '🚀';
    
    // Auth & Cert flags
    $reqAuth = "false";
    $reqCert = "false";
    if (strpos(strtolower($desc), 'exige credencial') !== false) {
        $reqAuth = "true";
        if (strpos(strtolower($desc), 'certificado digital') !== false || strpos(strtolower($desc), 'download físico') !== false || strpos(strtolower($desc), 'restrit') !== false) {
            $reqCert = "true";
            $reqAuth = "false"; // or handle BOTH
        } elseif (strpos(strtolower($desc), 'restrita') !== false) {
             $reqCert = "true";
        }
    }
    if ($titulo == 'Autos de um Processo - Documentos' || $titulo == 'Atualização baixando autos inteiros' || $titulo == 'Atualização baixando alguns documentos' || $titulo == 'Autos do processo (públicos e restritos)') {
        $reqCert = "true";
    }

    // PHP output element
    // Format: ['KEY', 'menu_slug', 'v1/v2', 'Icon', 'Titulo', 'Desc', 'HTTP', '']
    $phpOutput .= "    ['{$key}', '{$menuSlug}', '{$versao}', '{$icon}', '{$titulo}', '{$desc}', '{$req}', ''],\n";
    
    // JS output element
    $fields = $apiMapFields[$titulo] ?? "[]";
    $priceDisplay = $custo;
    if (strpos(strtolower($priceDisplay), 'r$') !== false) {
        //$priceDisplay = 'R$ ...'
    } else if (strtolower($priceDisplay) === 'grátis') {
        $priceDisplay = 'Grátis';
    }
    
    if($reqCert == "true") {
        $jsOutput .= "        '{$key}': { label: '{$icon} {$titulo}', price: '{$priceDisplay}', fields: {$fields}, req_cert: true },\n";
    } else if($reqAuth == "true") {
        $jsOutput .= "        '{$key}': { label: '{$icon} {$titulo}', price: '{$priceDisplay}', fields: {$fields}, req_auth: true },\n";
    } else {
        $jsOutput .= "        '{$key}': { label: '{$icon} {$titulo}', price: '{$priceDisplay}', fields: {$fields} },\n";
    }
}

$phpOutput .= "];\n";
$jsOutput .= "    };\n";

file_put_contents(__DIR__ . '/gen_out.txt', $phpOutput . "\n\n" . $jsOutput);
echo "Generated in gen_out.txt\n";
