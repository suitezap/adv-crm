@php
    $readOnly = $readOnly ?? false;
    $anexos = $processo->anexos; // Relacionamento correto do Model Processo
@endphp

<div
    class="mt-4 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <!-- Header com Toggle -->
    <div class="flex items-center justify-between cursor-pointer"
        onclick="toggleSection('documentos-content', 'documentos-icon')">
        <div class="flex items-center gap-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                Documentos e Anexos
                <i id="documentos-icon" class="icon-arrow-down text-gray-500 ml-2"></i>
            </p>
            <span class="text-xs text-gray-500"> {{ $anexos->count() }} arquivo(s) </span>
        </div>

        <div class="flex gap-2" onclick="event.stopPropagation()">
            @if(!$readOnly)
                <button type="submit" form="processo-form"
                    class="flex items-center justify-center gap-2 rounded border border-gray-400 bg-white px-3 py-1.5 text-gray-600 hover:bg-gray-100 transition-colors"
                    title="Salvar Anexos">
                    <span class="icon-save text-lg"></span>
                    <span class="text-sm font-medium">Salvar</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Conteúdo Colapsável (Default: Fechado 'display: none') -->
    <div id="documentos-content" style="display: none;">
        <div class="overflow-x-auto rounded border border-gray-100">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-center font-medium">Tipo</th>
                        <th class="px-4 py-2 text-left font-medium w-full">Nome do Arquivo</th>
                        <th class="px-4 py-2 text-right font-medium whitespace-nowrap">Tamanho</th>
                        @if(!$readOnly)
                            <th class="px-4 py-2 text-center font-medium">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($anexos as $anexo)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="{{ $anexo->icon }} text-2xl text-gray-500"></span>
                                    <span class="text-[10px] font-bold text-gray-400 mt-1">{{ $anexo->extension }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ $anexo->url }}" target="_blank"
                                    class="font-medium text-blue-600 hover:underline hover:text-blue-800 flex items-center gap-2">
                                    {{ $anexo->nome_original }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-500 whitespace-nowrap">
                                {{ number_format($anexo->tamanho / 1024, 2, ',', '.') }} KB
                            </td>
                            @if(!$readOnly)
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-4">
                                        <a href="{{ $anexo->url }}" target="_blank" title="Baixar"
                                            class="text-gray-500 hover:text-blue-600 cursor-pointer">
                                            <span class="icon-download text-xl"></span>
                                        </a>

                                        <form action="{{ route('admin.processos.delete_attachment', $anexo->id) }}"
                                            method="POST" id="delete-anexo-{{ $anexo->id }}" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        <a href="javascript:void(0);"
                                            onclick="if(confirm('Tem certeza que deseja remover este arquivo?')) { document.getElementById('delete-anexo-{{ $anexo->id }}').submit(); }"
                                            class="text-red-500 hover:text-red-700 cursor-pointer" title="Excluir">
                                            <span class="icon-delete text-xl display-block"></span>
                                        </a>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $readOnly ? 3 : 4 }}" class="px-4 py-6 text-center text-gray-400 italic"> Nenhum
                                documento anexado. </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!$readOnly)
            <div class="mt-4">
                <label class="mb-2 block text-sm font-medium text-gray-700">Adicionar Novos Arquivos</label>

                <x-admin::form.control-group.error control-name="anexos" />
                @if($errors->has('anexos.*'))
                    <div class="mb-2 text-sm text-red-600">
                        @foreach($errors->get('anexos.*') as $message)
                            <p>{{ $message[0] }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <div class="relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dropzone-container"
                        id="lf-dropzone">

                        <div class="text-center pointer-events-none">
                            <span class="icon-file text-4xl text-gray-400 mb-2 block"></span>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-bold text-blue-600 hover:text-blue-500">Clique para upload</span> ou
                                arraste e
                                solte
                            </p>
                            <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG, DOCX, TXT, CSV (Max: 20MB)</p>
                        </div>

                        <input type="file" name="anexos[]" multiple id="lf-file-input"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer file-upload-input">
                    </div>

                    <ul id="lf-file-list" class="mt-3 space-y-2"></ul>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- Checklist de Documentos --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        @php
            $checklistDocs = $processo->documents ?? collect([]);
            $checklistTemplates = \SuiteZap\LawFirm\Models\ChecklistTemplate::orderBy('name')->get();
        @endphp

        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-4 text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <span class="icon-menu text-xl text-purple-600"></span>
                Checklist de Documentos
                <span class="text-xs font-normal text-gray-500 ml-2">{{ $checklistDocs->count() }} item(ns)</span>
            </p>

            {{-- Importar Kit --}}
            @if(!$readOnly)
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <form action="{{ route('lawfirm.documents.import_v2', $processo->id) }}" method="POST"
                        class="flex flex-wrap gap-3 items-center">
                        @csrf
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Importar Kit:</label>
                        <select name="template_id"
                            class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm"
                            style="min-width: 280px;" required>
                            <option value="">Selecione um Modelo...</option>
                            @foreach($checklistTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="primary-button">
                            Importar Kit
                        </button>
                    </form>
                </div>
            @endif

            {{-- Tabela do Checklist --}}
            <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="min-w-full w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead
                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b dark:border-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-[120px]">Status</th>
                            <th scope="col" class="px-6 py-3">Documento Necessário</th>
                            <th scope="col" class="px-6 py-3">Observações</th>
                            @if(!$readOnly)
                                <th scope="col" class="px-6 py-3 w-[200px] text-center">Ações</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checklistDocs as $doc)
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'received' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                ];
                                $statusLabels = [
                                    'pending' => 'Pendente',
                                    'received' => 'Recebido',
                                    'approved' => 'Aprovado',
                                    'rejected' => 'Rejeitado',
                                ];
                                $badgeClass = $statusColors[$doc->status] ?? $statusColors['pending'];
                                $statusLabel = $statusLabels[$doc->status] ?? ucfirst($doc->status);
                            @endphp
                            <tr
                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $doc->name }}
                                    @if($doc->file_path)
                                        <span class="ml-2 text-green-500" title="Arquivo anexado">
                                            <span class="icon-check text-xs"></span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $doc->notes ?? '-' }}</td>
                                @if(!$readOnly)
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-3">
                                            {{-- Status Change Form --}}
                                            <form action="{{ route('lawfirm.documents.update', $doc->id) }}" method="POST"
                                                class="inline-flex">
                                                @csrf
                                                @method('PUT')
                                                <select name="status" onchange="this.form.submit()"
                                                    class="rounded border border-gray-300 px-2 py-1 text-xs bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white cursor-pointer">
                                                    <option value="pending" {{ $doc->status === 'pending' ? 'selected' : '' }}>
                                                        Pendente</option>
                                                    <option value="received" {{ $doc->status === 'received' ? 'selected' : '' }}>
                                                        Recebido</option>
                                                    <option value="approved" {{ $doc->status === 'approved' ? 'selected' : '' }}>
                                                        Aprovado</option>
                                                    <option value="rejected" {{ $doc->status === 'rejected' ? 'selected' : '' }}>
                                                        Rejeitado</option>
                                                </select>
                                            </form>

                                            {{-- Delete Form --}}
                                            <form action="{{ route('lawfirm.documents.delete', $doc->id) }}" method="POST"
                                                onsubmit="return confirm('Tem certeza que deseja remover este item do checklist?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"
                                                    title="Excluir">
                                                    <span class="icon-delete text-lg"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td colspan="{{ $readOnly ? 3 : 4 }}"
                                    class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Nenhum documento solicitado. Use a importação acima para adicionar um kit.
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
        // Toggle Section Logic (Safe definition)
        if (typeof toggleSection !== 'function') {
            window.toggleSection = function (contentId, iconId) {
                const content = document.getElementById(contentId);
                const icon = document.getElementById(iconId);

                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    if (icon) {
                        icon.classList.remove('icon-arrow-down');
                        icon.classList.add('icon-arrow-up');
                    }
                } else {
                    content.style.display = 'none';
                    if (icon) {
                        icon.classList.remove('icon-arrow-up');
                        icon.classList.add('icon-arrow-down');
                    }
                }
            }
        }

        (function () {
            var MAX_RETRIES = 50; // 50 * 100ms = 5 seconds max wait
            var retryCount = 0;

            function initFileUpload() {
                var fileInput = document.getElementById('lf-file-input');
                var fileList = document.getElementById('lf-file-list');
                var dropzone = document.getElementById('lf-dropzone');

                if (!fileInput || !fileList) {
                    retryCount++;
                    if (retryCount < MAX_RETRIES) {
                        setTimeout(initFileUpload, 100);
                        return;
                    }
                    console.warn('[LawFirm] File upload elements not found after ' + MAX_RETRIES + ' retries.');
                    return;
                }

                console.log('[LawFirm] File upload initialized after ' + retryCount + ' retries.');

                // State: array of { id, name, size, file }
                var files = [];
                var idCounter = 0;
                var syncing = false;

                function formatSize(bytes) {
                    if (bytes === 0) return '0 B';
                    var k = 1024;
                    var sizes = ['B', 'KB', 'MB', 'GB'];
                    var i = Math.floor(Math.log(bytes) / Math.log(k));
                    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
                }

                function addFiles(newFileList) {
                    var changed = false;
                    for (var i = 0; i < newFileList.length; i++) {
                        var f = newFileList[i];
                        var dup = false;
                        for (var j = 0; j < files.length; j++) {
                            if (files[j].name === f.name && files[j].size === f.size) {
                                dup = true;
                                break;
                            }
                        }
                        if (!dup) {
                            files.push({
                                id: 'lf_file_' + (++idCounter),
                                name: f.name,
                                size: f.size,
                                file: f
                            });
                            changed = true;
                        }
                    }
                    if (changed) {
                        renderList();
                        syncInput();
                    }
                }

                function removeFile(id) {
                    files = files.filter(function (f) { return f.id !== id; });
                    renderList();
                    syncInput();
                }

                function syncInput() {
                    syncing = true;
                    var dt = new DataTransfer();
                    for (var i = 0; i < files.length; i++) {
                        dt.items.add(files[i].file);
                    }
                    fileInput.files = dt.files;
                    setTimeout(function () { syncing = false; }, 50);
                }

                function renderList() {
                    fileList.innerHTML = '';
                    for (var i = 0; i < files.length; i++) {
                        var f = files[i];
                        var li = document.createElement('li');
                        li.className = 'flex items-center gap-2 text-sm text-gray-700 bg-gray-50 p-2 rounded justify-between border border-gray-200';

                        var infoDiv = document.createElement('div');
                        infoDiv.className = 'flex items-center gap-2 overflow-hidden';

                        var icon = document.createElement('span');
                        icon.className = 'icon-file text-blue-500 flex-shrink-0';
                        infoDiv.appendChild(icon);

                        var nameSpan = document.createElement('span');
                        nameSpan.className = 'truncate block max-w-[200px]';
                        nameSpan.title = f.name;
                        nameSpan.textContent = f.name;
                        infoDiv.appendChild(nameSpan);

                        var sizeSpan = document.createElement('span');
                        sizeSpan.className = 'text-xs text-gray-400 whitespace-nowrap';
                        sizeSpan.textContent = '(' + formatSize(f.size) + ')';
                        infoDiv.appendChild(sizeSpan);

                        li.appendChild(infoDiv);

                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'text-gray-400 hover:text-red-500 transition-colors p-1 rounded hover:bg-red-50';
                        btn.setAttribute('data-file-id', f.id);
                        btn.innerHTML = '<span class="icon-cross text-lg"></span>';
                        btn.onclick = (function (fileId) {
                            return function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                removeFile(fileId);
                            };
                        })(f.id);
                        li.appendChild(btn);

                        fileList.appendChild(li);
                    }
                }

                // Event: file input change
                fileInput.addEventListener('change', function () {
                    if (syncing) return;
                    if (this.files.length > 0) {
                        addFiles(this.files);
                    }
                });

                // Event: drag and drop
                if (dropzone) {
                    dropzone.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('border-blue-500', 'bg-blue-50');
                        dropzone.classList.remove('border-gray-300');
                    });

                    dropzone.addEventListener('dragleave', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('border-blue-500', 'bg-blue-50');
                        dropzone.classList.add('border-gray-300');
                    });

                    dropzone.addEventListener('drop', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('border-blue-500', 'bg-blue-50');
                        dropzone.classList.add('border-gray-300');
                        if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                            addFiles(e.dataTransfer.files);
                        }
                    });
                }
            }

            // Start polling after a small delay to give Vue time to mount
            setTimeout(initFileUpload, 200);
        })();
    </script>
@endpush