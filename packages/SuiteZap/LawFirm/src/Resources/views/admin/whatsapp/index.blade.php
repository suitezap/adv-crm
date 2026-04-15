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
                    <span id="header-status-badge" class="ml-3 px-3 py-1 rounded-full text-xs font-bold bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span> Buscando...
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1 pl-8">Conecte seu WhatsApp para habilitar notificações automáticas.</p>
            </div>
            <!-- Right: Actions -->
            <div class="flex items-center gap-3">
                <button type="button" onclick="disconnectWhatsapp()" id="btn-disconnect" class="hidden px-4 py-2 bg-red-50 text-red-600 text-sm font-bold rounded-lg hover:bg-red-100 border border-red-200 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400">
                    Desconectar WhatsApp
                </button>
            </div>
        </div>

        @if(!$isConfigured)
            <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-200 flex items-start gap-3">
                <span class="icon-warning text-yellow-600 text-xl mt-0.5"></span>
                <div>
                    <h3 class="text-sm font-bold text-yellow-800">Serviço Indisponível</h3>
                    <p class="text-sm text-yellow-700 mt-1">A Evolution API não está configurada para sua assinatura (Tenant ID: {{ \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getTenantId() }}). Entre em contato com o suporte para habilitar os disparos de WhatsApp.</p>
                </div>
            </div>
        @else
            <!-- Content Grid -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Conexão / QR Code Box -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 flex flex-col h-full">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Status de Conexão</h2>
                    
                    <div id="connection-status-container" class="flex flex-col items-center justify-center flex-1 p-4 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700 min-h-[300px]">
                        <!-- Loading State -->
                        <div id="loading-state" class="flex flex-col items-center">
                            <span class="icon-loader text-3xl text-green-600 animate-spin mb-3"></span>
                            <p class="text-sm text-gray-500 font-medium">Buscando status de conexão...</p>
                        </div>

                        <!-- QR Code State (Hidden by default) -->
                        <div id="qr-state" class="hidden flex-col items-center text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Abra o WhatsApp no seu celular, vá em <strong>Aparelhos Conectados</strong> e escaneie o código abaixo:</p>
                            <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200 mb-3">
                                <img id="qr-code-img" src="" alt="QR Code WhatsApp" class="w-64 h-64 object-contain">
                            </div>
                            <p class="text-xs text-gray-400 mt-2 flex items-center justify-center"><span class="icon-refresh mr-1"></span> Atualiza automaticamente a cada 40s</p>
                        </div>

                        <!-- Connected State (Hidden by default) -->
                        <div id="connected-state" class="hidden flex-col items-center text-center">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-4 dark:bg-green-900/30">
                                <span class="icon-check text-4xl text-green-600 text-bold dark:text-green-400"></span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">WhatsApp Conectado!</h3>
                            <p class="text-sm text-gray-500 mt-2">O sistema está pronto para enviar notificações no servidor <span class="badge font-mono">{{ $instanceName }}</span>.</p>
                        </div>

                        <!-- Error State -->
                        <div id="error-state" class="hidden flex-col items-center text-center">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4 dark:bg-red-900/30">
                                <span class="icon-warning text-3xl text-red-600 text-bold dark:text-red-400"></span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Erro de Comunicação</h3>
                            <p id="error-message" class="text-sm text-gray-500 mt-2 text-center">Ocorreu um erro ao comunicar com a Evolution API.</p>
                            <button type="button" onclick="loadStatusOrQr()" class="mt-4 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-200 border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">Tentar Novamente</button>
                        </div>
                    </div>
                </div>

                <!-- Testes Box -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 flex flex-col h-full">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Teste de Disparo</h2>
                    <p class="text-sm text-gray-500 mb-6">Utilize para verificar se as mensagens estão chegando corretamente ao destinatário.</p>
                    
                    <form id="test-form" onsubmit="sendTestMessage(event)" class="flex flex-col gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefone (com DDI)</label>
                            <input type="text" id="test-phone" placeholder="5511999999999" required
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white px-3 py-2">
                            <span class="text-xs text-gray-400 mt-1">Ex: 55 seguido do DDD e número.</span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensagem</label>
                            <textarea id="test-message" rows="3" required placeholder="Olá! Este é um teste do LawFirm."
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white px-3 py-2"></textarea>
                        </div>
                        <div class="pt-2">
                            <button type="submit" id="btn-test" disabled class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                Enviar Teste Assíncrono
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    @if($isConfigured)
    <script>
        // Financial Tab Pattern (Vanilla JS)
        document.addEventListener("DOMContentLoaded", function () {
            // Inicializa a UI
            loadStatusOrQr();
        });

        // Variaveis Globais do Escopo
        let qrInterval = null;
        let isConnectedGlobal = false;
        
        const csrfToken = "{{ csrf_token() }}";
        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        // Funções de UI
        function showState(stateId) {
            ['loading-state', 'qr-state', 'connected-state', 'error-state'].forEach(id => {
                document.getElementById(id).classList.add('hidden');
                document.getElementById(id).classList.remove('flex');
            });
            document.getElementById(stateId).classList.remove('hidden');
            document.getElementById(stateId).classList.add('flex');

            // Atualiza Badge no Header Principal
            const badge = document.getElementById('header-status-badge');
            if (stateId === 'connected-state') {
                badge.className = 'ml-3 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Conectado';
            } else if (stateId === 'qr-state') {
                badge.className = 'ml-3 px-3 py-1 rounded-full text-xs font-bold bg-yellow-50 text-yellow-700 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-yellow-500"></span> Aguardando Leitura';
            } else if (stateId === 'error-state') {
                badge.className = 'ml-3 px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500"></span> Desconectado / Erro';
            } else {
                badge.className = 'ml-3 px-3 py-1 rounded-full text-xs font-bold bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 flex items-center gap-1.5';
                badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span> Buscando...';
            }
        }

        function setConnected(isConnected) {
            isConnectedGlobal = isConnected;
            const btnDisconnect = document.getElementById('btn-disconnect');
            const btnTest = document.getElementById('btn-test');

            if (isConnected) {
                btnDisconnect.classList.remove('hidden');
                btnTest.disabled = false;
                stopDaemon(); // Se conectou, para o daemon de QR Code
            } else {
                btnDisconnect.classList.add('hidden');
                btnTest.disabled = true;
            }
        }

        // Motor Principal
        async function loadStatusOrQr() {
            showState('loading-state');
            
            try {
                // Sempre pedimos a QR-Code route, pois ela verifica status e retorna base64 se disconectado
                const response = await fetch("{{ route('admin.lawfirm.whatsapp.qr-code') }}", { headers });
                
                if (!response.ok) {
                    const errObj = await response.json();
                    throw new Error(errObj.message || 'Erro de comunicação');
                }

                const data = await response.json();

                if (data.state === 'open') {
                    // Já está conectado
                    showState('connected-state');
                    setConnected(true);
                } else if (data.qrcode) {
                    // Retornou QR Code
                    document.getElementById('qr-code-img').src = data.qrcode;
                    showState('qr-state');
                    setConnected(false);
                    startDaemon(); // Inicia daemon de 40s
                } else {
                    // Estado intermediário ou conectando
                    showState('loading-state');
                    setConnected(false);
                    setTimeout(loadStatusOrQr, 5000); // Tenta novamente em 5s
                }
            } catch (error) {
                console.error(error);
                document.getElementById('error-message').innerText = error.message.substring(0, 100);
                showState('error-state');
                setConnected(false);
                stopDaemon();
            }
        }

        // Daemon auto-atualização (40s)
        function startDaemon() {
            if (qrInterval) clearInterval(qrInterval);
            qrInterval = setInterval(() => {
                console.log("Daemon solicitando refresh do QR Code...");
                loadStatusOrQr();
            }, 40000); // 40 seconds
        }

        function stopDaemon() {
            if (qrInterval) {
                clearInterval(qrInterval);
                qrInterval = null;
            }
        }

        // Actions
        async function disconnectWhatsapp() {
            if (!confirm('Tem certeza que deseja desconectar o WhatsApp? O QR code anterior deixará de funcionar e o serviço de notificações será interrompido até novo pareamento.')) return;

            setConnected(false);
            showState('loading-state');
            stopDaemon();

            try {
                const response = await fetch("{{ route('admin.lawfirm.whatsapp.disconnect') }}", { method: 'POST', headers });
                const result = await response.json();
                
                if (result.success) {
                    // Recarrega a UI para gerar novo pareamento
                    loadStatusOrQr();
                } else {
                    alert(result.message || 'Falha ao desconectar.');
                    loadStatusOrQr(); // Tenta restaurar a tela
                }
            } catch(e) {
                alert('Erro de conexão ao tentar desconectar.');
                loadStatusOrQr();
            }
        }

        async function sendTestMessage(e) {
            e.preventDefault();
            if (!isConnectedGlobal) return alert("O WhatsApp precisa estar conectado!");

            const phone = document.getElementById('test-phone').value;
            const message = document.getElementById('test-message').value;
            const btn = document.getElementById('btn-test');

            btn.disabled = true;
            btn.innerText = 'Enviando...';

            try {
                const response = await fetch("{{ route('admin.lawfirm.whatsapp.test') }}", {
                    method: 'POST',
                    headers,
                    body: JSON.stringify({ phone, message })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Disparo agendado com sucesso (ficará no Queue Worker).');
                    document.getElementById('test-message').value = '';
                } else {
                    alert(result.message || 'Falha ao agendar teste.');
                }
            } catch(e) {
                alert('Erro de conexão do servidor.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Enviar Teste Assíncrono';
            }
        }
    </script>
    @endif
    @endpush
</x-admin::layouts>