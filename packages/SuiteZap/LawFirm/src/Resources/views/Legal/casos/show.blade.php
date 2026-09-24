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

        <!-- Layout Grid para Dados do Caso e Descrição -->
        <div id="row-info-basicas" class="grid grid-cols-2 gap-4 max-lg:grid-cols-1" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            
            <!-- Card 1: Dados do Caso -->
            <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="flex justify-between items-center flex-wrap gap-2 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight">📋 Dados do Caso</p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Título</p>
                    <p class="text-base font-bold text-gray-900 dark:text-white">{{ $caso->titulo ?? '-' }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Área</p>
                    <p class="text-base text-gray-900 dark:text-white">
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
                    </p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Status</p>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $caso->status_badge_class }}">{{ $caso->status_label }}</span>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Prioridade</p>
                    <p class="text-base text-gray-900 dark:text-white">
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
                    </p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Responsável</p>
                    <p class="text-base text-gray-900 dark:text-white">{{ optional($caso->responsavel)->name ?: '—' }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Cliente PF</p>
                    <p class="text-base text-gray-900 dark:text-white">{{ optional($caso->person)->name ?: '—' }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Cliente PJ</p>
                    <p class="text-base text-gray-900 dark:text-white">{{ optional($caso->organization)->name ?: '—' }}</p>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Criado em</p>
                    <p class="text-base text-gray-900 dark:text-white">{{ $caso->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div> <!-- end Card 1 -->

            <!-- Card 2: Descrição e Contexto de IA -->
            <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">📝 Descrição</p>
                
                <div class="space-y-1.5 flex-1">
                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Descrição do Caso</p>
                    <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/30">
                        <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed rich-text-content whitespace-pre-wrap">
                            {!! nl2br(e($caso->descricao ?? 'Sem descrição.')) !!}
                        </div>
                    </div>
                </div>

                @if ($triagem)
                    <!-- AI Context Sections (Collapsible) -->
                    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800 space-y-3">
                        <h4 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400">
                            <span class="icon-settings-flow"></span> Contexto de Inteligência Artificial
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
            </div> <!-- end Card 2 -->

        </div> <!-- end Layout Grid -->

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
