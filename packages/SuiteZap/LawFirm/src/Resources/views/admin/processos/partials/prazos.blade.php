@php
    $prazos = collect([]);

    if (old('prazos')) {
        foreach (old('prazos') as $oldPrazo) {
            $prazos->push((object) $oldPrazo);
        }
    } elseif (isset($processo)) {
        $prazos = $processo->prazos()->orderBy('data_vencimento', 'asc')->get();
    }
    // $prazos = isset($processo) ? $processo->prazos()->orderBy('data_vencimento', 'asc')->get() : collect([]);

    // Lógica de Cor para Audiência
    $audienceColorClass = "text-gray-600 dark:text-gray-400"; // Padrão
    if (isset($processo) && $processo->data_audiencia) {
        $audiencia = \Carbon\Carbon::parse($processo->data_audiencia)->startOfDay();
        $hoje = \Carbon\Carbon::now()->startOfDay();
        $diffDetails = $hoje->diffInDays($audiencia, false);

        // Verifica se processo está ativo para aplicar cores de alerta
        if ($processo->status === 'Ativo' || $processo->status === 'Suspenso') {
            if ($diffDetails <= 0) {
                // HOJE ou PASSADA: Vermelho Pastel
                $audienceColorClass = "text-red-800 bg-red-100 px-2 py-0.5 rounded font-bold animate-pulse";
            } elseif ($diffDetails <= 5) {
                // PRÓXIMOS 5 DIAS: Laranja
                $audienceColorClass = "text-orange-600 font-bold";
            } else {
                // FUTURO SEGURO: Verde
                $audienceColorClass = "text-emerald-600 font-medium";
            }
        }
    }
@endphp

