@php
    $personLookup = optional($caso->person)->id
        ? app('Webkul\Attribute\Repositories\AttributeRepository')
            ->getLookUpEntity('persons', $caso->person->id)
        : null;

    $orgLookup = optional($caso->organization)->id
        ? app('Webkul\Attribute\Repositories\AttributeRepository')
            ->getLookUpEntity('organizations', $caso->organization->id)
        : null;
@endphp

<x-admin::layouts>
    <x-slot:title>
        Editar Caso — {{ $caso->titulo }}
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">📂 Editar Caso #{{ $caso->id }}</div>
            </div>
            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.lawfirm.casos.show', $caso->id) }}" class="transparent-button">👁 Visualizar</a>
                <a href="{{ route('admin.lawfirm.casos.index') }}" class="transparent-button">← Voltar</a>
            </div>
        </div>

        {{-- ── FILTER BAR (harmonized with Processos edit pattern) ─────── --}}
        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
            <button type="button" class="lf-filter-btn" data-section="todos" onclick="lfShowAll()">&#10697; Todos</button>
            <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
            <button type="button" class="lf-filter-btn" data-section="dados" onclick="lfSwitchSection('dados')">&#128203; Dados do Caso</button>
            <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
            <button type="button" class="lf-filter-btn" data-section="processos" onclick="lfSwitchSection('processos')">&#9878;&#65039; Processos Vinculados</button>
            <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
            <button type="button" class="lf-filter-btn" data-section="financeiro" onclick="lfSwitchSection('financeiro')">&#128176; Resumo Financeiro</button>
        </div>

        <!-- Form -->
        <x-admin::form
            method="PUT"
            :action="route('admin.lawfirm.casos.update', $caso->id)"
        >
            <!-- Section: Dados do Caso -->
            <div id="section-dados" class="lf-section lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4" data-section="dados">
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
                            :value="old('titulo', $caso->titulo)"
                            rules="required"
                            label="Título do Caso"
                        />
                        <x-admin::form.control-group.error control-name="titulo" />
                    </x-admin::form.control-group>

                    <!-- Área -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Área do Direito</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="area"
                            :value="old('area', $caso->area)"
                            label="Área do Direito"
                        >
                            <option value="">— Selecione —</option>
                            @foreach (['Administrativo', 'Ambiental', 'Bancário', 'Consumidor', 'Cível', 'Digital / LGPD', 'Empresarial', 'Família', 'Imobiliário', 'Penal', 'Previdenciário', 'Trabalhista', 'Tributário'] as $a)
                                <option value="{{ $a }}" {{ old('area', $caso->area) == $a ? 'selected' : '' }}>{{ $a }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <!-- Status -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Status</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="status" :value="old('status', $caso->status)" label="Status">
                            @foreach(\SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES as $s)
                                <option value="{{ $s }}" {{ old('status', $caso->status) == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                            {{-- Legacy fallback for old status values --}}
                            @if($caso->status && !in_array($caso->status, \SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES))
                                <option value="{{ $caso->status }}" selected>(legado) {{ $caso->status }}</option>
                            @endif
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>


                    <!-- Prioridade -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Prioridade</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="prioridade" :value="old('prioridade', $caso->prioridade)" label="Prioridade">
                            <option value="">— Selecione —</option>
                            @foreach (['Baixa', 'Média', 'Alta', 'Crítica'] as $prio)
                                <option value="{{ $prio }}" {{ old('prioridade', $caso->prioridade) == $prio ? 'selected' : '' }}>{{ $prio }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <!-- Responsável -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Responsável</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="user_id" :value="old('user_id', $caso->user_id)" label="Responsável">
                            <option value="">— Selecione —</option>
                            @foreach (\Webkul\User\Models\User::where('status', 1)->get() as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', $caso->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <!-- Pessoa (PF) - Lookup -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Cliente (Pessoa Física)</x-admin::form.control-group.label>
                        <x-admin::attributes.edit.lookup />
                        <v-lookup-component
                            :attribute="{{ json_encode(['code' => 'person_id', 'name' => 'Pessoa', 'lookup_type' => 'persons']) }}"
                            :value="{{ json_encode($personLookup) }}"
                            validations=""
                        ></v-lookup-component>
                    </x-admin::form.control-group>

                    <!-- Organização (PJ) - Lookup -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Cliente (Pessoa Jurídica)</x-admin::form.control-group.label>
                        <v-lookup-component
                            :attribute="{{ json_encode(['code' => 'organization_id', 'name' => 'Empresa', 'lookup_type' => 'organizations']) }}"
                            :value="{{ json_encode($orgLookup) }}"
                            validations=""
                        ></v-lookup-component>
                    </x-admin::form.control-group>

                    <!-- Descrição -->
                    <x-admin::form.control-group class="md:col-span-2">
                        <x-admin::form.control-group.label>Descrição</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="textarea"
                            name="descricao"
                            :value="old('descricao', $caso->descricao)"
                            label="Descrição"
                            rows="15"
                        />
                    </x-admin::form.control-group>
                </div>

                <div class="flex justify-end gap-3 border-t pt-4">
                    <button type="submit" class="primary-button">💾 Salvar Caso</button>
                </div>
            </div>
        </x-admin::form>

        <!-- Section: Processos Vinculados -->
        <div id="section-processos" class="lf-section lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4" data-section="processos">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-lg font-semibold tracking-tight dark:text-white">
                    ⚖️ Processos Vinculados (<span id="lf-processos-count">{{ $caso->processos->count() }}</span>)
                </h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('lf-link-processo-card').classList.toggle('hidden')"
                        class="secondary-button text-sm flex items-center gap-1">
                        <i class="icon-search text-base"></i> Vincular Processo
                    </button>
                    <a href="{{ route('admin.processos.create') }}?caso_id={{ $caso->id }}" class="primary-button text-sm flex items-center gap-1">
                        + Criar Processo
                    </a>
                </div>
            </div>

            {{-- Search card for linking existing processos --}}
            <div id="lf-link-processo-card" class="hidden rounded-lg border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-800 dark:bg-blue-900/10">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    🔍 Buscar processo existente para vincular a este caso
                </p>
                <div class="relative">
                    <input type="text" id="lf-link-processo-search"
                        placeholder="Digite o título, Nº CNJ ou ID do processo..."
                        autocomplete="off"
                        oninput="lfSearchProcesso(this.value)"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" />
                    <div id="lf-link-processo-results"
                        class="absolute z-50 mt-1 hidden w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800 max-h-56 overflow-y-auto">
                    </div>
                </div>
                <p id="lf-link-processo-feedback" class="mt-2 text-xs text-green-600 dark:text-green-400 hidden"></p>
            </div>

            {{-- Table --}}
            @if ($caso->processos->isEmpty())
                <p id="lf-processos-empty" class="text-gray-500 dark:text-gray-400 text-sm italic">Nenhum processo vinculado a este caso.</p>
            @endif
            <div class="overflow-x-auto" id="lf-processos-table-wrapper" style="{{ $caso->processos->isEmpty() ? 'display:none' : '' }}">
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
                    <tbody id="lf-processos-tbody">
                        @foreach ($caso->processos as $processo)
                            <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" id="lf-processo-row-{{ $processo->id }}">
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
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.processos.edit', $processo->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar →</a>
                                        <button type="button" onclick="lfUnlinkProcesso({{ $processo->id }})"
                                            class="text-xs text-gray-400 hover:text-red-600 transition-colors" title="Desvincular">
                                            <i class="icon-delete text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Resumo Financeiro -->
        <div id="section-financeiro" class="lf-section lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4" data-section="financeiro">
            <h3 class="text-lg font-semibold tracking-tight border-b pb-3 dark:text-white">
                💰 Resumo Financeiro Consolidado
            </h3>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Processos</div>
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $kpis['processos_count'] }}</div>
                </div>
                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Receitas</div>
                    <div class="text-2xl font-bold text-green-700 dark:text-green-300">R$ {{ number_format($kpis['receita_total'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Despesas</div>
                    <div class="text-2xl font-bold text-red-700 dark:text-red-300">R$ {{ number_format($kpis['despesas_totais'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg {{ $kpis['lucro_liquido'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-orange-50 dark:bg-orange-900/20' }} p-4 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Lucro Líquido</div>
                    <div class="text-2xl font-bold {{ $kpis['lucro_liquido'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-orange-700 dark:text-orange-300' }}">
                        R$ {{ number_format($kpis['lucro_liquido'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <style>
            /* ╔══════════════════════════════════════════════════════════════╗
               ║  CASOS — UX HARMONIZAÇÃO (edit) — same as Processos        ║
               ╚══════════════════════════════════════════════════════════════╝ */

            /* — Section spacing override ––––––––––––––––––––––––––––––––– */
            #section-dados,
            #section-processos,
            #section-financeiro { gap: 1.5rem; }

            /* — Card field-group spacing ––––––––––––––––––––––––––––––––– */
            .lf-card .control-group,
            .lf-card [class*="control-group"] { margin-bottom: 0; }

            /* — Field label refinement –––––––––––––––––––––––––––––––––– */
            .lf-card label {
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.025em;
                text-transform: uppercase;
                color: #6b7280;
            }
            .dark .lf-card label { color: #9ca3af; }

            /* — Form input refinement –––––––––––––––––––––––––––––––––––– */
            .lf-card input:not([type="checkbox"]):not([type="radio"]),
            .lf-card select,
            .lf-card textarea {
                border-radius: 0.5rem;
                font-size: 0.875rem;
            }

            /* — Card shadow on hover ––––––––––––––––––––––––––––––––––––– */
            .lf-card {
                transition: box-shadow 200ms ease, border-color 200ms ease;
            }
            .lf-card:hover { border-color: #e5e7eb; }
            .dark .lf-card:hover { border-color: #374151; }

            /* — Filter bar polish (identical to Processos) ––––––––––––––– */
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
            // Filter bar logic (identical to Processos edit pattern)
            window.lfSwitchSection = function(name) {
                document.querySelectorAll('.lf-section').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.lf-filter-btn').forEach(el => el.classList.remove('active'));
                const target = document.getElementById('section-' + name);
                if (target) target.classList.remove('hidden');
                const btn = document.querySelector('[data-section="' + name + '"]');
                if (btn) btn.classList.add('active');
                localStorage.setItem('lf_caso_section_{{ $caso->id }}', name);
            };
            window.lfShowAll = function() {
                document.querySelectorAll('.lf-section').forEach(el => el.classList.remove('hidden'));
                document.querySelectorAll('.lf-filter-btn').forEach(el => el.classList.remove('active'));
                const btn = document.querySelector('[data-section="todos"]');
                if (btn) btn.classList.add('active');
                localStorage.setItem('lf_caso_section_{{ $caso->id }}', 'todos');
            };
            window.addEventListener('DOMContentLoaded', function() {
                const saved = localStorage.getItem('lf_caso_section_{{ $caso->id }}') || 'dados';
                if (saved === 'todos') { lfShowAll(); } else { lfSwitchSection(saved); }
            });
        </script>
        <script>
            // ── Vincular Processo — AJAX Search & Link Logic ─────────────
            // Uses global functions + inline oninput/onclick to survive Vue app.mount('#app')
            var _lfSearchTimer = null;
            var _lfSearchUrl   = "{{ route('admin.lawfirm.casos.search_processo') }}";
            var _lfLinkUrl     = "{{ route('admin.lawfirm.casos.link_processo', $caso->id) }}";
            var _lfCasoId      = {{ $caso->id }};
            var _lfCsrfToken    = '{{ csrf_token() }}';

            function _lfGetCsrf() {
                return _lfCsrfToken;
            }

            // Debounced search triggered by oninput
            window.lfSearchProcesso = function(value) {
                clearTimeout(_lfSearchTimer);
                var resultsBox = document.getElementById('lf-link-processo-results');
                var query = value.trim();
                if (query.length < 2) { resultsBox.classList.add('hidden'); return; }

                _lfSearchTimer = setTimeout(function() {
                    fetch(_lfSearchUrl + '?query=' + encodeURIComponent(query) + '&caso_id=' + _lfCasoId)
                        .then(function(r) { return r.json(); })
                        .then(function(items) {
                            if (!items.length) {
                                resultsBox.innerHTML = '<div class="px-3 py-3 text-xs text-gray-400 italic text-center">Nenhum processo encontrado</div>';
                            } else {
                                resultsBox.innerHTML = items.map(function(p) {
                                    var titleSafe = (p.titulo || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                                    var casoWarning = p.caso_id ? '<span class="text-xs text-orange-500">⚠ Já vinculado ao Caso #' + p.caso_id + '</span>' : '';
                                    var cnjInfo = p.numero_cnj ? '<span class="text-xs text-gray-400">CNJ: ' + p.numero_cnj + '</span>' : '';
                                    return '<div class="flex items-center justify-between px-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer" onclick="lfDoLinkProcesso(' + p.id + ', \'' + titleSafe + '\', \'' + (p.numero_cnj || '') + '\', \'' + (p.status || '') + '\')">' +
                                        '<div class="flex flex-col min-w-0 flex-1">' +
                                            '<span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">' +
                                                '<span class="font-mono text-xs text-gray-400">#' + p.id + '</span> — ' + p.titulo +
                                            '</span>' + cnjInfo + casoWarning +
                                        '</div>' +
                                        '<span class="ml-2 px-3 py-1 text-xs font-semibold rounded" style="background:#2563eb;color:#fff;">Vincular</span>' +
                                    '</div>';
                                }).join('');
                            }
                            resultsBox.classList.remove('hidden');
                        })
                        .catch(function() {
                            resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-red-500">Erro na busca</div>';
                            resultsBox.classList.remove('hidden');
                        });
                }, 300);
            };

            // Link processo to caso
            window.lfDoLinkProcesso = function(processoId, titulo, cnj, status) {
                var token = _lfGetCsrf();
                if (!token) { alert('Token CSRF inválido.'); return; }

                fetch(_lfLinkUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify({ processo_id: processoId }),
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('lf-processos-tbody');
                    var editUrl = "{{ route('admin.processos.edit', 0) }}".replace('/0', '/' + processoId);
                    var statusClass = (status || '').toLowerCase() === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';

                    var tr = document.createElement('tr');
                    tr.id = 'lf-processo-row-' + processoId;
                    tr.className = 'border-b hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors';
                    tr.innerHTML =
                        '<td class="px-3 py-2 font-mono text-xs">#' + processoId + '</td>' +
                        '<td class="px-3 py-2 font-medium">' + titulo + '</td>' +
                        '<td class="px-3 py-2 text-gray-600 dark:text-gray-400">' + (cnj || '—') + '</td>' +
                        '<td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs font-semibold ' + statusClass + '">' + (status || '—') + '</span></td>' +
                        '<td class="px-3 py-2 text-gray-600 dark:text-gray-400">—</td>' +
                        '<td class="px-3 py-2 text-right"><div class="flex items-center justify-end gap-2">' +
                            '<a href="' + editUrl + '" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Editar →</a>' +
                            '<button type="button" onclick="lfUnlinkProcesso(' + processoId + ')" class="text-xs text-gray-400 hover:text-red-600 transition-colors" title="Desvincular"><i class="icon-delete text-base"></i></button>' +
                        '</div></td>';
                    tbody.appendChild(tr);

                    document.getElementById('lf-processos-table-wrapper').style.display = '';
                    var emptyMsg = document.getElementById('lf-processos-empty');
                    if (emptyMsg) emptyMsg.style.display = 'none';

                    var counter = document.getElementById('lf-processos-count');
                    counter.textContent = parseInt(counter.textContent) + 1;

                    var feedback = document.getElementById('lf-link-processo-feedback');
                    feedback.textContent = '✅ ' + data.message;
                    feedback.classList.remove('hidden');
                    setTimeout(function() { feedback.classList.add('hidden'); }, 3000);

                    document.getElementById('lf-link-processo-search').value = '';
                    document.getElementById('lf-link-processo-results').classList.add('hidden');
                })
                .catch(function() { alert('Erro ao vincular processo.'); });
            };

            // Unlink processo from caso
            window.lfUnlinkProcesso = function(processoId) {
                if (!confirm('Desvincular este processo do caso?')) return;
                var token = _lfGetCsrf();
                var unlinkUrl = "{{ route('admin.lawfirm.casos.unlink_processo', [$caso->id, 0]) }}".replace('/0', '/' + processoId);

                fetch(unlinkUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var row = document.getElementById('lf-processo-row-' + processoId);
                    if (row) row.remove();
                    var counter = document.getElementById('lf-processos-count');
                    var newCount = Math.max(0, parseInt(counter.textContent) - 1);
                    counter.textContent = newCount;
                    if (newCount === 0) {
                        document.getElementById('lf-processos-table-wrapper').style.display = 'none';
                        var emptyMsg = document.getElementById('lf-processos-empty');
                        if (emptyMsg) emptyMsg.style.display = '';
                    }
                })
                .catch(function() { alert('Erro ao desvincular processo.'); });
            };

            // Close results on click outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#lf-link-processo-card')) {
                    var resultsBox = document.getElementById('lf-link-processo-results');
                    if (resultsBox) resultsBox.classList.add('hidden');
                }
            });
        </script>
    @endpush
</x-admin::layouts>

