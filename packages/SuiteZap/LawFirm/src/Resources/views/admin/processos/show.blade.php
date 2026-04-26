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

            {{-- ── PRAZOS ───────────────────────────────────────── --}}
            @include('lawfirm::Legal.processos.tabs.prazos', ['readOnly' => true])

            {{-- ── NOTAS ────────────────────────────────────────── --}}
            @include('lawfirm::Legal.processos.tabs.notas', ['readOnly' => true, 'startClosed' => true])

            {{-- ── FINANCEIRO ───────────────────────────────────── --}}
            @include('lawfirm::Financial.processos.tabs.financial', ['processo' => $processo, 'readOnly' => true, 'startClosed' => true])

            {{-- ── DOCUMENTOS ───────────────────────────────────── --}}
            @include('lawfirm::GED.processos.tabs.documents', ['processo' => $processo, 'readOnly' => true])

            {{-- ── ROW 1: INÍCIO + DATAS ──────────────── --}}
            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                {{-- Card: Informações Básicas --}}
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex justify-between items-center">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Informações Básicas</p>
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
                                    class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $processo->person->name }}
                                </a>
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
                                    class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300" target="_blank">
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
                        <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->whatsapp_responsavel ?? '-' }}</p>
                    </div>
                </div>

                {{-- Card: Datas e Observações --}}
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">Datas e Observações</p>

                    <div class="grid grid-cols-2 gap-4">
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

                    <div class="space-y-1 flex-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.desc')</p>
                        <p class="text-base text-gray-800 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
                            {!! nl2br(e($processo->descricao ?? 'Sem observações.')) !!}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── ROW 2: DETALHES DO PROCESSO (full width) ── --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-4 text-lg font-bold text-gray-800 dark:text-white">Detalhes do Processo</p>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">@lang('lawfirm::app.processos.form.cnj')</p>
                        <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->numero_cnj ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Protocolo de Distribuição</p>
                        <p class="text-base text-gray-900 dark:text-white">{{ $processo->protocolo_distribuicao ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-5 gap-4">
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

            {{-- ── ROW 3: ESTRATÉGICO + PARTE CONTRÁRIA ── --}}
            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                {{-- Card: Dados Estratégicos --}}
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">Dados Estratégicos</p>

                    <div class="grid grid-cols-2 gap-4">
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
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">Parte Contrária (Oponente)</p>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Nome / Razão Social</p>
                        <p class="text-base font-medium text-gray-900 dark:text-white">
                            {{ $processo->opposing_party_name ?? $processo->parte_contraria ?? '-' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Tipo</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->opposing_party_type ?? '-' }}</p>
                        </div>

                        <div class="space-y-1 col-span-2">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">CPF / CNPJ</p>
                            <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->opposing_party_document ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    @if($processo->whatsappMessages()->exists())
        @include('lawfirm::admin.processos.modals.whatsapp-history-modal', ['processo' => $processo])
    @endif

</x-admin::layouts>