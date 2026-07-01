<x-admin::layouts>
    <x-slot:title>
        Detalhes da Requisição Escavador #{{ $history->id }}
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>

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

        .ai-result-box h1 { font-size: 1.5em; }
        .ai-result-box h2 { font-size: 1.3em; }
        .ai-result-box h3 { font-size: 1.1em; }
        .ai-result-box ul, .ai-result-box ol { margin-left: 20px; margin-bottom: 10px; }
        .ai-result-box li { margin-bottom: 5px; }
        .ai-result-box p { margin-bottom: 10px; }
        .ai-result-box strong { color: #000; font-weight: 700; }
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
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    Detalhes da Requisição Escavador #{{ $history->id }}
                </div>
                <div class="text-sm text-gray-500">
                    Serviço: <strong>{{ str_replace('_', ' ', $history->endpoint_type) }}</strong> •
                    Processo: <strong>{{ $history->processo->numero_cnj ?? 'N/A' }}</strong> •
                    ID Externo: <strong>{{ $history->external_id ?? 'N/A' }}</strong> •
                    Data: {{ core()->formatDate($history->created_at, 'd/m/Y H:i') }}
                </div>
            </div>
            <a href="{{ route('lawfirm.escavador.history') }}" class="text-blue-600 hover:underline text-sm">
                ← Voltar para Histórico
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-1 gap-8">
            <!-- Result -->
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                <div class="p-8 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <span class="text-xl">📊</span> Resposta da Execução (Payload)
                        </h3>
                        <div class="flex items-center gap-4">
                            @if($history->status === 'completed' && $history->payload_response)
                                <button onclick="copyHistoryResult(this)"
                                    class="text-sm font-semibold text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300 transition-colors flex items-center gap-1">
                                    📋 Copiar JSON
                                </button>
                            @endif
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider
                                {{ $history->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
                                ($history->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300') }}">
                                {{ ucfirst($history->status) }}
                            </span>
                        </div>
                    </div>

                    @if($history->payload_response)
                        <div id="visual-result" class="ai-result-box border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50 px-6 py-4">
                        </div>
                        <input type="hidden" id="raw-content" value="{{ htmlspecialchars(json_encode($history->payload_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}">
                    @else
                        <div class="text-center py-12 text-gray-400">
                            <p>O payload não está disponível ou a execução ainda não foi concluída.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // jsonToMarkdown function from index.blade.php
            function jsonToMarkdown(obj, depth = 0) {
                if (obj === null || obj === undefined) return '';

                if (Array.isArray(obj)) {
                    let markdown = '';
                    obj.forEach(item => {
                        if (typeof item === 'object' && item !== null) {
                            markdown += `${'  '.repeat(depth)}- \n${jsonToMarkdown(item, depth + 1)}`;
                        } else {
                            markdown += `${'  '.repeat(depth)}- ${item}\n`;
                        }
                    });
                    return markdown;
                } else if (typeof obj === 'object') {
                    let markdown = '';
                    for (const key in obj) {
                        const value = obj[key];
                        if (Array.isArray(value)) {
                            markdown += `${'  '.repeat(depth)}> **${key}:**\n${jsonToMarkdown(value, depth + 1)}`;
                        } else if (typeof value === 'object' && value !== null) {
                            markdown += `${'  '.repeat(depth)}> **${key}:**\n${jsonToMarkdown(value, depth + 1)}`;
                        } else {
                            markdown += `${'  '.repeat(depth)}> **${key}:** ${value !== null ? value : '*nulo*'}\n\n`;
                        }
                    }
                    return markdown;
                } else {
                    return `${obj}\n`;
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const rawContent = document.getElementById('raw-content');
                if (rawContent && rawContent.value) {
                    const visualResult = document.getElementById('visual-result');
                    try {
                        const jsonData = JSON.parse(rawContent.value);
                        const mdText = jsonToMarkdown(jsonData);
                        
                        if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                            const rawHtml = marked.parse(mdText);
                            visualResult.innerHTML = (typeof DOMPurify !== 'undefined')
                                ? DOMPurify.sanitize(rawHtml)
                                : rawHtml;
                        } else {
                            visualResult.innerHTML = mdText.replace(/\n/g, '<br>');
                        }
                    } catch (e) {
                        visualResult.innerHTML = "<pre>" + rawContent.value + "</pre>";
                    }
                }
            });

            window.copyHistoryResult = async function (btn) {
                const rawContent = document.getElementById('raw-content');
                if (!rawContent || !rawContent.value) return;

                const originalText = btn.innerHTML;

                try {
                    let dataToCopy = rawContent.value;
                    try {
                        // Tentar copiar formatado
                        dataToCopy = JSON.stringify(JSON.parse(dataToCopy), null, 4);
                    } catch (e) {}

                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(dataToCopy);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = dataToCopy;
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
