<?php
$file = __DIR__ . "/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php";
$content = file_get_contents($file);

// 1. Remove the "Gratuitos" button
$content = preg_replace('/<button[^>]+data-filter="gratis"[^>]+>.*?<\/button>/s', '', $content);

// 2. We need to replace everything from {{-- ── CARDS GRID to </div>{{-- /Serviços Pagos --}}
$startMarker = '{{-- ── CARDS GRID ─────────────────────────────────────── --}}';
$endMarker = '</div>{{-- /Serviços Pagos --}}';
$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);
if ($startPos === false || $endPos === false) {
    die("Error finding grid markers");
}
$endPos += strlen($endMarker);

$newGrid = <<<'EOF'
{{-- ── 36 UNIFIED CARDS GRID ─────────────────────────────────────── --}}
            <div style="margin-top: 24px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="font-weight:700;font-size:.95rem;color:#1f2937;" class="dark:text-white">💳 Todos os Serviços</div>
                    <span
                        style="font-size:.72rem;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:9999px;font-weight:700;">SALDO
                        DEBITADO</span>
                    <span id="lf-svc-saldo-display" style="font-size:.8rem;color:#6b7280;margin-left:auto;"
                        class="dark:text-gray-400">💰 Carregando saldo...</span>
                </div>

                <div class="lf-esc-grid">
                    @php
                        $allCards = [
                            // API V1
                            ['BUSCA_TERMO', 'diarios', 'v1', '🔍', 'Busca Documental Ampla', 'Realiza varredura de um termo em toda a base nacional de processos, diários oficiais e estatísticas dos tribunais brasileiros.', 'GET api/v1/busca', ''],
                            ['BUSCA_DIARIO', 'diarios', 'v1', '🗞️', 'Pesquisa de Diários (Termo)', 'Filtra e pesquisa publicações, citações e editais em Diários Oficiais de Justiça da União e dos Estados do país.', 'GET api/v1/busca?qo=d', ''],
                            ['PAGINA_DIARIO', 'diarios', 'v1', '📄', 'Recuperar Página Oficial', 'Traz o texto original e as coordenadas de publicação de uma página avulsa do diário oficial estadual/federal.', 'GET api/v1/diarios/pagina/{id}', ''],
                            ['PDF_DIARIO', 'documentos', 'v1', '📰', 'Emitir PDF do Diário', 'Gera e permite baixar em formato PDF, com confiabilidade para prova de publicação, uma folha do diário de justiça.', 'GET api/v1/diarios/pdf', ''],
                            ['INFO_INSTITUICAO', 'pessoas', 'v1', '🏢', 'Quadro Empresarial (CNPJ)', 'Consulta detalhadamente estatísticas e acervos vinculados à existência e ações legais em nome de uma empresa ou organização.', 'GET api/v1/instituicoes/{id}', ''],
                            ['PESSOAS_INSTITUICAO', 'pessoas', 'v1', '👥', 'Sócios e Administradores', 'Revela indivíduos — diretores, quadro social, conselheiros — com ligações contratuais e gerenciais ativas com um CNPJ.', 'GET api/v1/instituicoes/{id}/pessoas', ''],
                            ['PROCESSOS_INSTITUICAO', 'tribunais', 'v1', '📋', 'Ações de uma Organização', 'Lista processos cíveis e comerciais em que a empresa ou entidade figura legalmente nas tramitações judiciais.', 'GET api/v1/instituicoes/{id}/processos', ''],
                            ['BUSCA_JURIS', 'diarios', 'v1', '⚖️', 'Encontrar Jurisprudências', 'Busca teses formatadas, ementas e peças sumuladas na alta corte superior e colegiados em tribunais estaduais (STJ, STF, TJs).', 'GET api/v1/jurisprudencias', ''],
                            ['DOC_JURIS', 'documentos', 'v1', '📜', 'Visualizar Ementa e Voto', 'Traz os pontos cardinais, a relatoria e as partes de uma ementa específica de jurisprudência.', 'GET api/v1/jurisprudencias/{id}', ''],
                            ['PDF_JURIS', 'documentos', 'v1', '📄', 'Download do Acórdão', 'Transfere e agrupa os pareceres decisivos na corte relatora em formato PDF autêntico para prova material judicial.', 'GET api/v1/jurisprudencias/{id}/pdf', ''],
                            ['BUSCA_LEGIS', 'documentos', 'v1', '⚖️', 'Procurar Textos da Lei', 'Filtra os recortes originários das assembleias federais com leis, resoluções federais e portarias governamentais do Estado.', 'GET api/v1/legislacoes', ''],
                            ['DOC_LEGIS', 'documentos', 'v1', '📜', 'Ler Texto de Legislação', 'Entrega a íntegra ou partes basilares dos anexos técnicos e das publicações nativas sobre decretos legislativos.', 'GET api/v1/legislacoes/{id}', ''],
                            ['FRAG_LEGIS', 'documentos', 'v1', '📄', 'Fragmentos Legais', 'Consolida a exatidão textual fragmentada de uma emenda para análises pontuais sem recorrer à peça magna inteira.', 'GET api/v1/legislacoes/{id}/fragmentos', ''],
                            ['MOV_PROCESSO_DIARIO', 'diarios', 'v1', '📜', 'Leitura da Publicação Judicial', 'Decodifica cada passo de evolução na vara em um tribunal, espelhado nas publicações oficiais publicizadas semanal ou diariamente.', 'GET api/v1/movimentacoes/{id}', ''],
                            ['INFO_PESSOA', 'pessoas', 'v1', '👤', 'Dossiê Completo de Pessoa Física', 'Recolhe fragmentos e consolida estatísticas de litígios em processos dos indivíduos atuantes nas varas brasileiras e comarcas.', 'GET api/v1/pessoas/{id}', ''],
                            ['PROCESSOS_PESSOA', 'tribunais', 'v1', '📋', 'Listar Acionamentos Físicos', 'Compila e descreve os pleitos cíveis e judiciais nos quais atua o portador real da identificação rastreada em fóruns abertos.', 'GET api/v1/pessoas/{id}/processos', ''],
                            ['AUTOS_DOCS_ESP', 'documentos', 'v1', '📂', 'Consulta ao Acervo Processual (Lim)', 'Verifica os anexos periciais e provas que já constem na matriz de acompanhamento judiciária unificada na v1 aberta.', 'GET api/v1/processos/autos', ''],
                            ['BUSCA_OAB_PAGA', 'diarios', 'v1', '📋', 'Investigar Portfólio de Advogado', 'Rastreia e elenca atuações assinadas em Diários Judiciais e de Imprensa a partir do número de inscrição e estado OAB.', 'GET api/v1/processos/oab/{oab}', ''],
                            ['BUSCA_PROC_DIARIO_NUM', 'diarios', 'v1', '🔢', 'Achar Diários via CNJ Unificado', 'Pesquisa editais passados cruzando o dígito unificado das secretarias públicas estaduais/federais.', 'GET api/v1/processos/numero/{numero}', ''],
                            ['ENVOLVIDOS_PROC_DIARIO', 'pessoas', 'v1', '👥', 'Partes Litigantes nas Cortes', 'Mostra requerentes, requeridos judiciais em conflito e profissionais de suporte jurídico anexados aos cadernos dos fóruns.', 'GET api/v1/processos/{id}/envolvidos', ''],
                            ['MOV_PROC_DIARIO', 'diarios', 'v1', '📜', 'Histórico e Despachos Retrospectivos', 'Recolhe despachos, sentenças, atos ordinatórios, com minutas de todo histórico forense atrelado publicamente.', 'GET api/v1/processos/{id}/movimentacoes', ''],
                            ['PROC_DIARIO', 'diarios', 'v1', '⚖️', 'Processo Raiz Documentado', 'Revela e formata dados mestre judiciarios extraídos originários do fórum unificado aberto (PJe e e-Saj).', 'GET api/v1/processos/{id}', ''],

                            // API V2
                            ['CAPA_PROCESSO', 'tribunais', 'v2', '🏛️', 'Capa Padrão (Sincronização em Tempo Real)', 'Recupera cabeçalhos completos com nome de juiz magistrado titular, valor da causa, tipo de cível e tribunal competente.', 'GET api/v2/processos/numero_cnj/{cnj}', ''],
                            ['ATUALIZAR_PROCESSO', 'tribunais', 'v2', '🔄', 'Forçar Atualização de Andamentos', 'Gatilho autônomo que atualiza os dados e andamentos mais recentes nos portais forenses das 27 UFs para visualização imediata.', 'POST api/v2/processos/numero_cnj/{cnj}/status-atualizacao', ''],
                            ['ATUALIZACAO_PROCESSO_DOCS', 'tribunais', 'v2', '🔄', 'Atualização com Extração Pontual', 'Aciona comando de sincronização de sistema colhendo apenas a lista processual de papéis apensados publicamente informados.', 'POST api/v2/processos/numero_cnj/{cnj}/status-atualizacao', ''],
                            ['ATUALIZACAO_PROCESSO_AUTOS', 'documentos', 'v2', '🔄', 'Atualização Massiva da Íntegra', 'Ordena a compilação extensiva autônoma virtual das pastas originais apensadas nos bancos estatais — puxando cópia real e inteira.', 'POST api/v2/processos/numero_cnj/{cnj}/status-atualizacao', ''],
                            ['ATUALIZACAO_PROCESSO_PUB', 'documentos', 'v2', '🔄', 'Atualização Seletiva Documental Livre', 'Faz sincronização judiciária com instrução rigorosa para respeitar e não extrair anexos tramitando em segredo de justiça.', 'POST api/v2/processos/numero_cnj/{cnj}/status-atualizacao', ''],
                            ['PROCESSOS_ENVOLVIDO_CPF', 'tribunais', 'v2', '📋', 'Malha Fina de Processos por CPF/CNPJ', 'Filtra e consolida processos e comarcas espalhados pelas federações forenses através do documento da parte.', 'GET api/v2/envolvido/processos', ' até 200 itens (+ R$ 3,00/200)'],
                            ['PROCESSOS_ADVOGADO_OAB', 'tribunais', 'v2', '📋', 'Atuação Judiciária Oficial do Advogado', 'Averígua atuação do procurador com a carteira de ordem, exibindo todos acordos digitais nas cortes judiciais estaduais.', 'GET api/v2/advogado/processos', ' até 200 itens (+ R$ 3,00/200)'],
                            ['RESUMO_ADVOGADO_OAB', 'pessoas', 'v2', '📋', 'Sintetizador de Advocacia (OAB)', 'Revela quantidades vitais com panorama macro da carteira processual assumida e defendida por um único procurador.', 'GET api/v2/advogado/resumo', ''],
                            ['RESUMO_ENVOLVIDO', 'pessoas', 'v2', '📋', 'Panorama Contábil do Cliente (CPF/CNPJ)', 'Compõe métrica simples consolidada de quantitativo e proporção do passivo/ativo em lides no território regional/federal do Brasil.', 'GET api/v2/envolvido/resumo', ''],
                            ['DOCUMENTOS_PUBLICOS', 'documentos', 'v2', '📄', 'Visão Panorâmica Despachos Abertos V2', 'Acesso direto à listagem consolidada de todas as deliberações documentadas no andamento da ação acessíveis em transparência global.', 'GET api/v2/processos/numero_cnj/{cnj}/documentos', ''],
                            ['ENVOLVIDOS_PROCESSO', 'pessoas', 'v2', '👥', 'Quadro Declaratório de Partes', 'Evidencia claramente toda cadeia nominal declaratória em autos forenses; nome do julgador, credenciados e intimados.', 'GET api/v2/processos/numero_cnj/{cnj}/envolvidos', ''],
                            ['MOVIMENTACOES_PROCESSO', 'tribunais', 'v2', '📜', 'Linha do Tempo Eletrônica de Instância', 'Lista cada etapa burocrática digital carimbada nas instâncias e varas civis do país contendo todas argumentações.', 'GET api/v2/processos/numero_cnj/{cnj}/movimentacoes', ''],
                            ['BAIXAR_AUTOS', 'documentos', 'v2', '📁', 'Extrair as Cópias de Autos (Arquivadas)', 'Baixa arquivos físicos contendo manifestações periciais e documentamentos na linha de tempo completa de instrução.', 'GET api/v2/processos/numero_cnj/{cnj}/autos', ''],
                            ['RESUMO_IA', 'ia', 'v2', '🧠', 'Tradução Contábil & Jurídica por IA', 'Lê com IA de ponta publicações longas convertendo a complexidade legal numa linguagem amigável pronta para enviar ao cliente no escritório.', 'POST api/v2/processos/numero_cnj/{cnj}/resumo/gerar', ' (Gerar) / R$ 0,05 (Ler)'],
                        ];
                    @endphp

                    @foreach($allCards as $card)
                        {{-- HTTP: {{ $card[6] ?? '???' }} --}}
                        <div class="lf-esc-card" data-module="{{ $card[1] }}" data-api="{{ $card[2] }}">
                            <div>
                                <div class="lf-esc-card-header">
                                    <div class="lf-esc-card-icon">{{ $card[3] }}</div>
                                    <div class="lf-esc-card-title">{{ $card[4] }}</div>
                                </div>
                                <div class="lf-esc-card-desc">{{ $card[5] }}</div>
                                <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                                    <span class="lf-esc-badge lf-esc-badge-{{ $card[2] }}">API {{ strtoupper($card[2]) }}@if($card[2] === 'v2') · Sync/Async @endif</span>
                                    <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 10px;background:#dcfce7;color:#166534;border-radius:9999px;font-size:.72rem;font-weight:800;">
                                        💰 {{ $card[2] == 'v2' && strpos($card[0], 'ATUALIZACAO') !== false && $card[0] !== 'ATUALIZAR_PROCESSO' ? 'Min ' : ($card[0] === 'ATUALIZAR_PROCESSO' ? 'Min ' : '') }} R$ {{ number_format($prices[$card[0]], 2, ',', '.') }}{{ $card[7] ?? '' }}
                                    </span>
                                </div>
                            </div>
                            <div style="margin-top:16px;border-top:1px solid #f3f4f6;padding-top:12px;" class="dark:border-gray-800">
                                <button class="lf-esc-btn" type="button" onclick="window.lfSvc.open('{{ $card[0] }}')">🚀 Executar</button>
                            </div>
                        </div>
                    @endforeach

                </div>{{-- /lf-esc-grid --}}
            </div>
