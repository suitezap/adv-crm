@inject('templateRepo', 'SuiteZap\LawFirm\Legal\Repositories\DocumentTemplateRepository')

@php
    $templates = $templates ?? $templateRepo->forProcesso($processo);
    $allTemplates = $templateRepo->allActive();
@endphp

<div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
    <div class="flex items-center justify-between border-b pb-3">
        <div>
            <h3 class="text-base font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                📄 Modelos de Documentos
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Gere contratos, petições e declarações pré-preenchidos com os dados deste processo.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.modelos.create') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                <i class="icon-plus text-xs"></i>
                Criar Modelo
            </a>
            <a href="{{ route('admin.modelos.index') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                <i class="icon-settings text-gray-500"></i>
                Gerenciar Modelos
            </a>
        </div>
    </div>

    <!-- Seletor de Modelo Pronto -->
    <div class="flex flex-col md:flex-row gap-4 items-end justify-between bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl border border-gray-100 dark:border-gray-800/60 shadow-sm">
        <div class="flex-1 w-full relative">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                📂 Utilizar um modelo pronto
            </label>
            
            <!-- Hidden input to store selected value for useSelectedTemplate compatibility -->
            <input type="hidden" id="lf-select-ready-template" value="">
            
            <!-- Custom Searchable Dropdown Wrapper -->
            <div class="relative w-full" id="lf-searchable-select-container">
                <input type="text" 
                       id="lf-searchable-select-input" 
                       placeholder="Pesquise ou selecione um modelo pronto..." 
                       autocomplete="off"
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 pr-10 text-sm text-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="icon-arrow-down text-xs"></i>
                </span>
                
                <div id="lf-searchable-select-dropdown" 
                     class="hidden absolute left-0 right-0 z-[100] mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    
                    <div id="lf-searchable-no-results" class="hidden p-3 text-xs text-gray-500 text-center dark:text-gray-400">
                        Nenhum modelo encontrado
                    </div>
                    
                    @foreach($allTemplates->groupBy('area_direito') as $area => $group)
                        <div class="lf-searchable-group" data-group-name="{{ $area ?: 'Outras Áreas / Geral' }}">
                            <div class="bg-gray-100 dark:bg-gray-700/60 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 sticky top-0">
                                {{ $area ?: 'Outras Áreas / Geral' }}
                            </div>
                            @foreach($group as $template)
                                <div class="lf-searchable-option cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white flex items-center justify-between"
                                     data-value="{{ $template->unique_id }}"
                                     data-search="{{ strtolower($template->titulo . ' ' . $template->tipo . ' ' . $area) }}">
                                    <span>
                                        {{ $template->is_global ? '🌐 ' : '' }}{{ $template->titulo }}
                                    </span>
                                    @if($template->tipo)
                                        <span class="text-[10px] uppercase bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-500 dark:text-gray-400 ml-2">
                                            {{ $template->tipo }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <button type="button" 
            onclick="window.useSelectedTemplate({{ $processo->id }})"
            class="primary-button h-[38px] w-full md:w-auto flex items-center justify-center gap-1">
            <i class="icon-doc"></i>
            Usar Modelo
        </button>
    </div>

    @if(isset($templates) && $templates->count() > 0)
        <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">
                Modelos sugeridos para este processo
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($templates as $template)
                    <div class="flex flex-col justify-between rounded-lg border {{ $template->is_global ? 'border-blue-200 dark:border-blue-800/60' : 'border-gray-100 dark:border-gray-800' }} bg-gray-50 p-4 dark:bg-gray-800/50">
                        <div>
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                @if($template->is_global)
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/20">
                                        🌐 Padrão
                                    </span>
                                @endif
                                @if($template->tipo)
                                    <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-700/10 dark:bg-slate-900/30 dark:text-slate-400 dark:ring-slate-400/20">
                                        {{ ucfirst($template->tipo) }}
                                    </span>
                                @endif
                                @if($template->area_direito)
                                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-400/20">
                                        {{ $template->area_direito }}
                                    </span>
                                @endif
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $template->titulo }}</h4>
                            @if($template->descricao)
                                <p class="mt-1 text-xs text-gray-500 line-clamp-2" title="{{ $template->descricao }}">{{ $template->descricao }}</p>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <button type="button"
                                onclick="window.renderDocumentTemplate({{ $processo->id }}, '{{ $template->unique_id }}')"
                                class="primary-button w-full">
                                <i class="icon-doc"></i>
                                Usar Modelo
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
            <p class="text-xs text-gray-500 text-center py-4">
                Nenhum modelo sugerido automaticamente para este processo. Utilize o seletor acima para escolher um modelo pronto.
            </p>
        </div>
    @endif
