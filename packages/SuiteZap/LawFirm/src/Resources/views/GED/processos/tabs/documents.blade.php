@php
    $readOnly = $readOnly ?? false;
    // Garante que a relação seja carregada ou coleção vazia
    $anexos = $processo->anexos ?? collect([]);
    $checklistDocs = $processo->documents ?? collect([]);

    // Carregar templates de checklist ordenados
    $checklistTemplates = \SuiteZap\LawFirm\Legal\Models\ChecklistTemplate::query()
        ->orderByRaw("CASE WHEN name LIKE '%Padrão Básico%' THEN 0 ELSE 1 END")
        ->orderBy('name')
        ->get();
@endphp

<!-- Container Principal -->
<div class="mt-4 flex flex-col gap-4 w-full rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 shadow-sm"
    id="lf-docs-container">

    <!-- Meta CSRF Token Fallback if not in head -->
    @if(!trim($__env->yieldContent('meta_csrf_token')))
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif

    <!-- Header com Toggle -->
    <div class="flex items-center justify-between cursor-pointer select-none" onclick="window.lfDocsToggle()">
        <div class="flex items-center gap-2">
            <span class="p-2 rounded-full bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <i class="icon-file text-xl"></i>
            </span>
            <div class="flex flex-col">
                <p class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    Documentos e Anexos
                </p>
                <span class="text-xs text-gray-500">
                    {{ $anexos->count() }} arquivo(s) &bull; {{ $checklistDocs->count() }} item(ns) checklist
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <i id="lf-docs-arrow-icon"
                class="icon-arrow-down text-2xl text-gray-400 transition-transform duration-200"></i>
        </div>
    </div>

    <!-- Conteúdo Colapsável -->
    <div id="lf-docs-content" style="display: none;"
        class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-4 w-full">

        @if(!$readOnly)
            <!-- Área de Upload (Dropzone) -->
            <div class="mb-6 w-full">
                <!-- REMOVIDO FORM NESTED: Agora é puramente JS/AJAX -->
                <div id="lf-docs-upload-area" class="w-full">

                    <!-- Input Invisível Principal -->
                    <input type="file" name="anexos[]" multiple id="lf-docs-input" class="hidden"
                        onchange="window.lfDocsHandleFiles(this.files)">

                    <div id="lf-docs-dropzone"
                        class="relative flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 hover:bg-gray-100 transition-all dark:bg-gray-800/50 dark:border-gray-700 dark:hover:bg-gray-800 cursor-pointer group w-full"
                        onclick="document.getElementById('lf-docs-input').click()" ondragover="window.lfDocsDragOver(event)"
                        ondragleave="window.lfDocsDragLeave(event)" ondrop="window.lfDocsDrop(event)">

                        <div
                            class="p-3 bg-white dark:bg-gray-700 rounded-full shadow-sm mb-3 group-hover:scale-110 transition-transform">
                            <i class="icon-file text-3xl text-blue-500"></i>
                        </div>

                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 text-center">
                            <span class="text-blue-600 font-bold hover:underline">Clique para adicionar</span> ou arraste
                            arquivos
                        </p>
                        <p class="text-xs text-gray-400 mt-1">PDF, DOCX, JPG, PNG (Max: 20MB)</p>
                    </div>

                    <!-- Lista de Arquivos Selecionados (Preview antes de salvar) -->
                    <ul id="lf-docs-preview-list" class="mt-3 space-y-2 hidden w-full">
                        <!-- Preenchido via JS -->
                    </ul>

                    <!-- Botão Salvar Uploads (Só aparece se houver arquivos) -->
                    <div id="lf-docs-save-actions" class="mt-4 hidden justify-end w-full">
                        <button type="button" onclick="window.lfDocsUploadFiles()"
                            class="primary-button flex items-center gap-2">
                            <i class="icon-save text-lg"></i>
                            <span id="lf-docs-save-text">Salvar Novos Arquivos</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Lista de Anexos Existentes -->
        <div class="mb-8 w-full">
            <h3 class="text-base font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                <i class="icon-folder text-gray-400"></i> Arquivos do Processo
            </h3>

            <div class="overflow-x-auto w-full rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm w-full">
                    <thead
                        class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium w-auto">Nome</th>
                            <th class="px-4 py-3 text-right font-medium whitespace-nowrap w-[120px]">Tamanho</th>
                            @if(!$readOnly)
                                <th class="px-4 py-3 text-center font-medium w-[100px]">Ações</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                        @forelse($anexos as $anexo)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group"
                                id="anexo-row-{{ $anexo->id }}">
                                <td class="px-4 py-3">
                                    <a href="{{ $anexo->url }}" target="_blank"
                                        class="flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:text-blue-600 transition-colors">
                                        <div
                                            class="flex items-center justify-center w-8 h-8 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 flex-shrink-0">
                                            <span class="{{ $anexo->icon ?? 'icon-file' }} text-lg"></span>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium truncate block">{{ $anexo->nome_original }}</span>
                                            <span class="text-[10px] text-gray-400 uppercase">{{ $anexo->extension }}</span>
                                        </div>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-500 whitespace-nowrap">
                                    {{ number_format(($anexo->tamanho ?? 0) / 1024, 2, ',', '.') }} KB
                                </td>
                                @if(!$readOnly)
                                    <td class="px-4 py-3 text-center">
                                        <div
                                            class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <!-- Download -->
                                            <a href="{{ $anexo->url }}" target="_blank"
                                                class="p-1.5 rounded-md text-gray-500 hover:bg-gray-100 hover:text-blue-600 dark:hover:bg-gray-700 transition-colors"
                                                title="Baixar">
                                                <span class="icon-download text-lg"></span>
                                            </a>

                                            <!-- Delete AJAX -->
                                            <button type="button" onclick="window.lfDocsDeleteAttachment('{{ $anexo->id }}')"
                                                class="p-1.5 rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 transition-colors"
                                                title="Excluir">
                                                <span class="icon-delete text-lg"></span>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $readOnly ? 2 : 3 }}" class="px-4 py-8 text-center text-gray-400 italic">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="icon-file text-3xl opacity-20"></i>
                                        <span>Nenhum documento anexado.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Divisor -->
        <div class="border-t border-gray-100 dark:border-gray-800 my-6"></div>

        <!-- Seção Checklist -->
        <div class="w-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <span class="p-1.5 rounded-md bg-purple-50 text-purple-600 dark:bg-purple-900/20">
                        <i class="icon-menu text-lg"></i>
                    </span>
                    Checklist de Documentos
                </h3>
            </div>

            @if(!$readOnly)
                <!-- Importar Kit (Compacto) -->
                <div
                    class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4 flex flex-wrap items-center gap-3 w-full">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">Importar
                        Modelo:</span>

                    <div class="flex flex-1 gap-2">
                        <select id="lf-docs-template-select"
                            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm py-1.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            @foreach($checklistTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="window.lfDocsImportTemplate()"
                            class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors shadow-sm">
                            Importar
                        </button>
                    </div>
                </div>
            @endif

            <!-- Tabela Checklist -->
            <div class="overflow-x-auto w-full rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm w-full">
                    <thead
                        class="bg-gray-50 dark:bg-gray-800 text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left w-[120px]">Status</th>
                            <th class="px-4 py-3 text-left">Documento</th>
                            <th class="px-4 py-3 text-left">Obs</th>
                            @if(!$readOnly)
                                <th class="px-4 py-3 text-center w-[150px]">Ações</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                        @forelse($checklistDocs as $doc)
                            @php
                                $statusMap = [
                                    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Pendente'],
                                    'received' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Recebido'],
                                    'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Aprovado'],
                                    'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Rejeitado'],
                                ];
                                $st = $statusMap[$doc->status] ?? $statusMap['pending'];
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $st['bg'] }} {{ $st['text'] }}">
                                        {{ $st['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $doc->name }}
                                    @if($doc->file_path)
                                        <i class="icon-attachment text-gray-400 ml-1" title="Anexo vinculado"></i>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 truncate max-w-[200px]" title="{{ $doc->notes }}">
                                    {{ $doc->notes ?? '-' }}
                                </td>
                                @if(!$readOnly)
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <select onchange="window.lfDocsUpdateStatus('{{ $doc->id }}', this.value)"
                                                class="text-xs border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 py-1 pr-6 cursor-pointer">
                                                <option value="pending" {{ $doc->status == 'pending' ? 'selected' : '' }}>Pendente
                                                </option>
                                                <option value="received" {{ $doc->status == 'received' ? 'selected' : '' }}>
                                                    Recebido</option>
                                                <option value="approved" {{ $doc->status == 'approved' ? 'selected' : '' }}>
                                                    Aprovado</option>
                                                <option value="rejected" {{ $doc->status == 'rejected' ? 'selected' : '' }}>
                                                    Rejeitado</option>
                                            </select>

                                            <!-- Delete -->
                                            <button type="button" onclick="window.lfDocsDeleteChecklistItem('{{ $doc->id }}')"
                                                class="text-gray-400 hover:text-red-500 transition-colors">
                                                <i class="icon-delete text-lg"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $readOnly ? 3 : 4 }}"
                                    class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400 italic">
                                    Nenhum item no checklist. Importe um kit acima.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // WINDOW GLOBAL FUNCTIONS (Financial Pattern)
        // ---------------------------------------------------------

        // --- UTILS ---
        window.lfDocsGetCsrfToken = function () {
            // Try meta tag first
            let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Try global object
            if (!token && window.Laravel?.csrfToken) {
                token = window.Laravel.csrfToken;
            }

            // Try finding hidden input in the form
            if (!token) {
                token = document.querySelector('input[name="_token"]')?.value;
            }
            return token;
        };

        // Toggle Section
        window.lfDocsToggle = function () {
            const content = document.getElementById('lf-docs-content');
            const icon = document.getElementById('lf-docs-arrow-icon');

            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        };

        // Drag & Drop Handlers
        window.lfDocsDragOver = function (e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('lf-docs-dropzone');
            if (dropzone) {
                dropzone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                dropzone.classList.remove('border-gray-300');
            }
        };

        window.lfDocsDragLeave = function (e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('lf-docs-dropzone');
            if (dropzone) {
                dropzone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                dropzone.classList.add('border-gray-300');
            }
        };

        window.lfDocsDrop = function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.lfDocsDragLeave(e); // Reset styles

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                window.lfDocsHandleFiles(files);
            }
        };

        // Handle File Selection (Store in global variable to persist across AJAX)
        window.lfDocsSelectedFiles = [];

        window.lfDocsHandleFiles = function (fileList) {
            const previewList = document.getElementById('lf-docs-preview-list');
            const saveActions = document.getElementById('lf-docs-save-actions');

            if (!fileList) return;

            // Convert FileList to Array and add to selection
            Array.from(fileList).forEach(file => {
                window.lfDocsSelectedFiles.push(file);
            });

            // Render Preview
            if (previewList) {
                previewList.innerHTML = '';
                if (window.lfDocsSelectedFiles.length > 0) {
                    previewList.classList.remove('hidden');
                    if (saveActions) {
                        saveActions.classList.remove('hidden');
                        saveActions.classList.add('flex');
                    }

                    window.lfDocsSelectedFiles.forEach((file, index) => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700';
                        li.innerHTML = `
                            <div class="flex items-center gap-2 overflow-hidden flex-1">
                                <i class="icon-file text-blue-500"></i>
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">${file.name}</span>
                                <span class="text-xs text-gray-500">(${window.lfDocsFormatSize(file.size)})</span>
                            </div>
                            <button type="button" onclick="window.lfDocsRemoveFile(${index})" class="text-red-500 hover:text-red-700 p-1">
                                <i class="icon-delete"></i>
                            </button>
                        `;
                        previewList.appendChild(li);
                    });
                } else {
                    previewList.classList.add('hidden');
                    if (saveActions) {
                        saveActions.classList.add('hidden');
                        saveActions.classList.remove('flex');
                    }
                }
            }
        };

        window.lfDocsRemoveFile = function (index) {
            window.lfDocsSelectedFiles.splice(index, 1);
            // Re-render
            // Hacky: just call handleFiles with empty list to trigger re-render logic 
            // passing current list won't work recursively well, better to extract render logic
            // For now, simpler:
            window.lfDocsHandleFiles([]);
        };

        window.lfDocsFormatSize = function (bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        // AJAX Upload Function
        window.lfDocsUploadFiles = function () {
            if (window.lfDocsSelectedFiles.length === 0) return;

            const formData = new FormData();
            const token = window.lfDocsGetCsrfToken();

            if (!token) {
                alert('Erro: Token de segurança não encontrado. Recarregue a página.');
                return;
            }

            // Append files
            window.lfDocsSelectedFiles.forEach(file => {
                formData.append('anexos[]', file);
            });

            // Add Process ID (assuming available in context or view)
            formData.append('processo_id', '{{ $processo->id }}');

            // We use the STORE route from DocumentController
            const url = "{{ route('admin.processos.store_documents') }}";

            const btn = document.querySelector('#lf-docs-save-actions button');
            const originalText = document.getElementById('lf-docs-save-text');

            if (btn) btn.disabled = true;
            if (originalText) originalText.innerText = 'Enviando...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Success
                    window.location.reload(); // Reload to show new files safely
                } else {
                    // Error
                    let msg = 'Erro ao enviar.';
                    try {
                        const resp = JSON.parse(xhr.responseText);
                        if (resp.message) msg = resp.message;
                    } catch (e) { }
                    alert(msg);
                    if (btn) btn.disabled = false;
                    if (originalText) originalText.innerText = 'Salvar Novos Arquivos';
                }
            };

            xhr.onerror = function () {
                alert('Erro de rede.');
                if (btn) btn.disabled = false;
                if (originalText) originalText.innerText = 'Salvar Novos Arquivos';
            };

            xhr.send(formData);
        };

        // AJAX Delete Attachment
        window.lfDocsDeleteAttachment = function (id) {
            if (!confirm('Tem certeza que deseja remover este arquivo?')) return;

            const row = document.getElementById('anexo-row-' + id);
            if (row) {
                row.style.opacity = '0.5';
                row.style.pointerEvents = 'none';
            }

            const token = window.lfDocsGetCsrfToken();
            if (!token) {
                alert('Token CSRF inválido.');
                return;
            }

            // Construct dynamic URL safely
            // Use a placeholder ID '0' to generate the base URL, then replace it.
            const baseUrl = "{{ route('admin.processos.delete_attachment', ['id' => 0]) }}";
            const finalUrl = baseUrl.replace('/0', '/' + id);

            const xhr = new XMLHttpRequest();
            xhr.open('DELETE', finalUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    if (row) row.remove();
                } else {
                    alert('Erro ao excluir: ' + xhr.statusText);
                    if (row) {
                        row.style.opacity = '1';
                        row.style.pointerEvents = 'auto';
                    }
                }
            };

            xhr.send();
        };

        // Helpers for Checklist
        window.lfDocsImportTemplate = function () {
            const select = document.getElementById('lf-docs-template-select');
            const templateId = select.value;
            if (!templateId) {
                alert('Selecione um modelo.');
                return;
            }

            // Dynamically create a form to submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('lawfirm.documents.import_v2', $processo->id) }}";

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = window.lfDocsGetCsrfToken();
            form.appendChild(csrf);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'template_id';
            input.value = templateId;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        };

        window.lfDocsUpdateStatus = function (id, status) {
            const token = window.lfDocsGetCsrfToken();
            const baseUrl = "{{ route('lawfirm.documents.update', ['id' => 0]) }}";
            const finalUrl = baseUrl.replace('/0', '/' + id);

            const xhr = new XMLHttpRequest();
            xhr.open('PUT', finalUrl, true);

            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    console.log('Status atualizado');
                    window.location.reload();
                } else {
                    alert('Erro ao atualizar status');
                }
            };

            xhr.send(JSON.stringify({ status: status }));
        };

        window.lfDocsDeleteChecklistItem = function (id) {
            if (!confirm('Remover item?')) return;

            const token = window.lfDocsGetCsrfToken();
            const baseUrl = "{{ route('lawfirm.documents.delete', ['id' => 0]) }}";
            const finalUrl = baseUrl.replace('/0', '/' + id);

            const xhr = new XMLHttpRequest();
            xhr.open('DELETE', finalUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    window.location.reload();
                } else {
                    alert('Erro ao remover: ' + xhr.statusText);
                }
            };

            xhr.send();
        }

    </script>
@endpush