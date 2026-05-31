<x-admin::layouts>
    <x-slot:title>
        @lang('lawfirm::app.processos.create-title')
        </x-slot>

        @inject('userRepository', 'Webkul\User\Repositories\UserRepository')

        <x-admin::form id="processo-form" :action="route('admin.processos.store')" enctype="multipart/form-data"
            onsubmit="window.appendExternalTabs(event, this)">
            <div class="flex flex-col gap-4">

                {{-- ── HEADER ──────────────────────────────────── --}}
                <div
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    <div class="flex flex-col gap-2">
                        <div class="flex cursor-pointer items-center gap-2">
                            <x-admin::breadcrumbs name="lawfirm.processos.create" />
                        </div>
                    </div>
                    <div class="flex items-center gap-x-2.5">
                        <button type="submit" class="primary-button">
                            @lang('lawfirm::app.processos.save-btn')
                        </button>
                    </div>
                </div>

                {{-- ── ROW 1: INÍCIO + DATAS ──────────────────── --}}
                <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                    {{-- Card: Iniciando Processo --}}
                    <div
                        class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Iniciando Processo</p>

                        {{-- Titulo --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('lawfirm::app.processos.form.titulo')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="titulo" rules="required"
                                :value="old('titulo')" :label="trans('lawfirm::app.processos.form.titulo')"
                                :placeholder="trans('lawfirm::app.processos.form.titulo')" />
                            <x-admin::form.control-group.error control-name="titulo" />
                        </x-admin::form.control-group>

                        {{-- Trigger v-lookup-component registration --}}
                        <x-admin::attributes.edit.lookup />

                        {{-- Cliente e Empresa --}}
                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">

                            {{-- Pessoa (Cliente) --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.person') (Opcional)
                                </x-admin::form.control-group.label>
                                <v-lookup-component
                                    :attribute="{{ json_encode(['code' => 'person_id', 'name' => 'Pessoa', 'lookup_type' => 'persons']) }}"
                                    :value="null"
                                    validations=""
                                ></v-lookup-component>
                                <x-admin::form.control-group.error control-name="person_id" />
                            </x-admin::form.control-group>

                            {{-- Empresa --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Empresa (Opcional)
                                </x-admin::form.control-group.label>
                                <v-lookup-component
                                    :attribute="{{ json_encode(['code' => 'organization_id', 'name' => 'Empresa', 'lookup_type' => 'organizations']) }}"
                                    :value="null"
                                    validations=""
                                ></v-lookup-component>
                                <x-admin::form.control-group.error control-name="organization_id" />
                            </x-admin::form.control-group>

                        </div>

                        {{-- Status e Responsável --}}
                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                            {{-- Status --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('lawfirm::app.processos.form.status')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="select" name="status" rules="required"
                                    :label="trans('lawfirm::app.processos.form.status')">
                                    @foreach(\SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES as $s)
                                        <option value="{{ $s }}" {{ old('status', 'Novo Caso') == $s ? 'selected' : '' }}>
                                            {{ $s }}
                                        </option>
                                    @endforeach
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="status" />
                            </x-admin::form.control-group>


                            {{-- Responsável Interno --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    Responsável Interno
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="select" name="user_id"
                                    label="Responsável Interno"
                                    :value="old('user_id', auth()->id())">
                                    <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                    @foreach($userRepository->all() as $user)
                                        <option value="{{ $user->id }}" {{ (int)old('user_id', auth()->id()) === (int)$user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="user_id" />
                            </x-admin::form.control-group>
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
                                <input type="hidden" name="caso_id" id="lf-caso-id" value="{{ old('caso_id', request('caso_id')) }}" />
                                <div id="lf-caso-results" class="absolute z-50 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 max-h-48 overflow-y-auto"></div>
                            </div>
                            <div id="lf-caso-selected" class="mt-1 hidden">
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    📂 <span id="lf-caso-selected-label"></span>
                                    <button type="button" onclick="lfClearCaso()" class="ml-1 text-blue-400 hover:text-red-500">&times;</button>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Datas e Observações --}}
                    <div
                        class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Datas e Observações</p>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Data Distribuição --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.data_distribuicao')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="date" name="data_distribuicao"
                                    :value="old('data_distribuicao')"
                                    :label="trans('lawfirm::app.processos.form.data_distribuicao')" />
                                <x-admin::form.control-group.error control-name="data_distribuicao" />
                            </x-admin::form.control-group>

                            {{-- Data Audiência --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.data_audiencia')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="datetime" name="data_audiencia"
                                    :value="old('data_audiencia')"
                                    :label="trans('lawfirm::app.processos.form.data_audiencia')" />
                                <x-admin::form.control-group.error control-name="data_audiencia" />
                            </x-admin::form.control-group>
                        </div>

                        {{-- Observações --}}
                        <x-admin::form.control-group class="flex-1">
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.desc')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="textarea" name="descricao" class="min-h-[120px]"
                                rows="5" :value="old('descricao')" :label="trans('lawfirm::app.processos.form.desc')"
                                placeholder="Informe aqui suas observações" />
                            <x-admin::form.control-group.error control-name="descricao" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                {{-- ── ROW 2: DETALHES DO PROCESSO (full width) ─ --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="mb-4 text-lg font-bold text-gray-800 dark:text-white">Detalhes do Processo</p>

                    {{-- Row: CNJ + Protocolo --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.cnj')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="numero_cnj"
                                :value="old('numero_cnj')" :label="trans('lawfirm::app.processos.form.cnj')"
                                :placeholder="trans('lawfirm::app.processos.form.cnj')" />
                            <x-admin::form.control-group.error control-name="numero_cnj" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                Protocolo de Distribuição
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="protocolo_distribuicao"
                                :value="old('protocolo_distribuicao')" label="Protocolo de Distribuição"
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
                                :value="old('area_direito')"
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
                                :value="old('fase_processual')"
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
                                :value="old('tribunal')"
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
                                :value="old('comarca')"
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
                                :value="old('vara')"
                                :label="trans('lawfirm::app.processos.form.vara')"
                                :placeholder="trans('lawfirm::app.processos.form.placeholder-vara')" />
                            <x-admin::form.control-group.error control-name="vara" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                {{-- ── ROW 3: ESTRATÉGICO + PARTE CONTRÁRIA ───── --}}
                <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                    {{-- Card: Dados Estratégicos --}}
                    <div
                        class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Dados Estratégicos</p>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Valor da Causa --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.valor')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="text" name="valor_causa"
                                    :value="old('valor_causa')" :label="trans('lawfirm::app.processos.form.valor')"
                                    placeholder="R$ 0,00" />
                                <x-admin::form.control-group.error control-name="valor_causa" />
                            </x-admin::form.control-group>

                            {{-- Probabilidade --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.probabilidade')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="select" name="probabilidade_exito"
                                    :label="trans('lawfirm::app.processos.form.probabilidade')">
                                    <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                    @foreach(['Alta', 'Muito Alta', 'Média', 'Baixa', 'Muito Baixa'] as $prob)
                                        <option value="{{ $prob }}" {{ old('probabilidade_exito') == $prob ? 'selected' : '' }}>{{ $prob }}</option>
                                    @endforeach
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="probabilidade_exito" />
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    {{-- Card: Parte Contrária --}}
                    <div
                        class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Parte Contrária (Oponente)</p>

                        {{-- Nome / Razão Social --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>Nome / Razão Social</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="opposing_party_name"
                                :value="old('opposing_party_name')" label="Nome / Razão Social" />
                            <x-admin::form.control-group.error control-name="opposing_party_name" />
                        </x-admin::form.control-group>

                        <div class="grid grid-cols-3 gap-4">
                            {{-- Tipo --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Tipo</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="select" name="opposing_party_type"
                                    id="opposing_party_type" label="Tipo" onchange="toggleMask()">
                                    <option value="PF" {{ old('opposing_party_type') == 'PF' ? 'selected' : '' }}>PF
                                    </option>
                                    <option value="PJ" {{ old('opposing_party_type') == 'PJ' ? 'selected' : '' }}>PJ
                                    </option>
                                </x-admin::form.control-group.control>
                                <x-admin::form.control-group.error control-name="opposing_party_type" />
                            </x-admin::form.control-group>

                            {{-- CPF / CNPJ --}}
                            <x-admin::form.control-group class="col-span-2">
                                <x-admin::form.control-group.label>CPF / CNPJ</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="text" name="opposing_party_document"
                                    id="opposing_party_document" :value="old('opposing_party_document')"
                                    label="CPF / CNPJ" oninput="applyMask()" />
                                <x-admin::form.control-group.error control-name="opposing_party_document" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>

            </div>
        </x-admin::form>

        @push('scripts')
            <script>
                window.appendExternalTabs = function (event, form) {
                    if (form.dataset.appended === 'true') return;

                    const containerNotas = document.getElementById('container-notas');
                    const tbodyPrazos = document.getElementById('container-prazos');

                    if (containerNotas) {
                        const notasInputs = containerNotas.querySelectorAll('input[name^="notas"], textarea[name^="notas"], select[name^="notas"]');
                        notasInputs.forEach(input => {
                            const clone = input.cloneNode(true);
                            clone.style.display = 'none';
                            clone.value = input.value;
                            form.appendChild(clone);
                        });
                    }

                    if (tbodyPrazos) {
                        const prazoInputs = tbodyPrazos.querySelectorAll('input[name^="prazos"], textarea[name^="prazos"], select[name^="prazos"]');
                        prazoInputs.forEach(input => {
                            const clone = input.cloneNode(true);
                            clone.style.display = 'none';
                            clone.value = input.value;
                            form.appendChild(clone);
                        });
                    }

                    form.dataset.appended = 'true';
                };
            </script>
            <script>
                function maskCPF(v) {
                    return v.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})/, '$1-$2').replace(/(-\d{2})\d+?$/, '$1');
                }
                function maskCNPJ(v) {
                    return v.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d)/, '$1-$2').replace(/(-\d{2})\d+?$/, '$1');
                }
                function applyMask() {
                    const type = document.getElementById('opposing_party_type').value;
                    const input = document.getElementById('opposing_party_document');
                    input.value = type === 'PF' ? maskCPF(input.value) : maskCNPJ(input.value);
                    input.maxLength = type === 'PF' ? 14 : 18;
                }
                function toggleMask() { document.getElementById('opposing_party_document').value = ''; }
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

                    // Debounced search on keyup
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

                                    // Bind click events
                                    resultsBox.querySelectorAll('.lf-caso-item').forEach(el => {
                                        el.addEventListener('click', function() {
                                            lfSelectCaso(this.dataset.id, this.dataset.titulo);
                                        });
                                    });
                                })
                                .catch(() => { resultsBox.classList.add('hidden'); });
                        }, 300);
                    });

                    // Close dropdown on outside click
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('#lf-caso-selector-wrapper')) {
                            resultsBox.classList.add('hidden');
                        }
                    });

                    // Select a caso
                    window.lfSelectCaso = function(id, titulo) {
                        hiddenInput.value = id;
                        selectedLabel.textContent = '#' + id + ' — ' + titulo;
                        selectedBox.classList.remove('hidden');
                        searchInput.value = '';
                        searchInput.classList.add('hidden');
                        resultsBox.classList.add('hidden');
                    };

                    // Clear selection
                    window.lfClearCaso = function() {
                        hiddenInput.value = '';
                        selectedBox.classList.add('hidden');
                        searchInput.classList.remove('hidden');
                        searchInput.value = '';
                        searchInput.focus();
                    };

                    // Pre-load if caso_id is set (from query param or old input)
                    const presetId = hiddenInput.value;
                    if (presetId) {
                        fetch(searchUrl + '?query=')
                            .then(r => r.json())
                            .then(data => {
                                const items = data.data || data;
                                const match = items.find(c => String(c.id) === String(presetId));
                                if (match) lfSelectCaso(match.id, match.titulo);
                            })
                            .catch(() => {});
                    }
                })();
            </script>
        @endpush

</x-admin::layouts>