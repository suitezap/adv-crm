<x-admin::layouts>
    <x-slot:title>
        Conexão WhatsApp
    </x-slot>

    <!-- Main Container -->
    <div class="flex flex-col gap-6" style="gap: 1.5rem; display: flex; flex-direction: column;">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <!-- Left: Context -->
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="icon-whatsapp text-2xl text-green-600"></span>
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white">Conexão WhatsApp</h1>
                </div>
                <p class="text-sm text-gray-500 mt-1 pl-8">Gerencie as duas conexões do escritório. <strong class="text-gray-700 dark:text-gray-300">Notificações (Padrão)</strong> é usada para todos os envios automáticos do sistema.</p>
            </div>
        </div>

        @if(!$isConfiguredDefault && !$isConfiguredAtendimento)
            <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-200 flex items-start gap-3">
                <span class="icon-warning text-yellow-600 text-xl mt-0.5"></span>
                <div>
                    <h3 class="text-sm font-bold text-yellow-800">Serviço Indisponível</h3>
                    <p class="text-sm text-yellow-700 mt-1">A Evolution API não está configurada para sua assinatura (Tenant ID: {{ \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getTenantId() }}).</p>
                </div>
            </div>
        @else
            <!-- Grid: Duas conexões lado a lado -->
            <div class="grid gap-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">

                <!-- ═══════════════════════════════════════════════════════
                     Conexão 1: Notificações (Padrão)
                     Usada para TODOS os envios automáticos do sistema
                     ═══════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 flex flex-col">
                    <!-- Header do card -->
                    <div class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold dark:bg-green-900/30 dark:text-green-400">1</span>
                                <h2 class="text-base font-bold text-gray-800 dark:text-white">Notificações (Padrão)</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5 pl-8">Alertas de prazo, boletos, CRM e disparo manual</p>
                        </div>
                        <span id="badge-default" class="px-3 py-1 rounded-full text-xs font-bold bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span> Buscando...
                        </span>
                    </div>

                    @if(!$isConfiguredDefault)
                        <div class="flex flex-col items-center justify-center p-6 text-center text-gray-400">
                            <span class="icon-warning text-2xl mb-2 text-yellow-500"></span>
                            <p class="text-sm">Conexão não configurada no MotherShip.</p>
                        </div>
                    @else
                        <div id="container-default" class="flex flex-col items-center justify-center flex-1 p-5 m-4 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700 min-h-[280px]">
                            <!-- Idle -->
                            <div id="idle-state-default" class="hidden flex-col items-center text-center">
                                <span class="icon-smartphone text-4xl text-gray-300 dark:text-gray-600 mb-3"></span>
                                <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-1">WhatsApp Desconectado</h3>
                                <p class="text-xs text-gray-500 mb-4">Clique no botão abaixo para gerar o QR Code de pareamento.</p>
                                <button type="button" onclick="startQrGeneration('default')" class="px-4 py-2 bg-green-50 text-green-700 text-sm font-bold rounded-lg hover:bg-green-100 border border-green-200 dark:bg-green-900/30 dark:border-green-800 dark:text-green-400 transition-colors">
                                    <span class="icon-scan mr-1 text-xs"></span> Gerar QR Code
                                </button>
                            </div>
                            <!-- Loading -->
                            <div id="loading-state-default" class="hidden flex-col items-center">
                                <span class="icon-loader text-3xl text-green-600 animate-spin mb-3"></span>
                                <p class="text-sm text-gray-500 font-medium">Buscando status de conexão...</p>
                            </div>
                            <!-- QR Code -->
                            <div id="qr-state-default" class="hidden flex-col items-center text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Abra o WhatsApp → <strong>Aparelhos Conectados</strong> → escaneie o código:</p>
                                <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200 mb-3">
                                    <img id="qr-code-img-default" src="" alt="QR Code WhatsApp" class="w-56 h-56 object-contain">
                                </div>
                                <p class="text-xs text-gray-400 flex items-center justify-center mb-3"><span class="icon-refresh mr-1"></span> Atualiza automaticamente a cada 40s</p>
                                <button type="button" onclick="stopQrGeneration('default')"
                                    class="px-4 py-1.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 transition-colors">
                                    <span class="icon-close mr-1 text-xs"></span> Parar Geração
                                </button>
                            </div>
                            <!-- Conectado -->
                            <div id="connected-state-default" class="hidden flex-col items-center text-center">
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-3 dark:bg-green-900/30">
                                    <span class="icon-check text-3xl text-green-600 dark:text-green-400"></span>
                                </div>
                                <h3 class="text-base font-bold text-gray-800 dark:text-white">WhatsApp Conectado!</h3>
                                <p class="text-xs text-gray-500 mt-1 mb-4">Pronto para notificações automáticas.</p>
                                <button type="button" onclick="disconnectWhatsapp('default')"
                                    class="px-4 py-2 bg-red-50 text-red-600 text-sm font-bold rounded-lg hover:bg-red-100 border border-red-200 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400 transition-colors">
                                    <span class="icon-close mr-1 text-xs"></span> Desconectar Notificações
                                </button>
                            </div>
                            <!-- Erro -->
                            <div id="error-state-default" class="hidden flex-col items-center text-center">
                                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mb-3 dark:bg-red-900/30">
                                    <span class="icon-warning text-2xl text-red-600 dark:text-red-400"></span>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Erro de Comunicação</h3>
                                <p id="error-message-default" class="text-xs text-gray-500 mt-1 mb-3">Ocorreu um erro ao comunicar com a Evolution API.</p>
                                <button type="button" onclick="loadStatusOrQr('default')" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200 border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">Tentar Novamente</button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     Conexão 2: Assistente de Atendimento
                     Usada pelo chatbot / Chatwoot
                     ═══════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 flex flex-col">
                    <!-- Header do card -->
                    <div class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold dark:bg-blue-900/30 dark:text-blue-400">2</span>
                                <h2 class="text-base font-bold text-gray-800 dark:text-white">Assistente de Atendimento</h2>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5 pl-8">Chatbot, triagem de leads e Chatwoot</p>
                        </div>
                        <span id="badge-atendimento" class="px-3 py-1 rounded-full text-xs font-bold bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span> Buscando...
                        </span>
                    </div>

                    @if(!$isConfiguredAtendimento)
                        <div class="flex flex-col items-center justify-center p-6 text-center text-gray-400">
                            <span class="icon-warning text-2xl mb-2 text-yellow-500"></span>
                            <p class="text-sm">Conexão não configurada no MotherShip.</p>
                            <p class="text-xs text-gray-400 mt-1">Instância: <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">{nome}_atendimento</code></p>
                        </div>
                    @else
                        <div id="container-atendimento" class="flex flex-col items-center justify-center flex-1 p-5 m-4 border-2 border-dashed border-blue-100 rounded-xl bg-blue-50/30 dark:bg-blue-900/10 dark:border-blue-900/30 min-h-[280px]">
                            <!-- Idle -->
                            <div id="idle-state-atendimento" class="hidden flex-col items-center text-center">
                                <span class="icon-smartphone text-4xl text-gray-300 dark:text-gray-600 mb-3"></span>
                                <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-1">WhatsApp Desconectado</h3>
                                <p class="text-xs text-gray-500 mb-4">Clique no botão abaixo para gerar o QR Code de pareamento.</p>
                                <button type="button" onclick="startQrGeneration('atendimento')" class="px-4 py-2 bg-blue-50 text-blue-700 text-sm font-bold rounded-lg hover:bg-blue-100 border border-blue-200 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-400 transition-colors">
                                    <span class="icon-scan mr-1 text-xs"></span> Gerar QR Code
                                </button>
                            </div>
                            <!-- Loading -->
                            <div id="loading-state-atendimento" class="hidden flex-col items-center">
                                <span class="icon-loader text-3xl text-blue-500 animate-spin mb-3"></span>
                                <p class="text-sm text-gray-500 font-medium">Buscando status de conexão...</p>
                            </div>
                            <!-- QR Code -->
                            <div id="qr-state-atendimento" class="hidden flex-col items-center text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Abra o WhatsApp → <strong>Aparelhos Conectados</strong> → escaneie o código:</p>
                                <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200 mb-3">
                                    <img id="qr-code-img-atendimento" src="" alt="QR Code WhatsApp Atendimento" class="w-56 h-56 object-contain">
                                </div>
                                <p class="text-xs text-gray-400 flex items-center justify-center mb-3"><span class="icon-refresh mr-1"></span> Atualiza automaticamente a cada 40s</p>
                                <button type="button" onclick="stopQrGeneration('atendimento')"
                                    class="px-4 py-1.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 transition-colors">
                                    <span class="icon-close mr-1 text-xs"></span> Parar Geração
                                </button>
                            </div>
                            <!-- Conectado -->
                            <div id="connected-state-atendimento" class="hidden flex-col items-center text-center">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-3 dark:bg-blue-900/30">
                                    <span class="icon-check text-3xl text-blue-600 dark:text-blue-400"></span>
                                </div>
                                <h3 class="text-base font-bold text-gray-800 dark:text-white">WhatsApp Conectado!</h3>
                                <p class="text-xs text-gray-500 mt-1 mb-4">Pronto para atender leads e clientes.</p>
                                <button type="button" onclick="disconnectWhatsapp('atendimento')"
                                    class="px-4 py-2 bg-red-50 text-red-600 text-sm font-bold rounded-lg hover:bg-red-100 border border-red-200 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400 transition-colors">
                                    <span class="icon-close mr-1 text-xs"></span> Desconectar Atendimento
                                </button>
                            </div>
                            <!-- Erro -->
                            <div id="error-state-atendimento" class="hidden flex-col items-center text-center">
                                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mb-3 dark:bg-red-900/30">
                                    <span class="icon-warning text-2xl text-red-600 dark:text-red-400"></span>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Erro de Comunicação</h3>
                                <p id="error-message-atendimento" class="text-xs text-gray-500 mt-1 mb-3">Ocorreu um erro ao comunicar com a Evolution API.</p>
                                <button type="button" onclick="loadStatusOrQr('atendimento')" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200 border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">Tentar Novamente</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 Teste de Disparo (Padrão Krayin Two-Column)
                 ═══════════════════════════════════════════════════════════ -->
            <!-- Krayin Native Two-Column Configuration Grid -->
            <div class="grid grid-cols-[1fr_2fr] gap-10 max-lg:grid-cols-1 max-lg:gap-4 lg:mt-6">
                <!-- Coluna Esquerda: Título / Info -->
                <div class="grid content-start gap-2.5 max-lg:mt-6">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        Teste de Disparo
                    </p>
                    <p class="leading-[140%] text-gray-600 dark:text-gray-300 text-sm">
                        Envie uma mensagem de teste manual para verificar se a comunicação com a API e o WhatsApp Web está ocorrendo corretamente em tempo real.
                    </p>
                </div>

                <!-- Coluna Direita: Box do Formulário com Margens Generosas -->
                <div class="box-shadow rounded bg-white p-6 dark:bg-gray-900 border border-gray-200 dark:border-gray-800" style="padding: 1.5rem;">
                    <form id="test-form" onsubmit="sendTestMessage(event)">
                        
                        <!-- Conexão de Saída -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                                🔌 Conexão de Saída <span class="text-red-500">*</span>
                            </label>
                            <select id="test-connection-type"
                                class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                <option value="default" selected>1. Notificações (Padrão)</option>
                                @if($isConfiguredAtendimento)
                                <option value="atendimento">2. Assistente de Atendimento</option>
                                @endif
                            </select>
                            <p class="text-xs text-gray-500 mt-1.5">Aviso: Todas as notificações automáticas do sistema usam a conexão <strong>Notificações (Padrão)</strong>.</p>
                        </div>

                        <!-- Telefone -->
                        <div class="mb-4 mt-6">
                            <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                                📱 Telefone / WhatsApp <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="test-phone" placeholder="(11) 99999-9999" required
                                class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 font-mono">
                            <p class="text-xs text-gray-500 mt-1.5">Inclua o DDD sem o 0.</p>
                        </div>

                        <!-- Mensagem de Teste -->
                        <div class="mb-4 mt-6">
                            <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                                💬 Mensagem de Teste <span class="text-red-500">*</span>
                            </label>
                            <textarea id="test-message" rows="3" required placeholder="Olá! Este é um teste do LawFirm."
                                class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"></textarea>
                        </div>

                        <div class="flex items-center justify-end mt-8 gap-x-2.5">
                            <button type="submit" id="btn-test" disabled class="primary-button disabled:opacity-50 disabled:cursor-not-allowed">
                                Enviar Teste
                            </button>
                        </div>
                    </form>

                    <!-- Aviso: conexão padrão não conectada -->
                    <div id="test-warning-default" class="hidden mt-6 rounded-lg p-4 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                        A conexão <strong>Notificações (Padrão)</strong> está desconectada. Conecte-a para habilitar os testes e os envios automáticos do sistema.
                    </div>
                    <div id="test-warning-atendimento" class="hidden mt-6 rounded-lg p-4 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                        A conexão <strong>Assistente de Atendimento</strong> está desconectada. Conecte-a para testar por este canal.
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    @if($isConfiguredDefault || $isConfiguredAtendimento)
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Inicializa a UI para cada conexão configurada usando o status retornado no carregamento da página
            @if($isConfiguredDefault)
            initConnectionState('default', '{{ $statusDefault }}');
            @endif
            @if($isConfiguredAtendimento)
            initConnectionState('atendimento', '{{ $statusAtendimento }}');
            @endif

            // Máscara de telefone
            const phoneInput = document.getElementById('test-phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', (e) => {
                    let v = e.target.value.replace(/\D/g, "");
                    if (v.startsWith('55') && v.length > 11) {
                         v = v.substring(2);
                    }
                    if (v.length <= 10) {
                        v = v.replace(/(\d{2})(\d)/, "($1) $2");
                        v = v.replace(/(\d{4})(\d)/, "$1-$2");
                    } else {
                        v = v.replace(/(\d{2})(\d)/, "($1) $2");
                        v = v.replace(/(\d{5})(\d)/, "$1-$2");
                    }
                    e.target.value = v.substring(0, 15);
                });
            }

            // Ao trocar a seleção de conexão, re-valida o botão
            const connSelect = document.getElementById('test-connection-type');
            if (connSelect) {
                connSelect.addEventListener('change', updateTestButton);
            }
        });

        const stateVars = {
            'default':      { qrInterval: null, isConnected: false },
            'atendimento':  { qrInterval: null, isConnected: false }
        };

        const csrfToken = "{{ csrf_token() }}";
        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        function showState(type, stateId) {
            ['idle-state', 'loading-state', 'qr-state', 'connected-state', 'error-state'].forEach(id => {
                const el = document.getElementById(id + '-' + type);
                if (el) {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            });
            const targetEl = document.getElementById(stateId + '-' + type);
            if (targetEl) {
                targetEl.classList.remove('hidden');
                targetEl.classList.add('flex');
            }

            const badge = document.getElementById('badge-' + type);
            if (!badge) return;

            if (stateId === 'connected-state') {
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Conectado';
            } else if (stateId === 'qr-state' || stateId === 'loading-state') {
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-yellow-50 text-yellow-700 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-yellow-500"></span> ' + (stateId === 'loading-state' ? 'Buscando...' : 'Aguardando Leitura');
            } else if (stateId === 'error-state') {
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500"></span> Desconectado / Erro';
            } else {
                badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-gray-400"></span> Desconectado';
            }

            // Sempre que um estado muda, re-valida o botão de teste
            updateTestButton();
        }

        /**
         * Atualiza o estado habilitado/desabilitado do botão de teste
         * com base na conexão selecionada no dropdown.
         */
        function updateTestButton() {
            const select = document.getElementById('test-connection-type');
            const selectedType = select ? select.value : 'default';
            const btnTest = document.getElementById('btn-test');
            const warnDefault = document.getElementById('test-warning-default');
            const warnAtendimento = document.getElementById('test-warning-atendimento');

            if (warnDefault) warnDefault.classList.add('hidden');
            if (warnAtendimento) warnAtendimento.classList.add('hidden');

            const isConnected = stateVars[selectedType]?.isConnected ?? false;

            if (btnTest) btnTest.disabled = !isConnected;

            if (!isConnected) {
                const warnEl = selectedType === 'atendimento' ? warnAtendimento : warnDefault;
                if (warnEl) warnEl.classList.remove('hidden');
            }
        }

        function setConnected(type, isConnected) {
            stateVars[type].isConnected = isConnected;

            if (isConnected) {
                stopDaemon(type);
            }

            updateTestButton();
        }

        function initConnectionState(type, initialStatus) {
            if (initialStatus === 'connected') {
                showState(type, 'connected-state');
                setConnected(type, true);
            } else {
                showState(type, 'idle-state');
                setConnected(type, false);
            }
        }

        function startQrGeneration(type) {
            loadStatusOrQr(type);
        }

        function stopQrGeneration(type) {
            stopDaemon(type);
            showState(type, 'idle-state');
        }

        async function loadStatusOrQr(type) {
            showState(type, 'loading-state');

            try {
                const response = await fetch(`{{ route('admin.lawfirm.whatsapp.qr-code') }}?type=${type}`, { headers });

                if (!response.ok) {
                    const errObj = await response.json();
                    throw new Error(errObj.message || 'Erro de comunicação');
                }

                const data = await response.json();

                if (data.state === 'open') {
                    showState(type, 'connected-state');
                    setConnected(type, true);
                } else if (data.qrcode) {
                    document.getElementById('qr-code-img-' + type).src = data.qrcode;
                    showState(type, 'qr-state');
                    setConnected(type, false);
                    startDaemon(type);
                } else {
                    showState(type, 'loading-state');
                    setConnected(type, false);
                    setTimeout(() => loadStatusOrQr(type), 5000);
                }
            } catch (error) {
                console.error(error);
                const errorMsgEl = document.getElementById('error-message-' + type);
                if (errorMsgEl) errorMsgEl.innerText = error.message.substring(0, 100);
                showState(type, 'error-state');
                setConnected(type, false);
                stopDaemon(type);
            }
        }

        function startDaemon(type) {
            if (stateVars[type].qrInterval) clearInterval(stateVars[type].qrInterval);
            stateVars[type].qrInterval = setInterval(() => {
                loadStatusOrQr(type);
            }, 40000);
        }

        function stopDaemon(type) {
            if (stateVars[type].qrInterval) {
                clearInterval(stateVars[type].qrInterval);
                stateVars[type].qrInterval = null;
            }
        }

        async function disconnectWhatsapp(type) {
            if (!confirm('Tem certeza que deseja desconectar este WhatsApp? O QR code anterior deixará de funcionar.')) return;

            setConnected(type, false);
            showState(type, 'loading-state');
            stopDaemon(type);

            try {
                const response = await fetch(`{{ route('admin.lawfirm.whatsapp.disconnect') }}?type=${type}`, { method: 'POST', headers });
                const result = await response.json();

                if (result.success) {
                    showState(type, 'idle-state');
                } else {
                    alert(result.message || 'Falha ao desconectar.');
                    showState(type, 'idle-state');
                }
            } catch(e) {
                alert('Erro de conexão ao tentar desconectar.');
                showState(type, 'idle-state');
            }
        }

        async function sendTestMessage(e) {
            e.preventDefault();

            // Descobre qual conexão foi selecionada pelo usuário
            const select = document.getElementById('test-connection-type');
            const selectedType = select ? select.value : 'default';

            if (!stateVars[selectedType]?.isConnected) {
                alert(`A conexão "${selectedType === 'atendimento' ? 'Assistente de Atendimento' : 'Notificações (Padrão)'}" precisa estar conectada para enviar o teste!`);
                return;
            }

            let phone = document.getElementById('test-phone').value.replace(/\D/g, "");
            if (phone.length && !phone.startsWith('55')) {
                phone = '55' + phone;
            }
            const message = document.getElementById('test-message').value;
            const btn = document.getElementById('btn-test');

            btn.disabled = true;
            btn.innerHTML = '<span class="icon-loader animate-spin mr-1 text-sm"></span> Enviando...';

            try {
                const response = await fetch("{{ route('admin.lawfirm.whatsapp.test') }}", {
                    method: 'POST',
                    headers,
                    // Passa o tipo de conexão para o controller roteá-lo corretamente
                    body: JSON.stringify({ phone, message, type: selectedType })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Disparo agendado com sucesso via ' + (selectedType === 'atendimento' ? 'Assistente de Atendimento' : 'Notificações (Padrão)') + '.');
                    document.getElementById('test-message').value = '';
                } else {
                    alert(result.message || 'Falha ao agendar teste.');
                }
            } catch(e) {
                alert('Erro de conexão do servidor.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="icon-send text-sm"></span> Enviar Teste';
            }
        }
    </script>
    @endif
    @endpush
</x-admin::layouts>