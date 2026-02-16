<x-admin::layouts>
    <x-slot:title>
        @lang('lawfirm::app.processos.view')
        </x-slot>

        <style>
            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                align-items: center;
                justify-content: center;
            }

            .modal-box {
                background: #fff;
                padding: 20px;
                width: 400px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .show-modal {
                display: flex !important;
            }
        </style>

        <div class="flex flex-col gap-4">

            <!-- Header -->
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
                    <a href="{{ route('admin.processos.index') }}" class="secondary-button">
                        Voltar
                    </a>
                </div>
            </div>

            <!-- 1. Prazos -->
            @include('lawfirm::Legal.processos.tabs.prazos', ['readOnly' => true])


            <!-- 2. Financeiro -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                @include('lawfirm::Financial.processos.tabs.financial', ['processo' => $processo, 'readOnly' => true, 'startClosed' => true])
            </div>

            <!-- 4. Checklist de Documentos -->
            <!-- 4. Checklist de Documentos & GED -->
            <!-- 4. Checklist de Documentos & GED -->
            @include('lawfirm::GED.processos.tabs.documents', ['processo' => $processo, 'readOnly' => true])

            <!-- 4. Identification & Process Details (2 Cols) -->
            <div class="flex gap-4">
                <!-- Left: Basic Info -->
                <div
                    class="w-1/2 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-info')
                    </p>

                    <!-- Status Badge -->
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.status')
                        </p>
                        <span class="inline-flex px-2 py-1 text-xs rounded-full {{ 
                        $processo->status == 'Ativo' ? 'bg-green-100 text-green-800' :
    ($processo->status == 'Suspenso' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') 
                    }}">
                            {{ $processo->status ?? '-' }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.titulo')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white font-medium">{{ $processo->titulo ?? '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.cnj')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">{{ $processo->numero_cnj ?? '-' }}</p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Protocolo de Distribuição</p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->protocolo_distribuicao ?? '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.link')
                        </p>
                        @if($processo->link_acesso)
                            <a href="{{ $processo->link_acesso }}" target="_blank"
                                class="text-blue-600 hover:underline break-all">
                                {{ $processo->link_acesso }}
                            </a>
                        @else
                            <p class="text-base text-gray-900 dark:text-white">-</p>
                        @endif
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.valor')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">R$
                            {{ number_format((float) $processo->valor_causa, 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                <!-- Right: Details -->
                <div
                    class="w-1/2 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-details')
                    </p>

                    <div class="grid grid-cols-2 gap-4">
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

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.vara')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->vara ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Juiz(a) Atual</p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->juiz_atual ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.fase')
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">{{ $processo->fase_processual ?? '-' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.area')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->area_direito ?? '-' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                @lang('lawfirm::app.processos.form.subarea')
                            </p>
                            <p class="text-base text-gray-900 dark:text-white">{{ $processo->subarea_direito ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Strategic Data -->
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                    Dados Estratégicos
                </p>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                        @lang('lawfirm::app.processos.form.probabilidade')
                    </p>
                    <p class="text-base text-gray-900 dark:text-white">{{ $processo->probabilidade_exito ?? '-' }}</p>
                </div>
            </div>

            <!-- 6. Parts Management (2 Cols) -->
            <div class="flex gap-4">
                <!-- Left: Client Info -->
                <div
                    class="w-1/2 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('lawfirm::app.processos.form.group-parts')
                    </p>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.person') (Cliente)
                        </p>
                        <p class="text-base text-gray-900 dark:text-white font-medium">
                            {{ $processo->person->name ?? '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Responsável Interno (CRM)</p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->responsavel->name ?? $processo->user->name ?? '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Advogado Responsável (Peça)
                        </p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->advogado_responsavel_nome ?: 'Não informado' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">OAB (Responsável)</p>
                        <p class="text-base text-gray-900 dark:text-white">
                            {{ $processo->advogado_responsavel_oab ?: '-' }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Qualificação da Parte</p>
                        <p class="text-base text-gray-900 dark:text-white capitalize">{{ $processo->tipo_parte ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- Right: Opposing Party -->
                <!-- Right: Opposing Party -->
                <div
                    class="w-1/2 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                        Parte Contrária (Oponente)
                    </p>

                    <!-- Dados do Oponente -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">Nome do
                                Oponente</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white break-words">{{ $processo->opposing_party_name ?? $processo->parte_contraria ?? '-' }}</span>
                        </div>

                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">Tipo da
                                Parte</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white">{{ $processo->opposing_party_type ?? '-' }}</span>
                        </div>

                        <div class="flex flex-col col-span-2">
                            <span
                                class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">Documento
                                (CPF/CNPJ)</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white">{{ $processo->opposing_party_document ?? '-' }}</span>
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700 mb-4">

                    <!-- Advogado do Oponente -->
                    <p class="text-sm font-bold text-gray-800 dark:text-white mb-3">
                        Advogado do Oponente
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">Nome do
                                Advogado</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white break-words">{{ $processo->advogado_parte_contraria ?? '-' }}</span>
                        </div>

                        <div class="flex flex-col">
                            <span
                                class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">OAB</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white">{{ $processo->advogado_oab ?? '-' }}</span>
                        </div>

                        <div class="flex flex-col">
                            <span
                                class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">E-mail</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white break-all">{{ $processo->email_advogado_contrario ?? '-' }}</span>
                        </div>

                        <div class="flex flex-col">
                            <span
                                class="text-gray-500 text-xs font-semibold uppercase mb-1 dark:text-gray-400">WhatsApp</span>
                            <span
                                class="text-gray-800 font-medium dark:text-white">{{ $processo->whatsapp_advogado_contrario ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Dates & Description -->
            <div class="flex gap-4">
                <!-- Left: Dates -->
                <div
                    class="w-1/2 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
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

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            @lang('lawfirm::app.processos.form.link_audiencia')
                        </p>
                        @if($processo->link_audiencia)
                            <a href="{{ $processo->link_audiencia }}" target="_blank"
                                class="text-blue-600 hover:underline break-all">
                                {{ $processo->link_audiencia }}
                            </a>
                        @else
                            <p class="text-base text-gray-900 dark:text-white">-</p>
                        @endif
                    </div>
                </div>

                <!-- Right: Description -->
                <div
                    class="w-1/2 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                        @lang('lawfirm::app.processos.form.desc')
                    </p>
                    <div class="flex flex-col h-full">
                        <span
                            class="text-gray-800 font-medium dark:text-white whitespace-pre-wrap leading-relaxed">{!! nl2br(e($processo->descricao ?? 'Sem observações.')) !!}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal HTML (Moved inside layout to ensure rendering) -->

</x-admin::layouts>