</div>

<!-- ================================================================
     Modal para Exibição/Edição do Documento Renderizado
     NOTE: z-[99999] para ficar acima do header (z-[10001]) e sidebar (z-[10002])
     ================================================================ -->
<div id="lf-document-modal" 
     class="hidden" 
     style="position:fixed;inset:0;z-index:99999;overflow:hidden;"
     role="dialog" 
     aria-modal="true"
     aria-labelledby="lf-document-modal-title">

    <!-- Backdrop / Overlay -->
    <div id="lf-document-backdrop"
         style="position:absolute;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(2px);"
         onclick="window.closeDocumentModal()">
    </div>

    <!-- Modal Panel (above the backdrop) -->
    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none;">
        <div style="position:relative;pointer-events:auto;width:100%;max-width:900px;height:90vh;display:flex;flex-direction:column;background:#fff;border-radius:0.75rem;box-shadow:0 25px 60px rgba(0,0,0,0.35);border:1px solid #e5e7eb;overflow:hidden;"
             class="dark:bg-gray-900 dark:border-gray-700">

            <!-- ── Modal Header ── -->
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4 bg-white dark:bg-gray-900 flex-shrink-0">
                <h2 id="lf-document-modal-title"
                    class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 min-w-0">
                    <i class="icon-doc text-blue-600 flex-shrink-0"></i>
                    <span id="lf-modal-title-text" class="truncate">Carregando Documento...</span>
                </h2>
                <button type="button"
                        onclick="window.closeDocumentModal()"
                        title="Fechar"
                        class="ml-4 flex-shrink-0 rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="sr-only">Fechar</span>
                    <i class="icon-close text-lg"></i>
                </button>
            </div>

            <!-- ── Modal Body: Document Content ── -->
            <div class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-800 p-4 relative">

                <!-- Loading Overlay -->
                <div id="lf-document-loading"
                     class="absolute inset-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm flex flex-col items-center justify-center z-10">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
                    <p class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">Gerando documento com dados do processo...</p>
                </div>

                <!-- A4 Paper View -->
                <div class="mx-auto w-full max-w-[850px] bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-gray-700 rounded-sm my-4">
                    <!--
                        contenteditable div: renders HTML from the template (no external CDN required).
                        The user can edit the text freely before printing or copying.
                    -->
                    <div id="lf-document-content-editor"
                         contenteditable="true"
                         spellcheck="false"
                         style="min-height:1000px;padding:2cm;font-family:Arial,Helvetica,sans-serif;font-size:11pt;line-height:1.6;color:#111;outline:none;word-break:break-word;"
                         class="focus:ring-0 focus:outline-none dark:text-gray-100">
                    </div>
                </div>
            </div>

            <!-- ── Modal Footer ── -->
            <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-6 py-3 flex items-center justify-between gap-4">
                <!-- Left side: Checkbox + info text -->
                <div class="flex items-center gap-3 min-w-0">
                    <label for="lf-use-layout" class="inline-flex items-center gap-2 cursor-pointer select-none flex-shrink-0">
                        <input type="checkbox"
                               id="lf-use-layout"
                               checked
                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer"
                               onchange="window.onLayoutCheckboxChange(this.checked)">
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Cabeçalho/Rodapé</span>
                    </label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 hidden sm:block flex items-center gap-1 truncate">
                        <i class="icon-info-circle"></i>
                        Clique no texto acima para editar livremente antes de copiar ou imprimir.
                    </p>
                </div>

                <!-- Right side: Action buttons -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button"
                            onclick="window.closeDocumentModal()"
                            class="inline-flex items-center justify-center rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        Cancelar
                    </button>

                    <button type="button"
                            id="btn-copy-doc"
                            onclick="window.copyDocumentText()"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <i class="icon-files"></i>
                        Copiar Texto
                    </button>

                    <button type="button"
                            id="btn-save-doc"
                            onclick="window.saveDocumentToS3({{ $processo->id }})"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 hover:bg-green-700 px-4 py-2 text-sm font-medium text-white transition-colors shadow-sm">
                        <i class="icon-save"></i>
                        Salvar no Drive
                    </button>

                    <button type="button"
                            onclick="window.printDocument()"
                            class="primary-button flex items-center gap-1.5">
                        <i class="icon-printer"></i>
                        Imprimir / PDF
                    </button>
                </div>
            </div>

        </div><!-- end modal panel -->
    </div><!-- end center wrapper -->
