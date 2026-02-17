{{-- CHECKLIST COMPONENT - Multi-Area Support - REFACTORED 2026-02-16 --}}
@php
    use SuiteZap\LawFirm\Legal\Models\CaseChecklist;
    use SuiteZap\LawFirm\Legal\Services\ChecklistTemplates;

    // Determine Context (Lead or Processo)
    $entityId = $id ?? ($lead->id ?? ($processo->id ?? 0));
    $leadId = $entityId; // Alias for template compatibility (used in HTML ids/onclick)
    $context = isset($processo) ? 'processo' : 'lead'; // Default to lead if not specified

    $brandColor = core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF';

    // 1. Determine Access
    if ($context === 'processo') {
        $isWon = true; // Processo is always "won" context
    } else {
        $isWon = optional($lead->stage)->code === 'won';
    }

    // 2. Fetch Existing Checklist
    $repo = app(\SuiteZap\LawFirm\Legal\Repositories\ChecklistRepository::class);
    if ($context === 'processo') {
        $checklist = $repo->getByProcessoId($entityId);
    } else {
        $checklist = $repo->getByLeadId($entityId);
    }

    // 3. Prepare Config
    if ($checklist) {
        // Existing Checklist
        $status = $checklist->status;
        $currentStep = $checklist->current_step;
        $steps = ChecklistTemplates::getTemplate($checklist->type);
        $stepData = $checklist->step_data ?? [];
        $availableTypes = []; // Not needed for existing
    } else {
        // New Checklist
        $status = 'new';
        $currentStep = 1;
        $steps = [];
        $stepData = [];
        $availableTypes = ChecklistTemplates::getAvailableTypes();
    }

    $initialConfig = [
        'entityId' => $entityId,
        'context' => $context,
        'isWon' => $isWon,
        'status' => $status,
        'currentStep' => $currentStep,
        'steps' => $steps,
        'stepData' => $stepData,
        'availableTypes' => $availableTypes,
        'endpoints' => [
            'save' => route('lawfirm.checklist.save', $entityId) . '?context=' . $context,
            'init' => route('lawfirm.checklist.init', $entityId) . '?context=' . $context,
            'validate' => route('lawfirm.checklist.validate', $entityId) . '?context=' . $context,
        ]
    ];
@endphp

