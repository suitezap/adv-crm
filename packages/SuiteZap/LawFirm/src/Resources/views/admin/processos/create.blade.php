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

                        {{-- Pessoa (Cliente) --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('lawfirm::app.processos.form.person')
                            </x-admin::form.control-group.label>
                            <x-admin::lookup src="{{ route('admin.contacts.persons.search') }}" name="person_id"
                                :placeholder="trans('lawfirm::app.processos.form.search-client')" />
                            <x-admin::form.control-group.error control-name="person_id" />
                        </x-admin::form.control-group>

                        {{-- Status --}}
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('lawfirm::app.processos.form.status')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="select" name="status" rules="required"
                                :label="trans('lawfirm::app.processos.form.status')">
                                @foreach(['Ativo', 'Suspenso', 'Arquivado', 'Encerrado'] as $s)
                                    <option value="{{ $s }}" {{ old('status') == $s ? 'selected' : '' }}>
                                        {{ trans('lawfirm::app.processos.status-options.' . strtolower($s)) }}
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
                                label="Responsável Interno">
                                <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                @foreach($userRepository->all() as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error control-name="user_id" />
                        </x-admin::form.control-group>
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

                    {{-- Row: Área + Fase + Tribunal + Comarca + Vara --}}
                    <div class="grid grid-cols-5 gap-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.area')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="select" name="area_direito"
                                :label="trans('lawfirm::app.processos.form.area')">
                                <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                @foreach(['Civil', 'Trabalhista', 'Penal', 'Tributário', 'Família', 'Consumidor', 'Previdenciário'] as $area)
                                    <option value="{{ $area }}" {{ old('area_direito') == $area ? 'selected' : '' }}>
                                        {{ $area }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error control-name="area_direito" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.fase')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="select" name="fase_processual"
                                :label="trans('lawfirm::app.processos.form.fase')">
                                <option value="">@lang('lawfirm::app.processos.form.select-choose')</option>
                                @foreach(['Inicial', 'Contestação', 'Réplica', 'Instrução', 'Julgamento', 'Sentença', 'Recurso', 'Execução'] as $fase)
                                    <option value="{{ $fase }}" {{ old('fase_processual') == $fase ? 'selected' : '' }}>
                                        {{ $fase }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error control-name="fase_processual" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.tribunal')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="tribunal" :value="old('tribunal')"
                                :label="trans('lawfirm::app.processos.form.tribunal')"
                                :placeholder="trans('lawfirm::app.processos.form.tribunal')" />
                            <x-admin::form.control-group.error control-name="tribunal" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.comarca')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="comarca" :value="old('comarca')"
                                :label="trans('lawfirm::app.processos.form.comarca')"
                                :placeholder="trans('lawfirm::app.processos.form.comarca')" />
                            <x-admin::form.control-group.error control-name="comarca" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('lawfirm::app.processos.form.vara')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" name="vara" :value="old('vara')"
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
        @endpush

</x-admin::layouts>