</div><!-- end #lf-document-modal -->

<!-- Print-only Container (hidden from normal view) -->
<div id="lf-print-container" class="hidden"></div>


<style>
    /* ── Print stylesheet ───────────────────────────────────────────────── */
    @media print {
        body > *:not(#lf-print-wrapper) { display: none !important; }
        #lf-print-wrapper {
            display: block !important;
            position: absolute;
            left: 0; top: 0;
            width: 100%;
            padding: 2cm;
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
        }
    }

    /* ── Contenteditable editor placeholder ─────────────────────────────── */
    #lf-document-content-editor:empty::before {
        content: 'Conteúdo do documento aparecerá aqui...';
        color: #9ca3af;
        font-style: italic;
        pointer-events: none;
    }

    /* ── Block cursor styling in the editor ─────────────────────────────── */
    #lf-document-content-editor:focus {
        outline: none;
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
</style>

<script>
    // ── Global Event Delegation ──────────────────────────────────────────
    // Use event delegation on document so it survives Vue/Livewire DOM replacements
    document.addEventListener('focus', (e) => {
        if (e.target && e.target.id === 'lf-searchable-select-input') {
            lfFilterOptions(e.target);
            lfOpenDropdown();
        }
    }, true); // use capture phase for focus

    document.addEventListener('click', (e) => {
        const input = document.getElementById('lf-searchable-select-input');
        if (e.target && e.target.id === 'lf-searchable-select-input') {
            e.target.select();
            lfFilterOptions(e.target);
            lfOpenDropdown();
            return;
        }

        const option = e.target.closest ? e.target.closest('.lf-searchable-option') : null;
        if (option && document.getElementById('lf-searchable-select-dropdown').contains(option)) {
            const hiddenInput = document.getElementById('lf-select-ready-template');
            const searchInput = document.getElementById('lf-searchable-select-input');
            const val      = option.getAttribute('data-value');
            const textSpan = option.querySelector('span');
            const text     = textSpan ? textSpan.innerText : option.innerText;

            if (hiddenInput) hiddenInput.value = val;
            if (searchInput) searchInput.value = text.trim();
            lfCloseDropdown();
            return;
        }
    });

    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'lf-searchable-select-input') {
            const hiddenInput = document.getElementById('lf-select-ready-template');
            if (!e.target.value.trim() && hiddenInput) {
                hiddenInput.value = '';
            }
            lfFilterOptions(e.target);
            lfOpenDropdown();
        }
    });

    document.addEventListener('mousedown', (e) => {
        const container = document.getElementById('lf-searchable-select-container');
        if (container && !container.contains(e.target)) {
            lfCloseDropdown();
        }
    });

    let lfActiveIndex = -1;
    document.addEventListener('keydown', (e) => {
        if (e.target && e.target.id === 'lf-searchable-select-input') {
            const dropdown = document.getElementById('lf-searchable-select-dropdown');
            if (!dropdown) return;
            const visibleOptions = Array.from(dropdown.querySelectorAll('.lf-searchable-option:not(.hidden)'));

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                lfOpenDropdown();
                if (visibleOptions.length > 0) {
                    if (lfActiveIndex >= 0 && visibleOptions[lfActiveIndex]) {
                        visibleOptions[lfActiveIndex].classList.remove('bg-blue-100', 'dark:bg-gray-700');
                    }
                    lfActiveIndex = (lfActiveIndex + 1) % visibleOptions.length;
                    visibleOptions[lfActiveIndex].classList.add('bg-blue-100', 'dark:bg-gray-700');
                    visibleOptions[lfActiveIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                lfOpenDropdown();
                if (visibleOptions.length > 0) {
                    if (lfActiveIndex >= 0 && visibleOptions[lfActiveIndex]) {
                        visibleOptions[lfActiveIndex].classList.remove('bg-blue-100', 'dark:bg-gray-700');
                    }
                    lfActiveIndex = (lfActiveIndex - 1 + visibleOptions.length) % visibleOptions.length;
                    visibleOptions[lfActiveIndex].classList.add('bg-blue-100', 'dark:bg-gray-700');
                    visibleOptions[lfActiveIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const target = (lfActiveIndex >= 0 && visibleOptions[lfActiveIndex])
                    ? visibleOptions[lfActiveIndex]
                    : visibleOptions[0];
                if (target) target.click();
            } else if (e.key === 'Escape') {
                lfCloseDropdown();
                e.target.blur();
            }
        }
    });

    // ── Helper: run filter ──────────────────────────────────────────
    function lfFilterOptions(inputEl) {
        const dropdown = document.getElementById('lf-searchable-select-dropdown');
        const noResults = document.getElementById('lf-searchable-no-results');
        if (!dropdown || !noResults || !inputEl) return;

        const query  = inputEl.value.toLowerCase().trim();
        const groups = dropdown.querySelectorAll('.lf-searchable-group');
        let totalVisible = 0;

        lfActiveIndex = -1;

        groups.forEach(group => {
            const options = group.querySelectorAll('.lf-searchable-option');
            let groupVisible = 0;

            options.forEach(option => {
                option.classList.remove('bg-blue-100', 'dark:bg-gray-700');
                const searchText = option.getAttribute('data-search') || '';
                const matches    = !query || searchText.includes(query);
                option.classList.toggle('hidden', !matches);
                if (matches) { groupVisible++; totalVisible++; }
            });

            group.classList.toggle('hidden', groupVisible === 0);
        });

        noResults.classList.toggle('hidden', totalVisible > 0);
    }

    function lfOpenDropdown() {
        const dropdown = document.getElementById('lf-searchable-select-dropdown');
        if (dropdown) dropdown.classList.remove('hidden');
    }

    function lfCloseDropdown() {
        const dropdown = document.getElementById('lf-searchable-select-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }



    // ── Expose template render URL pattern to JS ──────────────────────────
    const _lfRenderUrlPattern = "{{ route('admin.processos.modelos.render', ['processoId' => 'PROC_ID', 'templateId' => 'TPL_ID']) }}";
    const _lfLayoutUrl        = "{{ route('admin.modelos.layout.get') }}";
    const _lfSalvarPattern    = "{{ route('admin.processos.modelos.salvar', ['processoId' => 'PROC_ID']) }}";
    const _lfCsrfToken        = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ── Layout cache (fetched once per modal open) ────────────────────────
    let _lfLayouts = null;        // { cabecalho: '...html...', rodape: '...html...' }
    let _lfRawContent = '';       // The plain rendered document HTML (without layout)
    let _lfCurrentTitle = '';

    // ── Fetch layout templates from backend ───────────────────────────────
    async function _lfFetchLayouts() {
        if (_lfLayouts !== null) return _lfLayouts;
        try {
            const res  = await fetch(_lfLayoutUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success) {
                _lfLayouts = { cabecalho: data.cabecalho || '', rodape: data.rodape || '' };
            } else {
                _lfLayouts = { cabecalho: '', rodape: '' };
            }
        } catch (_) {
            _lfLayouts = { cabecalho: '', rodape: '' };
        }
        return _lfLayouts;
    }

    // ── Apply or remove layout from editor ───────────────────────────────
    function _lfApplyLayout(useLayout) {
        const editor = document.getElementById('lf-document-content-editor');
        if (!editor) return;

        if (useLayout && _lfLayouts) {
            // Usa flexbox para jogar o rodapé para o fim da tela (min-h do editor empurra)
            editor.style.display = 'flex';
            editor.style.flexDirection = 'column';
            
            // Removemos os <hr> conforme solicitado e adicionamos contenteditable="false" 
            // nos layouts para proteger de deleção acidental
            editor.innerHTML = 
                '<div class="lf-layout-header" contenteditable="false" style="margin-bottom: 2rem;">' + _lfLayouts.cabecalho + '</div>' +
                '<div class="lf-layout-content" style="flex: 1 1 auto; outline: none; min-height: 150px;">' + _lfRawContent + '</div>' +
                '<div class="lf-layout-footer" contenteditable="false" style="margin-top: 2rem;">' + _lfLayouts.rodape + '</div>';
        } else {
            editor.style.display = 'block';
            editor.innerHTML = _lfRawContent;
        }
    }

    // ── Checkbox toggle handler ───────────────────────────────────────────
    window.onLayoutCheckboxChange = function(checked) {
        // Salvar a edição atual de volta para _lfRawContent antes de refazer o layout
        const editor = document.getElementById('lf-document-content-editor');
        if (editor) {
            const contentDiv = editor.querySelector('.lf-layout-content');
            if (contentDiv) {
                _lfRawContent = contentDiv.innerHTML;
            } else {
                _lfRawContent = editor.innerHTML;
            }
        }
        _lfApplyLayout(checked);
    };

    // ── "Usar Modelo" from the dropdown selector ──────────────────────────
    window.useSelectedTemplate = function(processoId) {
        const select = document.getElementById('lf-select-ready-template');
        const templateId = select ? select.value : '';
        if (!templateId) {
            alert('Por favor, selecione um modelo de documento.');
            return;
        }
        window.renderDocumentTemplate(processoId, templateId);
    };

    // ── Core: fetch + display a rendered template ─────────────────────────
    window.renderDocumentTemplate = async function(processoId, templateId) {
        const modal    = document.getElementById('lf-document-modal');
        const loading  = document.getElementById('lf-document-loading');
        const editor   = document.getElementById('lf-document-content-editor');
        const titleEl  = document.getElementById('lf-modal-title-text');
        const checkbox = document.getElementById('lf-use-layout');

        // Show modal + loading state
        modal.classList.remove('hidden');
        loading.classList.remove('hidden');
        if (titleEl) titleEl.textContent = 'Carregando Documento...';
        if (editor)  editor.innerHTML = '';
        if (checkbox) checkbox.checked = true; // Força como padrão em todas as novas gerações

        // Reset layout cache so it's re-fetched on next open
        _lfLayouts = null;

        // Fetch layouts and rendered template in parallel
        const layoutsPromise = _lfFetchLayouts();

        // Build URL
        const url = _lfRenderUrlPattern
            .replace('PROC_ID', processoId)
            .replace('TPL_ID', templateId);

        try {
            const [layouts, response] = await Promise.all([
                layoutsPromise,
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            ]);

            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();

            loading.classList.add('hidden');

            if (data.success) {
                // ── 1. Update modal title ──
                _lfCurrentTitle = data.titulo || 'Documento';
                if (titleEl) titleEl.textContent = _lfCurrentTitle;

                // ── 2. Store raw content and apply layout if checkbox is checked ──
                _lfRawContent = data.conteudo || '';
                _lfApplyLayout(checkbox && checkbox.checked);
            } else {
                alert('Erro ao carregar o modelo: ' + (data.message || 'Resposta inválida.'));
                modal.classList.add('hidden');
            }
        } catch (error) {
            console.error('[LF] Erro ao carregar modelo:', error);
            loading.classList.add('hidden');
            alert('Erro de conexão ao carregar o modelo. Verifique o console para detalhes.');
            modal.classList.add('hidden');
        }
    };

    // ── Save document to S3 Drive ─────────────────────────────────────────
    window.saveDocumentToS3 = function(processoId) {
        const editor  = document.getElementById('lf-document-content-editor');
        const btn     = document.getElementById('btn-save-doc');
        if (!editor || !editor.innerHTML.trim()) {
            alert('Nenhum conteúdo para salvar. Gere um documento primeiro.');
            return;
        }

        const htmlContent = editor.innerHTML;
        const titulo      = _lfCurrentTitle || 'Documento';

        // Disable button during save
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="icon-loading animate-spin"></i> Salvando...'; }

        const url = _lfSalvarPattern.replace('PROC_ID', processoId);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     _lfCsrfToken(),
            },
            body: JSON.stringify({ titulo: titulo, conteudo_html: htmlContent }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (btn) { btn.innerHTML = '<i class="icon-check text-white"></i> Salvo!'; }
                setTimeout(() => {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="icon-save"></i> Salvar no Drive'; }
                }, 2500);
            } else {
                alert('Erro ao salvar: ' + (data.message || 'Tente novamente.'));
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="icon-save"></i> Salvar no Drive'; }
            }
        })
        .catch(err => {
            console.error('[LF] Erro ao salvar documento:', err);
            alert('Erro de conexão ao salvar o documento.');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="icon-save"></i> Salvar no Drive'; }
        });
    };

    // ── Close modal ───────────────────────────────────────────────────────
    window.closeDocumentModal = function() {
        const modal = document.getElementById('lf-document-modal');
        if (modal) modal.classList.add('hidden');
    };

    // ── Close with Escape key ─────────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('lf-document-modal');
            if (modal && !modal.classList.contains('hidden')) {
                window.closeDocumentModal();
            }
        }
    });

    // ── Copy document text ────────────────────────────────────────────────
    window.copyDocumentText = function() {
        const editor  = document.getElementById('lf-document-content-editor');
        const btn     = document.getElementById('btn-copy-doc');
        if (!editor)  return;

        const htmlContent = editor.innerHTML;
        const textContent = editor.innerText;

        const showSuccess = function() {
            if (!btn) return;
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="icon-check text-green-500"></i> Copiado!';
            btn.classList.add('ring-green-500', 'text-green-700');
            setTimeout(() => {
                btn.innerHTML = orig;
                btn.classList.remove('ring-green-500', 'text-green-700');
            }, 2000);
        };

        // Try rich-text copy (preserves formatting when pasting into Word/Google Docs)
        if (window.ClipboardItem && htmlContent) {
            try {
                const item = new ClipboardItem({
                    'text/html':  new Blob([htmlContent], { type: 'text/html' }),
                    'text/plain': new Blob([textContent], { type: 'text/plain' }),
                });
                navigator.clipboard.write([item]).then(showSuccess).catch(() => {
                    navigator.clipboard.writeText(textContent).then(showSuccess);
                });
                return;
            } catch (_) { /* fall through */ }
        }
        navigator.clipboard.writeText(textContent).then(showSuccess);
    };

    // ── Print / Save as PDF ───────────────────────────────────────────────
    window.printDocument = function() {
        const editor = document.getElementById('lf-document-content-editor');
        const titleEl = document.getElementById('lf-modal-title-text');
        if (!editor) return;

        // Create a temporary print iframe so we don't clobber the page layout
        const existingFrame = document.getElementById('lf-print-iframe');
        if (existingFrame) existingFrame.remove();

        const iframe = document.createElement('iframe');
        iframe.id = 'lf-print-iframe';
        iframe.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:1px;height:1px;border:0;';
        document.body.appendChild(iframe);

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(`<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>${titleEl ? titleEl.textContent : 'Documento'}</title>
  <style>
    @@page { margin: 2cm; }
    /* Apply flexbox to the body to ensure header is at top and footer at bottom for print */
    body { 
        font-family: Arial, Helvetica, sans-serif; 
        font-size: 11pt; 
        line-height: 1.6; 
        color: #000; 
        margin: 0; 
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 98vh; /* Use almost full viewport height for print to push footer down */
    }
    /* If the layout is injected, the lf-layout-content will expand */
    .lf-layout-content { flex: 1 1 auto; }
    
    p { margin: 0 0 0.5em; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #ccc; padding: 4px 8px; }
  </style>
</head>
<body>${editor.innerHTML}</body>
</html>`);
        doc.close();

        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
</script>

