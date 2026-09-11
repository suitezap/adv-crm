@push('scripts')
    <script type="text/x-template" id="pre-triagem-modal-template">
                <!-- Modal Overlay -->
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900 bg-opacity-50" x-show="isOpen"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

        <div class="relative w-full max-w-2xl transform rounded-lg bg-white p-6 shadow-xl transition-all dark:bg-gray-800"
            @click.away="closeModal()">

            <!-- Header -->
            <div class="mb-4 flex items-center justify-between border-b pb-2 dark:border-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    🤖 Pré-Triagem IA
                </h3>
                <button @click="closeModal()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    ✖
                </button>
            </div>

            <!-- Body -->
            <div class="relative min-h-[300px] space-y-4">
                <!-- Loading Overlay (User Provided Style) -->
                <div x-show="isLoading"
                    class="absolute inset-0 z-50 flex items-center justify-center rounded-lg bg-white/70 dark:bg-gray-900/70"
                    x-transition>
                    <svg class="h-10 w-10 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>

                <!-- Content Area -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Prompt / Resultado
                    </label>
                    <textarea x-model="content"
                        class="h-64 w-full rounded-md border border-gray-300 p-2 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-end gap-3 border-t pt-4 dark:border-gray-700">
                <button @click="closeModal()"
                    class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    Fechar
                </button>

                <button @click="copyToClipboard()"
                    class="flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                    📋 Copiar Texto
                </button>

                <button @click="executeAi()"
                    class="flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-white hover:bg-purple-700"
                    :disabled="isLoading">
                    ⚡ Processar IA
                </button>
            </div>
        </div>
    </div>
    </script>
@endpush

<!-- Wrapper with display contents to respect parent flex layout -->
<div x-data="{
    isOpen: false,
    isLoading: false,
    content: '',
    modalHtml: '',
    leadId: {{ $lead->id }},

    init() {
        this.modalHtml = document.getElementById('pre-triagem-modal-template').innerHTML;
    },

    openModal() {
        this.isOpen = true;
        // removing auto-loadPreview since user now chooses action
    },

    closeModal() {
        this.isOpen = false;
    },

    async loadPreview() {
        // ... (kept for compatibility but not auto-called)
        this.isLoading = true;
        try {
            const url = '{{ route('lawfirm.assistants.lead.pre-triagem', ':id') }}'.replace(':id', this.leadId);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ action: 'preview' })
            });
            const data = await response.json();
            if (data.success) {
                this.content = data.generated_prompt;
            } else {
                this.content = 'Erro ao carregar preview: ' + (data.error || 'Desconhecido');
            }
        } catch (e) {
            this.content = 'Erro de conexão: ' + e.message;
        }
        this.isLoading = false;
    },

    async executeAi() {
        this.isLoading = true;
        this.content = 'Iniciando processamento no n8n...';
        try {
            const url = '{{ route('lawfirm.assistants.lead.pre-triagem', ':id') }}'.replace(':id', this.leadId);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify({ action: 'execute' })
            });
            const data = await response.json();
            if (data.success) {
                this.content = data.generated_prompt;
            } else {
                this.content = 'Erro na execução IA: ' + (data.error || 'Desconhecido');
            }
        } catch (e) {
            this.content = 'Erro de conexão: ' + e.message;
        }
        this.isLoading = false;
    },

    copyToClipboard() {
        navigator.clipboard.writeText(this.content).then(() => {
            alert('Copiado para a área de transferência!');
        });
    }
}" style="display: contents;">

    <button type="button"
        class="inline-flex items-center gap-x-2 rounded-md border border-transparent bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600"
        @click="openModal()">
        🤖 Pré-Triagem IA
    </button>

    <!-- Modal Teleported to Body -->
    <template x-teleport="body">
        <div x-html="modalHtml"></div>
    </template>
</div>