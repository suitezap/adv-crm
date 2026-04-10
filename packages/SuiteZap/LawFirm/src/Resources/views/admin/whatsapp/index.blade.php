<x-admin::layouts>
    <x-slot:title>
        Configuração do WhatsApp
        </x-slot>

        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.whatsapp.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Integração WhatsApp
                    </div>
                </div>
            </div>

            <!-- COMPONENT USAGE -->
            <v-whatsapp-manager></v-whatsapp-manager>

            @pushOnce('scripts')
                <!-- TEMPLATE DEFINITION -->
                <script type="text/x-template" id="v-whatsapp-manager-template">
                                                                <div class="mt-3.5">
                                                                    <!-- STATE: LOADING -->
                                                                    <div v-if="loading" class="p-8 bg-white dark:bg-gray-900 rounded-lg shadow text-center">
                                                                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto mb-4"></div>
                                                                        <p class="text-gray-600 dark:text-gray-400">Verificando conexão...</p>
                                                                    </div>

                                                                    <!-- STATE: DISCONNECTED -->
                                                                    <div v-else-if="status === 'disconnected'" class="p-12 bg-white dark:bg-gray-900 rounded-lg shadow text-center border border-gray-200 dark:border-gray-700">
                                                                        <div class="mb-6">
                                                                            <span class="icon-calendar text-6xl text-gray-300"></span>
                                                                        </div>

                                                                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">WhatsApp Desconectado</h3>
                                                                        <p class="text-gray-500 mb-6 max-w-md mx-auto">
                                                                            Para conectar, clique no botão abaixo e escaneie o QR Code com o aplicativo do WhatsApp no seu celular.
                                                                        </p>

                                                                        <div class="mt-8 mb-4">
                                                                            <button
                                                                                @click="connect"
                                                                                class="btn btn-lg btn-primary"
                                                                                :disabled="connecting"
                                                                                style="padding: 10px 20px; font-weight: bold; background-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }}; border-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }}; color: white; border-radius: 30px;"
                                                                            >
                                                                                <span v-if="connecting">Gerando QR Code...</span>
                                                                                <span v-else>Conectar Agora</span>
                                                                            </button>
                                                                        </div>
                                                                    </div>

                                                                    <!-- STATE: QR CODE -->
                                                                    <div v-else-if="status === 'qrcode'" class="p-10 bg-white dark:bg-gray-900 rounded-lg shadow text-center border border-gray-200 dark:border-gray-700">
                                                                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6">Escaneie o QR Code</h3>

                                                                        <div class="flex justify-center mb-8 p-4 bg-gray-50 dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700 inline-block mx-auto">
                                                                            <img :src="qrCode" alt="QR Code" width="200" height="200" class="max-w-[200px]" v-if="qrCode"/>
                                                                            <div v-else class="h-[200px] w-[200px] bg-gray-100 flex items-center justify-center rounded">
                                                                               <span class="text-gray-400">Aguardando Imagem...</span>
                                                                            </div>
                                                                        </div>

                                                                        <p class="text-sm text-gray-500 animate-pulse">
                                                                            Aguardando leitura do código pelo app...
                                                                        </p>
                                                                    </div>

                                                                    <!-- STATE: CONNECTED -->
                                                                    <div v-else-if="status === 'connected'" class="p-8 md:p-12 bg-white dark:bg-gray-900 rounded-lg shadow border-l-4 border-green-500 border-t border-r border-b border-gray-200 dark:border-gray-700">
                                                                        <div class="flex items-center justify-between px-4 py-2">
                                                                            <div>
                                                                               <h3 class="text-xl font-bold text-green-600 mb-1 flex items-center gap-2">
                                                                                   ✅ WhatsApp Conectado
                                                                               </h3>
                                                                               <p class="text-gray-600 dark:text-gray-300">
                                                                                   Instância: <strong>@{{ instanceName }}</strong>
                                                                               </p>
                                                                               <p class="text-xs text-gray-400 mt-1">
                                                                                   Pronto para enviar mensagens.
                                                                               </p>
                                                                            </div>

                                                                            <button 
                                                                                @click="disconnect" 
                                                                                class="px-4 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm font-semibold border border-red-200"
                                                                            >
                                                                                Desconectar
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </script>

                <script type="module">
                    app.component('v-whatsapp-manager', {
                        template: '#v-whatsapp-manager-template',
                        data() {
                            return {
                                status: '{{ $status }}', // disconnected, qrcode, connected
                                instanceName: '{{ $instanceName }}',
                                loading: false,
                                connecting: false,
                                qrCode: null,
                                pollInterval: null,
                                refreshInterval: null
                            }
                        },
                        mounted() {
                            console.log('WhatsApp Manager Mounted. Status:', this.status);
                            if (this.status === 'qrcode') {
                                this.startPolling();
                                this.startRefreshTimer();
                            }
                        },
                        methods: {
                            async connect() {
                                this.connecting = true;
                                try {
                                    // Robust CSRF Retrieval
                                    let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                    if (!token) {
                                        token = document.querySelector('input[name="_token"]')?.value;
                                    }
                                    // Fallback to global Laravel object if available
                                    if (!token && window.laravel && window.laravel.csrfToken) {
                                        token = window.laravel.csrfToken;
                                    }

                                    if (!token) {
                                        throw new Error('CSRF Token not found');
                                    }

                                    const response = await fetch("{{ route('admin.lawfirm.whatsapp.connect') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": token
                                        }
                                    });

                                    const data = await response.json();

                                    if (data.base64) {
                                        this.qrCode = data.base64;
                                        this.status = 'qrcode';
                                        this.startPolling();
                                        this.startRefreshTimer();
                                    } else if (data.status === 'connected') {
                                        this.status = 'connected';
                                        this.stopRefreshTimer();
                                    }
                                } catch (error) {
                                    console.error(error);
                                    alert('Erro ao iniciar conexão: ' + error.message);
                                } finally {
                                    this.connecting = false;
                                }
                            },

                            async disconnect() {
                                if (!confirm('Tem certeza que deseja desconectar?')) return;

                                this.loading = true;
                                try {
                                    // Robust CSRF Retrieval
                                    let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                    if (!token) {
                                        token = document.querySelector('input[name="_token"]')?.value;
                                    }
                                    // Fallback to global Laravel object if available
                                    if (!token && window.laravel && window.laravel.csrfToken) {
                                        token = window.laravel.csrfToken;
                                    }

                                    if (!token) {
                                        throw new Error('CSRF Token not found');
                                    }

                                    const response = await fetch("{{ route('admin.lawfirm.whatsapp.disconnect') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": token
                                        }
                                    });

                                    if (!response.ok) {
                                        // Even if server error, we might want to force disconnect in UI if it was a 500
                                        console.warn('Backend returned error, but proceeding with local disconnect');
                                    }

                                    this.status = 'disconnected';
                                    this.qrCode = null;
                                    this.stopPolling();
                                    this.stopRefreshTimer();
                                } catch (error) {
                                    console.error(error);
                                    // Only alert if it's NOT a backend error we decided to ignore
                                    // alert('Erro ao desconectar');
                                    // In this case, we always want to reset the UI as per user requirement
                                    this.status = 'disconnected';
                                    this.qrCode = null;
                                    this.stopPolling();
                                    this.stopRefreshTimer();
                                } finally {
                                    this.loading = false;
                                }
                            },

                            startPolling() {
                                if (this.pollInterval) return;

                                this.pollInterval = setInterval(async () => {
                                    try {
                                        const response = await fetch("{{ route('admin.lawfirm.whatsapp.status') }}");
                                        const data = await response.json();

                                        if (data.status === 'connected') {
                                            this.status = 'connected';
                                            this.stopPolling();
                                            this.stopRefreshTimer();
                                        }
                                    } catch (e) {
                                        console.error('Polling error', e);
                                    }
                                }, 5000); // Check every 5 seconds
                            },

                            stopPolling() {
                                if (this.pollInterval) {
                                    clearInterval(this.pollInterval);
                                    this.pollInterval = null;
                                }
                            },

                            startRefreshTimer() {
                                if (this.refreshInterval) return;
                                
                                this.refreshInterval = setInterval(() => {
                                    if (this.status === 'qrcode') {
                                        console.log('Refreshing QR Code...');
                                        this.connect();
                                    }
                                }, 40000); // 40 seconds
                            },

                            stopRefreshTimer() {
                                if (this.refreshInterval) {
                                    clearInterval(this.refreshInterval);
                                    this.refreshInterval = null;
                                }
                            }
                        }
                    });
                </script>
            @endPushOnce

            {{-- ── ASSISTENTE IA WHATSAPP ────────────────────────── --}}
            @if(isset($whatsappAssistant))
                <div class="mt-2 rounded-lg border border-green-200 bg-white p-4 dark:border-green-900/50 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4 dark:border-gray-800">
                        <div class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-gray-200">
                            🤖 Assistente de IA para WhatsApp
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                            ● IA-WhatsApp
                        </span>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-green-50 text-3xl dark:bg-green-900/20">
                            {{ $whatsappAssistant->icon ?? '💬' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-800 dark:text-gray-200">{{ $whatsappAssistant->title }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $whatsappAssistant->description }}</p>
                            <div class="mt-3">
                                <a href="{{ route('lawfirm.assistants.index') }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 transition-colors">
                                    ✨ Abrir Assistente
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
</x-admin::layouts>