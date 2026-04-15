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
                <button type="submit" form="processo-form" class="primary-button">
                    @lang('lawfirm::app.processos.save-btn')
                </button>
                <a href="{{ route('admin.processos.show', $processo->id) }}" class="secondary-button text-xs">
                    Visualizar
                </a>
            </div>
        </div>

        {{-- ── TABS: Prazos, Notas, Financeiro, Documentos ────────── --}}
        <div class="flex flex-col gap-4">
            @include('lawfirm::Legal.processos.tabs.prazos')
            @include('lawfirm::Legal.processos.tabs.notas', ['processo' => $processo, 'startClosed' => true])
            @include('lawfirm::Financial.processos.tabs.financial', ['startClosed' => true])
            @include('lawfirm::GED.processos.tabs.documents', ['processo' => $processo])
        </div>

        {{-- ── MAIN FORM ───────────────────────────────────── --}}
        <x-admin::form id="processo-form" :action="route('admin.processos.update', $processo->id)" method="PUT" enctype="multipart/form-data" onsubmit="window.appendExternalTabs(event, this)">
            <div class="flex flex-col gap-4">

                {{-- ── ROW 1: INÍCIO + DATAS ──────────────── --}}
                <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                    {{-- Card: Informações Básicas --}}
                    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Informações Básicas</p>

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

                        {{-- Pessoa (Cliente) --}}
                        @php
                            $personId = old('person_id') ?? optional($processo->person)->id;
                            $personLookupData = app('Webkul\Attribute\Repositories\AttributeRepository')->getLookUpEntity('persons', $personId);
                        @endphp
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('lawfirm::app.processos.form.person')
                            </x-admin::form.control-group.label>
                            <x-admin::lookup
                                src="{{ route('admin.contacts.persons.search') }}"
                                name="person_id"
                                v-bind:value="{{ json_encode($personLookupData) }}"
                                :placeholder="trans('lawfirm::app.processos.form.search-client')" />
                            <x-admin::form.control-group.error control-name="person_id" />
                        </x-admin::form.control-group>

                        {{-- Status --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('lawfirm::app.processos.form.status')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="select" name="status" rules="required"
                                :value="old('status', $processo->status)"
                                :label="trans('lawfirm::app.processos.form.status')">
                                @foreach(['Ativo', 'Suspenso', 'Arquivado', 'Encerrado'] as $s)
                                    <option value="{{ $s }}" {{ old('status', $processo->status) == $s ? 'selected' : '' }}>
                                        {{ trans('lawfirm::app.processos.status-options.' . strtolower($s)) }}
                                    </option>
                                @endforeach
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

                        {{-- WhatsApp do Advogado Responsável (Robô Agendador) --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                📱 WhatsApp do Advogado Responsável
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text"
                                name="whatsapp_responsavel"
                                :value="old('whatsapp_responsavel', $processo->whatsapp_responsavel)"
                                label="WhatsApp do Advogado Responsável"
                                placeholder="(99) 99999-9999" />
                            <x-admin::form.control-group.error control-name="whatsapp_responsavel" />
                            <p class="text-xs text-gray-400 mt-1">Usado pelo Robô Agendador para envio de lembretes de prazo.</p>
                        </x-admin::form.control-group>
                    </div>

                    {{-- Card: Datas e Observações --}}
                    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Datas e Observações</p>

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

                            {{-- Data Audiência --}}
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.data_audiencia')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="datetime" name="data_audiencia"
                                    :value="old('data_audiencia', $processo->data_audiencia)"
                                    :label="trans('lawfirm::app.processos.form.data_audiencia')" />
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
                                class="min-h-[120px]" rows="5"
                                :value="old('descricao', $processo->descricao)"
                                :label="trans('lawfirm::app.processos.form.desc')"
                                placeholder="Informe aqui suas observações" />
                            <x-admin::form.control-group.error control-name="descricao" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                {{-- ── ROW 2: DETALHES DO PROCESSO (full width) ── --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="mb-4 text-lg font-bold text-gray-800 dark:text-white">Detalhes do Processo</p>

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

                    <div class="grid grid-cols-5 gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.area')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="select" name="area_direito"
                                :label="trans('lawfirm::app.processos.form.area')">
                                <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                @foreach(['Civil', 'Trabalhista', 'Penal', 'Tributário', 'Família', 'Consumidor', 'Previdenciário'] as $area)
                                    <option value="{{ $area }}" {{ old('area_direito', $processo->area_direito) == $area ? 'selected' : '' }}>{{ $area }}</option>
                                @endforeach
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error control-name="area_direito" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.fase')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="select" name="fase_processual"
                                :label="trans('lawfirm::app.processos.form.fase')">
                                <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                @foreach(['Inicial', 'Contestação', 'Réplica', 'Instrução', 'Julgamento', 'Sentença', 'Recurso', 'Execução'] as $fase)
                                    <option value="{{ $fase }}" {{ old('fase_processual', $processo->fase_processual) == $fase ? 'selected' : '' }}>{{ $fase }}</option>
                                @endforeach
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error control-name="fase_processual" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.tribunal')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="text" name="tribunal"
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
                                :value="old('vara', $processo->vara)"
                                :label="trans('lawfirm::app.processos.form.vara')"
                                :placeholder="trans('lawfirm::app.processos.form.placeholder-vara')" />
                            <x-admin::form.control-group.error control-name="vara" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                {{-- ── ROW 3: ESTRATÉGICO + PARTE CONTRÁRIA ── --}}
                <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">

                    {{-- Card: Dados Estratégicos --}}
                    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Dados Estratégicos</p>

                        <div class="grid grid-cols-2 gap-4">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('lawfirm::app.processos.form.valor')
                                </x-admin::form.control-group.label>
                                <x-admin::form.control-group.control
                                    type="text" name="valor_causa"
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
                    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">Parte Contrária (Oponente)</p>

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

            </div>
        </x-admin::form>
    </div>

    @push('scripts')
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
    @endpush

</x-admin::layouts>