{{-- MAIN CONTAINER --}}
<div id="lf-checklist-root-{{ $entityId }}" x-data="lawfirmChecklistApp(@json($initialConfig))"
    class="lf-checklist-wrapper relative z-10 w-full">

    {{-- ERROR ALERT --}}
    <div x-show="errorMessage && errorMessage.trim().length > 0" x-cloak
        class="mb-4 rounded-lg bg-red-100 p-4 text-sm text-red-700 dark:bg-red-900 dark:text-red-100">
        <span x-text="errorMessage"></span>
    </div>

    {{-- LOADING SPINNER --}}
    <div x-show="isLoading"
        class="absolute inset-0 z-50 flex items-center justify-center rounded-lg bg-white/70 dark:bg-gray-900/70">
        <!-- Krayin Spinner / Simple SVG -->
        <svg class="h-10 w-10 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
    </div>

    {{-- PRE-SCREENING PANEL (For Leads NOT WON) --}}
    <div x-show="!isWon" x-transition.opacity.duration.500ms>
        <div class="card bg-light border-0">
            <div class="card-body text-center p-4">

                <div class="mb-4">
                    <span style="font-size: 32px;">🛠️</span>
                    <h4 class="mt-2 text-primary font-weight-bold">Pré-Triagem & Negociação</h4>
                    <p class="text-muted">
                        O Lead ainda não foi ganho. Utilize estas ferramentas de apoio para qualificar o cliente.
                    </p>
                </div>

                {{-- Bootstrap Grid for Action Cards --}}
                <div class="row justify-content-center">

                    {{-- Card 1: IA Analysis --}}
                    <div class="col-md-4 mb-3">
                        <div class="card action-card h-100 p-3 shadow-sm"
                            style="cursor: pointer; border: 1px solid #e0e0e0; transition: all 0.2s;"
                            onclick="alert('IA em desenvolvimento')">
                            <div style="font-size: 24px; margin-bottom: 10px;">🧠</div>
                            <h6 style="font-weight: bold;">Análise de Viabilidade</h6>
                            <p class="text-muted small mb-2">Avaliar risco da causa com IA</p>
                            <button class="btn btn-sm btn-outline-primary mt-auto">Executar Análise</button>
                        </div>
                    </div>

                    {{-- Card 2: Proposal --}}
                    <div class="col-md-4 mb-3">
                        <div class="card action-card h-100 p-3 shadow-sm"
                            style="cursor: pointer; border: 1px solid #e0e0e0; transition: all 0.2s;"
                            onclick="alert('Gerador de Proposta em desenvolvimento')">
                            <div style="font-size: 24px; margin-bottom: 10px;">📄</div>
                            <h6 style="font-weight: bold;">Gerador de Proposta</h6>
                            <p class="text-muted small mb-2">Minuta de Honorários</p>
                            <button class="btn btn-sm btn-outline-success mt-auto">Criar Documento</button>
                        </div>
                    </div>

                    {{-- Card 3: Script --}}
                    <div class="col-md-4 mb-3">
                        <div class="card action-card h-100 p-3 shadow-sm"
                            style="cursor: pointer; border: 1px solid #e0e0e0; transition: all 0.2s;"
                            onclick="alert('Script em desenvolvimento')">
                            <div style="font-size: 24px; margin-bottom: 10px;">💬</div>
                            <h6 style="font-weight: bold;">Script de Vendas</h6>
                            <p class="text-muted small mb-2">Roteiro de Perguntas</p>
                            <button class="btn btn-sm btn-outline-info mt-auto">Ver Roteiro</button>
                        </div>
                    </div>

                </div>

                <div class="mt-3 pt-3 border-top">
                    <small class="text-danger font-weight-bold">
                        ⚠️ Marque este Lead como GANHO no Pipeline para iniciar o Processo Judicial.
                    </small>
                </div>

            </div>
        </div>
    </div>

    {{-- STANDARD CHECKLIST CONTENT (Wrapped for visibility control) --}}
    <div x-show="isWon" x-transition.opacity.duration.500ms style="position: relative;">

        {{-- AREA SELECTION (for new leads) --}}
        <div id="lf-area-selection-{{ $leadId }}" class="lf-hidden p-3">
            <div class="text-center mb-4">
                <h4 class="font-weight-bold">Selecione a Área de Atuação</h4>
                <p class="text-muted small">Escolha o tipo de checklist para inicializar.</p>
            </div>

            <div id="lf-area-cards-{{ $leadId }}" class="row justify-content-center">
                {{-- Cards will be injected by JS --}}
            </div>
        </div>

        {{-- STEPPER CONTENT (for existing/initialized leads) --}}
        <div id="lf-content-{{ $leadId }}" class="lf-hidden">

            <div class="card shadow-sm border">

                {{-- HEADER --}}
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center gap-2">
                        <strong id="lf-step-title-header-{{ $leadId }}" class="text-dark">...</strong>
                    </div>
                    <div>
                        <span id="lf-status-badge-{{ $leadId }}" class="badge badge-secondary">Rascunho</span>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="progress" style="height: 4px; border-radius: 0;">
                    <div id="lf-progress-fill-{{ $leadId }}" class="progress-bar" role="progressbar"
                        style="width: 0%; background-color: {{ $brandColor }};"></div>
                </div>

                {{-- STEPPER NAVIGATION --}}
                <div class="card-body p-2 border-bottom overflow-auto">
                    <div id="lf-stepper-{{ $leadId }}" class="d-flex align-items-center gap-3"
                        style="min-width: max-content; padding-bottom: 5px;">
                        {{-- JS Injected --}}
                    </div>
                </div>

                {{-- STEP BODY --}}
                <div id="lf-body-{{ $leadId }}" class="card-body p-4">
                    {{-- JS Injected --}}
                </div>

                {{-- FOOTER --}}
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">

                    {{-- Left --}}
                    <div>
                        <button type="button" id="lf-btn-prev-{{ $leadId }}" onclick="window.lfPrev{{ $leadId }}()"
                            class="btn btn-sm btn-outline-secondary lf-hidden">
                            &larr; Voltar
                        </button>

                        <button type="button" id="lf-btn-reset-{{ $leadId }}" onclick="window.lfReset{{ $leadId }}()"
                            class="btn btn-sm btn-outline-danger lf-hidden">
                            &#x21BA; Reabrir
                        </button>
                    </div>

                    {{-- Right --}}
                    <div class="d-flex gap-2">
                        <button type="button" id="lf-btn-skip-{{ $leadId }}" onclick="window.lfSkip{{ $leadId }}()"
                            class="btn btn-sm btn-link text-muted" style="text-decoration: none;">
                            Pular
                        </button>

                        <button type="button" onclick="window.lfAi{{ $leadId }}()" class="btn btn-sm btn-outline-info">
                            ✨ IA
                        </button>

                        <button type="button" id="lf-btn-next-{{ $leadId }}" onclick="window.lfNext{{ $leadId }}()"
                            class="btn btn-sm text-white" style="background-color: {{ $brandColor }};">
                            Avançar &rarr;
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

