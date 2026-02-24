@php
    $readOnly = $readOnly ?? false;
    $startClosed = $startClosed ?? false;
@endphp

<div id="notas-component-container"
    class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900" v-pre>

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2 cursor-pointer" onclick="window.toggleNotas()">
            <i id="notas-toggle-icon"
                class="{{ $startClosed ? 'icon-arrow-down' : 'icon-arrow-up' }} text-lg text-gray-500"></i>
            <p class="text-lg font-bold text-gray-800 dark:text-white select-none">Notas</p>
        </div>

        @if(!$readOnly)
            <div id="notas-action-buttons" class="flex gap-2">
                <button type="submit" form="processo-form"
                    class="flex items-center justify-center gap-2 rounded border border-emerald-600 bg-white px-3 py-1.5 text-emerald-600 hover:bg-emerald-50 transition-colors">
                    <span class="icon-save text-lg"></span>
                    <span class="text-sm font-medium">Salvar</span>
                </button>
                <button type="button" class="primary-button btn btn-primary" onclick="window.adicionarNota()">
                    <span class="icon-plus text-lg inline-block align-middle mr-1"></span> Nova Nota
                </button>
            </div>
        @endif
    </div>

    <!-- BODY -->
    <div id="notas-body-container" class="mt-4" style="display: block;">
        <div id="container-notas" class="flex flex-col gap-4">
            <!-- Loop para exibição de notas existentes -->
            @foreach($processo->notas as $index => $nota)
                <div class="relative flex flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/50 nota-row shadow-sm"
                    id="nota-row-{{ $index }}">
                    <!-- Metadata Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs text-gray-400 font-medium">
                            <span class="icon-calendar text-sm"></span>
                            <span>{{ $nota->created_at->format('d/m/Y H:i') }}</span>
                            <span>&bull;</span>
                            <span class="icon-user text-sm"></span>
                            <span>{{ $nota->user->name ?? 'Sistema' }}</span>
                        </div>

                        @if(!$readOnly)
                            <button type="button" class="text-red-500 hover:text-red-700 transition-colors"
                                onclick="window.removerNota('nota-row-{{ $index }}')" title="Excluir Nota" v-pre>
                                <span class="icon-delete text-lg"></span>
                            </button>
                        @endif
                    </div>

                    <!-- Content Body -->
                    <div>
                        @if($readOnly)
                            <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $nota->nota }}</div>
                        @else
                            <input type="hidden" name="notas[{{ $index }}][id]" value="{{ $nota->id }}">
                            <textarea name="notas[{{ $index }}][nota]" rows="3" required
                                class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm transition-all focus:border-blue-600 focus:ring-1 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Digite a nota aqui...">{{ $nota->nota }}</textarea>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($processo->notas->isEmpty() && $readOnly)
                <div class="text-sm text-gray-500 italic py-2">
                    Nenhuma nota registrada neste processo.
                </div>
            @endif
        </div>
    </div>
</div>

@if(!$readOnly)
    <script>
        // Make variables global to bypass Vue/Alpine isolation
        window.notaIndex = {{ isset($processo) ? $processo->notas->count() : 0 }};

        window.adicionarNota = function () {
            const containerNotas = document.getElementById('container-notas');
            if (!containerNotas) return;

            // Pre-format current date/time for the UI display
            const now = new Date();
            const dateStr = now.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const timestamp = `${dateStr} ${timeStr}`;
            // Auth user name using standard variable or fallback
            const userName = "{{ auth()->guard('user')->user()->name ?? 'Usuário Atual' }}";

            const template = `
                                            <div class="relative flex flex-col gap-2 rounded-lg border border-gray-100 bg-blue-50/30 p-4 dark:border-blue-900/20 dark:bg-gray-800/50 nota-row shadow-sm" id="nota-row-novo-${window.notaIndex}">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2 text-xs text-blue-400 font-medium">
                                                        <span class="icon-calendar text-sm"></span>
                                                        <span>${timestamp} (Não salva)</span>
                                                        <span>&bull;</span>
                                                        <span class="icon-user text-sm"></span>
                                                        <span>${userName}</span>
                                                    </div>
                                                    <button type="button" class="text-red-500 hover:text-red-700 transition-colors" onclick="window.removerNota('nota-row-novo-${window.notaIndex}')" title="Excluir Nota" v-pre>
                                                        <span class="icon-delete text-lg"></span>
                                                    </button>
                                                </div>
                                                <div>
                                                    <!-- No hidden ID field because this is a new note -->
                                                    <textarea name="notas[novo_${window.notaIndex}][nota]" rows="3" required
                                                        class="w-full rounded-md border border-blue-200 bg-white px-3 py-2 text-sm transition-all focus:border-blue-600 focus:ring-1 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                        placeholder="Digite a nota aqui..."></textarea>
                                                </div>
                                            </div>
                                        `;

            containerNotas.insertAdjacentHTML('afterbegin', template);
            window.notaIndex++;
        }

        window.removerNota = function (rowId) {
            if (confirm('Tem certeza que deseja remover esta nota?')) {
                const row = document.getElementById(rowId);
                if (row) {
                    row.remove();
                }
            }
        }
    </script>
@endif

<script>
    window.notasExpanded = {{ $startClosed ? 'false' : 'true' }};

    window.toggleNotas = function () {
        window.notasExpanded = !window.notasExpanded;
        const body = document.getElementById('notas-body-container');
        const icon = document.getElementById('notas-toggle-icon');
        const buttons = document.getElementById('notas-action-buttons');

        if (window.notasExpanded) {
            if (body) body.style.display = 'block';
            if (buttons) buttons.style.display = 'flex';
            if (icon) {
                icon.classList.remove('icon-arrow-down');
                icon.classList.add('icon-arrow-up');
            }
        } else {
            if (body) body.style.display = 'none';
            if (buttons) buttons.style.display = 'none';
            if (icon) {
                icon.classList.remove('icon-arrow-up');
                icon.classList.add('icon-arrow-down');
            }
        }
    }

    // Setup initial start state (if closed)
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.notasExpanded) {
            window.notasExpanded = true; // force flip to false on first toggle
            window.toggleNotas();
        }
    });
</script>