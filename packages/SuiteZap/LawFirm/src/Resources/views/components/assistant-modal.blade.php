@props(['entityId', 'lead', 'tenantId'])

<div x-data="assistantModalHandler()" @open-assistant-modal.window="openModal($event.detail)" class="relative z-[100]"
    aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;" x-show="isOpen">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="isOpen"
        x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">

            <!-- Modal Panel -->
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl dark:bg-gray-900"
                x-show="isOpen" @click.away="closeModal()" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div
                    class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between items-center dark:bg-gray-800 border-b dark:border-gray-700">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">
                        🤖 Assistente IA - Análise de Viabilidade
                    </h3>
                    <button @click="closeModal()" type="button"
                        class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Fechar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- LEFT: Inputs -->
                        <div>
                            <form id="checklist-assistant-form" onsubmit="return false;">
                                <div class="space-y-4">
                                    <!-- Hidden / Read-only Context Fields -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título
                                            do Lead</label>
                                        <input type="text" name="title" x-model="form.title" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                                        <textarea name="description" x-model="form.description" rows="4" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant
                                            ID</label>
                                        <input type="text" name="tenant_id" x-model="form.tenantId" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- RIGHT: Results -->
                        <div
                            class="flex flex-col h-full min-h-[300px] rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">

                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">Resultado da Análise</h4>
                                <button x-show="result" @click="copyResult()"
                                    class="text-xs text-purple-600 hover:text-purple-500 font-medium">
                                    Copiar
                                </button>
                            </div>

                            <!-- Initial State -->
                            <div x-show="!isLoading && !result"
                                class="flex flex-1 items-center justify-center text-gray-400 text-sm text-center">
                                Clique em "Executar Análise" para processar os dados.
                            </div>

                            <!-- Loading State -->
                            <div x-show="isLoading"
                                class="flex flex-1 flex-col items-center justify-center text-purple-600">
                                <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="text-sm font-medium">Processando com IA...</span>
                            </div>

                            <!-- Result State -->
                            <div x-show="!isLoading && result"
                                class="flex-1 overflow-y-auto max-h-[400px] prose prose-sm dark:prose-invert">
                                <div x-html="parsedResult"></div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-800 border-t dark:border-gray-700">
                    <button type="button" @click="executeAi()" :disabled="isLoading"
                        class="inline-flex w-full justify-center rounded-md bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isLoading">⚡ Executar Análise</span>
                        <span x-show="isLoading">Processando...</span>
                    </button>
                    <button type="button" @click="closeModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-gray-700 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-600">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function assistantModalHandler() {
        return {
            isOpen: false,
            isLoading: false,
            result: null,
            parsedResult: null,
            form: {
                title: '{{ addslashes($lead->title ?? "") }}',
                description: '{{ addslashes(str_replace(["\r", "\n"], " ", $lead->description ?? "")) }}',
                tenantId: '{{ $tenantId }}'
            },
            templateSlug: 'pre-triagem-checklist',

            openModal(detail) {
                this.isOpen = true;
                this.result = null;
                this.parsedResult = null;
                // We could switch templates here based on detail.type if needed
            },

            closeModal() {
                this.isOpen = false;
            },

            async executeAi() {
                this.isLoading = true;

                // Construct payload manually matching template variables
                const payload = {
                    title: this.form.title,
                    description: this.form.description,
                    tenant_id: this.form.tenantId,
                    lead_id: {{ $entityId ?? 'null' }}
                };

                try {
                    // 1. Trigger Execute
                    const response = await fetch("{{ route('lawfirm.assistants.execute', ':slug') }}".replace(':slug', this.templateSlug), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        const err = await response.json();
                        throw new Error(err.error || 'Erro na execução');
                    }

                    const data = await response.json();

                    if (data.status === 'queued' && data.history_id) {
                        await this.pollStatus(data.history_id);
                    } else {
                        throw new Error('Resposta inesperada da API');
                    }

                } catch (error) {
                    alert('Erro: ' + error.message);
                    this.isLoading = false;
                }
            },

            async pollStatus(historyId) {
                const maxAttempts = 60;
                let attempts = 0;
                const pollInterval = setInterval(async () => {
                    attempts++;
                    try {
                        const url = "{{ route('lawfirm.assistants.check_status', ':id') }}".replace(':id', historyId);
                        const res = await fetch(url);
                        const data = await res.json();

                        if (data.status === 'completed') {
                            clearInterval(pollInterval);
                            this.result = data.generated_content;
                            this.parsedResult = this.parseMarkdown(this.result);
                            this.isLoading = false;
                        } else if (data.status === 'failed') {
                            clearInterval(pollInterval);
                            alert('Falha no processamento: ' + (data.error_message || 'Erro desconhecido'));
                            this.isLoading = false;
                        } else if (attempts >= maxAttempts) {
                            clearInterval(pollInterval);
                            alert('Tempo limite excedido.');
                            this.isLoading = false;
                        }
                    } catch (e) {
                        console.error(e);
                        // continue polling
                    }
                }, 2000);
            },

            parseMarkdown(text) {
                if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                    return marked.parse(text);
                }
                return text.replace(/\n/g, '<br>');
            },

            copyResult() {
                navigator.clipboard.writeText(this.result).then(() => {
                    // Optional toast
                });
            }
        }
    }
</script>