@push('styles')
    <style>
        .lf-hidden {
            display: none !important;
        }

        .lf-checklist-wrapper .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
        }

        /* Stepper Circles */
        .lf-checklist-wrapper .lf-step-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            background-color: #e9ecef;
            color: #495057;
        }

        .lf-checklist-wrapper .lf-step-btn.active {
            transform: scale(1.1);
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            'use strict';

            var entityId = {{ $entityId }};
            var token = '{{ csrf_token() }}';
            var dbColor = '{{ $brandColor }}';

            // Global State Variables
            var steps = [];
            var availableTypes = [];
            var state = {
                step: 1,
                status: 'draft',
                done: [],
                data: {},
                isNew: true
            };
            var endpoints = {};

            var c = function (id) {
                return document.getElementById(id);
            };

            // Alpine Component Definition
            window.lawfirmChecklistApp = function (config) {
                return {
                    isLoading: false,
                    isWon: config.isWon,
                    entityId: config.entityId,
                    errorMessage: '',

                    init() {
                        console.log('LawFirm: Alpine Initialized');

                        // Initialize globals from config
                        endpoints = config.endpoints;
                        availableTypes = config.availableTypes || [];
                        steps = config.steps || [];

                        // Set specific state
                        if (config.status === 'new') {
                            state.isNew = true;
                            if (this.isWon) renderAreaSelection();
                        } else {
                            state.isNew = false;
                            state.step = config.currentStep || 1;
                            state.status = config.status || 'draft';

                            // Reconstruct 'done' array
                            state.done = [];
                            if (state.status === 'completed') {
                                for (var i = 1; i <= steps.length; i++) state.done.push(i);
                            } else {
                                for (var i = 1; i < state.step; i++) state.done.push(i);
                            }

                            initializeStateData();

                            // Merge saved data
                            if (config.stepData) {
                                Object.keys(config.stepData).forEach(function (k) {
                                    if (state.data[k]) Object.assign(state.data[k], config.stepData[k]);
                                });
                            }

                            if (this.isWon) renderChecklist();
                        }
                    }
                }
            };

            // Helper functions
            function initializeStateData() {
                steps.forEach(function (s) {
                    state.data[s.id] = {};
                    s.fields.forEach(function (f) {
                        state.data[s.id][f.key] = f.type === 'checkbox' ? false : '';
                    });
                });
            }

            function renderAreaSelection() {
                var areaEl = c('lf-area-selection-' + entityId);
                var contentEl = c('lf-content-' + entityId);

                if (contentEl) contentEl.classList.add('hidden');
                if (areaEl) areaEl.classList.remove('hidden');

                var cardsHtml = '';
                availableTypes.forEach(function (type) {
                    cardsHtml += '<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer dark:border-gray-800 dark:bg-gray-900" onclick="window.lfInitArea' + entityId + '(\'' + type.key + '\')">';
                    cardsHtml += '<div class="text-3xl mb-2 text-center">' + type.icon + '</div>';
                    cardsHtml += '<h6 class="font-bold text-center text-gray-800 dark:text-gray-200">' + type.label + '</h6>';
                    cardsHtml += '<p class="text-sm text-gray-500 text-center">' + type.description + '</p>';
                    cardsHtml += '</div>';
                });
                c('lf-area-cards-' + entityId).innerHTML = cardsHtml;
            }

            function renderChecklist() {
                var areaEl = c('lf-area-selection-' + entityId);
                var contentEl = c('lf-content-' + entityId);

                if (areaEl) areaEl.classList.add('hidden');
                if (contentEl) contentEl.classList.remove('hidden');

                if (steps.length === 0) return;

                var totalSteps = steps.length;
                var s = steps.find(function (x) { return x.id === state.step; });
                if (!s) s = steps[0];

                c('lf-step-title-header-' + entityId).textContent = s.id + '. ' + s.title;

                var pct = Math.round((state.done.length / totalSteps) * 100);
                var pBar = c('lf-progress-fill-' + entityId);
                if (pBar) {
                    pBar.style.width = pct + '%';
                    pBar.style.backgroundColor = dbColor;
                }

                var badge = c('lf-status-badge-' + entityId);
                if (badge) {
                    badge.className = 'px-2 py-1 text-xs font-semibold rounded-full';
                    if (state.status === 'completed') {
                        badge.textContent = 'Concluído';
                        badge.classList.add('bg-green-100', 'text-green-800');
                    } else if (state.status === 'draft') {
                        badge.textContent = 'Rascunho';
                        badge.classList.add('bg-yellow-100', 'text-yellow-800');
                    } else {
                        badge.textContent = 'Em Andamento';
                        badge.classList.add('bg-blue-100', 'text-blue-800');
                    }
                }

                var stH = '';
                steps.forEach(function (x) {
                    var isDone = state.done.includes(x.id);
                    var isAct = (x.id === state.step);
                    var displayText = x.shortTitle || x.title;

                    stH += '<div onclick="window.lfGoTo' + entityId + '(' + x.id + ')" class="text-center cursor-pointer">';
                    var bg = isDone ? dbColor : (isAct ? dbColor : '#e5e7eb');
                    var txtCol = (isDone || isAct) ? '#fff' : '#374151';

                    stH += '<div class="lf-step-btn ' + (isAct ? 'active ring-2 ring-offset-1 ring-blue-500' : '') + '" style="background-color: ' + bg + '; color: ' + txtCol + ';">';
                    stH += isDone ? '✓' : x.id;
                    stH += '</div>';

                    stH += '<small class="block mt-1 text-[10px] leading-tight ' + (isAct ? 'font-bold text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400') + '">' + displayText + '</small>';
                    stH += '</div>';
                });
                c('lf-stepper-' + entityId).innerHTML = stH;

                var bdH = '';
                var flds = s.fields || [];
                flds.forEach(function (f) {
                    var fid = 'lf-field-' + entityId + '-' + s.id + '-' + f.key;
                    var v = state.data[s.id] ? state.data[s.id][f.key] : '';
                    bdH += '<div class="mb-4 flex flex-col gap-2">';

                    if (f.type === 'textarea') {
                        bdH += '<label for="' + fid + '" class="text-sm font-semibold text-gray-700 dark:text-gray-300">' + f.label + '</label>';
                        bdH += '<textarea id="' + fid + '" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" rows="3" onchange="window.lfUpd' + entityId + '(' + s.id + ',\'' + f.key + '\',this.value)">' + (v || '') + '</textarea>';
                    } else if (f.type === 'select') {
                        bdH += '<label for="' + fid + '" class="text-sm font-semibold text-gray-700 dark:text-gray-300">' + f.label + '</label>';
                        bdH += '<select id="' + fid + '" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" onchange="window.lfUpd' + entityId + '(' + s.id + ',\'' + f.key + '\',this.value)">';
                        bdH += '<option value="">Selecione...</option>';
                        (f.options || []).forEach(function (opt) {
                            bdH += '<option value="' + opt + '" ' + (v === opt ? ' selected' : '') + '>' + opt + '</option>';
                        });
                        bdH += '</select>';
                    } else if (f.type === 'checkbox') {
                        var chk = v ? 'checked' : '';
                        bdH += '<div class="flex items-center gap-2">';
                        bdH += '<input type="checkbox" id="' + fid + '" ' + chk + ' class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" onchange="window.lfUpd' + entityId + '(' + s.id + ',\'' + f.key + '\',this.checked)">';
                        bdH += '<label class="text-sm text-gray-700 dark:text-gray-300" for="' + fid + '">' + f.label + '</label>';
                        bdH += '</div>';
                    } else if (f.type === 'date') {
                        bdH += '<label for="' + fid + '" class="text-sm font-semibold text-gray-700 dark:text-gray-300">' + f.label + '</label>';
                        bdH += '<input type="date" id="' + fid + '" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" value="' + (v || '') + '" onchange="window.lfUpd' + entityId + '(' + s.id + ',\'' + f.key + '\',this.value)">';
                    } else {
                        bdH += '<label for="' + fid + '" class="text-sm font-semibold text-gray-700 dark:text-gray-300">' + f.label + '</label>';
                        bdH += '<input type="text" id="' + fid + '" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" value="' + (v || '') + '" onchange="window.lfUpd' + entityId + '(' + s.id + ',\'' + f.key + '\',this.value)">';
                    }
                    bdH += '</div>';
                });
                c('lf-body-' + entityId).innerHTML = bdH;

                c('lf-btn-prev-' + entityId).classList.toggle('hidden', state.step === 1);
                c('lf-btn-skip-' + entityId).classList.toggle('hidden', state.step === totalSteps || state.status === 'completed');

                var resetBtn = c('lf-btn-reset-' + entityId);
                var nextBtn = c('lf-btn-next-' + entityId);
                if (state.status === 'completed') {
                    if (resetBtn) resetBtn.classList.remove('hidden');
                    if (nextBtn) nextBtn.classList.add('hidden');
                } else {
                    if (resetBtn) resetBtn.classList.add('hidden');
                    if (nextBtn) nextBtn.classList.remove('hidden');
                    if (nextBtn) {
                        if (state.step === totalSteps) nextBtn.innerHTML = '✓ Finalizar';
                        else nextBtn.innerHTML = 'Salvar e Avançar &rarr;';
                    }
                }
            }

            function setAlpineLoading(val) {
                var el = document.getElementById('lf-checklist-root-' + entityId);
                if (el && el.__x) {
                    el.__x.$data.isLoading = val;
                }
            }

            function save(goNext) {
                setAlpineLoading(true);
                var totalSteps = steps.length;
                var pl = { step: state.step, data: state.data[state.step], completed: goNext, _token: token };
                if (goNext && !state.done.includes(state.step)) state.done.push(state.step);

                fetch(endpoints.save, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(pl)
                })
                    .then(function (r) { return r.json() })
                    .then(function (d) {
                        if (d.status === 'success') {
                            if (goNext) {
                                if (state.step < totalSteps) {
                                    state.step++;
                                    state.status = 'in_progress';
                                } else {
                                    state.status = 'completed';
                                    alert('Checklist Concluído!');
                                }
                            }
                            renderChecklist();
                        }
                    })
                    .finally(function () {
                        setAlpineLoading(false);
                    });
            }

            window['lfInitArea' + entityId] = function (type) {
                setAlpineLoading(true);
                fetch(endpoints.init, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ type: type, _token: token })
                })
                    .then(function (r) { return r.json() })
                    .then(function (d) {
                        if (d.status === 'success') {
                            state.isNew = false;
                            steps = d.steps || [];
                            state.step = 1;
                            state.status = 'draft';
                            state.done = [];
                            initializeStateData();
                            renderChecklist();
                        } else {
                            alert(d.message || 'Erro ao inicializar checklist.');
                            renderAreaSelection();
                        }
                    })
                    .catch(function () {
                        alert('Erro de conexão.');
                        renderAreaSelection();
                    })
                    .finally(function () {
                        setAlpineLoading(false);
                    });
            };

            window['lfReset' + entityId] = function () {
                if (!confirm('Deseja reabrir este checklist? O progresso será mantido, mas o status voltará para Rascunho.')) return;
                state.status = 'in_progress';
                save(false);
            };

            window['lfNext' + entityId] = function () { save(true); };
            window['lfPrev' + entityId] = function () { if (state.step > 1) { state.step--; renderChecklist(); } };
            window['lfSkip' + entityId] = function () { save(true); };

            window['lfGoTo' + entityId] = function (s) {
                if (s < state.step || state.done.includes(s)) {
                    state.step = s;
                    renderChecklist();
                } else if (s === state.step) { } else {
                    alert('Você precisa concluir o passo atual antes de avançar.');
                }
            };

            window['lfUpd' + entityId] = function (sStep, key, val) {
                if (!state.data[sStep]) state.data[sStep] = {};
                state.data[sStep][key] = val;
            };

            window['lfAi' + entityId] = function () {
                alert('Funcionalidade IA em desenvolvimento');
            };

        })();
    </script>
@endpush