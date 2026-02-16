@php
    $readOnly = $readOnly ?? false;
@endphp
<div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between mb-4 mt-6">
        <div class="flex items-center gap-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white"> Gestão de Prazos </p>
        </div>
        @if(!$readOnly)
            <div class="flex gap-2">
                <button type="button" class="primary-button btn btn-primary" onclick="adicionarPrazo()">
                    <span class="icon-plus text-lg inline-block align-middle mr-1"></span> Novo Prazo
                </button>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full text-left text-sm text-gray-500 dark:text-gray-400" id="tabela-prazos">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 required" style="width:40%">Título</th>
                    <th scope="col" class="px-6 py-3 w-[160px] required">Vencimento</th>
                    <th scope="col" class="px-6 py-3 w-[150px] required">Status</th>
                    <th scope="col" class="px-6 py-3" style="width:60%">Descrição</th>
                    <th scope="col" class="px-6 py-3 w-[50px] text-center"></th>
                </tr>
            </thead>
            <tbody id="container-prazos">
                <!-- Loop para exibir prazos existentes -->
                @foreach($processo->prazos as $index => $prazo)
                    @if($readOnly)
                        <!-- READ ONLY MODE -->
                        <tr class="{{ $prazo->row_class }}">
                            <td class="px-6 py-4 {{ $prazo->text_class }}" style="width:40%">{{ $prazo->titulo }}
                            </td>
                            <td class="px-6 py-4 {{ $prazo->text_class }}">
                                {{ $prazo->data_vencimento ? $prazo->data_vencimento->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 {{ $prazo->text_class }} capitalize">{{ $prazo->status }}</td>
                            <td class="px-6 py-4 {{ $prazo->text_class }}" style="width:60%">{{ $prazo->descricao }}</td>
                            <td class="px-6 py-4"></td> <!-- Empty Action Column -->
                        </tr>
                    @else
                        <!-- EDIT MODE -->
                        <tr class="{{ $prazo->row_class }}" id="prazo-row-{{ $index }}">
                            <input type="hidden" name="prazos[{{ $index }}][id]" value="{{ $prazo->id }}">

                            <td class="px-1 py-1" style="width:40%">
                                <input type="text" name="prazos[{{ $index }}][titulo]"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm font-normal transition-all hover:border-gray-400 focus:border-blue-600 control bg-transparent"
                                    required value="{{ $prazo->titulo }}">
                            </td>
                            <td class="px-1 py-1">
                                <input type="date" name="prazos[{{ $index }}][data_vencimento]"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm font-normal transition-all hover:border-gray-400 focus:border-blue-600 control bg-transparent"
                                    required
                                    value="{{ $prazo->data_vencimento ? $prazo->data_vencimento->format('Y-m-d') : '' }}">
                            </td>
                            <td class="px-1 py-1">
                                <select name="prazos[{{ $index }}][status]"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm font-normal text-gray-600 control bg-transparent"
                                    required>
                                    <option value="pendente" {{ strtolower($prazo->status) == 'pendente' ? 'selected' : '' }}>
                                        Pendente
                                    </option>
                                    <option value="concluido" {{ strtolower($prazo->status) == 'concluido' || strtolower($prazo->status) == 'concluído' ? 'selected' : '' }}>Concluído
                                    </option>
                                </select>
                            </td>
                            <td class="px-1 py-1" style="width:60%">
                                <input type="text" name="prazos[{{ $index }}][descricao]"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm font-normal text-gray-600 control bg-transparent"
                                    value="{{ $prazo->descricao }}">
                            </td>
                            <td class="px-1 py-1 text-center">
                                <!-- Checkbox de Delete (Soft Delete via Update) -->
                                <div class="flex items-center justify-center">
                                    <label class="cursor-pointer text-red-600" title="Remover">
                                        <input type="checkbox" name="prazos[{{ $index }}][should_delete]" value="1"
                                            style="display:none;"
                                            onchange="this.closest('tr').style.opacity = this.checked ? '0.3' : '1'">
                                        <span class="icon trash-icon"></span>
                                    </label>
                                </div>
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
                                                    <td class="px-1 py-1" style="width:40%">
                                                        <input type="text" name="prazos[${prazoIndex}][titulo]" class="w-full rounded-md border px-3 py-2.5 text-sm control" required placeholder="Título">
                                                    </td>
                                                    <td class="px-1 py-1">
                                                        <input type="date" name="prazos[${prazoIndex}][data_vencimento]" class="w-full rounded-md border px-3 py-2.5 text-sm control" required>
                                                    </td>
                                                    <td class="px-1 py-1">
                                                        <select name="prazos[${prazoIndex}][status]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm control">
                                                            <option value="pendente">Pendente</option>
                                                            <option value="concluido">Concluído</option>
                                                        </select>
                                                    </td>
                                                    <td class="px-1 py-1" style="width:60%">
                                                        <input type="text" name="prazos[${prazoIndex}][descricao]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm control">
                                                    </td>
                                                    <td class="px-1 py-1 text-center">
                                                        <button type="button" class="text-red-600" onclick="document.getElementById('${rowId}').remove()">
                                                            <span class="icon trash-icon"></span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            `;

            container.insertAdjacentHTML('beforeend', html);
            prazoIndex++;
        }
    </script>
@endpush