<x-admin::layouts>
    <x-slot:title>
        Assistentes Jurídicos (IA)
        </x-slot>

        @php
            $tenantId = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getTenantId();
            $brandColor = core()->getConfigData('general.settings.menu_color.brand_color') ?? '#7c3aed';
        @endphp

        @push('styles')
            <style>
                /* ============================================================
                                                                                   ASSISTANTS PAGE — Krayin-native styles
                                                                                   Grid:  2 cards per row  (max-md: 1 col)
                                                                                   Modal: Same lf-modal-* vocabulary as lead-tools-panel
                                                                                ============================================================ */

                /* ── Native CSS grid (same max-* convention as Krayin) ─── */
                .lf-assist-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 1rem;
                }

                @media (max-width: 768px) {
                    .lf-assist-grid {
                        grid-template-columns: 1fr;
                    }
                }

                /* ── Card hover ────────────────────────────────────────── */
                .lf-assist-card {
                    position: relative;
                    overflow: hidden;
                    transition: border-color 0.15s, box-shadow 0.15s, transform 0.18s;
                }

                .lf-assist-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #7c3aed, #9d4edd);
                    opacity: 1;
                    transition: height .18s;
                }

                .lf-assist-card:hover {
                    border-color: #c4b5fd !important;
                    box-shadow: 0 4px 16px rgba(124, 58, 237, 0.12);
                    transform: translateY(-2px);
                }

                .lf-assist-card:hover::before {
                    height: 4px;
                }

                .dark .lf-assist-card:hover {
                    border-color: #7c3aed !important;
                    box-shadow: 0 4px 16px rgba(124, 58, 237, 0.18);
                }

                .lf-assist-card-header {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 0.75rem;
                }

                .lf-assist-card-icon {
                    width: 38px;
                    height: 38px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 8px;
                    font-size: 1.25rem;
                    background: linear-gradient(135deg, rgba(124, 58, 237, .1), rgba(157, 78, 221, .1));
                    flex-shrink: 0;
                }

                .dark .lf-assist-card-icon {
                    background: linear-gradient(135deg, rgba(124, 58, 237, .2), rgba(157, 78, 221, .2));
                }

                .lf-assist-card-title {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin: 0;
                }

                .dark .lf-assist-card-title {
                    color: #f3f4f6;
                }

                /* ── Category label ─────────────────────────────────────── */
                .lf-cat-label {
                    font-size: 0.7rem;
                    font-weight: 700;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: #9ca3af;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding-bottom: 0.5rem;
                    margin-bottom: 0.75rem;
                    border-bottom: 1px solid #e5e7eb;
                }

                .dark .lf-cat-label {
                    border-color: #374151;
                }

                .lf-cat-label::before {
                    content: '';
                    width: 3px;
                    height: 0.9rem;
                    border-radius: 2px;
                    background: #7c3aed;
                    flex-shrink: 0;
                    display: block;
                }

                /* ── Area Filter Bar ─────────────────────────────────────── */
                .lf-area-filter-bar {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    flex-wrap: wrap;
                }

                .lf-area-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.3rem;
                    padding: 0.3rem 0.85rem;
                    font-size: 0.8rem;
                    font-weight: 600;
                    border-radius: 9999px;
                    border: 1.5px solid #e5e7eb;
                    background: #fff;
                    color: #6b7280;
                    cursor: pointer;
                    transition: all 0.15s;
                    white-space: nowrap;
                }

                .lf-area-btn:hover {
                    border-color: #7c3aed;
                    color: #7c3aed;
                    background: #f5f3ff;
                }

                .lf-area-btn.active {
                    background: linear-gradient(135deg, #7c3aed, #9d4edd);
                    border-color: transparent;
                    color: #fff;
                    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.25);
                }

                .dark .lf-area-btn {
                    background: #1f2937;
                    border-color: #374151;
                    color: #9ca3af;
                }

                .dark .lf-area-btn:hover {
                    border-color: #7c3aed;
                    color: #a78bfa;
                    background: rgba(124, 58, 237, 0.12);
                }

                .dark .lf-area-btn.active {
                    background: linear-gradient(135deg, #7c3aed, #9d4edd);
                    color: #fff;
                    border-color: transparent;
                }

                /* ── Overlay ─────────────────────────────────────────────── */
                .lf-modal-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 10000;
                }

                /* ── Dialog ──────────────────────────────────────────────── */
                .lf-modal-dialog {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    z-index: 10001;
                    width: 95%;
                    max-width: 960px;
                    max-height: 90vh;
                    overflow-y: auto;
                    background: #fff;
                    border-radius: 12px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                }

                .dark .lf-modal-dialog {
                    background: #111827;
                    border: 1px solid #374151;
                }

                /* ── Header ──────────────────────────────────────────────── */
                .lf-modal-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 16px 24px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .dark .lf-modal-header {
                    border-color: #374151;
                }

                .lf-modal-title {
                    font-size: 1.125rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin: 0;
                }

                .dark .lf-modal-title {
                    color: #fff;
                }

                .lf-modal-close-btn {
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: none;
                    background: transparent;
                    color: #6b7280;
                    font-size: 18px;
                    cursor: pointer;
                    border-radius: 6px;
                    transition: all 0.2s;
                }

                .lf-modal-close-btn:hover {
                    background: #f3f4f6;
                    color: #1f2937;
                }

                .dark .lf-modal-close-btn:hover {
                    background: #374151;
                    color: #fff;
                }

                /* ── Body: Two columns ───────────────────────────────────── */
                .lf-modal-body {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 24px;
                    padding: 24px;
                }

                @media (max-width: 768px) {
                    .lf-modal-body {
                        grid-template-columns: 1fr;
                    }
                }

                /* ── Form side ───────────────────────────────────────────── */
                .lf-modal-form {
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                }

                .lf-section-title {
                    font-size: 0.88rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin: 0;
                }

                .dark .lf-section-title {
                    color: #fff;
                }

                .lf-field {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                .lf-label {
                    font-size: 0.78rem;
                    font-weight: 500;
                    color: #6b7280;
                }

                .dark .lf-label {
                    color: #9ca3af;
                }

                .lf-input {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    font-size: 0.875rem;
                    color: #1f2937;
                    background: #f9fafb;
                    box-sizing: border-box;
                    font-family: inherit;
                }

                .dark .lf-input {
                    background: #1f2937;
                    border-color: #4b5563;
                    color: #e5e7eb;
                }

                .lf-input:focus {
                    outline: none;
                    border-color: #7c3aed;
                    box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.15);
                }

                .lf-textarea {
                    resize: vertical;
                    min-height: 90px;
                }

                /* Tenant ID info row */
                .lf-tenant-row {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 12px;
                    background: #f3f0ff;
                    border: 1px solid #c4b5fd;
                    border-radius: 8px;
                    font-size: 0.8rem;
                }

                .dark .lf-tenant-row {
                    background: rgba(124, 58, 237, 0.12);
                    border-color: #6d28d9;
                }

                .lf-tenant-key {
                    color: #6b7280;
                    font-weight: 500;
                }

                .dark .lf-tenant-key {
                    color: #9ca3af;
                }

                .lf-tenant-val {
                    font-family: monospace;
                    font-weight: 700;
                    color: #5b21b6;
                    word-break: break-all;
                }

                .dark .lf-tenant-val {
                    color: #a78bfa;
                }

                /* ── Result side ─────────────────────────────────────────── */
                .lf-modal-result {
                    display: flex;
                    flex-direction: column;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    background: #f9fafb;
                    min-height: 300px;
                }

                .dark .lf-modal-result {
                    background: #1f2937;
                    border-color: #374151;
                }

                .lf-result-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 12px 16px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .dark .lf-result-header {
                    border-color: #374151;
                }

                .lf-copy-btn {
                    font-size: 0.75rem;
                    font-weight: 500;
                    color: #7c3aed;
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 4px 8px;
                    border-radius: 4px;
                    transition: all 0.2s;
                    display: none;
                }

                .lf-copy-btn:hover {
                    background: #ede9fe;
                }

                .lf-result-body {
                    flex: 1;
                    padding: 16px;
                    overflow-y: auto;
                    max-height: 420px;
                }

                .lf-result-placeholder {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    min-height: 200px;
                    text-align: center;
                    color: #9ca3af;
                    font-size: 0.875rem;
                }

                .lf-result-loading {
                    display: none;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                    height: 100%;
                    min-height: 200px;
                    color: #7c3aed;
                    font-weight: 600;
                    font-size: 0.875rem;
                }

                .lf-spinner {
                    width: 36px;
                    height: 36px;
                    border: 4px solid rgba(124, 58, 237, 0.2);
                    border-top-color: #7c3aed;
                    border-radius: 50%;
                    animation: lf-spin 0.8s linear infinite;
                }

                @keyframes lf-spin {
                    to {
                        transform: rotate(360deg);
                    }
                }

                .lf-result-content {
                    display: none;
                }

                /* ── AI Result Box ───────────────────────────────────────── */
                .ai-result-box {
                    border: 1px solid #e0e0e0;
                    border-radius: 5px;
                    padding: 16px;
                    background: #fff;
                    min-height: 150px;
                    line-height: 1.7;
                    font-size: 0.875rem;
                    color: #333;
                }

                .dark .ai-result-box {
                    background: #1f2937;
                    border-color: #374151;
                    color: #e5e7eb;
                }

                .ai-result-box h1,
                .ai-result-box h2,
                .ai-result-box h3,
                .ai-result-box h4 {
                    color: #2c3e50;
                    margin-top: 12px;
                    font-weight: 700;
                }

                .dark .ai-result-box h1,
                .dark .ai-result-box h2,
                .dark .ai-result-box h3,
                .dark .ai-result-box h4 {
                    color: #fff;
                }

                .ai-result-box ul,
                .ai-result-box ol {
                    margin-left: 18px;
                    margin-bottom: 8px;
                }

                .ai-result-box blockquote {
                    border-left: 4px solid #7c3aed;
                    padding-left: 10px;
                    color: #666;
                    font-style: italic;
                }

                .dark .ai-result-box strong {
                    color: #fff;
                }

                /* ── Footer ──────────────────────────────────────────────── */
                .lf-modal-footer {
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                    gap: 12px;
                    padding: 16px 24px;
                    border-top: 1px solid #e5e7eb;
                }

                .dark .lf-modal-footer {
                    border-color: #374151;
                }

                /* Krayin-compact buttons — matches datagrid action scale */
                .lf-btn-primary {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    padding: 0.3rem 0.75rem;
                    font-weight: 600;
                    font-size: 0.8125rem;
                    border-radius: 0.375rem;
                    transition: all 0.15s;
                    background: linear-gradient(135deg, #7c3aed 0%, #9d4edd 100%);
                    color: #fff;
                    border: none;
                    cursor: pointer;
                    white-space: nowrap;
                }

                .lf-btn-primary:hover {
                    opacity: 0.9;
                    transform: translateY(-1px);
                }

                .lf-btn-primary:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                }

                .lf-btn-secondary {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    padding: 0.3rem 0.75rem;
                    font-weight: 500;
                    font-size: 0.8125rem;
                    border-radius: 0.375rem;
                    transition: all 0.15s;
                    background: #f3f4f6;
                    color: #374151;
                    border: 1px solid #d1d5db;
                    cursor: pointer;
                    white-space: nowrap;
                }

                .lf-btn-secondary:hover {
                    background: #e5e7eb;
                }

                .lf-btn-secondary:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }

                .dark .lf-btn-secondary {
                    background: #374151;
                    color: #e5e7eb;
                    border-color: #4b5563;
                }

                .dark .lf-btn-secondary:hover {
                    background: #4b5563;
                }
            </style>
        @endpush

        <div class="flex flex-col gap-4">

            {{-- ── PAGE HEADER (Krayin standard) ──────────────── --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.assistants.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">Assistentes Jurídicos (IA)</div>
                </div>

                {{-- tenant_id oculto (necessário para JS mas não visível ao usuário) --}}
                <span id="lf-page-tenant-id" class="hidden">{{ $tenantId }}</span>
            </div>

            {{-- ── AREA FILTER BAR (filtra por required_module = IA-*) ── --}}
            @if($areas->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">⚖️ Filtrar
                            por Área:</span>
                        <div class="lf-area-filter-bar">
                            <button type="button" class="lf-area-btn active"
                                onclick="window.lfFilterByArea('todas', this)">Todas</button>
                            @foreach($areas as $modKey)
                                {{-- Mostra 'IA-Trabalhista' como 'Trabalhista' --}}
                                @php $label = Str::after($modKey, 'IA-'); @endphp
                                <button type="button" class="lf-area-btn" data-module="{{ $modKey }}"
                                    onclick="window.lfFilterByArea('{{ $modKey }}', this)">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- ── CARDS GRID ───────────────────────────────────── --}}
            @forelse($templates->groupBy('category') as $category => $categoryTemplates)

                {{-- Category wrapper --}}
                <div
                    class="lf-category-section rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="lf-cat-label">{{ $category ?: 'Geral' }}</div>

                    <div class="lf-assist-grid">
                        @foreach($categoryTemplates as $template)
                            <div data-module="{{ $template->required_module ?? '' }}"
                                class="lf-assist-card flex flex-col justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                <div>

                                    {{-- Title --}}
                                    <div class="lf-assist-card-header">
                                        <div class="lf-assist-card-icon">{{ $template->icon ?? '🤖' }}</div>
                                        <div class="lf-assist-card-title">{{ $template->title }}</div>
                                    </div>

                                    {{-- Description --}}
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $template->description ?? 'Sem descrição.' }}
                                    </p>

                                    {{-- Module tag --}}
                                    @if($template->required_module)
                                        <div class="mt-3">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                Requer: {{ ucfirst(str_replace('_', ' ', $template->required_module)) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Action button --}}
                                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-800">
                                    <button type="button" class="lf-btn-primary"
                                        onclick="window.lfAssistants.open(
                                                                                                                                                                                        {{ $template->id }},
                                                                                                                                                                                        '{{ addslashes($template->title) }}',
                                                                                                                                                                                        '{{ $template->slug }}',
                                                                                                                                                                                        {{ json_encode($template->prompt_structure) }},
                                                                                                                                                                                        {{ $template->n8n_webhook_url ? 'true' : 'false' }}
                                                                                                                                                                                    )">
                                        ✨ Usar Assistente
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            @empty
                <div
                    class="flex flex-col items-center gap-2 rounded-lg border border-gray-200 bg-white py-10 text-center dark:border-gray-800 dark:bg-gray-900">
                    <span class="text-4xl">🤖</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum assistente disponível no momento.</p>
                </div>
            @endforelse

        </div>{{-- /flex flex-col --}}

        {{-- ── MODAL (moved to

        <body> via JS to escape Krayin Vue) ── --}}
            <div id="lf-assist-modal" style="display:none;">
                {{-- Overlay --}}
                <div class="lf-modal-overlay" onclick="window.lfAssistants.close()"></div>

                {{-- Dialog --}}
                <div class="lf-modal-dialog">

                    {{-- Header --}}
                    <div class="lf-modal-header">
                        <h3 id="lf-assist-title" class="lf-modal-title">🤖 Assistente IA</h3>
                        <button onclick="window.lfAssistants.close()" class="lf-modal-close-btn">✕</button>
                    </div>

                    {{-- Body: Form | Result --}}
                    <div class="lf-modal-body">

                        {{-- LEFT: Form --}}
                        <div class="lf-modal-form">
                            <h4 class="lf-section-title">Parâmetros</h4>

                            {{-- Dynamic inputs injected by JS --}}
                            <div id="lf-assist-dynamic-inputs"></div>

                            {{-- Hidden fields (tenant_id sent to backend but not visible to user) --}}
                            <input type="hidden" id="lf-assist-template-id">
                            <input type="hidden" id="lf-assist-slug">
                            <input type="hidden" id="lf-assist-tenant" value="{{ $tenantId }}">

                            {{-- Error --}}
                            <div id="lf-assist-error"
                                class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                            </div>
                        </div>

                        {{-- RIGHT: Result --}}
                        <div class="lf-modal-result">
                            <div class="lf-result-header">
                                <h4 class="lf-section-title">Resultado</h4>
                                <button id="lf-assist-copy-btn" class="lf-copy-btn"
                                    onclick="window.lfAssistants.copy()">
                                    📋 Copiar
                                </button>
                            </div>
                            <div class="lf-result-body">
                                <div id="lf-assist-placeholder" class="lf-result-placeholder"
                                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 220px; text-align: center; color: #9ca3af; font-size: .85rem; gap: 8px;">
                                    <span style="font-size: 2.5rem; opacity: .5;">⚖️</span>
                                    <span>Preencha os campos e clique em <strong>"Gerar Prompt"</strong> ou
                                        <strong>"Executar com IA"</strong></span>
                                </div>
                                <div id="lf-assist-loading" class="lf-result-loading">
                                    <div class="lf-spinner"></div>
                                    <span>🧠 Processando com IA...</span>
                                </div>
                                <div id="lf-assist-result" class="lf-result-content">
                                    <div id="lf-assist-result-box" class="ai-result-box"></div>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /lf-modal-body --}}

                    {{-- Footer --}}
                    <div class="lf-modal-footer">
                        <button id="lf-assist-btn-generate" onclick="window.lfAssistants.generate()"
                            class="lf-btn-secondary">
                            <span id="lf-gen-text">📝 Gerar Prompt</span>
                            <span id="lf-gen-loading" style="display:none;">⏳ Gerando...</span>
                        </button>
                        <button id="lf-assist-btn-execute" onclick="window.lfAssistants.execute()"
                            class="lf-btn-primary" style="display:none;">
                            <span id="lf-exec-text">✨ Executar com IA</span>
                            <span id="lf-exec-loading" style="display:none;">🧠 Processando...</span>
                        </button>
                        <button onclick="window.lfAssistants.reset()" class="lf-btn-secondary">Novo</button>
                    </div>

                </div>{{-- /lf-modal-dialog --}}
            </div>{{-- /lf-assist-modal --}}

            @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
                <script>
                    (function () {
                        'use strict';

                        var TENANT_ID = '{{ $tenantId }}';
                        var CSRF_TOKEN = '{{ csrf_token() }}';

                        var ROUTES = {
                            generate: "{{ route('lawfirm.assistants.generate', '__SLUG__') }}",
                            execute: "{{ route('lawfirm.assistants.execute', '__SLUG__') }}",
                            status: "{{ route('lawfirm.assistants.check_status', '__ID__') }}"
                        };

                        // State
                        var activeSlug = '';
                        var rawResult = '';

                        // Lazy DOM helper — always gets fresh ref so timing is never an issue
                        function el(id) { return document.getElementById(id); }

                        // Move modal to <body> once DOM is ready (escapes Krayin Vue scope)
                        document.addEventListener('DOMContentLoaded', function () {
                            var modal = el('lf-assist-modal');
                            if (modal) document.body.appendChild(modal);
                        });

                        /* ── Helpers ─────────────────────────────── */
                        function setState(state) {
                            el('lf-assist-placeholder').style.display = state === 'placeholder' ? 'flex' : 'none';
                            el('lf-assist-loading').style.display = state === 'loading' ? 'flex' : 'none';
                            el('lf-assist-result').style.display = state === 'result' ? 'block' : 'none';
                            el('lf-assist-copy-btn').style.display = state === 'result' ? 'inline' : 'none';

                            var busy = (state === 'loading');
                            el('lf-assist-btn-generate').disabled = busy;
                            el('lf-assist-btn-execute').disabled = busy;
                            el('lf-gen-text').style.display = busy ? 'none' : '';
                            el('lf-gen-loading').style.display = busy ? '' : 'none';
                            el('lf-exec-text').style.display = busy ? 'none' : '';
                            el('lf-exec-loading').style.display = busy ? '' : 'none';
                        }

                        function renderMd(text) {
                            if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                                try { return marked.parse(String(text)); } catch (e) { }
                            }
                            return String(text).replace(/\n/g, '<br>');
                        }

                        function showError(msg) {
                            var box = el('lf-assist-error');
                            box.textContent = msg;
                            box.classList.remove('hidden');
                        }
                        function hideError() { el('lf-assist-error').classList.add('hidden'); }

                        function buildPayload() {
                            var payload = { tenant_id: TENANT_ID };
                            var inputs = el('lf-assist-dynamic-inputs').querySelectorAll('[data-lf-var]');
                            inputs.forEach(function (inp) { payload[inp.dataset.lfVar] = inp.value; });
                            return payload;
                        }

                        function pollStatus(historyId) {
                            var maxAttempts = 60, attempts = 0;
                            var poll = setInterval(async function () {
                                attempts++;
                                try {
                                    var url = ROUTES.status.replace('__ID__', historyId);
                                    var res = await fetch(url);
                                    var data = await res.json();
                                    if (data.status === 'completed') {
                                        clearInterval(poll);
                                        rawResult = data.generated_content;
                                        el('lf-assist-result-box').innerHTML = renderMd(rawResult);
                                        setState('result');
                                    } else if (data.status === 'failed') {
                                        clearInterval(poll);
                                        setState('placeholder');
                                        showError('Falha: ' + (data.error_message || 'Erro desconhecido'));
                                    } else if (attempts >= maxAttempts) {
                                        clearInterval(poll);
                                        setState('placeholder');
                                        showError('Tempo limite excedido.');
                                    }
                                } catch (e) { /* silently continue */ }
                            }, 2000);
                        }

                        /* ── Field Validation ─────────────────────── */
                        function validateFirstField() {
                            var container = el('lf-assist-dynamic-inputs');
                            if (!container) return true;
                            // Pega o primeiro input/textarea que NÃO seja hidden
                            var first = container.querySelector('input:not([type="hidden"]), textarea');
                            if (!first) return true; // sem campos = aceita
                            var val = first.value.trim();
                            if (val === '') {
                                first.focus();
                                first.style.borderColor = '#ef4444';
                                setTimeout(function () { first.style.borderColor = ''; }, 2500);
                                showError('Preencha pelo menos o primeiro campo antes de continuar.');
                                return false;
                            }
                            return true;
                        }

                        /* ── Public API ──────────────────────────── */
                        window.lfAssistants = {

                            open: function (id, title, slug, promptStructure, hasWebhook) {
                                activeSlug = slug;
                                rawResult = '';

                                el('lf-assist-template-id').value = id;
                                el('lf-assist-slug').value = slug;
                                el('lf-assist-title').textContent = '🤖 ' + title;

                                // Build inputs from template placeholders
                                var container = el('lf-assist-dynamic-inputs');
                                container.innerHTML = '';
                                var seen = new Set();
                                var src = typeof promptStructure === 'string'
                                    ? promptStructure
                                    : JSON.stringify(promptStructure || '');

                                // Must split {{ to prevent Blade parsing
                                var regex = new RegExp('{' + '{(.*?)}' + '}', 'g'), m;
                                while ((m = regex.exec(src)) !== null) {
                                    var varName = m[1].trim();
                                    if (seen.has(varName)) continue;
                                    seen.add(varName);

                                    var label = varName.replace(/_/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
                                    var isLong = varName.indexOf('descri') !== -1 || varName.indexOf('observ') !== -1 || varName.indexOf('texto') !== -1 || varName.indexOf('cnis') !== -1 || varName.indexOf('relato') !== -1 || varName.indexOf('fatos') !== -1;
                                    var wrap = document.createElement('div');
                                    wrap.className = 'lf-field';
                                    if (isLong) {
                                        wrap.innerHTML =
                                            '<label class="lf-label">' + label + '</label>' +
                                            '<textarea data-lf-var="' + varName + '" class="lf-input lf-textarea" placeholder="' + label + '..." rows="8"></textarea>';
                                    } else {
                                        wrap.innerHTML =
                                            '<label class="lf-label">' + label + '</label>' +
                                            '<input type="text" data-lf-var="' + varName + '" class="lf-input" placeholder="' + label + '...">';
                                    }
                                    container.appendChild(wrap);
                                }

                                if (seen.size === 0) {
                                    container.innerHTML = '<p class="text-sm text-gray-400 py-2">Este assistente não requer parâmetros adicionais.</p>';
                                }

                                // Show/hide execute button
                                el('lf-assist-btn-execute').style.display = hasWebhook ? 'inline-flex' : 'none';

                                hideError();
                                el('lf-assist-result-box').innerHTML = '';
                                setState('placeholder');
                                el('lf-assist-modal').style.display = '';
                            },

                            close: function () {
                                el('lf-assist-modal').style.display = 'none';
                            },

                            reset: function () {
                                el('lf-assist-result-box').innerHTML = '';
                                hideError();
                                setState('placeholder');
                            },

                            generate: async function () {
                                if (!validateFirstField()) return;
                                hideError();
                                setState('loading');
                                try {
                                    var url = ROUTES.generate.replace('__SLUG__', activeSlug);
                                    var res = await fetch(url, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                                        body: JSON.stringify(buildPayload())
                                    });
                                    var data = await res.json();
                                    if (!res.ok) throw new Error(data.error || 'Erro ao gerar prompt');
                                    rawResult = data.generated_prompt || '';
                                    el('lf-assist-result-box').innerHTML = renderMd(rawResult);
                                    setState('result');
                                } catch (e) {
                                    setState('placeholder');
                                    showError(e.message);
                                }
                            },

                            execute: async function () {
                                if (!validateFirstField()) return;
                                hideError();
                                setState('loading');
                                try {
                                    var url = ROUTES.execute.replace('__SLUG__', activeSlug);
                                    var res = await fetch(url, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                                        body: JSON.stringify(buildPayload())
                                    });
                                    var data = await res.json();
                                    if (!res.ok) throw new Error(data.error || 'Erro na execução');
                                    if (data.status === 'queued' && data.history_id) {
                                        pollStatus(data.history_id);
                                    } else if (data.generated_prompt) {
                                        rawResult = data.generated_prompt;
                                        el('lf-assist-result-box').innerHTML = renderMd(rawResult);
                                        setState('result');
                                    } else {
                                        throw new Error('Resposta inesperada da API');
                                    }
                                } catch (e) {
                                    setState('placeholder');
                                    showError(e.message);
                                }
                            },

                            copy: async function () {
                                if (!rawResult) return;

                                var btn = el('lf-assist-copy-btn');
                                var originalText = btn.innerHTML;

                                try {
                                    if (navigator.clipboard && window.isSecureContext) {
                                        await navigator.clipboard.writeText(rawResult);
                                    } else {
                                        // Fallback seguro para non-HTTPS (localhost/.test)
                                        var ta = document.createElement('textarea');
                                        ta.value = rawResult;
                                        ta.style.position = 'fixed';
                                        ta.style.opacity = '0';
                                        document.body.appendChild(ta);
                                        ta.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(ta);
                                    }

                                    btn.innerHTML = '✅ Copiado!';
                                } catch (e) {
                                    console.error('Erro ao copiar:', e);
                                    btn.innerHTML = '❌ Erro';
                                }

                                setTimeout(function () {
                                    if (btn) btn.innerHTML = originalText;
                                }, 2000);
                            }
                        };

                        /* ── Area Filter ─────────────────────────────────── */
                        window.lfFilterByArea = function (area, btn) {
                            // Update active button state
                            document.querySelectorAll('.lf-area-btn').forEach(function (b) {
                                b.classList.remove('active');
                            });
                            if (btn) btn.classList.add('active');

                            var sections = document.querySelectorAll('.lf-category-section');
                            sections.forEach(function (section) {
                                var cards = section.querySelectorAll('[data-module]');
                                var visibleCount = 0;

                                cards.forEach(function (card) {
                                    var cardModule = card.dataset.module || '';
                                    // Mostra se: filtro = 'todas', OU o módulo bate exatamente
                                    var matches = area === 'todas' || cardModule === area;
                                    card.style.display = matches ? '' : 'none';
                                    if (matches) visibleCount++;
                                });

                                // Hide entire category section if no cards visible
                                section.style.display = visibleCount === 0 ? 'none' : '';
                            });
                        };

                    })();
                </script>
            @endpush

</x-admin::layouts>