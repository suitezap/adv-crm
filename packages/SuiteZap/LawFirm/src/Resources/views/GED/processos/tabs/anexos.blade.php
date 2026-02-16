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

            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                <span class="text-xs text-gray-500">
                    {{ $processo->anexos->count() }} arquivo(s)
                </span>
                @if(!$readOnly)
                    <button type="button"
                        onclick="document.getElementById('anexos-content').style.display='block'; document.querySelector('input[name=&quot;anexos[]&quot;]').click();"
                        class="flex items-center gap-1 rounded border border-blue-400 bg-white px-3 py-1.5 text-blue-600 hover:bg-blue-50 transition-colors text-sm font-medium">
                        <span class="icon-upload text-lg"></span>
                        <span>Enviar Arquivos</span>
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

                                        <!-- Excluir (AJAX) -->
                                        @if(!$readOnly)
                                            <button type="button" class="text-red-500 hover:text-red-700 cursor-pointer"
                                                title="Excluir"
                                                onclick="deleteAnexo('{{ route('admin.lawfirm.ged.destroy', $anexo->id) }}')">
                                                <span class="icon-delete text-xl"></span>
                                            </button>
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
                <form action="{{ route('admin.lawfirm.ged.store') }}" method="POST" enctype="multipart/form-data"
                    id="form-upload-ged">
                    @csrf
                    <input type="hidden" name="processo_id" value="{{ $processo->id }}">

                    <div class="mt-4">
                        <p class="mb-2 font-medium text-gray-700 dark:text-gray-300">Adicionar Novos Arquivos</p>

                        <div
                            class="dropzone-container relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800">

                            <div class="flex flex-col items-center justify-center gap-2 text-center">
                                <span class="icon-upload text-4xl text-gray-400"></span>
                                <p class="text-sm text-gray-500">
                                    <span class="font-bold text-blue-600 hover:underline">Clique para selecionar</span> ou
                                    arraste
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

                        <!-- Submit Button (Forced Position) -->
                        <div class="mt-4">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-2 transition-colors">
                                <span class="icon-save"></span>
                                <span>Fazer Upload (Salvar)</span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
@endif

<script>
    // Utility functions for GED (file selection display, delete, toggle)

    if (typeof handleFileSelect !== 'function') {
        window.handleFileSelect = function (input) {
            const fileListId = input.getAttribute('data-list-target');
            const fileList = document.getElementById(fileListId);
            const files = input.files;

            fileList.innerHTML = '';

            if (files.length === 0) {
                return;
            }

            Array.from(files).forEach(file => {
                const li = document.createElement('li');
                li.className = "flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-2 rounded border border-gray-100";

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

            console.log('deleteAnexo: excluindo', url);

            var xhr = new XMLHttpRequest();
            xhr.open('DELETE', url, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                console.log('deleteAnexo: resposta status=' + xhr.status, xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300) {
                    window.location.reload();
                } else {
                    console.error('Delete failed:', xhr.responseText);
                    alert('Erro ao excluir anexo: HTTP ' + xhr.status);
                }
            };

            xhr.onerror = function () {
                console.error('deleteAnexo: network error');
                alert('Erro ao conectar com o servidor.');
            };

            xhr.send();
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

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });

            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files && files.length > 0 && input) {
                    input.files = files;
                    const event = new Event('change', { bubbles: true });
                    input.dispatchEvent(event);
                }
            }, false);
        });
    });

    console.log('GED scripts loaded: handleFileSelect, deleteAnexo, toggleSection available');
</script>