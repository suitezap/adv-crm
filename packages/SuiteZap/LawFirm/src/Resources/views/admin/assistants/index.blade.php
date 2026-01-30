<x-admin::layouts>
    <x-slot:title>
        Assistentes Jurídicos (IA)
        </x-slot>

        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.assistants.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Assistentes Jurídicos (IA)
                    </div>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($templates as $template)
                    <div
                        class="bg-white dark:bg-gray-900 rounded-lg shadow border border-gray-200 dark:border-gray-700 flex flex-col justify-between hover:shadow-lg transition-shadow">
                        <div style="padding: 1.5rem;">
                            <span
                                class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 mb-3">
                                {{ ucfirst($template->category) }}
                            </span>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                                {{ $template->title }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                {{ $template->description ?? 'Sem descrição.' }}
                            </p>
                        </div>
                        <div class="mt-4 px-8 pb-8">
                            <button type="button" class="btn btn-sm btn-primary btn-open-assistant"
                                data-id="{{ $template->id }}" data-title="{{ $template->title }}"
                                data-structure="{{ json_encode($template->prompt_structure) }}"
                                data-webhook="{{ !empty($template->n8n_webhook_url) ? '1' : '0' }}"
                                style="background-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }} !important; border-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }} !important;"
                                onclick="handleOpenAssistant(this)">
                                Usar Assistente
                            </button>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-full text-center py-12 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-500 dark:text-gray-400">Nenhum assistente disponível no momento.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Modal de Execução de Assistente (Inject by Antigravity) -->
        <div id="assistantModal" class="assistant-modal-overlay">
            <div class="assistant-modal-content">

                <!-- Header -->
                <div class="assistant-modal-header">
                    <h3 id="modalTitle">Assistente</h3>
                    <button type="button" class="assistant-modal-close" onclick="closeAssistantModal()">&times;</button>
                </div>

                <!-- Passo 1: Formulário -->
                <form id="assistantForm">
                    <input type="hidden" id="templateId" name="template_id">
                    <div id="dynamicInputs" class="mb-5">
                        <!-- Inputs injetados via JS -->
                    </div>

                    <div id="formError" class="assistant-error-message"></div>

                    <div class="assistant-modal-footer">
                        <!-- Botão Cancelar (Padrão Secundário) -->
                        <button type="button" class="btn btn-lg btn-secondary" onclick="closeAssistantModal()">
                            Cancelar
                        </button>

                        <div class="assistant-modal-actions">
                            <!-- Botão Copiar (Outline / Transparente) -->
                            <button type="button" onclick="submitAssistant('preview')" class="btn btn-lg btn-transparent">
                                <span class="icon heroicon-document-duplicate" style="margin-right: 5px;"></span>
                                Copiar Texto
                            </button>

                            <!-- Botão IA (Destaque Premium) -->
                            <button type="button" onclick="submitAssistant('execute')" id="btnExecuteIA"
                                class="btn btn-lg btn-primary btn-ai-magic" style="display: none;">
                                <span class="icon heroicon-sparkles" style="margin-right: 5px;"></span>
                                Processar com IA
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Passo 2: Loading -->
                <div id="loadingState" class="assistant-loading-state">
                    <div class="spinner"></div>
                    <p>A IA está processando sua solicitação...</p>
                    <small>Aguarde, isso pode levar alguns segundos.</small>
                </div>

                <!-- Passo 3: Resultado -->
                <div id="resultState" style="display: none;">
                    <label class="assistant-result-label">Resultado Gerado:</label>
                    <textarea id="resultOutput" class="assistant-result-textarea" rows="15"></textarea>

                    <div class="assistant-result-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetModal()">Novo</button>
                        <button type="button" class="btn btn-brand-dynamic" 
                            style="background-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }} !important; border-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }} !important;"
                            onclick="copyToClipboard()">
                            Copiar Texto
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @push('styles')
            <style>
                @keyframes spin {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }

                .form-group {
                    margin-bottom: 15px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    font-size: 0.9em;
                    text-transform: capitalize;
                    color: #333;
                }

                .form-group input,
                .form-group textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }

                .form-group input:focus {
                    border-color: #0056b3;
                    outline: none;
                }

                /* --- BUTTON RESETS & STYLES --- */
                .btn {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    column-gap: 0.625rem !important;
                    font-family: inherit;
                    cursor: pointer;
                    /* Default sizing and border */
                    padding: 0.6rem 1.2rem; 
                    border: 1px solid transparent;
                    border-radius: 6px;
                    transition: all 0.2s;
                }
                
                /* Sizes overriding default */
                .btn-lg {
                    padding: 0.75rem 1.5rem !important;
                    font-size: 1rem !important;
                }
                .btn-sm {
                    padding: 0.4rem 0.8rem !important;
                    font-size: 0.875rem !important;
                }

                /* Standard Colors (Fixing missing formatting for generic buttons) */
                .btn-primary {
                    background-color: #0041FF; /* Fallback brand color */
                    color: white;
                    border-color: #0041FF;
                }
                .btn-primary:hover {
                    opacity: 0.9;
                }

                .btn-secondary {
                    background-color: #ffffff;
                    border: 1px solid #d1d5db; /* Gray-300 */
                    color: #374151; /* Gray-700 */
                }
                .btn-secondary:hover {
                    background-color: #f9fafb;
                    border-color: #9ca3af;
                }

                /* Green Success Button */
                .btn-success {
                    background-color: #38c172; /* Laravel/Krayin Green */
                    color: white;
                    border-color: #38c172;
                }
                .btn-success:hover {
                    background-color: #2ea05e;
                    border-color: #2ea05e;
                }
                
                /* Botão GRID override */
                .btn-open-assistant {
                    border-width: 1px !important;
                    border-style: solid !important;
                    color: white !important;
                    border-radius: 6px !important;
                    padding: 8px 16px !important;
                    font-size: 14px !important;
                    font-weight: 600 !important;
                    line-height: 1.5 !important;
                }

                /* PROPOSED: Dynamic Brand Button Structure (matches btn-open-assistant styles) */
                .btn-brand-dynamic {
                    border-width: 1px !important;
                    border-style: solid !important;
                    color: white !important;
                    border-radius: 6px !important;
                    padding: 8px 16px !important;
                    font-size: 14px !important;
                    font-weight: 600 !important;
                    line-height: 1.5 !important;
                    /* Core btn props ensured */
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    column-gap: 0.625rem !important;
                    font-family: inherit;
                    cursor: pointer;
                }
                .btn-brand-dynamic:hover {
                    opacity: 0.9;
                }

                /* Botão Mágico IA */
                .btn-ai-magic {
                    background-image: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
                    background-color: transparent !important;
                    border: 0 solid transparent !important; 
                    border-radius: 8px !important;
                    color: white !important;
                    font-weight: 600 !important;
                    transition: all 0.2s ease;
                    position: relative;
                    z-index: 10;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    padding: 0.75rem 1.5rem !important; /* Force lg padding */
                }
                .btn-ai-magic:hover {
                    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.4);
                    transform: translateY(-1px);
                    opacity: 0.95;
                    color: white !important;
                }
                
                .btn .icon {
                    vertical-align: middle;
                    width: 20px;
                    height: 20px;
                }

                .btn-transparent {
                    background: transparent !important;
                    border: 1px solid #d1d5db !important;
                    border-radius: 8px !important;
                    color: #4b5563 !important;
                    padding: 0.5rem 1rem !important;
                }
                .btn-transparent:hover {
                    background-color: #f3f4f6 !important;
                    border-color: #9ca3af !important;
                    color: #1f2937 !important;
                }

                /* --- MODAL STYLES (Cleaned) --- */
                .assistant-modal-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 9999 !important;
                    align-items: center;
                    justify-content: center;
                }
                .assistant-modal-overlay.active {
                    display: flex !important;
                }

                .assistant-modal-content {
                    background: white;
                    width: 600px;
                    max-width: 90%;
                    padding: 25px;
                    border-radius: 8px;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }

                .assistant-modal-header {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }

                .assistant-modal-header h3 {
                    font-size: 1.25rem;
                    font-weight: bold;
                    margin: 0;
                }

                .assistant-modal-close {
                    font-size: 1.5rem;
                    background: none;
                    border: none;
                    cursor: pointer;
                }

                .assistant-modal-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-top: 25px;
                    padding-top: 15px;
                    border-top: 1px solid #e1e1e1;
                }

                .assistant-modal-actions {
                    display: flex;
                    gap: 10px;
                }

                .assistant-error-message {
                    color: #dc3545;
                    display: none;
                    margin-bottom: 15px;
                    padding: 10px;
                    background: #f8d7da;
                    border-radius: 4px;
                }

                .assistant-loading-state {
                    display: none; 
                    text-align: center; 
                    padding: 40px;
                }

                .assistant-loading-state .spinner {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #0056b3;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px;
                }
                .assistant-loading-state p {
                    font-weight: 500;
                }
                .assistant-loading-state small {
                     color: #6b7280;
                }

                .assistant-result-label {
                    font-weight: bold;
                    display: block;
                    margin-bottom: 5px;
                }

                .assistant-result-textarea {
                    width: 100%;
                    border: 1px solid #ccc;
                    padding: 10px;
                    border-radius: 4px;
                    font-family: monospace;
                    background-color: #f9f9f9;
                }

                .assistant-result-actions {
                    margin-top: 15px;
                    text-align: right;
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
            </style>
        @endpush

        @push('scripts')
            <script>
                function handleOpenAssistant(btn) {
                    const id = btn.dataset.id;
                    const title = btn.dataset.title;
                    const hasWebhook = btn.dataset.webhook === "1"; 
                    let structure = {};

                    try {
                        if (btn.dataset.structure && btn.dataset.structure !== "null") {
                            structure = JSON.parse(btn.dataset.structure);
                        }
                    } catch (e) {
                        console.error('Error parsing prompt structure', e);
                    }

                    openAssistant(id, title, structure, hasWebhook);
                }

                function openAssistant(id, title, promptStructure, hasWebhook) {
                    document.getElementById('modalTitle').innerText = title;
                    document.getElementById('templateId').value = id;

                    const btnExecute = document.getElementById('btnExecuteIA');
                    if (hasWebhook) {
                        btnExecute.style.display = 'inline-block';
                    } else {
                        btnExecute.style.display = 'none';
                    }

                    resetModal();

                    const inputsContainer = document.getElementById('dynamicInputs');
                    inputsContainer.innerHTML = '';

                    const regex = new RegExp('\\{\\{(.*?)\\}\\}', 'g');
                    let match;
                    const foundVars = new Set();

                    if (!promptStructure) promptStructure = "";
                    if (typeof promptStructure !== 'string') {
                        promptStructure = JSON.stringify(promptStructure);
                    }

                    while ((match = regex.exec(promptStructure)) !== null) {
                        const varName = match[1].trim();
                        if (!foundVars.has(varName)) {
                            foundVars.add(varName);

                            const div = document.createElement('div');
                            div.className = 'form-group';
                            const labelText = varName.replace(/_/g, ' ');

                            div.innerHTML = `
                        <label>${labelText}</label>
                        <input type="text" name="${varName}" required placeholder="Informe ${labelText}...">
                    `;
                            inputsContainer.appendChild(div);
                        }
                    }

                    if (foundVars.size === 0) {
                        inputsContainer.innerHTML = '<div style="padding: 10px; background: #e9ecef; border-radius: 4px;">Este assistente não requer informações adicionais. Clique em Gerar.</div>';
                    }

                    document.getElementById('assistantModal').classList.add('active');
                }

                function closeAssistantModal() {
                    document.getElementById('assistantModal').classList.remove('active');
                }

                function resetModal() {
                    document.getElementById('assistantForm').style.display = 'block';
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('resultState').style.display = 'none';
                    document.getElementById('formError').style.display = 'none';
                    document.getElementById('assistantForm').reset();
                }

                async function submitAssistant(action) {
                    const form = document.getElementById('assistantForm');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    form.style.display = 'none';
                    document.getElementById('loadingState').style.display = 'block';
                    document.getElementById('formError').style.display = 'none';

                    const formData = new FormData(form);
                    const dataPayload = {};
                    formData.forEach((value, key) => {
                        if (key !== 'template_id') dataPayload[key] = value;
                    });

                    try {
                        const url = "{{ route('lawfirm.assistants.process') }}";
                        const response = await fetch(url, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({
                                template_id: document.getElementById('templateId').value,
                                data: dataPayload,
                                action: action
                            })
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.error || 'Erro desconhecido ao processar.');
                        }

                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('resultState').style.display = 'block';
                        document.getElementById('resultOutput').value = result.generated_prompt || result.result || 'Sucesso!';

                    } catch (error) {
                        document.getElementById('loadingState').style.display = 'none';
                        form.style.display = 'block';
                        document.getElementById('formError').innerText = error.message;
                        document.getElementById('formError').style.display = 'block';
                    }
                }

                function copyToClipboard() {
                    const copyText = document.getElementById("resultOutput");
                    copyText.select();
                    document.execCommand("copy"); 
                    alert("Texto copiado!");
                }
            </script>
        @endpush
</x-admin::layouts>