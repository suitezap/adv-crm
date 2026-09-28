<x-admin::layouts>
    <x-slot:title>
        Novo Caso
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">📂 Novo Caso</div>
            </div>
            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.lawfirm.casos.index') }}" class="transparent-button">← Voltar</a>
            </div>
        </div>

        <!-- Form -->
        <x-admin::form
            method="POST"
            :action="route('admin.lawfirm.casos.store')"
        >
            <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                <h3 class="text-lg font-semibold tracking-tight border-b pb-3 dark:text-white">
                    📋 Dados do Caso
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Título -->
                    <x-admin::form.control-group class="md:col-span-2">
                        <x-admin::form.control-group.label class="required">Título do Caso</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            name="titulo"
                            :value="old('titulo')"
                            rules="required"
                            label="Título do Caso"
                            placeholder="Ex: Revisão de Contrato de Locação"
                        />
                        <x-admin::form.control-group.error control-name="titulo" />
                    </x-admin::form.control-group>

                    <!-- Área -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Área do Direito</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="area"
                            :value="old('area')"
                            label="Área do Direito"
                        >
                            <option value="">— Selecione —</option>
                            <option value="Administrativo">Administrativo</option>
                            <option value="Ambiental">Ambiental</option>
                            <option value="Bancário">Bancário</option>
                            <option value="Consumidor">Consumidor</option>
                            <option value="Cível">Cível</option>
                            <option value="Digital / LGPD">Digital / LGPD</option>
                            <option value="Empresarial">Empresarial</option>
                            <option value="Família">Família</option>
                            <option value="Imobiliário">Imobiliário</option>
                            <option value="Penal">Penal</option>
                            <option value="Previdenciário">Previdenciário</option>
                            <option value="Trabalhista">Trabalhista</option>
                            <option value="Tributário">Tributário</option>
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="area" />
                    </x-admin::form.control-group>

                    <!-- Status -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Status</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="status"
                            :value="old('status', 'Novo Caso')"
                            label="Status"
                        >
                            @foreach(\SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES as $s)
                                <option value="{{ $s }}" {{ old('status', 'Novo Caso') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>


                    <!-- Prioridade -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Prioridade</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="prioridade"
                            :value="old('prioridade')"
                            label="Prioridade"
                        >
                            <option value="">— Selecione —</option>
                            <option value="baixa">Baixa</option>
                            <option value="media">Média</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="prioridade" />
                    </x-admin::form.control-group>

                    <!-- Responsável -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Responsável</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="user_id"
                            :value="old('user_id', auth()->id())"
                            label="Responsável"
                        >
                            <option value="">— Selecione —</option>
                            @foreach (\Webkul\User\Models\User::where('status', 1)->get() as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="user_id" />
                    </x-admin::form.control-group>

                    <!-- Pessoa (PF) - Lookup -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Cliente (Pessoa Física)</x-admin::form.control-group.label>

                        <div class="relative" id="lf-person-wrapper">
                            <input type="text" id="lf-person-search" autocomplete="off" placeholder="Buscar cliente..." class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" />
                            <input type="hidden" name="person_id" id="lf-person-id" value="" />
                            <div id="lf-person-results" class="absolute top-full z-10 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-900 dark:bg-gray-800 max-h-40 overflow-y-auto"></div>
                        </div>
                        <div id="lf-person-selected" class="mt-1 hidden">
                            <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                👤 <span id="lf-person-selected-label"></span>
                                <button type="button" onclick="lfClearPerson()" class="ml-1 text-blue-400 hover:text-red-500">&times;</button>
                            </span>
                        </div>
                        <x-admin::form.control-group.error control-name="person_id" />
                    </x-admin::form.control-group>

                    <!-- Organização (PJ) - Lookup -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Cliente (Pessoa Jurídica) — Opcional</x-admin::form.control-group.label>

                        <div class="relative" id="lf-org-wrapper">
                            <input type="text" id="lf-org-search" autocomplete="off" placeholder="Buscar empresa..." class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" />
                            <input type="hidden" name="organization_id" id="lf-org-id" value="" />
                            <div id="lf-org-results" class="absolute top-full z-10 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-900 dark:bg-gray-800 max-h-40 overflow-y-auto"></div>
                        </div>
                        <div id="lf-org-selected" class="mt-1 hidden">
                            <span class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                🏢 <span id="lf-org-selected-label"></span>
                                <button type="button" onclick="lfClearOrg()" class="ml-1 text-green-400 hover:text-red-500">&times;</button>
                            </span>
                        </div>
                        <x-admin::form.control-group.error control-name="organization_id" />
                    </x-admin::form.control-group>

                    <!-- Descrição -->
                    <x-admin::form.control-group class="md:col-span-2">
                        <x-admin::form.control-group.label>Descrição</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="textarea"
                            name="descricao"
                            :value="old('descricao')"
                            label="Descrição"
                            rows="4"
                            placeholder="Descreva o caso e suas particularidades..."
                        />
                        <x-admin::form.control-group.error control-name="descricao" />
                    </x-admin::form.control-group>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 border-t pt-4">
                    <a href="{{ route('admin.lawfirm.casos.index') }}" class="transparent-button">Cancelar</a>
                    @if (bouncer()->hasPermission('lawfirm.casos.create'))
                        <button type="submit" class="primary-button">💾 Criar Caso</button>
                    @endif
                </div>
            </div>
        </x-admin::form>
    </div>

    @push('scripts')
        <script>
            // --- Vanilla JS Lookup Logic (Event Delegation — works after Vue mounts) ---
            let lfDebouncePerson = null;
            let lfDebounceOrg = null;

            document.addEventListener('input', function(e) {
                // Pessoa Search
                if (e.target && e.target.id === 'lf-person-search') {
                    clearTimeout(lfDebouncePerson);
                    const q = e.target.value.trim();
                    const resultsBox = document.getElementById('lf-person-results');
                    if (q.length < 2) { resultsBox.classList.add('hidden'); return; }

                    lfDebouncePerson = setTimeout(function() {
                        fetch("{{ route('admin.processos.search_person') }}?query=" + encodeURIComponent(q))
                            .then(r => r.json())
                            .then(data => {
                                const items = data.data || data;
                                if (!items.length) {
                                    resultsBox.innerHTML = '<div class="px-4 py-2 text-sm text-gray-400 italic">Nenhuma pessoa encontrada</div>';
                                } else {
                                    resultsBox.innerHTML = items.map(p => {
                                        const nameEscaped = p.name ? p.name.replace(/'/g, "\\'") : '';
                                        return `<div class="lf-person-item cursor-pointer px-4 py-2 text-sm text-gray-800 hover:bg-blue-50 dark:text-white dark:hover:bg-gray-900 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                              onclick="lfSelectPerson('${p.id}', '${nameEscaped}')">${p.name}</div>`;
                                    }).join('');
                                }
                                resultsBox.classList.remove('hidden');
                            })
                            .catch(() => resultsBox.classList.add('hidden'));
                    }, 300);
                }

                // Empresa Search
                if (e.target && e.target.id === 'lf-org-search') {
                    clearTimeout(lfDebounceOrg);
                    const q = e.target.value.trim();
                    const resultsBox = document.getElementById('lf-org-results');
                    if (q.length < 2) { resultsBox.classList.add('hidden'); return; }

                    lfDebounceOrg = setTimeout(function() {
                        fetch("{{ route('admin.processos.search_organization') }}?query=" + encodeURIComponent(q))
                            .then(r => r.json())
                            .then(data => {
                                const items = data.data || data;
                                if (!items.length) {
                                    resultsBox.innerHTML = '<div class="px-4 py-2 text-sm text-gray-400 italic">Nenhuma empresa encontrada</div>';
                                } else {
                                    resultsBox.innerHTML = items.map(o => {
                                        const nameEscaped = o.name ? o.name.replace(/'/g, "\\'") : '';
                                        return `<div class="lf-org-item cursor-pointer px-4 py-2 text-sm text-gray-800 hover:bg-green-50 dark:text-white dark:hover:bg-gray-900 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                              onclick="lfSelectOrg('${o.id}', '${nameEscaped}')">${o.name}</div>`;
                                    }).join('');
                                }
                                resultsBox.classList.remove('hidden');
                            })
                            .catch(() => resultsBox.classList.add('hidden'));
                    }, 300);
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target && ['lf-person-search', 'lf-org-search'].includes(e.target.id)) {
                    e.preventDefault();
                }
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#lf-person-wrapper') && !e.target.closest('#lf-person-selected')) {
                    const r = document.getElementById('lf-person-results'); if(r) r.classList.add('hidden');
                }
                if (!e.target.closest('#lf-org-wrapper') && !e.target.closest('#lf-org-selected')) {
                    const r = document.getElementById('lf-org-results'); if(r) r.classList.add('hidden');
                }
            });

            window.lfSelectPerson = function(id, name) {
                document.getElementById('lf-person-id').value = id;
                document.getElementById('lf-person-selected-label').textContent = name;
                document.getElementById('lf-person-selected').classList.remove('hidden');
                const s = document.getElementById('lf-person-search'); s.value = ''; s.classList.add('hidden');
                document.getElementById('lf-person-results').classList.add('hidden');
            };
            window.lfClearPerson = function() {
                document.getElementById('lf-person-id').value = '';
                document.getElementById('lf-person-selected').classList.add('hidden');
                const s = document.getElementById('lf-person-search'); s.classList.remove('hidden'); s.value = ''; s.focus();
            };

            window.lfSelectOrg = function(id, name) {
                document.getElementById('lf-org-id').value = id;
                document.getElementById('lf-org-selected-label').textContent = name;
                document.getElementById('lf-org-selected').classList.remove('hidden');
                const s = document.getElementById('lf-org-search'); s.value = ''; s.classList.add('hidden');
                document.getElementById('lf-org-results').classList.add('hidden');
            };
            window.lfClearOrg = function() {
                document.getElementById('lf-org-id').value = '';
                document.getElementById('lf-org-selected').classList.add('hidden');
                const s = document.getElementById('lf-org-search'); s.classList.remove('hidden'); s.value = ''; s.focus();
            };
        </script>
    @endpush
</x-admin::layouts>