@if(!isset($hideTitle) || !$hideTitle)
    <div class="flex items-center justify-between mb-4 mt-6">
        <div class="flex items-center gap-2">
            <p class="text-lg font-bold text-gray-800 dark:text-white">
                {{ __('lawfirm::app.prazos.section-title') ?? 'Gestão de Prazos' }}
            </p>
            @if(isset($processo) && $processo->data_audiencia)
                <span class="text-base {{ $audienceColorClass }}">
                    - Audiência: {{ \Carbon\Carbon::parse($processo->data_audiencia)->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>

        <div class="flex gap-2">
            <button type="button" class="primary-button" onclick="adicionarPrazo()">
                <span class="icon-plus text-lg inline-block align-middle mr-1"></span>
                {{ __('lawfirm::app.prazos.new-btn') ?? 'Novo Prazo' }}
            </button>
        </div>
    </div>
@else
    <div class="flex items-center justify-between mb-4 mt-2">
        <!-- Audiência info (preserved if exists) -->
        <div class="flex items-center gap-2">
            @if(isset($processo) && $processo->data_audiencia)
                <span class="text-base {{ $audienceColorClass }}">
                    Audiência: {{ \Carbon\Carbon::parse($processo->data_audiencia)->format('d/m/Y H:i') }}
                </span>
            @endif
        </div>

        <!-- Button (preserved) -->
        <div class="flex gap-2 ml-auto">
            <button type="button" class="primary-button" onclick="adicionarPrazo()">
                <span class="icon-plus text-lg inline-block align-middle mr-1"></span>
                {{ __('lawfirm::app.prazos.new-btn') ?? 'Novo Prazo' }}
            </button>
        </div>
    </div>
@endif

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <table class="min-w-full text-left text-sm text-gray-500 dark:text-gray-400" id="tabela-prazos">
        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3 min-w-[400px] required">@lang('lawfirm::app.prazos.title-table')</th>
                <th scope="col" class="px-6 py-3 w-[160px] required">@lang('lawfirm::app.prazos.due-date')</th>
                <th scope="col" class="px-6 py-3 min-w-[150px] required">@lang('lawfirm::app.prazos.status')</th>
                <th scope="col" class="px-6 py-3 w-full">Descrição</th>
                <th scope="col" class="px-6 py-3 w-[50px] text-center"></th>
            </tr>
        </thead>
        <tbody id="container-prazos">
            @foreach ($prazos as $index => $prazo)
                @php
                    $prazoId = $prazo->id ?? null;
                    $prazoTitulo = $prazo->titulo ?? '';
                    $prazoDataVencimento = $prazo->data_vencimento ?? null;
                    $prazoStatus = $prazo->status ?? 'Pendente';
                    $prazoDescricao = $prazo->descricao ?? '';

                    $diff = 999;
                    if ($prazoDataVencimento) {
                        try {
                            $vencimento = \Carbon\Carbon::parse($prazoDataVencimento)->startOfDay();
                            $hoje = \Carbon\Carbon::now()->startOfDay();
                            $diff = $hoje->diffInDays($vencimento, false); // false mantem negativo se venceu
                        } catch (\Exception $e) {
                            $diff = 999;
                        }
                    }

                    // Classes Padrão
                    $colorClass = "bg-white border-gray-300 text-gray-600"; // Neutro

                    // Normaliza status para comparação
                    $statusNormalized = mb_strtolower($prazoStatus, 'UTF-8');
                    
                    if ($statusNormalized !== 'concluído' && $statusNormalized !== 'concluido') {
                        if ($prazoDataVencimento && $diff <= 0) {
                            // HOJE ou VENCIDO: Vermelho Piscante
                            $colorClass = "bg-red-100 border-red-500 text-red-800 font-bold animate-pulse";
                        } elseif ($prazoDataVencimento && $diff <= 5) {
                            // ATENÇÃO (5 dias): Laranja
                            $colorClass = "bg-orange-100 border-orange-500 text-orange-800 font-medium";
                        } else {
                            // NO PRAZO: Verde Esmeralda
                            $colorClass = "bg-emerald-50 border-emerald-500 text-emerald-800";
                        }
                    } else {
                        // CONCLUÍDO: Cinza/Verde suave (Visual de tarefa feita)
                        $colorClass = "bg-gray-50 border-gray-200 text-gray-400 line-through";
                    }
                @endphp
                <tr class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
                    id="prazo-row-{{ $index }}">
                    <!-- Hidden ID -->
                    <input type="hidden" name="prazos[{{ $index }}][id]" value="{{ $prazoId }}">

                    <!-- Título -->
                    <td class="px-1 py-1 min-w-[350px]" style="min-width: 350px;">
                        <input type="text" name="prazos[{{ $index }}][titulo]" value="{{ $prazoTitulo }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm font-normal transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 {{ $colorClass }} @error("prazos.{$index}.titulo") border-red-500 @enderror"
                            style="width: 100%; box-sizing: border-box;" required>
                        @error("prazos.{$index}.titulo")
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </td>

                    <!-- Data -->
                    <td class="px-1 py-1">
                        @php
                            $dtVenc = $prazoDataVencimento;
                            if ($dtVenc && $dtVenc instanceof \Carbon\Carbon) {
                                $dtVenc = $dtVenc->format('Y-m-d');
                            } elseif ($dtVenc) {
                                // Tenta formatar string se possível, ou mantem raw
                                try {
                                    $dtVenc = \Carbon\Carbon::parse($dtVenc)->format('Y-m-d');
                                } catch (\Exception $e) {}
                            }
                        @endphp
                        <input type="date" name="prazos[{{ $index }}][data_vencimento]" value="{{ $dtVenc ?? '' }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm font-normal transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 {{ $colorClass }} @error("prazos.{$index}.data_vencimento") border-red-500 @enderror"
                            required>
                        @error("prazos.{$index}.data_vencimento")
                            <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </td>

                    <!-- Status -->
                    <td class="px-1 py-1">
                        <select name="prazos[{{ $index }}][status]"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            data-deadline-id="{{ $prazoId }}" onchange="updateDeadlineStatus(this)" required>
                            <option value="pendente" {{ in_array(strtolower($prazoStatus), ['pendente']) ? 'selected' : '' }}>Pendente</option>
                            <option value="concluido" {{ in_array(strtolower(str_replace('í','i',$prazoStatus)), ['concluido']) ? 'selected' : '' }}>Concluído
                            </option>
                        </select>
                    </td>

                    <!-- Descrição -->
                    <td class="px-1 py-1">
                        <input type="text" name="prazos[{{ $index }}][descricao]" value="{{ $prazoDescricao }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    </td>

                    <!-- Actions -->
                    <td class="px-1 py-1 text-center">
                        <button type="button" class="text-red-600 hover:text-red-900 cursor-pointer"
                            onclick="removerPrazo('prazo-row-{{ $index }}')">
                            <span class="icon-delete text-xl"></span>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
    <script>
        function adicionarPrazo() {
            const index = Date.now();
            const container = document.getElementById('container-prazos');
            const rowId = `prazo-row-${index}`;

            const row = document.createElement('tr');
            row.id = rowId;
            row.className = 'border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800';

            row.innerHTML = `
                            <td class="px-1 py-1 min-w-[350px]" style="min-width: 350px;">
                                <input type="text" name="prazos[${index}][titulo]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" style="width: 100%; box-sizing: border-box;" placeholder="Título" required>
                            </td>
                            <td class="px-1 py-1">
                                    <input type="date" name="prazos[${index}][data_vencimento]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" required>
                            </td>
                            <td class="px-1 py-1">
                                <select name="prazos[${index}][status]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="Pendente">Pendente</option>
                                    <option value="Concluído">Concluído</option>
                                </select>
                            </td>
                            <td class="px-1 py-1">
                                    <input type="text" name="prazos[${index}][descricao]" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Descrição">
                            </td>
                            <td class="px-1 py-1 text-center">
                                <button type="button" class="text-red-600 hover:text-red-900 cursor-pointer" onclick="removerPrazo('${rowId}')">
                                    <span class="icon-delete text-xl"></span>
                                </button>
                            </td>
                        `;

            container.appendChild(row);
        }

        function removerPrazo(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                row.remove();
            }
        }

        function updateDeadlineStatus(element) {
            const deadlineId = element.getAttribute('data-deadline-id');
            const newStatus = element.value;

            if (!deadlineId) return; // Se for novo prazo ainda não salvo, ignora

            // Visual Feedback - Loading
            const originalBorder = element.style.borderColor;
            element.style.borderColor = '#3b82f6'; // Blue
            element.disabled = true;

            // CSRF Token Fallback
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                const inputToken = document.querySelector('input[name="_token"]');
                csrfToken = inputToken ? inputToken.value : '';
            }

            if (!csrfToken) {
                alert('Erro: Token CSRF não encontrado. Por favor, recarregue a página.');
                element.disabled = false;
                element.style.borderColor = '#ef4444'; // Red
                return;
            }

            // Base URL com ID zerado para substituição
            const baseUrl = "{{ route('admin.lawfirm.legal.deadlines.update', 0) }}";
            const url = baseUrl.replace('/0', '/' + deadlineId);

            fetch(url, {
                method: 'POST', // Usando POST com _method PUT para compatibilidade
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'PUT',
                    status: newStatus
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Success Feedback - Green
                        element.style.borderColor = '#10b981'; // Emerald 500
                        setTimeout(() => {
                            element.style.borderColor = '';
                            element.classList.remove('border-red-500'); // Remove erro se houver
                        }, 1500);
                    } else {
                        throw new Error(data.message || 'Erro ao atualizar');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    // Error Feedback - Red
                    element.style.borderColor = '#ef4444'; // Red 500
                    alert('Erro ao atualizar status: ' + error.message);
                })
                .finally(() => {
                    element.disabled = false;
                });
        }
    </script>
@endpush