<x-admin::layouts>
    <x-slot:title>
        @lang('lawfirm::app.processos.view')
        </x-slot>

        <div class="flex flex-col gap-4">

            {{-- ── HEADER ──────────────────────────────────────── --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center gap-2">
                        <x-admin::breadcrumbs name="lawfirm.processos.show" :entity="$processo" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        {{ $processo->titulo }}
                        <span class="ml-2 text-sm font-normal text-gray-500">#{{ $processo->id }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-x-2.5">
                    <button type="button"
                            class="secondary-button text-xs bg-green-50 text-green-700 border-green-200 hover:bg-green-100"
                            onclick="window.lfOpenWaHistory()">
                        💬 Histórico WhatsApp
                    </button>
                    <a href="{{ route('admin.processos.edit', $processo->id) }}" class="primary-button">
                        @lang('lawfirm::app.processos.edit')
                    </a>
                    <a href="{{ route('admin.processos.index') }}" class="secondary-button">Voltar</a>
                </div>
            </div>

            {{-- ── FILTER BAR ─────────────────────────────────────────────── --}}
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
                <button type="button" class="lf-filter-btn" data-section="todos" onclick="lfShowAll()">&#10697; Todos</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="info" onclick="lfSwitchSection('info')">&#128203; Info. Processo</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="prazos" onclick="lfSwitchSection('prazos')">&#9878;&#65039; Prazos e Tarefas</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="notas" onclick="lfSwitchSection('notas')">&#128221; Notas</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="docs" onclick="lfSwitchSection('docs')">&#128206; Docs e Anexos</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="modelos" onclick="lfSwitchSection('modelos')">📄 Model. Docs</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="escavador" onclick="lfSwitchSection('escavador')">&#128220; Dados Oficiais (IA)</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="partes" onclick="lfSwitchSection('partes')">&#128101; Partes e Advogados</button>
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-filter-btn" data-section="financeiro" onclick="lfSwitchSection('financeiro')">&#128176; Financeiro</button>
            </div>

            {{-- ── SECTION: Dados Oficiais ──────────────────────────────── --}}
            <div id="section-escavador" class="lf-section hidden">
                @include('lawfirm::admin.processos.tabs.escavador-tab', ['processo' => $processo])
            </div>

            {{-- ── SECTION: Gestão de Prazos e Tarefas ──────────────────── --}}
            <div id="section-prazos" class="lf-section hidden flex flex-col gap-4">
                @include('lawfirm::Legal.processos.tabs.prazos', ['readOnly' => true])
            </div>

            {{-- ── SECTION: Notas ───────────────────────────────────────── --}}
            <div id="section-notas" class="lf-section hidden">
                @include('lawfirm::Legal.processos.tabs.notas', ['readOnly' => true, 'startClosed' => false])
            </div>

            {{-- ── SECTION: Gestão Financeira ──────────────────────────── --}}
            <div id="section-financeiro" class="lf-section hidden">
                @include('lawfirm::financial.processos.tabs.financial', ['processo' => $processo, 'readOnly' => true, 'startClosed' => false])
            </div>

            {{-- ── SECTION: Documentos ──────────────────────────────────── --}}
            <div id="section-docs" class="lf-section hidden flex flex-col gap-6">
                @include('lawfirm::GED.processos.tabs.documents', ['processo' => $processo, 'readOnly' => true])
            </div>

            {{-- ── SECTION: Modelos de Documentos ────────────────────────── --}}
            <div id="section-modelos" class="lf-section hidden flex flex-col gap-6">
                @include('lawfirm::Legal.processos.tabs.modelos-tab', ['processo' => $processo])
            </div>

            {{-- ── SECTION: Info. Processo ──────────────────────────────── --}}
            <div id="section-info" class="lf-section flex flex-col gap-6">

                {{-- ROW 1: INFORMAÇÕES BÁSICAS + DATAS --}}
                <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                    {{-- Card: Informações Básicas --}}
                    <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="flex justify-between items-center flex-wrap gap-2 pb-3 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight">Informações Básicas</p>
                            @if($processo->lead)
                                <a href="{{ route('admin.leads.view', $processo->lead->id) }}"
                                    class="text-sm text-blue-600 hover:underline inline-flex items-center gap-1" target="_blank">
                                    🔗 Lead: #{{ $processo->lead->id }} - {{ $processo->lead->title }}
                                </a>
                            @endif
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.titulo')</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white">{{ $processo->titulo ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.person')</p>
                            <p class="text-base text-gray-900 dark:text-white">
                                @if($processo->person)
                                    <a href="{{ route('admin.contacts.persons.view', $processo->person->id) }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400">
                                        {{ $processo->person->name }}
                                    </a>
                                    <form action="{{ route('admin.processos.request_registration', $processo->id) }}" method="POST" class="inline ml-2">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-x-1 rounded-md bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20 hover:bg-green-100" title="Solicitar Cadastro e Atualização por WhatsApp">
                                            <i class="icon-whatsapp font-bold"></i> Solicitar Cadastro
                                        </button>
                                    </form>
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Lead de Origem</p>
                            <p class="text-base text-gray-900 dark:text-white">
                                @if($processo->lead)
                                    <a href="{{ route('admin.leads.view', $processo->lead->id) }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400" target="_blank">
                                        🔗 #{{ $processo->lead->id }} - {{ $processo->lead->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.status')</p>
                            <span class="inline-flex px-2 py-1 text-xs rounded-full {{
                                $processo->status == 'Ativo' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' :
                                ($processo->status == 'Suspenso' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' :
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                {{ $processo->status ?? '-' }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Responsável Interno</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->responsavel->name ?? $processo->user->name ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">📱 WhatsApp do Advogado Responsável</p>
                            <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->responsavel->whatsapp ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Card: Datas e Observações --}}
                    <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Datas e Observações</p>

                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.data_distribuicao')</p>
                                <p class="text-base text-gray-900 dark:text-white">
                                    {{ $processo->data_distribuicao ? \Carbon\Carbon::parse($processo->data_distribuicao)->format('d/m/Y') : '-' }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.data_audiencia')</p>
                                <p class="text-base text-gray-900 dark:text-white">
                                    {{ $processo->data_audiencia ? \Carbon\Carbon::parse($processo->data_audiencia)->format('d/m/Y H:i') : '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-1.5 flex-1">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">@lang('lawfirm::app.processos.form.desc')</p>
                            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/30">
                                <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed rich-text-content">
                                    {!! $processo->descricao ?? 'Sem observações.' !!}
                                </div>
                            </div>
                        </div>

                        {{-- ── SEÇÕES DE INTELIGÊNCIA ARTIFICIAL ── --}}
                        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                            <h4 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400">
                                <span class="icon-settings-flow"></span> Contexto de Inteligência Artificial
                            </h4>

                            <div class="grid grid-cols-1 gap-2">
                                {{-- Análise de Viabilidade --}}
                                @if(isset($triagem) && $triagem->viabilidade)
                                    <div class="lf-ai-section">
                                        <button type="button" class="lf-ai-toggle" onclick="lfToggleAi('viabilidade')">
                                            <span class="flex items-center gap-2">
                                                <span class="text-base">🧠</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-200">Análise de Viabilidade</span>
                                            </span>
                                            <span class="icon-arrow-down transform transition-transform duration-200" id="icon-viabilidade"></span>
                                        </button>
                                        <div id="ai-viabilidade" class="lf-ai-content hidden">
                                            <div class="ai-result-box">{!! \Illuminate\Support\Str::markdown($triagem->viabilidade) !!}</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Qualificação Jurídica --}}
                                @if(isset($triagem) && $triagem->qualificacao)
                                    <div class="lf-ai-section">
                                        <button type="button" class="lf-ai-toggle" onclick="lfToggleAi('qualificacao')">
                                            <span class="flex items-center gap-2">
                                                <span class="text-base">📋</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-200">Qualificação Jurídica</span>
                                            </span>
                                            <span class="icon-arrow-down transform transition-transform duration-200" id="icon-qualificacao"></span>
                                        </button>
                                        <div id="ai-qualificacao" class="lf-ai-content hidden">
                                            <div class="ai-result-box">{!! \Illuminate\Support\Str::markdown($triagem->qualificacao) !!}</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Sugestão de Proposta --}}
                                @if(isset($triagem) && $triagem->proposta)
                                    <div class="lf-ai-section">
                                        <button type="button" class="lf-ai-toggle" onclick="lfToggleAi('proposta')">
                                            <span class="flex items-center gap-2">
                                                <span class="text-base">📄</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-200">Sugestão de Proposta</span>
                                            </span>
                                            <span class="icon-arrow-down transform transition-transform duration-200" id="icon-proposta"></span>
                                        </button>
                                        <div id="ai-proposta" class="lf-ai-content hidden">
                                            <div class="ai-result-box">{!! \Illuminate\Support\Str::markdown($triagem->proposta) !!}</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Negociação & Conversão --}}
                                @if(isset($triagem) && $triagem->negociacao)
                                    <div class="lf-ai-section">
                                        <button type="button" class="lf-ai-toggle" onclick="lfToggleAi('negociacao')">
                                            <span class="flex items-center gap-2">
                                                <span class="text-base">💬</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-200">Negociação & Conversão</span>
                                            </span>
                                            <span class="icon-arrow-down transform transition-transform duration-200" id="icon-negociacao"></span>
                                        </button>
                                        <div id="ai-negociacao" class="lf-ai-content hidden">
                                            <div class="ai-result-box">{!! \Illuminate\Support\Str::markdown($triagem->negociacao) !!}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ROW 2: DETALHES DO PROCESSO --}}
                <div class="lf-card rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <p class="mb-5 text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Detalhes do Processo</p>

                    <div class="grid grid-cols-2 gap-4 mb-4 max-sm:grid-cols-1">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.cnj')</p>
                            <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->numero_cnj ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Protocolo de Distribuição</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->protocolo_distribuicao ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 max-md:grid-cols-2 max-sm:grid-cols-1">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.area')</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->area_direito ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.fase')</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->fase_processual ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.tribunal')</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->tribunal ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.comarca')</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->comarca ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.vara')</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->vara ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                {{-- ROW 3: ESTRATÉGICO + PARTE CONTRÁRIA --}}
                <div class="grid grid-cols-2 gap-6 max-lg:grid-cols-1">

                    {{-- Card: Dados Estratégicos --}}
                    <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Dados Estratégicos</p>

                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.valor')</p>
                                <p class="text-base font-bold text-gray-900 dark:text-white">
                                    R$ {{ number_format((float) $processo->valor_causa, 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.probabilidade')</p>
                                <p class="text-base text-gray-900 dark:text-white">{{ $processo->probabilidade_exito ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Parte Contrária --}}
                    <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Parte Contrária (Oponente)</p>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Nome / Razão Social</p>
                            <p class="text-base font-medium text-gray-900 dark:text-white">
                                {{ $processo->opposing_party_name ?? $processo->parte_contraria ?? '-' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Tipo</p>
                                <p class="text-base text-gray-900 dark:text-white">{{ $processo->opposing_party_type ?? '-' }}</p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">CPF / CNPJ</p>
                                <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->opposing_party_document ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- end #section-info --}}

            {{-- ── SECTION: Partes e Advogados ─────────────────────────── --}}
            <div id="section-partes" class="lf-section hidden flex flex-col gap-4">
                <div class="lf-card flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-800">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight">⚖️ Partes e Advogados</p>
                    </div>
                    @if($processo->envolvidos_escavador)
                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed bg-gray-50 dark:bg-gray-800 rounded-lg p-4">{{ $processo->envolvidos_escavador }}</pre>
                    @else
                        <div class="py-8 text-center text-sm text-gray-400">
                            Nenhuma informação de partes importada. Use "Dados Oficiais (IA)" na página de edição.
                        </div>
                    @endif
                </div>
            </div>{{-- end #section-partes --}}
        </div>

    @include('lawfirm::admin.processos.modals.whatsapp-history-modal', ['processo' => $processo])

    @push('scripts')
        <style>
            /* ╔══════════════════════════════════════════════════════════════╗
               ║  PROCESSOS — UX HARMONIZAÇÃO  (show)                       ║
               ╚══════════════════════════════════════════════════════════════╝ */

            /* — Section gap override ––––––––––––––––––––––––––––––––– */
            #section-info,#section-partes,#section-prazos,
            #section-notas,#section-financeiro,
            #section-docs,#section-modelos,#section-escavador { gap: 1.5rem; }

            /* — Static field label style (show-mode) ––––––––––––––– */
            .lf-card .space-y-1 > p:first-child,
            .lf-card .space-y-1.5 > p:first-child {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #9ca3af;
                line-height: 1.2;
            }
            .lf-card .space-y-1 > p:last-child,
            .lf-card .space-y-1.5 > p:last-child {
                font-size: 0.9375rem;
                font-weight: 500;
                line-height: 1.5;
            }

            /* — Card hover transition ––––––––––––––––––––––––––––– */
            .lf-card { transition: box-shadow 200ms ease, border-color 200ms ease; }
            .lf-card:hover { border-color: #e5e7eb; }
            .dark .lf-card:hover { border-color: #374151; }

            /* — Filter bar ––––––––––––––––––––––––––––––––––––––– */
            .lf-filter-btn {
                white-space: nowrap;
                padding: 0.35rem 0.85rem;
                border-radius: 0.5rem;
                font-size: 0.8rem;
                font-weight: 500;
                color: #6b7280;
                border: 1px solid transparent;
                transition: all 150ms;
                cursor: pointer;
                background: transparent;
            }
            .lf-filter-btn:hover { background: #f3f4f6; color: #111827; }
            .dark .lf-filter-btn:hover { background: #1f2937; color: #f9fafb; }
            .lf-filter-btn.active {
                background: #eff6ff;
                color: #1d4ed8;
                border-color: #bfdbfe;
                font-weight: 600;
            }
            .dark .lf-filter-btn.active {
                background: #1e3a5f;
                color: #93c5fd;
                border-color: #1d4ed8;
            }

            /* AI Collapsible Sections */
            .lf-ai-section {
                border: 1px solid #f3e8ff;
                border-radius: 8px;
                background: #fff;
                overflow: hidden;
                margin-bottom: 4px;
                transition: all 0.2s;
            }
            .dark .lf-ai-section { border-color: #3b0764; background: #111827; }
            .lf-ai-section:hover { border-color: #7c3aed; }
            .lf-ai-toggle {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 16px;
                background: none;
                border: none;
                cursor: pointer;
                text-align: left;
            }
            .lf-ai-content {
                padding: 0 16px 16px 16px;
                border-top: 1px solid #f3e8ff;
                background: #fafafa;
            }
            .dark .lf-ai-content { border-color: #3b0764; background: #0f172a; }
        </style>
        <script>
            window.lfSwitchSection = function(name) {
                document.querySelectorAll('.lf-section').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.lf-filter-btn').forEach(el => el.classList.remove('active'));
                const target = document.getElementById('section-' + name);
                if (target) target.classList.remove('hidden');
                const btn = document.querySelector('[data-section="' + name + '"]');
                if (btn) btn.classList.add('active');
                localStorage.setItem('lf_processo_section_{{ $processo->id }}', name);
            };
            window.lfShowAll = function() {
                document.querySelectorAll('.lf-section').forEach(el => el.classList.remove('hidden'));
                document.querySelectorAll('.lf-filter-btn').forEach(el => el.classList.remove('active'));
                const btn = document.querySelector('[data-section="todos"]');
                if (btn) btn.classList.add('active');
                localStorage.setItem('lf_processo_section_{{ $processo->id }}', 'todos');
            };
            window.addEventListener('DOMContentLoaded', function() {
                const saved = localStorage.getItem('lf_processo_section_{{ $processo->id }}') || 'info';
                if (saved === 'todos') { lfShowAll(); } else { lfSwitchSection(saved); }
            });

            window.lfToggleAi = function(id) {
                const content = document.getElementById('ai-' + id);
                const icon = document.getElementById('icon-' + id);
                if (content && icon) {
                    content.classList.toggle('hidden');
                    icon.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            };

            window.lfCopyPortalUrl = function(url) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).then(() => {
                        window.addFlashMessages([{type: 'success', message: 'Link do Portal copiado para área de transferência!'}]);
                    }).catch(err => {
                        prompt("Copie este link manualmente:", url);
                    });
                } else {
                    prompt("Copie este link manualmente:", url);
                }
            };
        </script>
    @endpush

</x-admin::layouts>
