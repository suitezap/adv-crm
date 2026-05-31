@php
    $readOnly = $readOnly ?? false;
@endphp
<div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white"> Gestão de Prazos e Tarefas </p>
        </div>
        @if(!$readOnly)
            <div class="flex gap-2">
                <button type="button" class="secondary-button" onclick="abrirAgenda()">
                    <span class="icon-calendar text-lg inline-block align-middle mr-1"></span> Ver Agenda
                </button>
                <button type="button" class="primary-button btn btn-primary" onclick="adicionarPrazo()">
                    <span class="icon-plus text-lg inline-block align-middle mr-1"></span> Novo Item
                </button>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full text-left text-sm text-gray-500 dark:text-gray-400" id="tabela-prazos">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 required" style="width: 32%; min-width: 250px;">Título</th>
                    <th scope="col" class="px-6 py-3 required" style="width: 100px; min-width: 100px;">Tipo</th>
                    <th scope="col" class="px-6 py-3 required" style="width: 15%; min-width: 160px; padding-left: 1.5rem;">Vencimento</th>
                    <th scope="col" class="px-6 py-3 required" style="width: 5%; min-width: 120px;">Status</th>
                    <th scope="col" class="px-6 py-3" style="width: 48%; min-width: 300px;">Descrição</th>
                    <th scope="col" class="px-6 py-3 text-center" style="width: 50px;"></th>
                </tr>
            </thead>
            <tbody id="container-prazos">
                <!-- Loop para exibir prazos existentes -->
                @foreach($processo->prazos as $index => $prazo)
                    @if($readOnly)
                        <!-- READ ONLY MODE -->
                        <tr class="{{ $prazo->row_class }}">
                            <td class="px-6 py-4 {{ $prazo->text_class }}">{{ $prazo->titulo }}
                            </td>
                            <td class="px-6 py-4">
                                @if(rtrim(strtolower($prazo->tipo ?? 'prazo')) === 'tarefa')
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-gray-50 px-2 py-1 text-xs font-medium leading-none text-gray-600 ring-1 ring-inset ring-gray-500/10">✅ Tarefa</span>
                                @else
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-blue-50 px-2 py-1 text-xs font-medium leading-none text-blue-700 ring-1 ring-inset ring-blue-700/10">⚖️ Prazo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 {{ $prazo->text_class }}">
                                {{ $prazo->data_vencimento ? $prazo->data_vencimento->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 {{ $prazo->text_class }} capitalize">{{ $prazo->status }}</td>
                            <td class="px-6 py-4 {{ $prazo->text_class }}">{{ $prazo->descricao }}</td>
                            <td class="px-6 py-4"></td> <!-- Empty Action Column -->
                        </tr>
                    @else
                        <!-- EDIT MODE -->
                        <tr class="{{ $prazo->row_class }}" id="prazo-row-{{ $index }}">
                            <input type="hidden" name="prazos[{{ $index }}][id]" value="{{ $prazo->id }}">

                            <td class="px-1 py-1">
                                <input type="text" name="prazos[{{ $index }}][titulo]"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm font-normal transition-all hover:border-gray-400 focus:border-blue-600 control bg-transparent"
                                    required value="{{ $prazo->titulo }}">
                            </td>
                            <td class="pr-1 pl-0 py-1 min-w-[200px]">
                                @php $prazoTipo = $prazo->tipo ?? 'prazo'; @endphp
                                <select name="prazos[{{ $index }}][tipo]"
                                    class="w-full rounded-md border pl-0 py-2.5 text-sm font-normal text-gray-600 control bg-transparent"
                                    style="padding-right: 24px;">
                                    <option value="prazo" {{ rtrim(strtolower($prazoTipo)) === 'prazo' ? 'selected' : '' }}>⚖️ Prazo</option>
                                    <option value="tarefa" {{ rtrim(strtolower($prazoTipo)) === 'tarefa' ? 'selected' : '' }}>✅ Tarefa</option>
                                </select>
                            </td>
                            <td class="px-1 py-1 pl-4">
                                <span class="relative inline-block w-full">
                                    <input type="text" name="prazos[{{ $index }}][data_vencimento]"
                                        class="w-full rounded-md border pl-3 py-2.5 text-sm font-normal transition-all hover:border-gray-400 focus:border-blue-600 control bg-transparent lf-flatpickr-input"
                                        style="padding-right: 29px;"
                                        required
                                        value="{{ $prazo->data_vencimento ? $prazo->data_vencimento->format('Y-m-d H:i:s') : '' }}"
                                        placeholder="Selecione data e hora..."
                                        autocomplete="off">
                                    <i class="icon-calendar pointer-events-none absolute top-1/2 -translate-y-1/2 text-2xl text-gray-400 ltr:right-2 rtl:left-2"></i>
                                </span>
                            </td>
                            <td class="px-1 py-1">
                                <select name="prazos[{{ $index }}][status]"
                                    class="w-full rounded-md border border-gray-300 pl-3 py-2.5 text-sm font-normal text-gray-600 control bg-transparent"
                                    style="padding-right: 8px;"
                                    required>
                                    <option value="pendente" {{ in_array(strtolower($prazo->status), ['pendente']) ? 'selected' : '' }}>
                                        Pendente
                                    </option>
                                    <option value="concluido" {{ in_array(strtolower(str_replace('í','i',$prazo->status)), ['concluido']) ? 'selected' : '' }}>Concluído
                                    </option>
                                </select>
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" name="prazos[{{ $index }}][descricao]"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm font-normal text-gray-600 control bg-transparent"
                                    value="{{ $prazo->descricao }}">
                            </td>
                            <td class="px-1 py-1 text-center">
                                {{-- AJAX Delete: removes row immediately without needing to save the form --}}
                                <button type="button"
                                    class="text-red-500 hover:text-red-700"
                                    title="Remover Prazo"
                                    onclick="deletarPrazo({{ $prazo->id }}, 'prazo-row-{{ $index }}')">
                                    <span class="icon-delete text-xl"></span>
                                </button>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>



@push('scripts')
    <script>
        let prazoIndex = {{ $processo->prazos->count() }};

        function adicionarPrazo() {
            const container = document.getElementById('container-prazos');
            const rowId = `prazo-row-${prazoIndex}`;

            const html = `
                                                        <tr class="border-b bg-white hover:bg-gray-50" id="${rowId}">
                                                            <td class="px-1 py-1">
                                                                <input type="text" name="prazos[${prazoIndex}][titulo]" class="w-full rounded-md border px-3 py-2.5 text-sm control" required placeholder="Título">
                                                            </td>
                                                            <td class="px-1 py-1">
                                                                <select name="prazos[${prazoIndex}][tipo]" class="w-full rounded-md border bg-white pl-2 py-2.5 text-sm control text-gray-600" style="padding-right: 24px;">
                                                                    <option value="prazo" selected>⚖️ Prazo</option>
                                                                    <option value="tarefa">✅ Tarefa</option>
                                                                </select>
                                                            </td>
                                                            <td class="px-1 py-1">
                                                                <span class="relative inline-block w-full">
                                                                    <input type="text" name="prazos[${prazoIndex}][data_vencimento]" class="w-full rounded-md border pl-3 py-2.5 text-sm control lf-flatpickr-input" style="padding-right: 29px;" required placeholder="Selecione data e hora..." id="new-prazo-date-${prazoIndex}" autocomplete="off">
                                                                    <i class="icon-calendar pointer-events-none absolute top-1/2 -translate-y-1/2 text-2xl text-gray-400 ltr:right-2 rtl:left-2"></i>
                                                                </span>
                                                            </td>
                                                            <td class="px-1 py-1">
                                                                <select name="prazos[${prazoIndex}][status]" class="w-full rounded-md border border-gray-300 bg-white pl-3 py-2.5 text-sm control" style="padding-right: 8px;">
                                                                    <option value="pendente">Pendente</option>
                                                                    <option value="concluido">Concluído</option>
                                                                </select>
                                                            </td>
                                                            <td class="px-1 py-1">
                                                                <input type="text" name="prazos[${prazoIndex}][descricao]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm control">
                                                            </td>
                                                             <td class="px-1 py-1 text-center">
                                                                <button type="button" class="text-red-500 hover:text-red-700" onclick="document.getElementById('${rowId}').remove()" title="Remover">
                                                                    <span class="icon-delete text-xl"></span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `;

            container.insertAdjacentHTML('beforeend', html);

            // Inicializa o Flatpickr na nova linha!
            setTimeout(function() {
                let novoInput = document.getElementById(`new-prazo-date-${prazoIndex}`);
                if (novoInput && typeof window.Flatpickr !== 'undefined') {
                    new window.Flatpickr(novoInput, {
                        allowInput: true,
                        altInput: true,
                        altFormat: "d-m-Y H:i",
                        dateFormat: "Y-m-d H:i:S",
                        enableTime: true,
                        time_24hr: true
                    });
                }
                prazoIndex++;
            }, 50);
        }

        // Funções para controle do Modal da Agenda
        function abrirAgenda() {
            window.open("{{ route('admin.lawfirm.agenda.index') }}?clean=true", 'AgendaJuridica', 'width=1200,height=800,left=100,top=100');
        }

        // Initialize explicitly on load for existing inputs
        window.addEventListener("load", function() {
            setTimeout(function() {
                if (typeof window.Flatpickr !== 'undefined') {
                    document.querySelectorAll('.lf-flatpickr-input').forEach(function(el) {
                        new window.Flatpickr(el, {
                            allowInput: true,
                            altInput: true,
                            altFormat: "d-m-Y H:i",
                            dateFormat: "Y-m-d H:i:S",
                            enableTime: true,
                            time_24hr: true
                        });
                    });
                }
            }, 100);
        });

        /**
         * Immediately deletes an existing saved Prazo via AJAX.
         * Triggers PrazoObserver::deleted to remove the linked Calendar Activity too.
         */
        function deletarPrazo(prazoId, rowId) {
            if (!confirm('Tem certeza que deseja apagar este prazo?')) return;

            const row = document.getElementById(rowId);
            if (row) row.style.opacity = '0.4';

            const url = `{{ url('admin/juridico/prazos') }}/${prazoId}`;
            const csrfToken = '{{ csrf_token() }}';

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(res => {
                if (!res.ok) throw new Error('Erro ao apagar prazo.');
                return res.json();
            })
            .then(data => {
                if (row) row.remove();
            })
            .catch(err => {
                if (row) row.style.opacity = '1';
                alert('Não foi possível apagar o prazo. Tente novamente.');
                console.error(err);
            });
        }
    </script>
@endpush