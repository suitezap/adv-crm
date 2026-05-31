<x-admin::layouts>
    <x-slot:title>
        @lang('lawfirm::app.processos.edit-title')
    </x-slot>

    @inject('userRepository', 'Webkul\User\Repositories\UserRepository')

    <div class="flex flex-col gap-4">

        {{-- ── HEADER ──────────────────────────────────────── --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center gap-2">
                    <x-admin::breadcrumbs name="lawfirm.processos.edit" :entity="$processo" />
                </div>
            </div>
            <div class="flex items-center gap-x-2.5">
                <button type="button" class="secondary-button text-xs" onclick="window.lfOpenWhatsappImportModal()">
                    ☁️ Importar WhatsApp
                </button>

                <button type="button" class="secondary-button text-xs bg-green-50 text-green-700 border-green-200 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800" onclick="window.lfOpenWaHistory()">
                    💬 Histórico WhatsApp
                </button>

                <button type="submit" form="processo-form" class="primary-button">
                    @lang('lawfirm::app.processos.save-btn')
                </button>
                <a href="{{ route('admin.processos.show', $processo->id) }}" class="secondary-button text-xs">
                    Visualizar
                </a>
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
            @include('lawfirm::Legal.processos.tabs.prazos')
        </div>

        {{-- ── SECTION: Notas ─────────────────────────────────────────── --}}
        <div id="section-notas" class="lf-section hidden">
            @include('lawfirm::Legal.processos.tabs.notas', ['processo' => $processo, 'startClosed' => false])
        </div>

        {{-- ── SECTION: Gestão Financeira ────────────────────────────── --}}
        <div id="section-financeiro" class="lf-section hidden">
            @include('lawfirm::financial.processos.tabs.financial', ['startClosed' => false])
        </div>

        {{-- ── SECTION: Documentos (dentro de Info. Processo) ────────── --}}
        <div id="section-docs" class="lf-section hidden flex flex-col gap-6">
            @include('lawfirm::GED.processos.tabs.documents', ['processo' => $processo])
        </div>

        {{-- ── SECTION: Modelos de Documentos ────────────────────────── --}}
        <div id="section-modelos" class="lf-section hidden flex flex-col gap-6">
            @include('lawfirm::Legal.processos.tabs.modelos-tab', ['processo' => $processo])
        </div>

        {{-- ── SECTION: Info. Processo (form) ────────────────────────── --}}
        <x-admin::form id="processo-form" :action="route('admin.processos.update', $processo->id)" method="PUT" enctype="multipart/form-data" onsubmit="window.appendExternalTabs(event, this)">
            <div class="flex flex-col gap-6">
            <div id="section-info" class="lf-section flex flex-col gap-6">

                {{-- ── ROW 1: INÍCIO + DATAS ──────────────── --}}
                <div class="grid grid-cols-2 gap-6 max-lg:grid-cols-1">

                    {{-- Card: Informações Básicas --}}
                    <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight">Informações Básicas</p>
                            @if($processo->lead)
                                <a href="{{ route('admin.leads.view', $processo->lead->id) }}"
                                    class="text-xs text-blue-600 hover:underline inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-md" target="_blank">
                                    🔗 Lead #{{ $processo->lead->id }}
                                </a>
                            @endif
                        </div>

                        {{-- Titulo --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('lawfirm::app.processos.form.titulo')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="titulo" rules="required"
                                :value="old('titulo', $processo->titulo)"
                                :label="trans('lawfirm::app.processos.form.titulo')"
                                :placeholder="trans('lawfirm::app.processos.form.titulo')" />
                            <x-admin::form.control-group.error control-name="titulo" />
                        </x-admin::form.control-group>

                        {{-- Trigger v-lookup-component registration --}}
                        <x-admin::attributes.edit.lookup />

                        {{-- Cliente e Empresa --}}
                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">

                            {{-- Pessoa (Cliente) --}}
                            @php
                                $personId = optional($processo->person)->id;
                                $personLookup = $personId ? app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpEntity('persons', $personId) : null;
                            @endphp
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.person') (Opcional)
                                </x-admin::form.control-group.label>
                                <v-lookup-component
                                    :attribute="{{ json_encode(['code' => 'person_id', 'name' => 'Pessoa', 'lookup_type' => 'persons']) }}"
                                    :value="{{ json_encode($personLookup) }}"
                                    validations=""
                                    @lookup-removed="handleLookupAdded"
                                    @lookup-added="handleLookupAdded"
                                ></v-lookup-component>
                                <x-admin::form.control-group.error control-name="person_id" />
                            </x-admin::form.control-group>

                            {{-- Empresa --}}
                            @php
                                $orgId = optional($processo->organization)->id;
                                $orgLookup = $orgId ? app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpEntity('organizations', $orgId) : null;
                            @endphp
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Empresa (Opcional)
                                </x-admin::form.control-group.label>
                                <v-lookup-component
                                    :attribute="{{ json_encode(['code' => 'organization_id', 'name' => 'Empresa', 'lookup_type' => 'organizations']) }}"
                                    :value="{{ json_encode($orgLookup) }}"
                                    validations=""
                                    @lookup-removed="handleLookupAdded"
                                    @lookup-added="handleLookupAdded"
                                ></v-lookup-component>
                                <x-admin::form.control-group.error control-name="organization_id" />
                            </x-admin::form.control-group>

                        </div>

                        {{-- Lead de Origem --}}
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Lead de Origem</p>
                            <p class="text-base text-gray-900 dark:text-white">
                                @if($processo->lead)
                                    <input type="hidden" name="lead_id" value="{{ $processo->lead_id }}">
                                    <a href="{{ route('admin.leads.view', $processo->lead->id) }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300" target="_blank">
                                        🔗 #{{ $processo->lead->id }} - {{ $processo->lead->title }}
                                    </a>
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        {{-- Caso Vinculado (Select AJAX) --}}
                        <div class="mt-2" id="lf-caso-selector-wrapper">
                            <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                📂 Caso Vinculado (Opcional)
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="lf-caso-search"
                                    placeholder="Digite para buscar um caso..."
                                    autocomplete="off"
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                />
                                <input type="hidden" name="caso_id" id="lf-caso-id" value="{{ old('caso_id', $processo->caso_id) }}" />
                                <div id="lf-caso-results" class="absolute z-50 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 max-h-48 overflow-y-auto"></div>
                            </div>
                            <div id="lf-caso-selected" class="mt-1 {{ $processo->caso_id ? '' : 'hidden' }}">
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    📂 <span id="lf-caso-selected-label">{{ $processo->caso ? '#' . $processo->caso->id . ' — ' . $processo->caso->titulo : '' }}</span>
                                    <button type="button" onclick="lfClearCaso()" class="ml-1 text-blue-400 hover:text-red-500">&times;</button>
                                </span>
                            </div>
                        </div>

                        {{-- Status e Responsável --}}
                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1 mt-4">
                            {{-- Status --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('lawfirm::app.processos.form.status')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select" name="status" rules="required"
                                    :value="old('status', $processo->status)"
                                    :label="trans('lawfirm::app.processos.form.status')">
                                    @foreach(\SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES as $s)
                                        <option value="{{ $s }}" {{ old('status', $processo->status) == $s ? 'selected' : '' }}>
                                            {{ $s }}
                                        </option>
                                    @endforeach
                                    {{-- Legacy fallback so existing records don't lose their value --}}
                                    @if($processo->status && !in_array($processo->status, \SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES))
                                        <option value="{{ $processo->status }}" selected>(legado) {{ $processo->status }}</option>
                                    @endif
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="status" />
                            </x-admin::form.control-group>

                            {{-- Responsável Interno --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Responsável Interno</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select" name="user_id" label="Responsável Interno"
                                    :value="old('user_id', $processo->user_id)">
                                    <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                    @foreach($userRepository->all() as $user)
                                        <option value="{{ $user->id }}" {{ (int)old('user_id', $processo->user_id) === (int)$user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="user_id" />
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    {{-- Card: Datas e Observações --}}
                    <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Datas e Observações</p>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Data Distribuição --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.data_distribuicao')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="date" name="data_distribuicao"
                                    :value="$processo->data_distribuicao ? \Carbon\Carbon::parse($processo->data_distribuicao)->format('Y-m-d') : ''"
                                    :label="trans('lawfirm::app.processos.form.data_distribuicao')" />
                                <x-admin::form.control-group.error control-name="data_distribuicao" />
                            </x-admin::form.control-group>

                            {{-- Data Audiência — Componente nativo do Krayin (Flatpickr) --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.data_audiencia')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="datetime"
                                    name="data_audiencia"
                                    id="field_data_audiencia"
                                    :value="old('data_audiencia', $processo->data_audiencia ? \Carbon\Carbon::parse($processo->data_audiencia)->format('Y-m-d H:i:s') : '')"
                                    :label="trans('lawfirm::app.processos.form.data_audiencia')"
                                />
                                <x-admin::form.control-group.error control-name="data_audiencia" />
                            </x-admin::form.control-group>
                        </div>

                        {{-- Observações --}}
                        <x-admin::form.control-group class="flex-1">
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.desc')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="textarea" name="descricao"
                                class="min-h-[120px]" rows="15"
                                :value="old('descricao', $processo->descricao)"
                                :label="trans('lawfirm::app.processos.form.desc')"
                                placeholder="Informe aqui suas observações" />
                            <x-admin::form.control-group.error control-name="descricao" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                {{-- ── ROW 2: DETALHES DO PROCESSO (full width) ── --}}
                <div class="lf-card rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between pb-3 mb-5 border-b border-gray-100 dark:border-gray-800">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight">Detalhes do Processo</p>
                        {{-- Botão de preenchimento automático via dados do Escavador --}}
                        <button type="button"
                            onclick="LFSyncFromEscavador()"
                            class="secondary-button text-xs flex items-center gap-1 bg-primary-50 text-primary-700 border-primary-200 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:border-primary-800">
                            📥 Preencher com Dados Oficiais
                        </button>
                    </div>

                    {{-- CNJ + Protocolo (2 colunas) --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.cnj')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="numero_cnj"
                                :value="old('numero_cnj', $processo->numero_cnj)"
                                :label="trans('lawfirm::app.processos.form.cnj')"
                                :placeholder="trans('lawfirm::app.processos.form.cnj')" />
                            <x-admin::form.control-group.error control-name="numero_cnj" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>Protocolo de Distribuição</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="protocolo_distribuicao"
                                :value="old('protocolo_distribuicao', $processo->protocolo_distribuicao)"
                                label="Protocolo de Distribuição"
                                placeholder="Caso não tenha ATSumCNJ ainda" />
                            <x-admin::form.control-group.error control-name="protocolo_distribuicao" />
                        </x-admin::form.control-group>
                    </div>

                    {{-- Área do Direito + Fase Processual (2 colunas) --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.area')
                                <span class="text-xs text-gray-400 ml-1 font-normal">(Ex: Indenização por Dano Moral)</span>
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text"
                                name="area_direito"
                                id="field_area_direito"
                                :value="old('area_direito', $processo->area_direito)"
                                :label="trans('lawfirm::app.processos.form.area')"
                                placeholder="Ex: Cível, Trabalhista, Consumidor, Familiar..." />
                            <x-admin::form.control-group.error control-name="area_direito" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.fase')
                                <span class="text-xs text-gray-400 ml-1 font-normal">(Ex: Procedimento Comum Cível)</span>
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text"
                                name="fase_processual"
                                id="field_fase_processual"
                                :value="old('fase_processual', $processo->fase_processual)"
                                :label="trans('lawfirm::app.processos.form.fase')"
                                placeholder="Ex: Inicial, Instrução, Procedimento Comum Cível..." />
                            <x-admin::form.control-group.error control-name="fase_processual" />
                        </x-admin::form.control-group>
                    </div>

                    {{-- Tribunal + Comarca + Vara (3 colunas) --}}
                    <div class="grid grid-cols-3 gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.tribunal')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="tribunal"
                                id="field_tribunal"
                                :value="old('tribunal', $processo->tribunal)"
                                :label="trans('lawfirm::app.processos.form.tribunal')"
                                :placeholder="trans('lawfirm::app.processos.form.tribunal')" />
                            <x-admin::form.control-group.error control-name="tribunal" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.comarca')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="comarca"
                                id="field_comarca"
                                :value="old('comarca', $processo->comarca)"
                                :label="trans('lawfirm::app.processos.form.comarca')"
                                :placeholder="trans('lawfirm::app.processos.form.comarca')" />
                            <x-admin::form.control-group.error control-name="comarca" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.vara')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="vara"
                                id="field_vara"
                                :value="old('vara', $processo->vara)"
                                :label="trans('lawfirm::app.processos.form.vara')"
                                :placeholder="trans('lawfirm::app.processos.form.placeholder-vara')" />
                            <x-admin::form.control-group.error control-name="vara" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                {{-- ── ROW 3: ESTRATÉGICO + PARTE CONTRÁRIA ── --}}
                <div class="grid grid-cols-2 gap-6 max-lg:grid-cols-1">

                    {{-- Card: Dados Estratégicos --}}
                    <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Dados Estratégicos</p>

                        <div class="grid grid-cols-2 gap-4">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.valor')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text" name="valor_causa"
                                    id="field_valor_causa"
                                    :value="old('valor_causa', number_format($processo->valor_causa, 2, ',', '.'))"
                                    :label="trans('lawfirm::app.processos.form.valor')"
                                    placeholder="R$ 0,00" />
                                <x-admin::form.control-group.error control-name="valor_causa" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.probabilidade')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select" name="probabilidade_exito"
                                    :value="old('probabilidade_exito', $processo->probabilidade_exito)"
                                    :label="trans('lawfirm::app.processos.form.probabilidade')">
                                    <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                    @foreach(['Alta', 'Muito Alta', 'Média', 'Baixa', 'Muito Baixa'] as $prob)
                                        <option value="{{ $prob }}" {{ old('probabilidade_exito', $processo->probabilidade_exito) == $prob ? 'selected' : '' }}>{{ $prob }}</option>
                                    @endforeach
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="probabilidade_exito" />
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    {{-- Card: Parte Contrária --}}
                    <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <p class="text-base font-semibold text-gray-800 dark:text-white tracking-tight pb-3 border-b border-gray-100 dark:border-gray-800">Parte Contrária (Oponente)</p>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>Nome / Razão Social</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="opposing_party_name"
                                :value="old('opposing_party_name', $processo->opposing_party_name ?? $processo->parte_contraria)"
                                label="Nome / Razão Social" />
                            <x-admin::form.control-group.error control-name="opposing_party_name" />
                        </x-admin::form.control-group>

                        <div class="grid grid-cols-3 gap-4">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Tipo</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="select" name="opposing_party_type"
                                    id="opposing_party_type" label="Tipo"
                                    onchange="toggleMask()">
                                    <option value="PF" {{ old('opposing_party_type', $processo->opposing_party_type) == 'PF' ? 'selected' : '' }}>PF</option>
                                    <option value="PJ" {{ old('opposing_party_type', $processo->opposing_party_type) == 'PJ' ? 'selected' : '' }}>PJ</option>
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="opposing_party_type" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="col-span-2">
                                <x-admin::form.control-group.label>CPF / CNPJ</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text" name="opposing_party_document"
                                    id="opposing_party_document"
                                    :value="old('opposing_party_document', $processo->opposing_party_document)"
                                    label="CPF / CNPJ" oninput="applyMask()" />
                                <x-admin::form.control-group.error control-name="opposing_party_document" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>

            </div>{{-- end #section-info --}}

            {{-- ── SECTION: Partes e Advogados ──────────────────────── --}}
            <div id="section-partes" class="lf-section hidden flex flex-col gap-4">

                {{-- Card: Partes e Advogados --}}
                <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                        <div>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">⚖️ Partes e Advogados</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Dados extraídos da Capa Oficial do processo. Clique em "Dados Oficiais (IA)" para importar.
                            </p>
                        </div>
                        <button type="button"
                            onclick="lfSwitchSection('escavador')"
                            class="secondary-button text-xs flex items-center gap-1 bg-primary-50 text-primary-700 border-primary-200 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:border-primary-800">
                            📥 Importar de Dados Oficiais
                        </button>
                    </div>
                    <x-admin::form.control-group class="mt-4">
                        <x-admin::form.control-group.control
                            type="textarea"
                            name="envolvidos_escavador"
                            id="field_envolvidos_escavador"
                            class="font-mono text-sm min-h-[200px]"
                            rows="8"
                            :value="old('envolvidos_escavador', $processo->envolvidos_escavador)"
                            label="Partes e Advogados"
                            placeholder="Os dados serão preenchidos automaticamente ao clicar em 'Importar de Dados Oficiais'..." />
                        <x-admin::form.control-group.error control-name="envolvidos_escavador" />
                    </x-admin::form.control-group>
                </div>

            </div>{{-- end #section-partes --}}

            </div>{{-- end flex wrapper --}}

        </x-admin::form>

        {{-- Whatsapp Modals --}}
        @include('lawfirm::admin.processos.modals.whatsapp-import-modal')
        @include('lawfirm::admin.processos.modals.whatsapp-history-modal')
    </div>

    @push('scripts')
        <style>
            /* ╔══════════════════════════════════════════════════════════════╗
               ║  PROCESSOS — UX HARMONIZAÇÃO  (edit)                        ║
               ╚══════════════════════════════════════════════════════════════╝ */

            /* — Section spacing override (ensures gap-6 is applied) ––––––– */
            #section-info,
            #section-partes,
            #section-prazos,
            #section-notas,
            #section-financeiro,
            #section-docs,
            #section-modelos,
            #section-escavador { gap: 1.5rem; }

            /* — Partes section: breathing room from the filter bar ––––––––– */
            #section-partes:not(.hidden) { margin-top: 0; }

            /* — Card field-group spacing (breathing room between inputs) ––– */
            .lf-card .control-group,
            .lf-card [class*="control-group"] { margin-bottom: 0; }

            /* — Field label refinement –––––––––––––––––––––––––––––––––––– */
            .lf-card label {
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.025em;
                text-transform: uppercase;
                color: #6b7280;
            }
            .dark .lf-card label { color: #9ca3af; }

            /* — Form input refinement ––––––––––––––––––––––––––––––––––––– */
            .lf-card input:not([type="checkbox"]):not([type="radio"]),
            .lf-card select,
            .lf-card textarea {
                border-radius: 0.5rem;
                font-size: 0.875rem;
            }

            /* — Card shadow on hover (Tailwind hover:shadow-md complement) — */
            .lf-card {
                transition: box-shadow 200ms ease, border-color 200ms ease;
            }
            .lf-card:hover { border-color: #e5e7eb; }
            .dark .lf-card:hover { border-color: #374151; }

            /* — Filter bar polish ––––––––––––––––––––––––––––––––––––––––– */
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
            window.lfToggleEscavadorTab = function() { lfSwitchSection('escavador'); };
            window.addEventListener('DOMContentLoaded', function() {
                const saved = localStorage.getItem('lf_processo_section_{{ $processo->id }}') || 'info';
                if (saved === 'todos') { lfShowAll(); } else { lfSwitchSection(saved); }
            });
        </script>
        <script>
            window.appendExternalTabs = function(event, form) {
                // Prevent duplicate appending
                if (form.dataset.appended === 'true') return;

                // Find external inputs (notas and prazos)
                const containerNotas = document.getElementById('container-notas');
                const tbodyPrazos = document.getElementById('container-prazos');

                // Append Notas
                if (containerNotas) {
                    const notasInputs = containerNotas.querySelectorAll('input[name^="notas"], textarea[name^="notas"], select[name^="notas"]');
                    notasInputs.forEach(input => {
                        const clone = input.cloneNode(true);
                        clone.style.display = 'none';
                        // Copy values for textareas and selects manually as cloneNode does not always preserve dynamic values
                        clone.value = input.value;
                        if (input.type === 'checkbox' || input.type === 'radio') clone.checked = input.checked;
                        form.appendChild(clone);
                    });
                }

                // Append Prazos
                if (tbodyPrazos) {
                    const prazoInputs = tbodyPrazos.querySelectorAll('input[name^="prazos"], textarea[name^="prazos"], select[name^="prazos"]');
                    prazoInputs.forEach(input => {
                        const clone = input.cloneNode(true);
                        clone.style.display = 'none';
                        clone.value = input.value;
                        if (input.type === 'checkbox' || input.type === 'radio') clone.checked = input.checked;
                        form.appendChild(clone);
                    });
                }

                form.dataset.appended = 'true';
            };

            window.lfToggleEscavadorTab = function() {
                const tab = document.getElementById('escavador-tab-content');
                if (tab) {
                    tab.classList.toggle('hidden');
                }
            };

            /**
             * Preenche campos do formulário com dados cacheados do Escavador.
             * Os dados vêm do EscavadorTab.lastData carregado pela aba do Escavador.
             */
            window.LFSyncFromEscavador = function() {
                // EscavadorTab.lastData é populado pelo escavador-tab.blade.php via loadDetails()
                const esc = (typeof EscavadorTab !== 'undefined' && EscavadorTab.lastData)
                    ? EscavadorTab.lastData
                    : null;

                if (!esc) {
                    alert('Dados da base oficial não carregados ainda.\n\nAbra a aba \"Dados Oficiais (IA)\" e sincronize o processo primeiro.');
                    return;
                }

                // — Tribunal
                const fieldTribunal = document.getElementById('field_tribunal');
                if (fieldTribunal && esc.tribunal) {
                    fieldTribunal.value = esc.tribunal;
                    fieldTribunal.dispatchEvent(new Event('input'));
                }

                // — Vara
                const fieldVara = document.getElementById('field_vara');
                if (fieldVara && esc.vara) {
                    fieldVara.value = esc.vara;
                    fieldVara.dispatchEvent(new Event('input'));
                }

                // — Comarca (extraída do início da vara, se possível)
                const fieldComarca = document.getElementById('field_comarca');
                if (fieldComarca && esc.vara) {
                    // Heurística simples: pegar a fração após "Foro" ou usar a vara como comarca
                    const match = esc.vara.match(/Foro(?:\s+Regional)?\s+(?:de\s+)?(.+?)(?:\s+-|\s*$)/i);
                    fieldComarca.value = match ? match[1].trim() : (esc.vara || '');
                    fieldComarca.dispatchEvent(new Event('input'));
                }

                // — Área do Direito (assunto principal do processo)
                const fieldArea = document.getElementById('field_area_direito');
                if (fieldArea && esc.assunto_principal) {
                    fieldArea.value = esc.assunto_principal;
                    fieldArea.dispatchEvent(new Event('input'));
                }

                // — Fase Processual (classe CNJ do processo)
                const fieldFase = document.getElementById('field_fase_processual');
                if (fieldFase && esc.classe_principal) {
                    fieldFase.value = esc.classe_principal;
                    fieldFase.dispatchEvent(new Event('input'));
                }

                // — Valor da Causa
                const fieldValor = document.getElementById('field_valor_causa');
                if (fieldValor && esc.valor_causa) {
                    const valorFormatted = parseFloat(esc.valor_causa).toFixed(2).replace('.', ',');
                    fieldValor.value = valorFormatted;
                    fieldValor.dispatchEvent(new Event('input'));
                }

                // — Partes e Advogados Importados
                const fieldEnvolvidos = document.getElementById('field_envolvidos_escavador');
                if (fieldEnvolvidos && esc.envolvidos && esc.envolvidos.length > 0) {
                    const linhas = esc.envolvidos.map(env => {
                        const partes = [];
                        partes.push(`${env.tipo_participacao || 'Parte'}: ${env.nome || '-'}`);
                        if (env.cpf_cnpj)    partes.push(`  Doc: ${env.cpf_cnpj}`);
                        if (env.oab)         partes.push(`  OAB: ${env.oab}`);
                        return partes.join('\n');
                    });
                    fieldEnvolvidos.value = linhas.join('\n\n');
                    fieldEnvolvidos.dispatchEvent(new Event('input'));
                }

                // Feedback visual
                const btn = document.querySelector('[onclick="LFSyncFromEscavador()"]');
                if (btn) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '✅ Campos preenchidos!';
                    btn.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                    setTimeout(() => { btn.innerHTML = original; btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-200'); }, 3000);
                }
            };
        </script>
        <script>
            function maskCPF(v) {
                return v.replace(/\D/g,'').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})/,'$1-$2').replace(/(-\d{2})\d+?$/,'$1');
            }
            function maskCNPJ(v) {
                return v.replace(/\D/g,'').replace(/(\d{2})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1/$2').replace(/(\d{4})(\d)/,'$1-$2').replace(/(-\d{2})\d+?$/,'$1');
            }
            function applyMask() {
                const type = document.getElementById('opposing_party_type').value;
                const input = document.getElementById('opposing_party_document');
                input.value = type === 'PF' ? maskCPF(input.value) : maskCNPJ(input.value);
                input.maxLength = type === 'PF' ? 14 : 18;
            }
            function toggleMask() { document.getElementById('opposing_party_document').value = ''; }
            window.addEventListener('load', function () { applyMask(); });
        </script>

        {{-- ── Caso AJAX Selector Logic ────────────────────────── --}}
        <script>
            (function() {
                const searchInput = document.getElementById('lf-caso-search');
                const hiddenInput = document.getElementById('lf-caso-id');
                const resultsBox = document.getElementById('lf-caso-results');
                const selectedBox = document.getElementById('lf-caso-selected');
                const selectedLabel = document.getElementById('lf-caso-selected-label');
                const searchUrl = "{{ route('admin.processos.search_caso') }}";
                let debounceTimer = null;

                // If already selected, hide search input
                if (hiddenInput.value) {
                    searchInput.classList.add('hidden');
                }

                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();
                    if (query.length < 2) { resultsBox.classList.add('hidden'); return; }

                    debounceTimer = setTimeout(function() {
                        fetch(searchUrl + '?query=' + encodeURIComponent(query))
                            .then(r => r.json())
                            .then(data => {
                                const items = data.data || data;
                                if (!items.length) {
                                    resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-gray-400 italic">Nenhum caso encontrado</div>';
                                } else {
                                    resultsBox.innerHTML = items.map(c =>
                                        `<div class="lf-caso-item px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                              data-id="${c.id}" data-titulo="${c.titulo}">
                                            <span class="font-medium">#${c.id}</span> — ${c.titulo}
                                            ${c.area ? '<span class="text-xs text-gray-400 ml-1">(' + c.area + ')</span>' : ''}
                                        </div>`
                                    ).join('');
                                }
                                resultsBox.classList.remove('hidden');
                                resultsBox.querySelectorAll('.lf-caso-item').forEach(el => {
                                    el.addEventListener('click', function() {
                                        lfSelectCaso(this.dataset.id, this.dataset.titulo);
                                    });
                                });
                            })
                            .catch(() => { resultsBox.classList.add('hidden'); });
                    }, 300);
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('#lf-caso-selector-wrapper')) {
                        resultsBox.classList.add('hidden');
                    }
                });

                window.lfSelectCaso = function(id, titulo) {
                    hiddenInput.value = id;
                    selectedLabel.textContent = '#' + id + ' — ' + titulo;
                    selectedBox.classList.remove('hidden');
                    searchInput.value = '';
                    searchInput.classList.add('hidden');
                    resultsBox.classList.add('hidden');
                };

                window.lfClearCaso = function() {
                    hiddenInput.value = '';
                    selectedBox.classList.add('hidden');
                    searchInput.classList.remove('hidden');
                    searchInput.value = '';
                    searchInput.focus();
                };
            })();
        </script>
    @endpush

</x-admin::layouts>