EOF;

$content = substr_replace($content, $newGrid, $startPos, $endPos - $startPos);

// 3. Remove the old free modal layout from `{{-- ══════════════════════════════════════════════════════════` up to `<script>`
$oldModalStart = '{{-- ══════════════════════════════════════════════════════════';
$scriptStartPos = strpos($content, "@push('scripts')");
$oldModalPos = strpos($content, $oldModalStart);
if ($oldModalPos !== false && $scriptStartPos !== false && $oldModalPos < $scriptStartPos) {
    // we take everything from the old modal start and remove it up to the @push('scripts')
    $content = substr_replace($content, "", $oldModalPos, $scriptStartPos - $oldModalPos);
}

// 4. Update JavaScript to support complex modals and insert the full SVC_INFO
$pushMarker = "@push('scripts')";
$endModalMarker = '</div>{{-- /lf-svc-modal --}}';

$pushPos = strpos($content, $pushMarker);
$endModalPos = strpos($content, $endModalMarker);
if ($pushPos !== false && $endModalPos !== false) {
    $endModalPos += strlen($endModalMarker);

    $newJSAndModal = <<<'EOF'
@push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
                <script>
                    // Only one modal system exists now (paid)
                    (function () {
                        'use strict';

                        var CSRF = '{{ csrf_token() }}';
                        var ROUTE_SERVICO = "{{ route('lawfirm.escavador.servico') }}";
                        var ROUTE_SALDO = "{{ route('lawfirm.escavador.saldo_cliente') }}";

                        var UF_OPTIONS = 'AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO'.split(',');

                        var SVC_INFO = {
                            // V1
                            BUSCA_TERMO: { label: '🔍 Busca Documental Ampla', price: 'R$ {{ number_format($prices["BUSCA_TERMO"], 2, ",", ".") }}', fields: [{ name: 'q', label: 'Termo exato de Busca', required: true }] },
                            BUSCA_DIARIO: { label: '🗞️ Pesquisa de Diários', price: 'R$ {{ number_format($prices["BUSCA_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'q', label: 'Termo', required: true }] },
                            PAGINA_DIARIO: { label: '📄 Recuperar Página Oficial', price: 'R$ {{ number_format($prices["PAGINA_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID da Página', required: true }] },
                            PDF_DIARIO: { label: '📰 Emitir PDF do Diário', price: 'R$ {{ number_format($prices["PDF_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID da Publicação', required: true }] },
                            INFO_INSTITUICAO: { label: '🏢 Quadro Empresarial (CNPJ)', price: 'R$ {{ number_format($prices["INFO_INSTITUICAO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID Instituição', required: true }] },
                            PESSOAS_INSTITUICAO: { label: '👥 Sócios e Administradores', price: 'R$ {{ number_format($prices["PESSOAS_INSTITUICAO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID da Instituição', required: true }] },
                            PROCESSOS_INSTITUICAO: { label: '📋 Ações da Organização', price: 'R$ {{ number_format($prices["PROCESSOS_INSTITUICAO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID da Instituição', required: true }] },
                            BUSCA_JURIS: { label: '⚖️ Encontrar Jurisprudências', price: 'R$ {{ number_format($prices["BUSCA_JURIS"], 2, ",", ".") }}', fields: [{ name: 'q', label: 'Termo (Ex: Indenização)', required: true }] },
                            DOC_JURIS: { label: '📜 Visualizar Ementa e Voto', price: 'R$ {{ number_format($prices["DOC_JURIS"], 2, ",", ".") }}', fields: [{ name: 'tipo_documento', label: 'Tipo Documento', required: true }, { name: 'id_documento', label: 'ID Documento', required: true }] },
                            PDF_JURIS: { label: '📄 Download do Acórdão', price: 'R$ {{ number_format($prices["PDF_JURIS"], 2, ",", ".") }}', fields: [{ name: 'tipo_documento', label: 'Tipo Documento', required: true }, { name: 'id_documento', label: 'ID Documento', required: true }] },
                            BUSCA_LEGIS: { label: '⚖️ Procurar Textos da Lei', price: 'R$ {{ number_format($prices["BUSCA_LEGIS"], 2, ",", ".") }}', fields: [{ name: 'q', label: 'Termo de Busca', required: true }] },
                            DOC_LEGIS: { label: '📜 Ler Texto de Legislação', price: 'R$ {{ number_format($prices["DOC_LEGIS"], 2, ",", ".") }}', fields: [{ name: 'tipo_documento', label: 'Tipo Documento', required: true }, { name: 'id_documento', label: 'ID Documento', required: true }] },
                            FRAG_LEGIS: { label: '📄 Fragmentos Legais', price: 'R$ {{ number_format($prices["FRAG_LEGIS"], 2, ",", ".") }}', fields: [{ name: 'tipo_documento', label: 'Tipo Documento', required: true }, { name: 'id_documento', label: 'ID Documento', required: true }] },
                            MOV_PROCESSO_DIARIO: { label: '📜 Leitura da Publicação Judicial', price: 'R$ {{ number_format($prices["MOV_PROCESSO_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID do Movimento', required: true }] },
                            INFO_PESSOA: { label: '👤 Dossiê Pessoa Física', price: 'R$ {{ number_format($prices["INFO_PESSOA"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID Pessoa', required: true }] },
                            PROCESSOS_PESSOA: { label: '📋 Listar Acionamentos Físicos', price: 'R$ {{ number_format($prices["PROCESSOS_PESSOA"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID da Pessoa', required: true }] },
                            AUTOS_DOCS_ESP: { label: '📂 Acervo Processual (Lim)', price: 'R$ {{ number_format($prices["AUTOS_DOCS_ESP"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID Processo', required: true }] },
                            BUSCA_OAB_PAGA: { label: '📋 Portfólio de Advogado OAB', price: 'R$ {{ number_format($prices["BUSCA_OAB_PAGA"], 2, ",", ".") }}', fields: [{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero', label: 'Número OAB', required: true }] },
                            BUSCA_PROC_DIARIO_NUM: { label: '🔢 Achar Diários CNJ Unificado', price: 'R$ {{ number_format($prices["BUSCA_PROC_DIARIO_NUM"], 2, ",", ".") }}', fields: [{ name: 'numero', label: 'Nº do Processo (Busca)', required: true }] },
                            ENVOLVIDOS_PROC_DIARIO: { label: '👥 Partes Litigantes Diário', price: 'R$ {{ number_format($prices["ENVOLVIDOS_PROC_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID Processo', required: true }] },
                            MOV_PROC_DIARIO: { label: '📜 Histórico e Despachos Retro.', price: 'R$ {{ number_format($prices["MOV_PROC_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID Processo', required: true }] },
                            PROC_DIARIO: { label: '⚖️ Processo Raiz Documentado', price: 'R$ {{ number_format($prices["PROC_DIARIO"], 2, ",", ".") }}', fields: [{ name: 'id', label: 'ID Processo', required: true }] },

                            // V2
                            CAPA_PROCESSO: { label: '🏛️ Capa Padrão', price: 'R$ {{ number_format($prices["CAPA_PROCESSO"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', placeholder: '0000000-00.0000.0.00.0000', required: true }] },
                            ATUALIZAR_PROCESSO: { label: '🔄 Forçar Atualização Completa', price: 'Min R$ {{ number_format($prices["ATUALIZAR_PROCESSO"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            ATUALIZACAO_PROCESSO_DOCS: { label: '🔄 Atualização com Docs Pontuais', price: 'Min R$ {{ number_format($prices["ATUALIZACAO_PROCESSO_DOCS"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            ATUALIZACAO_PROCESSO_AUTOS: { label: '🔄 Íntegra Anexada (Autos)', price: 'Min R$ {{ number_format($prices["ATUALIZACAO_PROCESSO_AUTOS"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            ATUALIZACAO_PROCESSO_PUB: { label: '🔄 Sincronização Despachos Públicos', price: 'Min R$ {{ number_format($prices["ATUALIZACAO_PROCESSO_PUB"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            PROCESSOS_ENVOLVIDO_CPF: { label: '📋 Malha Fina CPF/CNPJ', price: 'R$ {{ number_format($prices["PROCESSOS_ENVOLVIDO_CPF"], 2, ",", ".") }} até 200 itens (+ R$ 3,00/200)', fields: [{ name: 'cpf_cnpj', label: 'CPF / CNPJ', required: true }] },
                            PROCESSOS_ADVOGADO_OAB: { label: '📋 Atuação Oficial OAB', price: 'R$ {{ number_format($prices["PROCESSOS_ADVOGADO_OAB"], 2, ",", ".") }} até 200 itens (+ R$ 3,00/200)', fields: [{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero_oab', label: 'Número OAB', required: true }] },
                            RESUMO_ADVOGADO_OAB: { label: '📋 Sintetizador Comarca OAB', price: 'R$ {{ number_format($prices["RESUMO_ADVOGADO_OAB"], 2, ",", ".") }}', fields: [{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero_oab', label: 'Número OAB', required: true }] },
                            RESUMO_ENVOLVIDO: { label: '📋 Panorama Contábil (CPF/CNPJ)', price: 'R$ {{ number_format($prices["RESUMO_ENVOLVIDO"], 2, ",", ".") }}', fields: [{ name: 'cpf_cnpj', label: 'CPF/CNPJ', required: true }] },
                            DOCUMENTOS_PUBLICOS: { label: '📄 Despachos Abertos V2', price: 'R$ {{ number_format($prices["DOCUMENTOS_PUBLICOS"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            ENVOLVIDOS_PROCESSO: { label: '👥 Nome de Partes e Testemunhas', price: 'R$ {{ number_format($prices["ENVOLVIDOS_PROCESSO"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            MOVIMENTACOES_PROCESSO: { label: '📜 Linha do Tempo de Instância', price: 'R$ {{ number_format($prices["MOVIMENTACOES_PROCESSO"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            BAIXAR_AUTOS: { label: '📁 Cópias de Autos (Arquivadas)', price: 'R$ {{ number_format($prices["BAIXAR_AUTOS"], 2, ",", ".") }}', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }] },
                            RESUMO_IA: { label: '🧠 Tradução Contábil & Jurídica por IA', price: 'R$ {{ number_format($prices["RESUMO_IA"], 2, ",", ".") }} (Gerar) / R$ 0,05 (Ler)', fields: [{ name: 'cnj', label: 'Número CNJ', required: true }, { name: 'action', label: 'Ação', type: 'select', options: ['solicitar|Gerar (R$ 0,08)', 'consultar|Ler (R$ 0,05)'], hint: 'Selecione a ação baseada no custo' }] },
                        };

                        var currentType = '';
                        var currentBalance = 0;

                        function syntaxHighlight(json) {
                            if (typeof json !== 'string') json = JSON.stringify(json, null, 2);
                            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            return json.replace(
                                /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?|null)/g,
                                function (match) {
                                    var cls = 'esc-num';
                                    if (/^"/.test(match)) {
                                        cls = /:$/.test(match) ? 'esc-key' : 'esc-str';
                                    } else if (/true|false/.test(match)) {
                                        cls = 'esc-bool';
                                    } else if (/null/.test(match)) {
                                        cls = 'esc-null';
                                    }
                                    return '<span class="' + cls + '">' + match + '</span>';
                                }
                            );
                        }

                        function copyContent() {
                            var box = document.getElementById('lf-svc-result-content');
                            if(!box) return;
                            navigator.clipboard.writeText(box.innerText).then(function() {
                                alert("Copiado com sucesso!");
                            });
                        }

                        window.lfCopyContent = copyContent;

                        function loadBalance() {
                            fetch(ROUTE_SALDO, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(function (r) { return r.json(); })
                                .then(function (d) {
                                    currentBalance = d.ai_tokens_balance || 0;
                                    document.getElementById('lf-svc-saldo-display').textContent = '💰 Saldo: R$ ' + currentBalance.toFixed(2).replace('.', ',');
                                })
                                .catch(function () {
                                    document.getElementById('lf-svc-saldo-display').textContent = '💰 Saldo: indisponível';
                                });
                        }

                        document.addEventListener('DOMContentLoaded', function () {
                            loadBalance();
                            var m = document.getElementById('lf-svc-modal');
                            if (m) document.body.appendChild(m);
                        });

                        function openSvc(type) {
                            currentType = type;
                            var info = SVC_INFO[type];
                            if (!info) return;

                            document.getElementById('lf-svc-modal-title').textContent = info.label;
                            document.getElementById('lf-svc-price-badge').textContent = '💰 ' + info.price;
                            document.getElementById('lf-svc-balance').textContent = 'Seu saldo: R$ ' + currentBalance.toFixed(2).replace('.', ',');
                            
                            document.getElementById('lf-svc-error').style.display = 'none';
                            document.getElementById('lf-svc-success').style.display = 'none';
                            
                            document.getElementById('lf-svc-fields').style.display = 'flex';
                            document.getElementById('lf-svc-btn-submit').style.display = 'inline-block';

                            var prevResult = document.getElementById('lf-svc-result-area');
                            if (prevResult) prevResult.style.display = 'none';

                            var container = document.getElementById('lf-svc-fields');
                            container.innerHTML = '';
                            
                            info.fields.forEach(function (f) {
                                var wrap = document.createElement('div');
                                wrap.className = 'lf-esc-field';
                                
                                var label = document.createElement('label');
                                label.className = 'lf-esc-label';
                                label.textContent = f.label + (f.required ? ' *' : '');
                                wrap.appendChild(label);
                                
                                if (f.type === 'select') {
                                    var select = document.createElement('select');
                                    select.className = 'lf-esc-select';
                                    select.id = 'lf-svc-field-' + f.name;
                                    select.name = f.name;
                                    
                                    (f.options || []).forEach(function (opt) {
                                        var option = document.createElement('option');
                                        if (typeof opt === 'string' && opt.indexOf('|') > -1) {
                                            var parts = opt.split('|');
                                            option.value = parts[0];
                                            option.textContent = parts[1];
                                        } else {
                                            option.value = opt;
                                            option.textContent = opt;
                                        }
                                        select.appendChild(option);
                                    });
                                    wrap.appendChild(select);
                                } else {
                                    var input = document.createElement('input');
                                    input.type = 'text';
                                    input.className = 'lf-esc-input';
                                    input.id = 'lf-svc-field-' + f.name;
                                    input.name = f.name;
                                    input.placeholder = f.placeholder || '';
                                    wrap.appendChild(input);
                                }
                                
                                if (f.hint) {
                                    var hint = document.createElement('div');
                                    hint.className = 'lf-esc-hint';
                                    hint.style.fontSize = '0.75rem';
                                    hint.style.color = '#6b7280';
                                    hint.style.marginTop = '4px';
                                    hint.textContent = f.hint;
                                    wrap.appendChild(hint);
                                }

                                container.appendChild(wrap);
                            });

                            document.getElementById('lf-svc-modal').style.display = 'block';
                        }

                        function closeSvc() {
                            document.getElementById('lf-svc-modal').style.display = 'none';
                            currentType = '';
                        }

                        function executeSvc() {
                            var info = SVC_INFO[currentType];
                            if (!info) return;

                            var data = {};
                            var valid = true;
                            info.fields.forEach(function (f) {
                                var inp = document.getElementById('lf-svc-field-' + f.name);
                                if (!inp) return;
                                var val = inp.value.trim();
                                if (f.required && !val) { valid = false; }
                                data[f.name] = val;
                            });

                            if (!valid) {
                                showSvcError('Preencha os campos obrigatórios.');
                                return;
                            }

                            var btn = document.getElementById('lf-svc-btn-submit');
                            btn.disabled = true;
                            btn.textContent = '⏳ Executando...';
                            document.getElementById('lf-svc-error').style.display = 'none';
                            document.getElementById('lf-svc-success').style.display = 'none';

                            fetch(ROUTE_SERVICO, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ service_type: currentType, data: data })
                            })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                btn.disabled = false;
                                btn.textContent = '🚀 Executar';
                                if (d.success) {
                                    
                                    var msg = "Consulta concluída com sucesso!";
                                    if (d.async) msg = "Solicitação enviada! O resultado será processado em segundo plano e você será notificado.";
                                    
                                    document.getElementById('lf-svc-success').textContent = msg;
                                    document.getElementById('lf-svc-success').style.display = 'block';
                                    
                                    if (!d.async) {
                                        document.getElementById('lf-svc-fields').style.display = 'none';
                                        btn.style.display = 'none';
                                        
                                        var resultHtml = '<div id="lf-svc-result-area" style="margin-top:10px;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><h4 style="font-weight:600;color:#374151;">Resposta Formadatada</h4><button type="button" onclick="window.lfCopyContent()" style="font-size:0.8rem;background:#f3f4f6;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;">📋 Copiar Resposta</button></div><div class="lf-esc-data-box" style="display:block;max-height:400px;overflow:auto;"><pre id="lf-svc-result-content">' + syntaxHighlight(d.data || d) + '</pre></div></div>';
                                        
                                        var parent = document.getElementById('lf-svc-fields').parentElement;
                                        var prev = document.getElementById('lf-svc-result-area');
                                        if (prev) prev.remove();
                                        parent.insertAdjacentHTML('beforeend', resultHtml);
                                    }

                                    loadBalance();
                                } else {
                                    showSvcError(d.error || d.message || 'Erro ao executar o serviço.');
                                }
                            })
                            .catch(function (err) {
                                btn.disabled = false;
                                btn.textContent = '🚀 Executar';
                                showSvcError('Erro de conexão. Tente novamente.');
                            });
                        }

                        function showSvcError(msg) {
                            var box = document.getElementById('lf-svc-error');
                            box.textContent = '⚠️ ' + msg;
                            box.style.display = 'block';
                        }

                        window.lfFilterByArea = function (filterKey, btn) {
                            document.querySelectorAll('.lf-area-btn').forEach(function (b) { b.classList.remove('active'); });
                            if (btn) btn.classList.add('active');

                            var isTagFilter = ['v1', 'v2', 'gratis'].includes(filterKey);
                            var paidCards = document.querySelectorAll('.lf-esc-grid')[0]?.querySelectorAll('.lf-esc-card') || [];
                            
                            paidCards.forEach(function (card) {
                                var cardModule = card.dataset.module || '';
                                var cardApi = card.dataset.api || '';

                                var matches = false;
                                if (filterKey === 'todas') {
                                    matches = true;
                                } else if (isTagFilter) {
                                    if (filterKey === 'v1' && cardApi === 'v1') matches = true;
                                    if (filterKey === 'v2' && cardApi === 'v2') matches = true;
                                } else {
                                    matches = (cardModule === filterKey);
                                }
                                card.style.display = matches ? '' : 'none';
                            });
                        };

                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape' && document.getElementById('lf-svc-modal').style.display !== 'none') closeSvc();
                        });

                        window.lfSvc = { open: openSvc, close: closeSvc, execute: executeSvc };

                    })();
                </script>
            @endpush

            {{-- ── PAID SERVICE MODAL ────────────────────────────────────── --}}
            <div id="lf-svc-modal" style="display:none;">
                <div class="lf-esc-overlay" onclick="window.lfSvc.close()"></div>
                <div class="lf-esc-dialog" style="max-width:540px;width:95%;">
                    <div class="lf-esc-modal-header">
                        <h3 id="lf-svc-modal-title" class="lf-esc-modal-title">🚀 Executar Serviço</h3>
                        <button onclick="window.lfSvc.close()" class="lf-esc-close-btn">✕</button>
                    </div>
                    <div style="padding:20px;display:flex;flex-direction:column;gap:16px;">

                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:linear-gradient(135deg,rgba(13,148,136,.06),rgba(2,132,199,.06));border:1px solid #99f6e4;border-radius:10px;">
                            <div>
                                <div style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Custo do Serviço</div>
                                <div id="lf-svc-price-badge" style="font-size:1.3rem;font-weight:800;color:#0d9488;">R$ 0,00</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">Saldo Disponível</div>
                                <div id="lf-svc-balance" style="font-size:.95rem;font-weight:700;color:#374151;" class="dark:text-gray-200">R$ 0,00</div>
                            </div>
                        </div>

                        <div id="lf-svc-fields" style="display:flex;flex-direction:column;gap:12px;"></div>

                        <div id="lf-svc-error" class="lf-esc-error" style="display:none;"></div>
                        <div id="lf-svc-success" style="display:none;padding:12px;background:#dcfce7;color:#166534;border-radius:8px;font-size:0.9rem;font-weight:500;"></div>

                    </div>
                    <div class="lf-esc-modal-footer" style="padding:16px 20px;display:flex;gap:12px;justify-content:flex-end;">
                        <button onclick="window.lfSvc.close()" class="lf-esc-btn-secondary" type="button" style="padding:10px 16px;">Cancelar</button>
                        <button id="lf-svc-btn-submit" onclick="window.lfSvc.execute()" class="lf-esc-btn" type="button" style="padding:10px 30px;">🚀 Executar</button>
                    </div>
                </div>
            </div>
EOF;

    $content = substr_replace($content, $newJSAndModal, $pushPos, $endModalPos - $pushPos);
} else {
    echo "Warning: Could not find JS injection bounds\n";
}

file_put_contents($file, $content);
echo "File updated successfully!\n";
?>