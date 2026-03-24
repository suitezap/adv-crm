<?php

use Illuminate\Support\Facades\DB;

$newConfigs = [
    // NOVOS V1 (Síncronos)
    ['key' => 'escavador_price_pagina_diario', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Página do Diário'],
    ['key' => 'escavador_price_pessoas_instituicao', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Pessoas Instituição'],
    ['key' => 'escavador_price_processos_instituicao', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Processos Instituição'],
    ['key' => 'escavador_price_doc_juris', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Documento Jurisprudência'],
    ['key' => 'escavador_price_pdf_juris', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 PDF Jurisprudência'],
    ['key' => 'escavador_price_busca_legis', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Busca Legislação'],
    ['key' => 'escavador_price_doc_legis', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Documento Legislação'],
    ['key' => 'escavador_price_frag_legis', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Fragmentos Legislação'],
    ['key' => 'escavador_price_mov_processo_diario', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Movimentações Diário'],
    ['key' => 'escavador_price_detalhes_pessoa', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Detalhes Pessoa'],
    ['key' => 'escavador_price_processos_pessoa', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Processos Pessoa'],
    ['key' => 'escavador_price_autos_docs_esp', 'value' => '0.75', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Autos Docs Específicos'],
    ['key' => 'escavador_price_busca_proc_diario_oab', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Busca Procs Diário OAB'],
    ['key' => 'escavador_price_busca_proc_diario_num', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Busca Procs Diário Número'],
    ['key' => 'escavador_price_envolvidos_proc_diario', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Envolvidos Proc Diário'],
    ['key' => 'escavador_price_mov_proc_diario', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Movimentos Proc Diário'],
    ['key' => 'escavador_price_proc_diario', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V1 Processo Diário Oficial'],

    // NOVOS V2 (Assíncronos e Detalhados)
    ['key' => 'escavador_price_atualizacao_processo_docs', 'value' => '0.75', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Atualização Processo (alguns docs)'],
    ['key' => 'escavador_price_atualizacao_processo_autos', 'value' => '1.50', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Atualização Processo (baixar autos)'],
    ['key' => 'escavador_price_atualizacao_processo_pub', 'value' => '0.20', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Atualização Processo (docs publicos)'],
    ['key' => 'escavador_price_processos_envolvido_cpf', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Busca Env CPF Assincrono'],
    ['key' => 'escavador_price_resumo_advogado_oab', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Resumo Adv OAB'],
    ['key' => 'escavador_price_resumo_envolvido', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Resumo Envolvido'],
    ['key' => 'escavador_price_documentos_publicos', 'value' => '0.06', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Documentos Publicos'],
    ['key' => 'escavador_price_envolvidos_processo', 'value' => '0.05', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Envolvidos Processo'],
    ['key' => 'escavador_price_movimentacoes_processo', 'value' => '3.00', 'type' => 'decimal', 'group' => 'escavador', 'description' => 'Preço V2 Movimentos Processo CNJ'],
];

$now = now();
foreach ($newConfigs as $config) {
    try {
        $exists = DB::connection('mothership')->table('app_config')->where('key', $config['key'])->first();
        if (!$exists) {
            $config['created_at'] = $now;
            $config['updated_at'] = $now;
            DB::connection('mothership')->table('app_config')->insert($config);
            echo "Inserted " . $config['key'] . "\n";
        } else {
            echo "Skipped (Exists) " . $config['key'] . "\n";
        }
    } catch (\Exception $e) {
        echo "Error " . $config['key'] . ": " . $e->getMessage() . "\n";
    }
}
echo "Done!\n";
