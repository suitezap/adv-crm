<x-admin::layouts>
    <x-slot:title>
        {{ $template->title }}
        </x-slot>

        {{-- Direct script inclusion to avoid stack issues --}}
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

        <style>
            .ai-result-box {
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                padding: 20px;
                background: #fff;
                min-height: 200px;
                line-height: 1.6;
                font-family: 'Inter', sans-serif;
                color: #333;
            }

            .ai-result-box h1,
            .ai-result-box h2,
            .ai-result-box h3,
            .ai-result-box h4 {
                color: #2c3e50;
                margin-top: 15px;
                margin-bottom: 10px;
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
                /* gray-800 */
                border-color: #374151;
                /* gray-700 */
                color: #e5e7eb;
                /* gray-200 */
            }

            .dark .ai-result-box h1,
            .dark .ai-result-box h2,
            .dark .ai-result-box h3,
            .dark .ai-result-box h4 {
                color: #fff;
            }

            .dark .ai-result-box strong {
                color: #fff;
            }
        </style>

        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.assistants.show" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        {{ $template->title }}
                    </div>
                    <p class="text-sm text-gray-500">{{ $template->description }}</p>
                </div>
                <a href="{{ route('lawfirm.assistants.index') }}" class="text-blue-600 hover:underline text-sm">
                    ← Voltar
                </a>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- LEFT: Form -->
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <div style="padding: 1.5rem;">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Preencha os Campos</h3>

                        <form id="assistant-form" class="space-y-4" onsubmit="return false;">
                            @csrf
                            @foreach($template->form_schema as $field)
                                <div class="mb-4">
                                    <label for="{{ $field['name'] }}"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $field['label'] }}
                                    </label>

                                    @if($field['type'] === 'textarea')
                                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4"
                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"></textarea>
                                    @else
                                        <input type="text" id="{{ $field['name'] }}" name="{{ $field['name'] }}"
                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white" />
                                    @endif
                                </div>
                            @endforeach

                            <div class="flex items-center gap-4 pt-2">
                                <button type="button" id="generate-btn"
                                    class="px-6 py-3 text-white rounded-lg font-bold transition-colors"
                                    style="background-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }};">
                                    🚀 Gerar Prompt
                                </button>
    
                                <button type="button" id="btn-execute-ia"
                                    class="px-6 py-3 text-white rounded-lg font-bold transition-colors"
                                    style="background-color: #7B2CBF; border-color: #7B2CBF;">
                                    <span class="icon magic-icon"></span> Executar com IA (Agente)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT: Result -->
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <div style="padding: 1.5rem;">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Resultado</h3>
                            <button id="copy-btn"
                                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors hidden">
                                📋 Copiar
                            </button>
                        </div>

                        <div id="result-placeholder" class="text-center py-12 text-gray-400">
                            <p>Preencha o formulário e clique em "Gerar Prompt".</p>
                        </div>

                        <div id="loading-indicator"
                            style="display:none; margin-top: 10px; color: #7B2CBF; font-weight: bold; text-align: center; padding: 2rem;">
                            <span class="spinner"></span> 🧠 Conectando ao Cérebro IA... aguarde...
                        </div>

                        <!-- Visual Result (Rendered HTML) -->
                        <div id="visual-result" class="ai-result-box hidden">
                            <span class="placeholder-text" style="color:#999;">O resultado da IA aparecerá aqui
                                formatado...</span>
                        </div>

                        <!-- Hidden Textarea for Copy/Raw data -->
                        <textarea id="raw-result" rows="10" readonly
                            class="hidden w-full px-3 py-2 mt-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 dark:text-white font-mono text-sm"
                            style="display:none;"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                console.log('--- Assistant Script Loaded v4 (Full) ---');

                // SAFETY: Global Error Handler to catch unexpected loops
                window.onerror = function (msg, url, line, col, error) {
                    const loading = document.getElementById('loading-indicator');
                    if (loading) loading.style.display = 'none';

                    alert("Erro Crítico no Script:\n" + msg + "\nLinha: " + line);

                    // Unlock buttons
                    const btns = document.querySelectorAll('button');
                    btns.forEach(b => b.disabled = false);
                    return false;
                };

                // Helper to gather form data
                function getFormData() {
                    const form = document.getElementById('assistant-form');
                    const formData = new FormData(form);
                    const jsonData = {};
                    formData.forEach((value, key) => {
                        if (key !== '_token') jsonData[key] = value;
                    });
                    return jsonData;
                }

                // Helper to safe parse markdown
                function parseMarkdown(text) {
                    if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                        try {
                            return marked.parse(text);
                        } catch (e) {
                            console.error('Marked Parse Error:', e);
                            return text;
                        }
                    } else {
                        console.warn('Marked library not loaded');
                        return text.replace(/\n/g, '<br>');
                    }
                }

                // EVENT DELEGATION for both buttons
                document.body.addEventListener('click', async function (e) {

                    // --- GENERATE PROMPT BUTTON ---
                    const btnGen = e.target.closest('#generate-btn');
                    if (btnGen) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('>>> GENERATE CLICK <<<');

                        if (btnGen.disabled) return;

                        const originalText = btnGen.innerHTML;
                        btnGen.disabled = true;
                        btnGen.innerHTML = '⏳ Gerando...';

                        const resultArea = document.getElementById('raw-result');
                        const placeholder = document.getElementById('result-placeholder');
                        const copyBtn = document.getElementById('copy-btn');
                        const loading = document.getElementById('loading-indicator');

                        try {
                            const response = await fetch("{{ route('lawfirm.assistants.generate', $template->slug) }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                                body: JSON.stringify(getFormData())
                            });
                            const data = await response.json();

                            if (data.generated_prompt || data.success) {
                                const content = data.generated_prompt || '';

                                // Update Raw
                                const rawArea = document.getElementById('raw-result');
                                rawArea.value = content;

                                // Update Visual
                                const visualResult = document.getElementById('visual-result');
                                visualResult.innerHTML = parseMarkdown(content);
                                visualResult.classList.remove('hidden');

                                placeholder.classList.add('hidden');
                                loading.style.display = 'none';
                                copyBtn.classList.remove('hidden');
                            } else {
                                alert('Erro: ' + JSON.stringify(data));
                            }
                        } catch (error) {
                            alert('Erro ao processar: ' + error.message);
                        } finally {
                            btnGen.disabled = false;
                            btnGen.innerHTML = originalText;
                        }
                    }

                    // --- EXECUTE AI (N8N) BUTTON ---
                    const btnExec = e.target.closest('#btn-execute-ia');
                    if (btnExec) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('>>> EXECUTE AI CLICK <<<');

                        if (btnExec.disabled) return;

                        const originalText = btnExec.innerHTML;
                        btnExec.disabled = true;
                        btnExec.innerHTML = '⏳ Enviando...';

                        const resultArea = document.getElementById('raw-result');
                        const placeholder = document.getElementById('result-placeholder');
                        const copyBtn = document.getElementById('copy-btn');
                        const loading = document.getElementById('loading-indicator');

                        // UI State
                        resultArea.classList.add('hidden');
                        placeholder.classList.add('hidden');
                        loading.style.display = 'block';

                        try {
                            const response = await fetch("{{ route('lawfirm.assistants.execute', $template->slug) }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                                body: JSON.stringify(getFormData())
                            });

                            // Handle errors based on status first
                            if (!response.ok) {
                                const errData = await response.json();
                                throw new Error(errData.error || 'Erro na execução remota');
                            }

                            const data = await response.json();

                            if (data.result || data.success) {
                                const content = data.result || data.generated_prompt || '';

                                // Update Raw
                                const rawArea = document.getElementById('raw-result');
                                rawArea.value = content;

                                // Update Visual
                                const visualResult = document.getElementById('visual-result');
                                visualResult.innerHTML = parseMarkdown(content);
                                visualResult.classList.remove('hidden');

                                copyBtn.classList.remove('hidden');
                            } else {
                                // Fallback
                                const visualResult = document.getElementById('visual-result');
                                visualResult.textContent = JSON.stringify(data, null, 2);
                                visualResult.classList.remove('hidden');
                            }
                        } catch (error) {
                            console.error(error);
                            alert('Erro: ' + error.message);
                            resultArea.classList.add('hidden');
                            placeholder.classList.remove('hidden');
                        } finally {
                            loading.style.display = 'none';
                            btnExec.disabled = false;
                            btnExec.innerHTML = originalText;
                        }
                    }

                    // Copy button
                    const cBtn = e.target.closest('#copy-btn');
                    if (cBtn) {
                        e.preventDefault();
                        const rawArea = document.getElementById('raw-result');

                        // Copy logic
                        rawArea.style.display = 'block'; // Ensure visible for select()
                        rawArea.select();
                        document.execCommand('copy');
                        rawArea.style.display = 'none'; // Hide again

                        cBtn.textContent = '✓ Copiado!';
                        setTimeout(() => cBtn.textContent = '📋 Copiar', 2000);
                    }
                });
            });
        </script>
</x-admin::layouts>