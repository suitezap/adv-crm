<!-- MÓDULO DE DOCUMENTOS (Padronizado) -->
@php
    $startClosed = $startClosed ?? false;
    $readOnly = $readOnly ?? false;
@endphp

@if(isset($processo) && $processo->exists)
    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">

        <!-- CABEÇALHO DA SEÇÃO -->
        <div class="flex items-center justify-between cursor-pointer"
            onclick="toggleSection('anexos-content', 'anexos-icon')">
            <div class="flex items-center gap-2">
                <p class="text-lg font-bold text-gray-800 dark:text-white">
                    Documentos e Anexos
                </p>
                <!-- Icone Toggle -->
                <i id="anexos-icon" class="{{ $startClosed ? 'icon-arrow-down' : 'icon-arrow-up' }} text-gray-500"></i>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">
                    {{ $processo->anexos->count() }} arquivo(s)
                </span>
                @if(!$readOnly)
                    <button type="submit"
                        class="ml-auto flex items-center justify-center gap-2 rounded border border-gray-400 bg-white px-3 py-1.5 text-gray-600 hover:bg-gray-100 transition-colors"
                        title="Salvar Anexos" @click.stop>
                        <span class="icon-save text-lg"></span>
                        <span class="text-sm font-medium">Salvar</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- CONTEUDO (Collapsible) -->
        <div id="anexos-content" style="display: {{ $startClosed ? 'none' : 'block' }};">

            <!-- TABELA DE ARQUIVOS -->
            <div class="overflow-x-auto rounded border border-gray-100 mb-4">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-2 text-center font-medium">Tipo</th>
                            <th class="px-4 py-2 text-left font-medium w-full">Nome do Arquivo</th>
                            <th class="px-4 py-2 text-right font-medium whitespace-nowrap">Tamanho</th>
                            <th class="px-4 py-2 text-center font-medium">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($processo->anexos as $anexo)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Ícone do Tipo + Extensão -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="{{ $anexo->icon }} text-2xl text-gray-500"></span>
                                        <span class="text-[10px] font-bold text-gray-400 mt-1">{{ $anexo->extension }}</span>
                                    </div>
                                </td>

                                <!-- Nome com Link -->
                                <td class="px-4 py-3">
                                    <a href="{{ $anexo->url }}" target="_blank"
                                        class="font-medium text-blue-600 hover:underline hover:text-blue-800 flex items-center gap-2">
                                        {{ $anexo->nome_original }}
                                    </a>
                                </td>

                                <!-- Tamanho -->
                                <td class="px-4 py-3 text-right text-gray-500 whitespace-nowrap">
                                    {{ round($anexo->tamanho / 1024, 2) }} KB
                                </td>

                                <!-- Ações (Download / Excluir) -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-4">
                                        <!-- Download -->
                                        <a href="{{ $anexo->url }}" target="_blank" title="Baixar"
                                            class="text-gray-500 hover:text-blue-600 cursor-pointer">
                                            <span class="icon-download text-xl"></span>
                                        </a>

                                        <!-- Excluir (Apenas se NÃO for modo leitura) -->
                                        @if(!$readOnly)
                                            <form action="{{ route('admin.lawfirm.ged.destroy', $anexo->id) }}" method="POST"
                                                style="display:inline;"
                                                onsubmit="return confirm('Tem certeza que deseja excluir este anexo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 cursor-pointer"
                                                    title="Excluir">
                                                    <span class="icon-delete text-xl"></span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    Nenhum anexo encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- UPLOAD SECTION (Apenas se NÃO for modo leitura) -->
            @if(!$readOnly)
                <div class="mt-4">
                    <p class="mb-2 font-medium text-gray-700 dark:text-gray-300">Adicionar Novos Arquivos</p>

                    <div
                        class="dropzone-container relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800">

                        <div class="flex flex-col items-center justify-center gap-2 text-center">
                            <span class="icon-upload text-4xl text-gray-400"></span>
                            <p class="text-sm text-gray-500">
                                <span class="font-bold text-blue-600 hover:underline">Clique para selecionar</span> ou arraste
                                arquivos aqui
                            </p>
                            <p class="text-xs text-gray-400">PDF, Imagens, Docx (Máx. 20MB)</p>
                        </div>

                        <!-- Input File -->
                        <input type="file" name="anexos[]" multiple
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer file-upload-input"
                            data-list-target="file-list-create" onchange="handleFileSelect(this)" />
                    </div>

                    <!-- Lista de arquivos selecionados -->
                    <ul id="file-list-create" class="mt-3 space-y-2"></ul>

                    <!-- Upload Button (AJAX) - Only in Edit Mode -->
                    @if(isset($processo) && $processo->exists)
                        <div class="mt-4 flex justify-end">
                            <button type="button" id="btn-upload-ged" onclick="uploadDocumentsGED()"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="icon-upload"></span>
                                <span>Enviar Arquivos Agora</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
