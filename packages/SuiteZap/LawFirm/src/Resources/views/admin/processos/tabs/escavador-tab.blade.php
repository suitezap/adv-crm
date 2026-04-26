<div class="hidden" id="escavador-tab-content">
    <div class="flex flex-col gap-4">
        <!-- Dashboard / Capa -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Capa Processual -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        <i class="icon-legal-document text-primary-600 mr-1"></i> Capa do Processo (V2)
                    </h3>
                    <div id="escavador-status-badge" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        Não sincronizado
                    </div>
                </div>

                <div class="space-y-3 text-sm hidden" id="escavador-capa-details">
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wider">Número CNJ</span>
                        <span class="font-medium text-gray-900 dark:text-white" id="esc-cnj">-</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wider">Tribunal</span>
                        <span class="font-medium text-gray-900 dark:text-white" id="esc-tribunal">-</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 text-xs uppercase tracking-wider">Vara</span>
                        <span class="font-medium text-gray-900 dark:text-white" id="esc-vara">-</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block text-gray-500 text-xs uppercase tracking-wider">Segredo de Justiça</span>
                            <span class="font-medium text-gray-900 dark:text-white" id="esc-segredo">-</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-xs uppercase tracking-wider">Última Validação</span>
                            <span class="font-medium text-gray-900 dark:text-white text-xs" id="esc-data-verificacao">-</span>
                        </div>
                    </div>
                </div>

                <div id="escavador-sync-state" class="flex flex-col items-center justify-center p-6 text-center">
                    <i class="icon-info text-4xl text-gray-300 dark:text-gray-600 mb-2"></i>
                    <p class="text-sm text-gray-500 mb-4">Para importar ou atualizar os dados deste processo, sincronize com a base de Dados Oficiais.</p>
                    <button type="button" class="primary-button" onclick="EscavadorTab.sync()">
                        <i class="icon-refresh mr-1"></i> Sincronizar Capa (R$ 0,05)
                    </button>
                    <p class="text-xs text-gray-400 mt-2" id="escavador-saldo-display">Carregando saldo...</p>
                </div>
            </div>

            <!-- Resumo IA -->
            <div class="rounded-lg border border-primary-100 bg-primary-50/30 p-4 shadow-sm dark:border-primary-900/30 dark:bg-primary-900/10">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-semibold text-primary-800 dark:text-primary-300">
                        <i class="icon-magic text-primary-500 mr-1 animate-pulse"></i> Visão Geral do Caso (IA)
                    </h3>
                </div>
                
                <div id="escavador-resumo-ia-content" class="text-sm text-gray-700 dark:text-gray-300 mt-2 leading-relaxed">
                    <p class="italic text-gray-500">Nenhum resumo gerado ou processo não sincronizado.</p>
                </div>

                <div class="mt-4 pt-4 border-t border-primary-100/50 dark:border-primary-800/20 flex justify-end">
                     <button type="button" id="btn-request-ia" class="secondary-button" onclick="EscavadorTab.requestResumoIa()" disabled>
                        Gerar / Atualizar IA (R$ 0,08)
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabelas de Dados (Hidden until Synced) -->
        <div id="escavador-data-tables" class="hidden flex-col gap-4">
            
            <div class="flex justify-end gap-2 my-2">
                <button type="button" class="secondary-button" onclick="EscavadorTab.requestAtualizacao()">
                    <i class="icon-refresh mr-1"></i> Puxar Tribunal (R$ 0,10)
                </button>
                <button type="button" class="secondary-button" onclick="EscavadorTab.downloadAutos()">
                    <i class="icon-download mr-1"></i> Dossiê/Autos Públicos (R$ 1,50)
                </button>
            </div>

            <!-- Movimentações -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-4">Movimentações</h3>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="pb-2 font-medium text-gray-500 w-32">Data</th>
                            <th class="pb-2 font-medium text-gray-500 w-48">Tipo</th>
                            <th class="pb-2 font-medium text-gray-500">Conteúdo</th>
                        </tr>
                    </thead>
                    <tbody id="esc-movimentacoes-body">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>

            <!-- Envolvidos -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-4">Envolvidos e Personagens</h3>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="pb-2 font-medium text-gray-500">Nome</th>
                            <th class="pb-2 font-medium text-gray-500">Participação</th>
                            <th class="pb-2 font-medium text-gray-500">Documento / OAB</th>
                        </tr>
                    </thead>
                    <tbody id="esc-envolvidos-body">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Documentos Publicos -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-4">Documentos Identificados</h3>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="pb-2 font-medium text-gray-500">Tipo</th>
                            <th class="pb-2 font-medium text-gray-500">Fonte</th>
                            <th class="pb-2 font-medium text-gray-500 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="esc-documentos-body">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const EscavadorTab = {
        processoId: {{ $processo->id }},
        cnj: '{{ $processo->numero_cnj }}',
        escProcessoId: null,
        lastData: null, // Exposto para LFSyncFromEscavador() no edit.blade.php

        init() {
            if (!this.cnj && !this.processoId) return;
            
            // Carregar saldo (reaproveitando endpoind do admin-escavador)
            fetch('{{ route('lawfirm.escavador.saldo_cliente') }}')
                .then(r => r.json())
                .then(r => {
                    if (r.success) {
                        document.getElementById('escavador-saldo-display').innerHTML = `Saldo atual: <strong>R$ ${r.ai_tokens_balance.toFixed(2)}</strong>`;
                    }
                });

            this.loadDetails();
        },

        loadDetails() {
            const detailsUrl = '{{ route("lawfirm.escavador.processo_details", ":processoId") }}'.replace(':processoId', this.processoId);
            fetch(detailsUrl)
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data.processo) {
                        this.renderData(res.data.processo, res.data.needs_refresh, res.data.resumo_ia_short);
                    }
                })
                .catch(err => console.log('Processo Escavador DB Mirror Not Found', err));
        },

        sync() {
            if (!this.cnj) {
                alert('Preencha o campo CNJ e salve o processo primeiro.');
                return;
            }

            if (!confirm('Deseja iniciar a sincronização com os Dados Oficiais? Será debitado R$ 0,05 de seu saldo de IA.')) return;
            
            const btn = document.querySelector('#escavador-sync-state button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="icon-loader animate-spin mr-1"></i> Sincronizando...';
            btn.disabled = true;

            fetch('{{ route('lawfirm.escavador.sync_processo') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ cnj: this.cnj, processo_id: this.processoId })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.loadDetails();
                    alert('Sincronização com Dados Oficiais concluída!');
                } else {
                    alert(res.message || 'Erro ao sincronizar.');
                }
            })
            .catch(err => {
                alert('Erro de comunicação. Tente novamente mais tarde.');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        },

        requestResumoIa() {
             if (!this.escProcessoId) return;
             if(!confirm('Deseja solicitar um Resumo Inteligente por E R$ 0,08? Isso levará alguns minutos.')) return;

             fetch('{{ route('lawfirm.escavador.servico') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ 
                    service_type: 'RESUMO_IA', 
                    data: { cnj: this.cnj },
                    processo_id: this.processoId
                })
            })
            .then(r => r.json())
            .then(r => {
                alert(r.message || 'Resumo IA solicitado. Recarregue a página em alguns instantes.');
            });
        },

        requestAtualizacao() {
             if (!this.escProcessoId) return;
             if(!confirm('Deseja solicitar uma busca quente no Tribunal por R$ 0,10? Isso forçará o robô a ir na fonte.')) return;

             fetch('{{ route('lawfirm.escavador.atualizar_tribunal') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ escavador_processo_id: this.escProcessoId })
            })
            .then(r => r.json())
            .then(r => {
                alert(r.message || 'Atualização solicitada via robô.');
            });
        },

        downloadAutos() {
            alert('Funcionalidade de download de autos massivo chegará em breve na v3.33');
        },

        renderData(processo, needsRefresh, resumoShort) {
            this.escProcessoId = processo.id;
            
            document.getElementById('escavador-sync-state').classList.add('hidden');
            document.getElementById('escavador-capa-details').classList.remove('hidden');
            document.getElementById('escavador-data-tables').classList.remove('hidden');
            document.getElementById('btn-request-ia').disabled = false;

            const statusE = document.getElementById('escavador-status-badge');
            statusE.innerText = needsRefresh ? 'Desatualizado (+24h)' : 'Em cache local';
            if (needsRefresh) {
                statusE.classList.replace('bg-gray-100', 'bg-orange-100');
                statusE.classList.replace('text-gray-700', 'text-orange-700');
            } else {
                statusE.classList.replace('bg-gray-100', 'bg-emerald-100');
                statusE.classList.replace('text-gray-700', 'text-emerald-700');
            }

            document.getElementById('esc-cnj').innerText = processo.numero_cnj || '-';
            document.getElementById('esc-tribunal').innerText = processo.tribunal || '-';
            document.getElementById('esc-vara').innerText = processo.vara || '-';
            document.getElementById('esc-segredo').innerText = processo.segredo_justica ? 'Sim' : 'Não';
            document.getElementById('esc-data-verificacao').innerText = processo.data_ultima_verificacao ? new Date(processo.data_ultima_verificacao).toLocaleDateString('pt-BR') : '-';

            if (resumoShort) {
                document.getElementById('escavador-resumo-ia-content').innerHTML = '<p>' + resumoShort + '</p>';
            } else if (processo.status_atualizacao === 'resumo_solicitado') {
                document.getElementById('escavador-resumo-ia-content').innerHTML = '<p class="italic text-primary-600"><i class="icon-loader animate-spin mr-1"></i> A IA está processando o resumo no momento...</p>';
            }

            // Mapear Movemntacoes
            const tbodyMoves = document.getElementById('esc-movimentacoes-body');
            tbodyMoves.innerHTML = '';
            if (processo.movimentacoes && processo.movimentacoes.length > 0) {
                let movesHtml = '';
                processo.movimentacoes.forEach(m => {
                    const dt = m.data_movimentacao ? new Date(m.data_movimentacao).toLocaleDateString('pt-BR') : '-';
                    const tipo = m.tipo || 'Movimentação';
                    const texto = m.texto_movimentacao || '';
                    movesHtml += '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-0">' +
                                 '<td class="py-2 text-gray-600 dark:text-gray-400">' + dt + '</td>' +
                                 '<td class="py-2 font-medium text-gray-800 dark:text-gray-200">' + tipo + '</td>' +
                                 '<td class="py-2 text-gray-700 dark:text-gray-300"><div class="truncate max-w-lg" title="' + texto.replace(/"/g, '&quot;') + '">' + texto + '</div></td>' +
                                 '</tr>';
                });
                tbodyMoves.innerHTML = movesHtml;
            } else {
                tbodyMoves.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-500">Nenhuma movimentação em cache</td></tr>';
            }

            // Envolvidos
            const tbodyEnvolvidos = document.getElementById('esc-envolvidos-body');
            tbodyEnvolvidos.innerHTML = '';
            if (processo.envolvidos && processo.envolvidos.length > 0) {
                let envHtml = '';
                processo.envolvidos.forEach(env => {
                     const docId = env.cpf_cnpj ? 'Doc: ' + env.cpf_cnpj : (env.oab ? 'OAB: ' + env.oab : '-');
                     const tipo_part = env.tipo_participacao || 'Desconhecido';
                     envHtml += '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-0">' +
                                '<td class="py-2 font-medium text-gray-800 dark:text-gray-200">' + env.nome + '</td>' +
                                '<td class="py-2 text-gray-600 dark:text-gray-400">' +
                                '<span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-full text-xs">' + tipo_part + '</span>' +
                                '</td>' +
                                '<td class="py-2 text-gray-500">' + docId + '</td>' +
                                '</tr>';
                });
                tbodyEnvolvidos.innerHTML = envHtml;
            } else {
                 tbodyEnvolvidos.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-500">Nenhum envolvido em cache</td></tr>';
            }

            // Documentos
            const tbodyDocs = document.getElementById('esc-documentos-body');
            tbodyDocs.innerHTML = '';
            if (processo.documentos && processo.documentos.length > 0) {
                let docsHtml = '';
                processo.documentos.forEach(doc => {
                     const tipoDoc = doc.tipo || 'Documento';
                     const linkPdf = doc.url_pdf ? '<a href="' + doc.url_pdf + '" target="_blank" class="text-primary-600 hover:underline text-xs">Visualizar</a>' : '<span class="text-xs text-gray-400">Pendente</span>';
                     docsHtml += '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-0">' +
                                 '<td class="py-2 font-medium text-gray-800 dark:text-gray-200"><i class="icon-file-text mr-1"></i> ' + tipoDoc + '</td>' +
                                 '<td class="py-2 text-gray-600 dark:text-gray-400 capitalize">' + doc.fonte + '</td>' +
                                 '<td class="py-2 text-right">' + linkPdf + '</td>' +
                                 '</tr>';
                });
                tbodyDocs.innerHTML = docsHtml;
            } else {
                 tbodyDocs.innerHTML = '<tr><td colspan="3" class="py-4 text-center text-gray-500">Nenhum documento público encontrado</td></tr>';
            }
        }
    };

    // Init when tab is clicked
    document.addEventListener('DOMContentLoaded', () => {
        EscavadorTab.init();
    });
</script>
