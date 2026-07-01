<x-admin::layouts>
    <x-slot:title>
        Caso — {{ $caso->titulo }}
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">📂 Caso #{{ $caso->id }} — {{ $caso->titulo }}</div>
            </div>
            <div class="flex items-center gap-x-2.5">
                @if (bouncer()->hasPermission('lawfirm.casos.edit'))
                    <a href="{{ route('admin.lawfirm.casos.edit', $caso->id) }}" class="primary-button">✏️ Editar</a>
                @endif
                <a href="{{ route('admin.lawfirm.casos.index') }}" class="transparent-button">← Voltar</a>
            </div>
        </div>

        <!-- Dados do Caso -->
        <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
            <h3 class="text-lg font-semibold tracking-tight border-b pb-3 dark:text-white">📋 Dados do Caso</h3>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Título: </span>
                    <span class="font-bold dark:text-white">{{ $caso->titulo }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Área: </span>
                    @php
                        $areaColors = [
                            'Administrativo' => '#A9CCE3', 'Ambiental' => '#A3E4D7', 'Bancário' => '#D4E6B5',
                            'Consumidor' => '#F8CBA6', 'Cível' => '#C7D3DD', 'Digital / LGPD' => '#C5CAE9',
                            'Empresarial' => '#D7BDE2', 'Família' => '#F5B7B1', 'Imobiliário' => '#E8D8C3',
                            'Penal' => '#F4A7A7', 'Previdenciário' => '#A8D5BA', 'Trabalhista' => '#A7C7E7',
                            'Tributário' => '#F9E79F',
                        ];
                        $areaBg = $areaColors[$caso->area] ?? null;
                    @endphp
                    @if ($areaBg)
                        <span class="rounded-xl px-2.5 py-0.5 text-xs font-semibold" style="background-color: {{ $areaBg }}; color: #333;">{{ $caso->area }}</span>
                    @else
                        <span class="dark:text-white">{{ $caso->area ?: '—' }}</span>
                    @endif
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Status: </span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $caso->status_badge_class }}">{{ $caso->status_label }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Prioridade: </span>
                    @php
                        $prioridadeColors = [
                            'Alta' => '#E89B4D', 'Baixa' => '#7BC67B',
                            'Crítica' => '#D96B6B', 'Média' => '#E6C15A',
                        ];
                        $prioKey = ucfirst(mb_strtolower($caso->prioridade ?? ''));
                        $prioBg = $prioridadeColors[$prioKey] ?? null;
                    @endphp
                    @if ($prioBg)
                        <span class="rounded-xl px-2.5 py-0.5 text-xs font-semibold" style="background-color: {{ $prioBg }}; color: #fff;">{{ ucfirst($caso->prioridade) }}</span>
                    @else
                        <span class="dark:text-white">{{ $caso->prioridade ? ucfirst($caso->prioridade) : '—' }}</span>
                    @endif
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Responsável: </span>
                    <span class="dark:text-white">{{ optional($caso->responsavel)->name ?: '—' }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Cliente PF: </span>
                    <span class="dark:text-white">{{ optional($caso->person)->name ?: '—' }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Cliente PJ: </span>
                    <span class="dark:text-white">{{ optional($caso->organization)->name ?: '—' }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500 dark:text-gray-400">Criado em: </span>
                    <span class="dark:text-white">{{ $caso->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <!-- Descrição Mirroring Processos Logic -->
                <div class="md:col-span-2 space-y-1">
                    <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Descrição</span>
                    <p class="text-base text-gray-800 dark:text-gray-300 whitespace-pre-wrap leading-relaxed bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border border-gray-100 dark:border-gray-800">
                        {!! nl2br(e($caso->descricao ?? 'Sem descrição.')) !!}
                    </p>
                </div>

                @if ($triagem)
                    <!-- AI Context Sections (Collapsible) -->
                    <div class="md:col-span-2 mt-4 space-y-3">
                        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <span>🤖 Contexto de Inteligência Artificial</span>
                            <span class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></span>
                        </h4>

                        @php
                            $aiFields = [
                                ['id' => 'viabilidade', 'label' => '🧠 Análise de Viabilidade', 'content' => $triagem->viabilidade],
                                ['id' => 'qualificacao', 'label' => '📋 Qualificação Jurídica', 'content' => $triagem->qualificacao],
                                ['id' => 'proposta', 'label' => '📄 Sugestão de Proposta', 'content' => $triagem->proposta],
                                ['id' => 'negociacao', 'label' => '💬 Negociação & Conversão', 'content' => $triagem->negociacao],
                            ];
                        @endphp

                        @foreach ($aiFields as $field)
                            @if ($field['content'])
                                <div class="lf-accordion-item rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm transition-all duration-200">
                                    <button type="button" 
                                            class="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                            onclick="lfToggleAccordion('{{ $field['id'] }}')">
                                        <span>{{ $field['label'] }}</span>
                                        <svg id="icon-{{ $field['id'] }}" class="w-4 h-4 transition-transform duration-200 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div id="content-{{ $field['id'] }}" class="hidden px-4 py-3 bg-gray-50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800">
                                        <div class="text-sm text-gray-800 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                                            {!! nl2br(e($field['content'])) !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

@push('scripts')
<script>
    window.lfToggleAccordion = function(id) {
        const content = document.getElementById('content-' + id);
        const icon = document.getElementById('icon-' + id);
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }
</script>
@endpush

        <!-- Processos Vinculados -->
        <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-lg font-semibold tracking-tight dark:text-white">⚖️ Processos Vinculados ({{ $caso->processos->count() }})</h3>
            </div>

            @if ($caso->processos->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-sm italic">Nenhum processo vinculado.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b">
                            <tr>
                                <th class="px-3 py-2">ID</th>
                                <th class="px-3 py-2">Título</th>
                                <th class="px-3 py-2">Nº CNJ</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Tribunal</th>
                                <th class="px-3 py-2 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($caso->processos as $processo)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">#{{ $processo->id }}</td>
                                    <td class="px-3 py-2 font-medium">{{ $processo->titulo }}</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $processo->numero_cnj ?: '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ strtolower($processo->status) === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ ucfirst($processo->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $processo->tribunal ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <a href="{{ route('admin.processos.show', $processo->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Ver →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Resumo Financeiro -->
        <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
            <h3 class="text-lg font-semibold tracking-tight border-b pb-3 dark:text-white">💰 Resumo Financeiro Consolidado</h3>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Processos</div>
                    <div class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ $kpis['processos_count'] }}</div>
                </div>
                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Receitas</div>
                    <div class="text-lg font-bold text-green-700 dark:text-green-300">R$ {{ number_format($kpis['receita_total'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Despesas</div>
                    <div class="text-lg font-bold text-red-700 dark:text-red-300">R$ {{ number_format($kpis['despesas_totais'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg {{ $kpis['lucro_liquido'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-orange-50 dark:bg-orange-900/20' }} p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Lucro Líquido</div>
                    <div class="text-lg font-bold {{ $kpis['lucro_liquido'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-orange-700 dark:text-orange-300' }}">
                        R$ {{ number_format($kpis['lucro_liquido'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