@endif

@push('scripts')
    <script>
        // GED AJAX Upload Function
        window.uploadDocumentsGED = function () {
            const input = document.querySelector('input[name="anexos[]"]');
            const btn = document.getElementById('btn-upload-ged');

            if (!input || input.files.length === 0) {
                alert('Por favor, selecione pelo menos um arquivo para enviar.');
                return;
            }

            if (!confirm('Deseja enviar os arquivos selecionados agora?')) return;

            // UI Loading State
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="icon-settings animate-spin"></span> Enviando...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('processo_id', '{{ $processo->id ?? "" }}');

            Array.from(input.files).forEach(file => {
                formData.append('anexos[]', file);
            });

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch("{{ route('admin.lawfirm.ged.store') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Success
                        alert('Documentos enviados com sucesso!');
                        window.location.reload();
                    } else {
                        // Error from server
                        throw new Error(data.message || 'Erro desconhecido');
                    }
                })
                .catch(error => {
                    console.error('GED Error:', error);
                    alert('Erro ao enviar documentos: ' + error.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        if (typeof handleFileSelect !== 'function') {
            window.handleFileSelect = function (input) {
                const fileListId = input.getAttribute('data-list-target');
                const fileList = document.getElementById(fileListId);
                const files = input.files;

                fileList.innerHTML = ''; // Limpar lista atual

                if (files.length === 0) {
                    return;
                }

                Array.from(files).forEach(file => {
                    const li = document.createElement('li');
                    li.className = "flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-2 rounded border border-gray-100";

                    // Icone simples baseado na extensão (simplificado)
                    let iconClass = 'icon-file';
                    const fileType = file.type || '';
                    if (fileType.includes('image')) iconClass = 'icon-image';
                    if (fileType.includes('pdf')) iconClass = 'icon-file';

                    li.innerHTML = `
                            <span class="${iconClass} text-gray-400"></span>
                            <span class="font-medium truncate flex-1">${file.name}</span>
                            <span class="text-xs text-gray-400 whitespace-nowrap">${formatBytes(file.size)}</span>
                        `;
                    fileList.appendChild(li);
                });
            }
        }

        if (typeof formatBytes !== 'function') {
            window.formatBytes = function (bytes, decimals = 2) {
                if (!+bytes) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
            }
        }

        if (typeof deleteAnexo !== 'function') {
            window.deleteAnexo = function (url) {
                if (!confirm('Tem certeza que deseja excluir este anexo?')) return;

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ _method: 'DELETE' })
                })
                    .then(response => {
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            response.text().then(text => {
                                console.error('Delete failed:', text);
                                alert('Erro ao excluir anexo: ' + response.statusText);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao conectar com o servidor.');
                    });
            }
        }

        // Toggle Section Logic
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

        document.addEventListener('DOMContentLoaded', () => {
            const dropzones = document.querySelectorAll('.dropzone-container');

            dropzones.forEach(dropzone => {
                const input = dropzone.querySelector('input[type="file"]');

                // 1. Ensure Form has enctype
                if (input) {
                    const form = input.closest('form');
                    if (form && form.getAttribute('enctype') !== 'multipart/form-data') {
                        form.setAttribute('enctype', 'multipart/form-data');
                    }
                }

                const highlight = () => {
                    dropzone.classList.remove('border-gray-300');
                    dropzone.classList.add('border-blue-500', 'bg-blue-50');
                };

                const unhighlight = () => {
                    dropzone.classList.remove('border-blue-500', 'bg-blue-50');
                    dropzone.classList.add('border-gray-300');
                };

                // Remove default behavior
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Visual Feedback
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, unhighlight, false);
                });

                // 2. CRITICAL: Handle Drop to assign files to Input
                dropzone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;

                    if (files && files.length > 0 && input) {
                        // Assign files to input
                        input.files = files;

                        // Trigger change event manually
                        const event = new Event('change', { bubbles: true });
                        input.dispatchEvent(event);
                    }
                }, false);
            });
        });
    </script>
@endpush