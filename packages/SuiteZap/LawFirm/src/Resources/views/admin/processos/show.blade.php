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
                    <a href="{{ route('lawfirm.documents.procuration', $processo->id) }}" class="secondary-button"
                        target="_blank">
                        ⚖️ Gerar Procuração
                    </a>
                    <a href="{{ route('lawfirm.documents.contract', $processo->id) }}" class="secondary-button"
                        target="_blank">
                        📄 Gerar Contrato
                    </a>
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

            {{-- ── ROW 1: IDENTIFICAÇÃO + DETALHES ────────────── --}}
            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                {{-- Card: Identificação --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-info')
                    </p>

                    {{-- Pessoa Vinculada --}}
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.person')
                        </p>
                        <p class="text-base font-bold text-gray-900 dark:text-white">
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

                    {{-- Status --}}
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.status')
                        </p>
                        <span class="inline-flex px-2 py-1 text-xs rounded-full {{
    $processo->status == 'Ativo' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' :
    ($processo->status == 'Suspenso' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' :
        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                            {{ $processo->status ?? '-' }}
                        </span>
                    </div>

                    {{-- CNJ --}}
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.cnj')
                        </p>
                        <p class="text-base font-mono text-gray-900 dark:text-white">{{ $processo->numero_cnj ?? '-' }}
                        </p>
                    </div>

                    {{-- Protocolo --}}
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Protocolo de Distribuição</p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->protocolo_distribuicao ?: 'Caso não tenha ATSumCNJ ainda' }}
                        </p>
                    </div>

                    {{-- Valor --}}
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.valor')
                        </p>
                        <p class="text-base font-bold text-gray-900 dark:text-white">
                            R$ {{ number_format((float) $processo->valor_causa, 2, ',', '.') }}
                        </p>
                    </div>

                    {{-- Probabilidade --}}
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.probabilidade')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">{{ $processo->probabilidade_exito ?? '-' }}
                        </p>
                    </div>
                </div>

                {{-- Card: Detalhes Processuais --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-details')
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.area')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->area_direito ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.fase')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->fase_processual ?? '-' }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.tribunal')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->tribunal ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.comarca')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->comarca ?? '-' }}</p>
                        </div>

                        <div class="space-y-1 col-span-2">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.vara')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->vara ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── ROW 2: RESPONSÁVEIS + PARTE CONTRÁRIA ───────── --}}
            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                {{-- Card: Responsáveis --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-parts')
                    </p>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.person') (Cliente)
                        </p>
                        <p class="text-base font-medium text-gray-900 dark:text-white">
                            {{ $processo->person->name ?? '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Responsável Interno</p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->responsavel->name ?? $processo->user->name ?? '-' }}
                        </p>
                    </div>
                </div>

                {{-- Card: Parte Contrária --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">Parte Contrária (Oponente)</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Nome / Razão Social</p>
                            <p class="text-base font-medium text-gray-900 dark:text-white">
                                {{ $processo->opposing_party_name ?? $processo->parte_contraria ?? '-' }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Tipo</p>
                            <p class="text-base text-gray-900 dark:text-white">
                                {{ $processo->opposing_party_type ?? '-' }}
                            </p>
                        </div>

                        <div class="space-y-1 col-span-2">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Documento (CPF/CNPJ)</p>
                            <p class="text-base font-mono text-gray-900 dark:text-white">
                                {{ $processo->opposing_party_document ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── ROW 3: DATAS + OBSERVAÇÕES ──────────────────── --}}
            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                {{-- Card: Datas --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-dates')
                    </p>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.data_distribuicao')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->data_distribuicao ? $processo->data_distribuicao->format('d/m/Y') : '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.data_audiencia')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->data_audiencia ? $processo->data_audiencia->format('d/m/Y H:i') : '-' }}
                        </p>
                    </div>
                </div>

                {{-- Card: Observações --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.desc')
                    </p>
                    <p class="text-base font-medium text-gray-800 dark:text-white whitespace-pre-wrap leading-relaxed">
                        {!! nl2br(e($processo->descricao ?? 'Sem observações.')) !!}
                    </p>
                </div>
            </div>

        </div>

</x-admin::layouts>