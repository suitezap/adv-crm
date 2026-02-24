<x-admin::layouts>
    <x-slot:title>
        Detalhes da Execução IA #{{ $history->id }}
        </x-slot>

        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

        <style>
            .ai-result-box {
                padding: 24px;
                min-height: 200px;
                line-height: 1.8;
                font-family: 'Inter', sans-serif;
                font-size: 15px;
                flex-grow: 1;
            }

            .ai-result-box h1,
            .ai-result-box h2,
            .ai-result-box h3,
            .ai-result-box h4 {
                color: inherit;
                margin-top: 24px;
                margin-bottom: 12px;
                font-weight: 700;
            }

            .ai-result-box h1 {
                font-size: 1.5em;
            }

            .ai-result-box h2 {
                font-size: 1.3em;
            }

            .ai-result-box h3 {
                font-size: 1.1em;
            }

            .ai-result-box ul,
            .ai-result-box ol {
                margin-left: 20px;
                margin-bottom: 10px;
            }

            .ai-result-box li {
                margin-bottom: 5px;
            }

            .ai-result-box p {
                margin-bottom: 10px;
            }

            .ai-result-box strong {
                color: #000;
                font-weight: 700;
            }

            .ai-result-box blockquote {
                border-left: 4px solid #7B2CBF;
                padding-left: 10px;
                color: #666;
                font-style: italic;
                margin: 10px 0;
            }

            /* Dark mode support */
            .dark .ai-result-box {
                background: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
            }

            .dark .ai-result-box h1,
            .dark .ai-result-box h2,
            .dark .ai-result-box h3,
            .dark .ai-result-box h4,
            .dark .ai-result-box strong {
                color: #fff;
            }
        </style>

        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="text-xl font-bold dark:text-white">
                        Detalhes da Execução IA #{{ $history->id }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Assistente: <strong>{{ $history->template->title ?? 'Desconhecido' }}</strong> •
                        Usuário: <strong>{{ $history->user->name ?? 'Desconhecido' }}</strong> •
                        Data: {{ core()->formatDate($history->created_at, 'd/m/Y H:i') }}
                    </div>
                </div>
                <a href="{{ route('lawfirm.assistants.history.index') }}" class="text-blue-600 hover:underline text-sm">
                    ← Voltar para Histórico
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left: Inputs -->
                <div
                    class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                    <div class="p-8">
                        <h3
                            class="text-lg font-bold text-gray-800 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-3 flex items-center gap-2">
                            <span class="text-xl">📋</span> Dados de Entrada
                        </h3>

                        <div class="space-y-6">
                            @if($history->input_data && is_array($history->input_data))
                                @foreach($history->input_data as $key => $value)
                                    @if($key !== 'tenant_id')
                                        <div class="border-b border-gray-100 dark:border-gray-800 pb-4">
                                            <p
                                                class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            </p>
                                            <p
                                                class="text-base text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">
                                                {{ $value }}
                                            </p>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <p class="text-gray-500">Nenhum dado de entrada registrado.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right: Result -->
                <div
                    class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                    <div class="p-8 h-full flex flex-col">
                        <div
                            class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-800 pb-3">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="text-xl">✨</span> Resultado Gerado
                            </h3>
                            <div class="flex items-center gap-4">
                                @if($history->status === 'completed' && $history->generated_content)
                                    <button onclick="copyHistoryResult(this)"
                                        class="text-sm font-semibold text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300 transition-colors flex items-center gap-1">
                                        📋 Copiar
                                    </button>
                                @endif
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                    {{ $history->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
    ($history->status === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300') }}">
                                    {{ ucfirst($history->status) }}
                                </span>
                            </div>
                        </div>

                        @if($history->status === 'completed' && $history->generated_content)
                            <div id="visual-result"
                                class="ai-result-box border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50 px-6 py-4">
                            </div>
                            <input type="hidden" id="raw-content"
                                value="{{ htmlspecialchars($history->generated_content) }}">
                        @else
                            <div class="text-center py-12 text-gray-400">
                                <p>O resultado não está disponível ou a execução ainda não foi concluída.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const rawContent = document.getElementById('raw-content');
                    if (rawContent) {
                        const visualResult = document.getElementById('visual-result');
                        if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                            visualResult.innerHTML = marked.parse(rawContent.value);
                        } else {
                            visualResult.innerHTML = rawContent.value.replace(/\n/g, '<br>');
                        }
                    }
                });

                window.copyHistoryResult = async function (btn) {
                    const rawContent = document.getElementById('raw-content');
                    if (!rawContent || !rawContent.value) return;

                    const originalText = btn.innerHTML;

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(rawContent.value);
                        } else {
                            var ta = document.createElement('textarea');
                            ta.value = rawContent.value;
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                        }

                        btn.innerHTML = '✅ Copiado!';
                    } catch (e) {
                        console.error('Erro ao copiar:', e);
                        btn.innerHTML = '❌ Erro';
                    }

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                    }, 2000);
                };
            </script>
        @endpush
</x-admin::layouts>