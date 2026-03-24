<x-admin::layouts>
    <x-slot:title>
        Assistente Jurídico
        </x-slot>

        @php
            $brandColor = '#0d9488';
            $brandGradient = 'linear-gradient(135deg, #0d9488, #0284c7)';
        @endphp

        @push('styles')
            <style>
                /* ============================================================
                                                                                                                                               ESCAVADOR PAGE — Krayin-native styles
                                                                                                                                               Grid: 2 cards per row (max-md: 1 col)
                                                                                                                                               Modal: lf-esc-modal-* vocabulary
                                                                                                                                            ============================================================ */

                /* ── Grid ─────────────────────────────────────────────── */
                .lf-esc-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 1rem;
                }

                @media (max-width: 768px) {
                    .lf-esc-grid {
                        grid-template-columns: 1fr;
                    }
                }

                /* ── Card ─────────────────────────────────────────────── */
                .lf-esc-card {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    padding: 1rem;
                    border-radius: 0.5rem;
                    border: 1px solid #e5e7eb;
                    background: #fff;
                    transition: border-color .18s, box-shadow .18s, transform .18s;
                    cursor: default;
                    overflow: hidden;
                }

                .lf-esc-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #0d9488, #0284c7);
                    opacity: 1;
                    transition: height .18s;
                }

                .lf-esc-card:hover {
                    border-color: #99f6e4;
                    box-shadow: 0 4px 16px rgba(13, 148, 136, .12);
                    transform: translateY(-2px);
                }

                .lf-esc-card:hover::before {
                    height: 4px;
                }

                .dark .lf-esc-card {
                    background: #111827;
                    border-color: #374151;
                }

                .dark .lf-esc-card:hover {
                    border-color: #0d9488;
                    box-shadow: 0 4px 16px rgba(13, 148, 136, .18);
                }

                .lf-esc-card-header {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 0.75rem;
                }

                .lf-esc-card-icon {
                    width: 38px;
                    height: 38px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 8px;
                    font-size: 1.25rem;
                    background: linear-gradient(135deg, rgba(13, 148, 136, .1), rgba(2, 132, 199, .1));
                    flex-shrink: 0;
                }

                .dark .lf-esc-card-icon {
                    background: linear-gradient(135deg, rgba(13, 148, 136, .2), rgba(2, 132, 199, .2));
                }

                .lf-esc-card-title {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin: 0;
                }

                .dark .lf-esc-card-title {
                    color: #f3f4f6;
                }

                .lf-esc-card-desc {
                    font-size: .82rem;
                    color: #6b7280;
                    line-height: 1.45;
                    margin-bottom: .5rem;
                }

                .dark .lf-esc-card-desc {
                    color: #9ca3af;
                }

                .lf-esc-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: .25rem;
                    padding: .15rem .55rem;
                    font-size: .68rem;
                    font-weight: 700;
                    letter-spacing: .04em;
                    text-transform: uppercase;
                    border-radius: 9999px;
                    white-space: nowrap;
                }

                .lf-esc-badge-v1 {
                    background: #fef3c7;
                    color: #92400e;
                }

                .lf-esc-badge-v2 {
                    background: #d1fae5;
                    color: #065f46;
                }

                .dark .lf-esc-badge-v1 {
                    background: rgba(251, 191, 36, .15);
                    color: #fbbf24;
                }

                .dark .lf-esc-badge-v2 {
                    background: rgba(16, 185, 129, .15);
                    color: #34d399;
                }

                /* ── Button ───────────────────────────────────────────── */
                .lf-esc-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: .35rem;
                    padding: .3rem .75rem;
                    font-weight: 600;
                    font-size: .8125rem;
                    border-radius: 0.375rem;
                    border: none;
                    cursor: pointer;
                    white-space: nowrap;
                    transition: all .15s;
                    background: linear-gradient(135deg, #0d9488, #0284c7);
                    color: #fff;
                }

                .lf-esc-btn:hover {
                    opacity: .9;
                    transform: translateY(-1px);
                }

                .lf-esc-btn:disabled {
                    opacity: .55;
                    cursor: not-allowed;
                    transform: none;
                }

                .lf-esc-btn-secondary {
                    display: inline-flex;
                    align-items: center;
                    gap: .35rem;
                    padding: .3rem .75rem;
                    font-weight: 500;
                    font-size: .8125rem;
                    border-radius: 0.375rem;
                    background: #f3f4f6;
                    color: #374151;
                    border: 1px solid #d1d5db;
                    cursor: pointer;
                    white-space: nowrap;
                    transition: all .15s;
                }

                .lf-esc-btn-secondary:hover {
                    background: #e5e7eb;
                }

                .dark .lf-esc-btn-secondary {
                    background: #374151;
                    color: #e5e7eb;
                    border-color: #4b5563;
                }

                .dark .lf-esc-btn-secondary:hover {
                    background: #4b5563;
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
                    border-color: #3b82f6;
                    color: #3b82f6;
                    background: #eff6ff;
                }

                .lf-area-btn.active {
                    background: linear-gradient(135deg, #3b82f6, #60a5fa);
                    border-color: transparent;
                    color: #fff;
                    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
                }

                .dark .lf-area-btn {
                    background: #1f2937;
                    border-color: #374151;
                    color: #9ca3af;
                }

                .dark .lf-area-btn:hover {
                    border-color: #3b82f6;
                    color: #93c5fd;
                    background: rgba(59, 130, 246, 0.12);
                }

                .dark .lf-area-btn.active {
                    background: linear-gradient(135deg, #3b82f6, #60a5fa);
                    color: #fff;
                    border-color: transparent;
                }

                /* ── Overlay + Dialog (reusable) ──────────────────────── */
                .lf-esc-overlay {
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, .5);
                    z-index: 10000;
                    backdrop-filter: blur(2px);
                }

                .lf-esc-dialog {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    z-index: 10001;
                    width: 95%;
                    max-width: 1020px;
                    max-height: 90vh;
                    overflow-y: auto;
                    background: #fff;
                    border-radius: 14px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
                }

                .dark .lf-esc-dialog {
                    background: #111827;
                    border: 1px solid #374151;
                }

                /* ── Modal Header ─────────────────────────────────────── */
                .lf-esc-modal-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 16px 24px;
                    border-bottom: 1px solid #e5e7eb;
                    background: linear-gradient(135deg, rgba(13, 148, 136, .04), rgba(2, 132, 199, .04));
                }

                .dark .lf-esc-modal-header {
                    border-color: #374151;
                    background: linear-gradient(135deg, rgba(13, 148, 136, .08), rgba(2, 132, 199, .08));
                }

                .lf-esc-modal-title {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: .5rem;
                }

                .dark .lf-esc-modal-title {
                    color: #fff;
                }

                .lf-esc-close-btn {
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
                    transition: all .2s;
                }

                .lf-esc-close-btn:hover {
                    background: #f3f4f6;
                    color: #1f2937;
                }

                .dark .lf-esc-close-btn:hover {
                    background: #374151;
                    color: #fff;
                }

                /* ── Modal Body — Two columns ─────────────────────────── */
                .lf-esc-modal-body {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 24px;
                    padding: 24px;
                }

                @media (max-width: 768px) {
                    .lf-esc-modal-body {
                        grid-template-columns: 1fr;
                    }
                }

                /* ── Form Column ──────────────────────────────────────── */
                .lf-esc-form {
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                }

                .lf-esc-section-title {
                    font-size: .88rem;
                    font-weight: 700;
                    color: #1f2937;
                    margin: 0;
                }

                .dark .lf-esc-section-title {
                    color: #fff;
                }

                .lf-esc-field {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                .lf-esc-label {
                    font-size: .78rem;
                    font-weight: 500;
                    color: #6b7280;
                }

                .dark .lf-esc-label {
                    color: #9ca3af;
                }

                .lf-esc-input {
                    width: 100%;
                    padding: 9px 12px;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    font-size: .875rem;
                    color: #1f2937;
                    background: #f9fafb;
                    box-sizing: border-box;
                    font-family: inherit;
                }

                .dark .lf-esc-input {
                    background: #1f2937;
                    border-color: #4b5563;
                    color: #e5e7eb;
                }

                .lf-esc-input:focus {
                    outline: none;
                    border-color: #0d9488;
                    box-shadow: 0 0 0 2px rgba(13, 148, 136, .15);
                }

                .lf-esc-select {
                    width: 100%;
                    padding: 9px 12px;
                    border: 1px solid #d1d5db;
                    border-radius: 8px;
                    font-size: .875rem;
                    color: #1f2937;
                    background: #f9fafb;
                    box-sizing: border-box;
                    font-family: inherit;
                    cursor: pointer;
                }

                .dark .lf-esc-select {
                    background: #1f2937;
                    border-color: #4b5563;
                    color: #e5e7eb;
                }

                .lf-esc-select:focus {
                    outline: none;
                    border-color: #0d9488;
                    box-shadow: 0 0 0 2px rgba(13, 148, 136, .15);
                }

                .lf-esc-hint {
                    font-size: .72rem;
                    color: #9ca3af;
                    margin-top: 2px;
                }

                .lf-esc-pg-toggle {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 12px;
                    background: #f0fdfa;
                    border: 1px solid #99f6e4;
                    border-radius: 8px;
                    font-size: .8rem;
                }

                .dark .lf-esc-pg-toggle {
                    background: rgba(13, 148, 136, .1);
                    border-color: rgba(13, 148, 136, .3);
                }

                .lf-esc-pg-toggle label {
                    cursor: pointer;
                    color: #6b7280;
                    font-weight: 500;
                }

                .dark .lf-esc-pg-toggle label {
                    color: #9ca3af;
                }

                /* ── Result Column ─────────────────────────────────────── */
                .lf-esc-result-panel {
                    display: flex;
                    flex-direction: column;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    background: #f9fafb;
                    min-height: 320px;
                }

                .dark .lf-esc-result-panel {
                    background: #1f2937;
                    border-color: #374151;
                }

                .lf-esc-result-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 10px 16px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .dark .lf-esc-result-header {
                    border-color: #374151;
                }

                .lf-esc-result-body {
                    flex: 1;
                    padding: 16px;
                    overflow-y: auto;
                    max-height: 460px;
                }

                .lf-esc-result-placeholder {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    min-height: 220px;
                    text-align: center;
                    color: #9ca3af;
                    font-size: .85rem;
                    gap: 8px;
                }

                .lf-esc-result-placeholder span.big-icon {
                    font-size: 2.5rem;
                    opacity: .5;
                }

                .lf-esc-loading {
                    display: none;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                    height: 100%;
                    min-height: 220px;
                    color: #0d9488;
                    font-weight: 600;
                    font-size: .85rem;
                }

                .lf-esc-spinner {
                    width: 36px;
                    height: 36px;
                    border: 4px solid rgba(13, 148, 136, .2);
                    border-top-color: #0d9488;
                    border-radius: 50%;
                    animation: lf-esc-spin .8s linear infinite;
                }

                @keyframes lf-esc-spin {
                    to {
                        transform: rotate(360deg);
                    }
                }

                .lf-esc-result-content {
                    display: none;
                }

                .lf-esc-copy-btn {
                    font-size: .75rem;
                    font-weight: 500;
                    color: #0d9488;
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 4px 8px;
                    border-radius: 4px;
                    transition: all .2s;
                    display: none;
                }

                .lf-esc-copy-btn:hover {
                    background: #f0fdfa;
                }

                /* ── Result Data Formatting ────────────────────────────── */
                .lf-esc-data-box {
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 14px;
                    background: #fff;
                    font-size: .82rem;
                    line-height: 1.65;
                    color: #374151;
                    word-break: break-word;
                }

                .dark .lf-esc-data-box {
                    background: #111827;
                    border-color: #374151;
                    color: #e5e7eb;
                }

                .lf-esc-data-box pre {
                    white-space: pre-wrap;
                    font-size: .78rem;
                    margin: 0;
                    font-family: 'Cascadia Code', 'Fira Code', monospace;
                }

                .lf-esc-data-box .esc-key {
                    color: #0d9488;
                    font-weight: 600;
                }

                .lf-esc-data-box .esc-str {
                    color: #b45309;
                }

                .dark .lf-esc-data-box .esc-str {
                    color: #fbbf24;
                }

                .lf-esc-data-box .esc-num {
                    color: #7c3aed;
                }

                .lf-esc-data-box .esc-bool {
                    color: #dc2626;
                }

                .lf-esc-data-box .esc-null {
                    color: #9ca3af;
                    font-style: italic;
                }

                .markdown-body ul {
                    list-style-type: disc;
                    padding-left: 20px;
                    margin-top: 4px;
                    margin-bottom: 8px;
                }

                .markdown-body li {
                    margin-bottom: 4px;
                }

                .markdown-body strong {
                    color: #111827;
                }

                .dark .markdown-body strong {
                    color: #f9fafb;
                }

                .markdown-body p {
                    margin-bottom: 8px;
                }

                /* ── Error Box ─────────────────────────────────────────── */
                .lf-esc-error {
                    padding: 10px 14px;
                    border-radius: 8px;
                    border: 1px solid #fca5a5;
                    background: #fef2f2;
                    color: #991b1b;
                    font-size: .82rem;
                }

                .dark .lf-esc-error {
                    background: rgba(220, 38, 38, .1);
                    border-color: rgba(220, 38, 38, .3);
                    color: #fca5a5;
                }

                /* ── Saldo widget ──────────────────────────────────────── */
                .lf-esc-saldo-box {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 14px 18px;
                    border-radius: 10px;
                    background: linear-gradient(135deg, rgba(13, 148, 136, .06), rgba(2, 132, 199, .06));
                    border: 1px solid #99f6e4;
                    font-size: .85rem;
                }

                .dark .lf-esc-saldo-box {
                    background: linear-gradient(135deg, rgba(13, 148, 136, .12), rgba(2, 132, 199, .12));
                    border-color: rgba(13, 148, 136, .3);
                }

                .lf-esc-saldo-value {
                    font-size: 1.5rem;
                    font-weight: 800;
                    color: #0d9488;
                }

                .lf-esc-saldo-label {
                    color: #6b7280;
                    font-weight: 500;
                }

                .dark .lf-esc-saldo-label {
                    color: #9ca3af;
                }

                /* ── Footer ───────────────────────────────────────────── */
                .lf-esc-modal-footer {
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                    gap: 12px;
                    padding: 14px 24px;
                    border-top: 1px solid #e5e7eb;
                }

                .dark .lf-esc-modal-footer {
                    border-color: #374151;
                }
            </style>
        @endpush

        <div class="flex flex-col gap-4">

            {{-- ── PAGE HEADER ────────────────────────────────────── --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.escavador.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">⚖️ Assistente Jurídico</div>
                </div>
            </div>

            {{-- ── INFO BAR ───────────────────────────────────────── --}}
                <div
                    class="rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800 dark:border-teal-900 dark:bg-teal-900/20 dark:text-teal-300">
                    💡 Acesse dados públicos de processos, pessoas e instituições
                    extraídos de Diários Oficiais e Tribunais de todo o Brasil. Selecione uma funcionalidade abaixo para
                    começar.
                </div>

                                    {{-- ── AREA FILTER BAR ── --}}
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">⚖️ Filtrar
                        por Categoria:</span>
                    
                    <div class="lf-area-filter-bar flex gap-2 w-full mt-2 lg:mt-0 flex-wrap">
                        <button type="button" class="lf-area-btn active"
                            onclick="window.lfFilterByArea('todas', this)">Todas</button>

                        <div class="border-l border-gray-300 dark:border-gray-700 mx-1 h-6 self-center"></div>
                        <button type="button" class="lf-area-btn" data-module="processo" onclick="window.lfFilterByArea('processo', this)">Processo</button>
                        <button type="button" class="lf-area-btn" data-module="pessoa" onclick="window.lfFilterByArea('pessoa', this)">Pessoa</button>
                        <button type="button" class="lf-area-btn" data-module="empresa" onclick="window.lfFilterByArea('empresa', this)">Empresa</button>
                        <button type="button" class="lf-area-btn" data-module="advogado" onclick="window.lfFilterByArea('advogado', this)">Advogado(a)</button>
                        <button type="button" class="lf-area-btn" data-module="relatorios" onclick="window.lfFilterByArea('relatorios', this)">Relatórios Jurídicos</button>
                        <button type="button" class="lf-area-btn" data-module="jurisprudencia" onclick="window.lfFilterByArea('jurisprudencia', this)">Jurisprudência</button>
                        <button type="button" class="lf-area-btn" data-module="legislacao" onclick="window.lfFilterByArea('legislacao', this)">Legislações</button>
                        <button type="button" class="lf-area-btn" data-module="pessoa_empresa" onclick="window.lfFilterByArea('pessoa_empresa', this)">Pessoa / Empresa</button>
                        <button type="button" class="lf-area-btn" data-module="outro" onclick="window.lfFilterByArea('outro', this)">Outro</button>

                        <div class="border-l border-gray-300 dark:border-gray-700 mx-1 h-6 self-center"></div>

                        <button type="button" class="lf-area-btn text-blue-600 dark:text-blue-400" data-filter="v1"
                            onclick="window.lfFilterByArea('v1', this)">⚡ API V1 (Imediato)</button>
                        <button type="button" class="lf-area-btn text-purple-600 dark:text-purple-400" data-filter="v2"
                            onclick="window.lfFilterByArea('v2', this)">⏳ API V2 (Assíncrono)</button>
                    </div>
                </div>
            </div>

            {{-- ── 36 UNIFIED CARDS GRID ─────────────────────────────────────── --}}
            <div v-pre style="margin-top: 24px;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="font-weight:700;font-size:.95rem;color:#1f2937;" class="dark:text-white">💳 Todos os
                        Serviços</div>
                    <span
                        style="font-size:.72rem;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:9999px;font-weight:700;">SALDO
                        DEBITADO</span>
                    <span id="lf-svc-saldo-display" style="font-size:.8rem;color:#6b7280;margin-left:auto;"
                        class="dark:text-gray-400">💰 Carregando saldo...</span>
                </div>

                <div class="lf-esc-grid">
                    @php
                        $allCards = [
    ['API_V1_BUSCARPORTERMO', 'outro', 'v1', '⚙️', 'Buscar por termo', 'Pesquisa um termo no escavador.', 'GET api/v1/busca', ''],
    // ['API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL', 'relatorios', 'v1', '📊', 'Download do PDF da página do Diário Oficial', 'Retorna em formato PDF, uma página do Diário Oficial pelo identificador.', 'GET api/v1/diarios/{id}/pdf/pagina/{pagina}/baixar', ''],
    // ['API_V1_PGINADODIRIOOFICIAL', 'relatorios', 'v1', '📊', 'Página do Diário Oficial', 'Retorna uma página específica do Diário Oficial pelo seu identificador.', 'GET api/v1/diarios/pagina/{id}', ''],
    // ['INFO_INSTITUICAO', 'empresa', 'v1', '🏢', 'Obter Instituição', 'Retorna dados relacionados a uma instituição (Empresa/Órgão).', 'GET api/v1/instituicoes/{instituicaoId}', ''],
    // ['PESSOAS_INSTITUICAO', 'empresa', 'v1', '🏢', 'Pessoas de uma Instituição', 'Retorna as pessoas que estão associadas a uma instituição.', 'GET api/v1/instituicoes/{instituicaoId}/pessoas', ''],
    // ['PROCESSOS_INSTITUICAO', 'empresa', 'v1', '🏢', 'Processos de uma Instituição', 'Retorna os processos de uma instituição em Diários Oficiais.', 'GET api/v1/instituicoes/{instituicaoId}/processos', ''],
    ['API_V1_BUSCAPORJURISPRUDNCIAS', 'jurisprudencia', 'v1', '⚖️', 'Busca por Jurisprudências', 'Traz a lista paginada dos itens encontrados na busca (Rota legada).', 'GET api/v1/jurisprudencias', ''],
    // ['API_V1_DOCUMENTODEJURISPRUDNCIA', 'jurisprudencia', 'v1', '⚖️', 'Documento de Jurisprudência', 'Traz informações sobre um documento de Jurisprudência específico.', 'GET api/v1/jurisprudencias/{id}', ''],
    // ['API_V1_PDFDEUMAJURISPRUDNCIA', 'jurisprudencia', 'v1', '⚖️', 'PDF de uma jurisprudência', 'Retorna, em formato PDF, um documento de jurisprudência.', 'GET api/v1/jurisprudencias/{id}/pdf', ''],
    ['API_V1_BUSCAPORLEGISLAO', 'legislacao', 'v1', '📜', 'Busca por Legislação', 'Traz a lista paginada dos itens encontrados na busca (Rota legada).', 'GET api/v1/legislacoes', ''],
    // ['API_V1_DOCUMENTODELEGISLAO', 'legislacao', 'v1', '📜', 'Documento de Legislação', 'Traz informações sobre um documento de Legislação específico.', 'GET api/v1/legislacoes/{id}', ''],
    // ['API_V1_FRAGMENTOSDOTEXTODEUMALEGISLAO', 'legislacao', 'v1', '📜', 'Fragmentos do texto de uma Legislação', 'Traz os fragmentos de uma legislação paginados.', 'GET api/v1/legislacoes/{id}/fragmentos', ''],
    // ['API_V1_RETORNARUMAMOVIMENTAO', 'processo', 'v1', '📋', 'Retornar uma movimentação', 'Retorna uma movimentação de processo específica.', 'GET api/v1/movimentacoes/{movimentaco}', ''],
    // ['API_V1_OBTERPESSOA', 'pessoa', 'v1', '👤', 'Obter pessoa', 'Retorna dados relacionados a uma pessoa física pelo identificador.', 'GET api/v1/pessoas/{pessoaId}', ''],
    // ['API_V1_PROCESSOSDEUMAPESSOA', 'pessoa', 'v1', '👤', 'Processos de uma Pessoa', 'Retorna os processos de uma pessoa que saíram em Diários Oficiais.', 'GET api/v1/pessoas/{pessoaId}/processos', ''],
    ['API_V1_AUTOSDEUMPROCESSO', 'processo', 'v1', '📋', 'Autos de um Processo', 'Retorna os autos de um processo na busca de processos em tribunais (Rota legada).', 'GET api/v1/processos/autos', ''],
    ['API_V1_AUTOSDEUMPROCESSODOCUMENTOS', 'processo', 'v1', '📋', 'Autos de um Processo - Documentos', 'Retorna alguns documentos dos autos de um processo, na busca em tribunais (Rota legada).', 'GET api/v1/processos/autos', ''],
    ['API_V1_BUSCARPROCESSOSDOSDIRIOSPOROAB', 'advogado', 'v1', '💼', 'Buscar processos dos Diários por OAB', 'Busca processos que estão nos Diários Oficiais do Escavador que estão relacionados ao OAB informado.', 'GET api/v1/oab/{estado}/{numero}/processos', ''],
    ['BUSCA_PROC_DIARIO_NUM', 'processo', 'v1', '📋', 'Buscar processos dos Diários por número', 'Busca processos que estão nos Diários Oficiais do Escavador e contenham o número único informado.', 'GET api/v1/processos/numero/{numero}', ''],
    // ['API_V1_ENVOLVIDOSDEUMPROCESSO', 'processo', 'v1', '📋', 'Envolvidos de um Processo', 'Retorna as partes envolvidas no processo.', 'GET api/v1/processos/{processoId}/envolvidos', ''],
    // ['API_V1_MOVIMENTAESDEUMPROCESSODO', 'processo', 'v1', '📋', 'Movimentações de um processo (D.O.)', 'Retorna as movimentações capturadas via diários oficiais.', 'GET api/v1/processos/{processoId}/movimentacoes', ''],
    // ['API_V1_PROCESSONODIRIOOFICIAL', 'processo', 'v1', '📋', 'Processo no Diário Oficial', 'Retorna detalhes da capa de um processo no Escavador.', 'GET api/v1/processos/{id}', ''],
    ['API_V1_PESQUISARPROCESSONOTRIBUNAL', 'processo', 'v1', '📋', 'Pesquisar processo no tribunal (assíncrono)', 'A busca é feita diretamente nos sites dos tribunais, pelos robôs do Escavador. (Inclui as opções de baixar autos ou documentos públicos por parâmetros).', 'POST api/v1/processo-tribunal/{numero}/async', ''],
    ['API_V1_PESQUISARPROCESSOSPORCPFOUCNPJ', 'pessoa_empresa', 'v1', '👥', 'Pesquisar processos no site do tribunal por CPF ou CNPJ (assíncrono)', 'A busca é feita diretamente nos sites dos tribunais através do CPF/CNPJ.', 'POST api/v1/tribunal/{origem}/busca-por-documento/async', ''],
    ['API_V1_PESQUISARPROCESSOSPOROAB', 'advogado', 'v1', '💼', 'Pesquisar processos no site do tribunal por OAB (assíncrono)', 'A busca é feita diretamente nos sites dos tribunais através do número OAB.', 'POST api/v1/tribunal/{origem}/busca-por-oab/async', ''],
    ['API_V1_PESQUISARPROCESSOSPORNOME', 'pessoa', 'v1', '👤', 'Pesquisar processos no site do tribunal por nome do envolvido (assíncrono)', 'A busca é feita diretamente nos sites dos tribunais através do nome da parte.', 'POST api/v1/tribunal/{origem}/busca-por-nome/async', ''],
    ['API_V2_SOLICITARGERAOATUALIZAODERESUMOIA', 'processo', 'v2', '📋', 'Solicitar geração/atualização de Resumo IA', 'Esta rota registra uma solicitação para gerar ou atualizar o resumo inteligente do processo.', 'POST api/v2/processos/numero_cnj/{numero}/ia/resumo/solicitar-atualizacao', ''],
    ['API_V2_SOLICITARATUALIZAODEUMPROCESSO', 'processo', 'v2', '📋', 'Solicitar atualização de um processo', 'Solicita a atualização de um processo nos sistemas dos Tribunais para obter as informações mais recentes.', 'POST api/v2/processos/numero_cnj/{numero}/solicitar-atualizacao', ''],
    ['API_V2_ATUALIZAOBAIXANDOALGUNSDOCUMENTOS', 'processo', 'v2', '📋', 'Solicitar atualização de um processo (baixando alguns documentos)', 'Atualiza o processo no tribunal e baixa apenas alguns documentos específicos dos autos enviando autos=1 e documentos_especificos.', 'POST api/v2/processos/numero_cnj/{numero}/solicitar-atualizacao', ''],
    ['API_V2_ATUALIZAOBAIXANDOAUTOSINTEIROS', 'processo', 'v2', '📋', 'Solicitar atualização de um processo (baixando autos inteiros)', 'Atualiza o processo no tribunal e baixa a íntegra dos autos do processo enviando autos=1.', 'POST api/v2/processos/numero_cnj/{numero}/solicitar-atualizacao', ''],
    ['API_V2_ATUALIZAOBAIXANDODOCUMENTOSPBLICOS', 'processo', 'v2', '📋', 'Solicitar atualização de um processo (baixando documentos públicos)', 'Atualiza o processo no tribunal e baixa os documentos públicos enviando documentos_publicos=1.', 'POST api/v2/processos/numero_cnj/{numero}/solicitar-atualizacao', ''],
    ['API_V2_PROCESSOSDEUMADVOGADOPOROAB', 'advogado', 'v2', '💼', 'Processos de um advogado por OAB', 'Retorna os processos de um advogado a partir da OAB.', 'GET api/v2/advogado/processos', ''],
    ['API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME', 'pessoa_empresa', 'v2', '👥', 'Processos do envolvido por Nome ou CPF/CNPJ', 'Retorna os processos de um envolvido a partir do nome ou CPF/CNPJ.', 'GET api/v2/envolvido/processos', ''],
    ['API_V2_RESUMODEPROCESSOSPOROAB', 'advogado', 'v2', '💼', 'Resumo de processos do advogado por OAB', 'Retorna um resumo do advogado a partir da OAB, mostrando a quantidade de processos e o tipo da OAB informada.', 'GET api/v2/advogado/resumo', ''],
    ['API_V2_RESUMODEPROCESSOSDOENVOLVIDO', 'pessoa_empresa', 'v2', '👥', 'Resumo de Processos do envolvido por Nome ou CPF/CNPJ', 'Retorna a quantidade de processos de um envolvido a partir do nome ou CPF/CNPJ.', 'GET api/v2/envolvido/resumo', ''],
    ['API_V2_AUTOSDOPROCESSOPBLICOSERESTRITOS', 'processo', 'v2', '📋', 'Autos do processo (públicos e restritos)', 'Retorna a lista paginada de todos os documentos de um processo (públicos e restritos), conhecidos como autos.', 'GET api/v2/processos/numero_cnj/{numero}/autos', ''],
    ['API_V2_PROCESSOPORNUMERAOCNJCAPA', 'processo', 'v2', '📋', 'Processo por numeração CNJ', 'Retorna dados completos da capa de um processo judicial brasileiro a partir da numeração CNJ.', 'GET api/v2/processos/numero_cnj/{numero}', ''],
    ['API_V2_DOCUMENTOSPBLICOSDEUMPROCESSO', 'processo', 'v2', '📋', 'Documentos públicos de um processo', 'Retorna uma lista dos documentos públicos de um processo a partir da numeração CNJ.', 'GET api/v2/processos/numero_cnj/{numero}/documentos-publicos', ''],
    ['API_V2_ENVOLVIDOSDEUMPROCESSO', 'processo', 'v2', '📋', 'Envolvidos de um processo', 'Retorna uma lista dos envolvidos de um processo a partir da numeração CNJ.', 'GET api/v2/processos/numero_cnj/{numero}/envolvidos', ''],
    ['API_V2_MOVIMENTAESDEUMPROCESSO', 'processo', 'v2', '📋', 'Movimentações de um processo', 'Retorna as movimentações de um processo a partir do número CNJ.', 'GET api/v2/processos/numero_cnj/{numero}/movimentacoes', ''],
    ['API_V2_RESUMOINTELIGENTEDEUMPROCESSO', 'processo', 'v2', '📋', 'Resumo inteligente de um processo', 'Retorna o resumo inteligente do processo gerado por IA, caso o resumo já exista.', 'GET api/v2/processos/numero_cnj/{numero}/ia/resumo', ''],
    ['TRIBUNAIS_SISTEMAS', 'outro', 'v1', '🏛️', 'Retornar detalhes de um Tribunal', 'Retorna os sistemas e origens dos tribunais suportados pelo Escavador.', 'GET api/v1/tribunal/origens', ''],

];

                        $prices = [
    'API_V1_BUSCARPORTERMO' => 3,
    'API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL' => 3,
    'API_V1_PGINADODIRIOOFICIAL' => 3,
    'INFO_INSTITUICAO' => 3,
    'PESSOAS_INSTITUICAO' => 3,
    'PROCESSOS_INSTITUICAO' => 3,
    'API_V1_BUSCAPORJURISPRUDNCIAS' => 3,
    'API_V1_DOCUMENTODEJURISPRUDNCIA' => 3,
    'API_V1_PDFDEUMAJURISPRUDNCIA' => 3,
    'API_V1_BUSCAPORLEGISLAO' => 3,
    'API_V1_DOCUMENTODELEGISLAO' => 3,
    'API_V1_FRAGMENTOSDOTEXTODEUMALEGISLAO' => 3,
    'API_V1_REGISTRARNOVOMONITORAMENTO' => 0,
    'API_V1_REGISTRARNOVOMONITORAMENTOTRIBUNAL' => 0,
    'API_V1_RETORNARUMAMOVIMENTAO' => 3,
    'API_V1_OBTERPESSOA' => 3,
    'API_V1_PROCESSOSDEUMAPESSOA' => 3,
    'API_V1_AUTOSDEUMPROCESSO' => 3,
    'API_V1_AUTOSDEUMPROCESSODOCUMENTOS' => 0.75,
    'API_V1_BUSCARPROCESSOSDOSDIRIOSPOROAB' => 3,
    'BUSCA_PROC_DIARIO_NUM' => 3,
    'API_V1_ENVOLVIDOSDEUMPROCESSO' => 3,
    'API_V1_MOVIMENTAESDEUMPROCESSODO' => 3,
    'API_V1_PROCESSONODIRIOOFICIAL' => 3,
    'API_V1_PESQUISARPROCESSONOTRIBUNAL' => 3,
    'API_V1_PESQUISARPROCESSOSPORCPFOUCNPJ' => 3,
    'API_V1_PESQUISARPROCESSOSPOROAB' => 3,
    'API_V1_PESQUISARPROCESSOSPORNOME' => 3,
    'API_V2_SOLICITARGERAOATUALIZAODERESUMOIA' => 0.08,
    'API_V2_SOLICITARATUALIZAODEUMPROCESSO' => 3,
    'API_V2_ATUALIZAOBAIXANDOALGUNSDOCUMENTOS' => 0.75,
    'API_V2_ATUALIZAOBAIXANDOAUTOSINTEIROS' => 1.5,
    'API_V2_ATUALIZAOBAIXANDODOCUMENTOSPBLICOS' => 0.2,
    'API_V2_PROCESSOSDEUMADVOGADOPOROAB' => 0,
    'API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME' => 0,
    'API_V2_RESUMODEPROCESSOSPOROAB' => 3,
    'API_V2_RESUMODEPROCESSOSDOENVOLVIDO' => 3,
    'API_V2_AUTOSDOPROCESSOPBLICOSERESTRITOS' => 0.18,
    'API_V2_PROCESSOPORNUMERAOCNJCAPA' => 3,
    'API_V2_DOCUMENTOSPBLICOSDEUMPROCESSO' => 0.06,
    'API_V2_ENVOLVIDOSDEUMPROCESSO' => 0.05,
    'API_V2_MOVIMENTAESDEUMPROCESSO' => 3,
    'API_V2_RESUMOINTELIGENTEDEUMPROCESSO' => 0.05,
    'API_V2_CRIARNOVOMONITORAMENTODEPROCESSO' => 0,
    'API_V2_CRIARNOVOMONITORAMENTODETERMOSNOMES' => 0,
    'TRIBUNAIS_SISTEMAS' => 0,
];

                    @endphp

                    @foreach($allCards as $card)
                        {{-- HTTP: {{ $card[6] ?? '???' }} --}}
                        <div class="lf-esc-card" data-module="{{ $card[1] }}" data-api="{{ $card[2] }}">
                            <div>
                                <div class="lf-esc-card-header">
                                    <div class="lf-esc-card-icon">{{ $card[3] }}</div>
                                    <div class="lf-esc-card-title">{{ $card[4] }}</div>
                                </div>
                                <div class="lf-esc-card-desc">{{ $card[5] }}</div>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:8px;">
                                    <span class="lf-esc-badge lf-esc-badge-{{ $card[2] }}">API
                                        {{ strtoupper($card[2]) }}@if($card[2] === 'v2') · Sync/Async @endif</span>
                                    @if(isset($card[8]) && ($card[8] === true || $card[8] === 'oab'))
                                        <span
                                            style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:6px;font-size:.70rem;font-weight:700;">
                                            🔒 Requer Autenticação (OAB)
                                        </span>
                                    @elseif(isset($card[8]) && $card[8] === 'cert')
                                        <span
                                            style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:#fff7ed;color:#92400e;border:1px solid #fed7aa;border-radius:6px;font-size:.70rem;font-weight:700;">
                                            🔐 Requer Certificado Digital
                                        </span>
                                    @endif
                                    @if((float)$prices[$card[0]] <= 0.00)
                                        <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                            Grátis
                                        </span>
                                    @else
                                        <span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                            💰 
                                            {{ $card[2] == 'v2' && strpos($card[0], 'ATUALIZACAO') !== false && $card[0] !== 'ATUALIZAR_PROCESSO' ? 'Min ' : ($card[0] === 'ATUALIZAR_PROCESSO' ? 'Min ' : '') }}
                                            R$ {{ number_format($prices[$card[0]], 2, ',', '.') }}{{ $card[7] ?? '' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div style="margin-top:16px;border-top:1px solid #f3f4f6;padding-top:12px;"
                                class="dark:border-gray-800">
                                <button class="lf-esc-btn" type="button" onclick="window.lfSvc.open('{{ $card[0] }}')">🚀
                                    Executar</button>
                            </div>
                        </div>
                    @endforeach

                </div>{{-- /lf-esc-grid --}}
            </div>{{-- /v-pre --}}
        </div>{{-- /flex flex-col --}}

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
            <script>
                // Only one modal system exists now (paid)
                (function () {
                    'use strict';

                    var CSRF = '{{ csrf_token() }}';
                    var ROUTE_SERVICO = "{{ route('lawfirm.escavador.servico') }}";
                    var ROUTE_SALDO = "{{ route('lawfirm.escavador.saldo_cliente') }}";
                    var ROUTE_CERTIFICADOS = "{{ route('lawfirm.escavador.certificados.index') }}";

                    var UF_OPTIONS = 'AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO'.split(',');

                    var SVC_INFO = {
        'API_V1_BUSCARPORTERMO': { label: '⚙️ Buscar por termo', price: 'R$ 3,00', fields: [
            { name: 'q', label: 'Termo de Busca', required: true },
            { name: 'qo', label: 'Tipo de Entidade', type: 'select', options: ['t|Todos (Padrão)', 'p|Pessoas', 'i|Instituições', 'd|Diários Oficiais', 'en|Pessoas e Instituições (Envolvidos)'], required: true },
            { name: 'utilizar_operadores_logicos', label: 'Usar Operadores Lógicos?', type: 'select', options: ['0|Não (Normal)', '1|Sim (Avançada: AND/OR/NOT)'], required: false }
        ] },
        'API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL': { label: '📊 Download do PDF da página do Diário Oficial', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID da Publicação', required: true }, { name: 'pagina', label: 'Página', required: true }] },
        'API_V1_PGINADODIRIOOFICIAL': { label: '📊 Página do Diário Oficial', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID da Página', required: true }] },
        'INFO_INSTITUICAO': { label: '🏢 Obter Instituição', price: 'R$ 3,00', fields: [{ name: 'instituicaoId', label: 'ID Instituição (Opcional)', type: 'number', min: 1, required: false }] },
        'PESSOAS_INSTITUICAO': { label: '🏢 Pessoas de uma Instituição', price: 'R$ 3,00', fields: [
            { name: 'instituicaoId', label: 'ID da Instituição (Opcional)', type: 'number', min: 1, required: false },
            { name: 'limit', label: 'Limite (máx 60)', type: 'number', min: 1, max: 60, required: false },
            { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
        ] },
        'PROCESSOS_INSTITUICAO': { label: '🏢 Processos de uma Instituição', price: 'R$ 3,00', fields: [
            { name: 'instituicaoId', label: 'ID da Instituição', type: 'number', min: 1, required: false },
            { name: 'limit', label: 'Limite (máx 60)', type: 'number', min: 1, max: 60, required: false },
            { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
        ] },
        'API_V1_BUSCAPORJURISPRUDNCIAS': { label: '⚖️ Busca por Jurisprudências', price: 'R$ 3,00', fields: [{ name: 'q', label: 'Termo (Ex: Indenização)', required: true }] },
        'API_V1_DOCUMENTODEJURISPRUDNCIA': { label: '⚖️ Documento de Jurisprudência', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID Documento', required: true }] },
        'API_V1_PDFDEUMAJURISPRUDNCIA': { label: '⚖️ PDF de uma jurisprudência', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID Documento', required: true }] },
        'API_V1_BUSCAPORLEGISLAO': { label: '📜 Busca por Legislação', price: 'R$ 3,00', fields: [{ name: 'q', label: 'Termo de Busca', required: true }] },
        'API_V1_DOCUMENTODELEGISLAO': { label: '📜 Documento de Legislação', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID Documento', required: true }] },
        'API_V1_FRAGMENTOSDOTEXTODEUMALEGISLAO': { label: '📜 Fragmentos do texto de uma Legislação', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID Documento', required: true }] },
        'API_V1_RETORNARUMAMOVIMENTAO': { label: '📋 Retornar uma movimentação', price: 'R$ 3,00', fields: [{ name: 'movimentaco', label: 'ID do Movimento', required: true }] },
        'API_V1_OBTERPESSOA': { label: '👤 Obter pessoa', price: 'R$ 3,00', fields: [{ name: 'pessoaId', label: 'ID Pessoa', required: true }] },
        'API_V1_PROCESSOSDEUMAPESSOA': { label: '👤 Processos de uma Pessoa', price: 'R$ 3,00', fields: [{ name: 'pessoaId', label: 'ID da Pessoa', required: true }] },
        'API_V1_AUTOSDEUMPROCESSO': { label: '📋 Autos de um Processo', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID Processo', required: true }] },
        'API_V1_AUTOSDEUMPROCESSODOCUMENTOS': { label: '📋 Autos de um Processo - Documentos', price: 'R$ 0,75', fields: [{ name: 'id', label: 'ID Processo', required: true }], req_cert: true },
        'API_V1_BUSCARPROCESSOSDOSDIRIOSPOROAB': { label: '💼 Buscar processos dos Diários por OAB', price: 'R$ 3,00', fields: [{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero', label: 'Número OAB', required: true }] },
        'BUSCA_PROC_DIARIO_NUM': { label: '📋 Buscar processos dos Diários por número', price: 'R$ 3,00', fields: [
            { name: 'numero', label: 'Nº do Processo (opcional)', placeholder: '0000000-00.0000.0.00.0000', required: false },
            { name: 'match_exato', label: 'Busca Exata?', type: 'select', options: ['0|Não', '1|Sim'], required: false }
        ] },
        'API_V1_ENVOLVIDOSDEUMPROCESSO': { label: '📋 Envolvidos de um Processo', price: 'R$ 3,00', fields: [{ name: 'processoId', label: 'ID Processo', required: true }] },
        'API_V1_MOVIMENTAESDEUMPROCESSODO': { label: '📋 Movimentações de um processo (D.O.)', price: 'R$ 3,00', fields: [{ name: 'processoId', label: 'ID Processo', required: true }] },
        'API_V1_PROCESSONODIRIOOFICIAL': { label: '📋 Processo no Diário Oficial', price: 'R$ 3,00', fields: [{ name: 'id', label: 'ID Processo', required: true }] },
        'API_V1_PESQUISARPROCESSONOTRIBUNAL': { label: '📋 Pesquisar processo no tribunal', price: 'R$ 3,00', fields: [
            { name: 'numero', label: 'Nº do Processo CNJ', required: true },
            { name: 'processo', label: 'Processo ID/CNJ (numeração única)', type: 'number', required: false },
            { name: 'send_callback', label: 'Enviar p/ Callback?', type: 'select', options: ['0|Não', '1|Sim'], required: false },
            { name: 'wait', label: 'Aguardar resposta síncrona (1 min)?', type: 'select', options: ['0|Não', '1|Sim'], required: false },
            { name: 'autos', label: 'Trazer autos restritos?', type: 'select', options: ['0|Não', '1|Sim (Requer senha)'], required: false },
            { name: 'documentos_publicos', label: 'Trazer documentos públicos?', type: 'select', options: ['0|Não', '1|Sim'], required: false },
            { name: 'usuario', label: 'Usuário do Tribunal', required: false },
            { name: 'senha', label: 'Senha do Tribunal', type: 'password', required: false },
            { name: 'origem', label: 'Origem (Ex: STJ, STF)', required: false },
            { name: 'tipo_numero', label: 'Tipo de Número', type: 'select', options: ['|Padrão', 'classe_numero|Classe/Número (STF)', 'numero_registro|Registro (STJ)'], required: false },
            { name: 'dias_ultima_atualizacao', label: 'Dias últ. att. (Cache)', type: 'number', required: false },
            { name: 'utilizar_certificado', label: 'Usar Certificado OAB?', type: 'select', options: ['0|Não', '1|Sim'], required: false },
            { name: 'certificado_id', label: 'ID Certificado (Opcional)', type: 'number', required: false },
            { name: 'documentos_especificos', label: 'Docs específicos (ex: INICIAIS)', required: false }
        ] },
        'API_V1_PESQUISARPROCESSOSPORCPFOUCNPJ': { label: '👥 Pesquisar processos por CPF ou CNPJ', price: 'R$ 3,00', fields: [{ name: 'origem', label: 'Sigla Origem (Ex: tjsp)', required: true }, { name: 'documento', label: 'CPF ou CNPJ', required: true }] },
        'API_V1_PESQUISARPROCESSOSPOROAB': { label: '💼 Pesquisar processos por OAB', price: 'R$ 3,00', fields: [{ name: 'origem', label: 'Sigla Origem (Ex: tjsp)', required: true }, { name: 'oab', label: 'Número OAB', required: true }] },
        'API_V1_PESQUISARPROCESSOSPORNOME': { label: '👤 Pesquisar processos por Nome', price: 'R$ 3,00', fields: [{ name: 'origem', label: 'Sigla Origem (Ex: tjsp)', required: true }, { name: 'nome', label: 'Nome da Pessoa', required: true }] },
        'API_V2_SOLICITARGERAOATUALIZAODERESUMOIA': { label: '📋 Solicitar geração/atualização de Resumo IA', price: 'R$ 0,08', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'API_V2_SOLICITARATUALIZAODEUMPROCESSO': { label: '📋 Solicitar atualização de um processo', price: 'R$ 3,00', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'API_V2_ATUALIZAOBAIXANDOALGUNSDOCUMENTOS': { label: '📋 Atualização baixando alguns documentos', price: 'R$ 0,75', fields: [{ name: 'numero', label: 'Número CNJ', required: true }], req_cert: true },
        'API_V2_ATUALIZAOBAIXANDOAUTOSINTEIROS': { label: '📋 Atualização baixando autos inteiros', price: 'R$ 1,50', fields: [{ name: 'numero', label: 'Número CNJ', required: true }], req_cert: true },
        'API_V2_ATUALIZAOBAIXANDODOCUMENTOSPBLICOS': { label: '📋 Atualização baixando documentos públicos', price: 'R$ 0,20', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'API_V2_PROCESSOSDEUMADVOGADOPOROAB': { label: '💼 Processos de um advogado por OAB', price: 'R$ 3,00 (+ R a cada 200)', fields: [{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero_oab', label: 'Número OAB', required: true }] },
        'API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME': { label: '👥 Processos do envolvido por CPF/CNPJ ou Nome', price: 'R$ 3,00 (+ R a cada 200)', fields: [{ name: 'cpf_cnpj', label: 'CPF/CNPJ ou Nome', required: true }] },
        'API_V2_RESUMODEPROCESSOSPOROAB': { label: '💼 Resumo de processos por OAB', price: 'R$ 3,00', fields: [{ name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true }, { name: 'numero_oab', label: 'Número OAB', required: true }] },
        'API_V2_RESUMODEPROCESSOSDOENVOLVIDO': { label: '👥 Resumo de Processos do Envolvido', price: 'R$ 3,00', fields: [{ name: 'cpf_cnpj', label: 'CPF/CNPJ ou Nome', required: true }] },
        'API_V2_AUTOSDOPROCESSOPBLICOSERESTRITOS': { label: '📋 Autos do processo (públicos e restritos)', price: 'R$ 0,18', fields: [{ name: 'numero', label: 'Número CNJ', required: true }], req_cert: true },
        'API_V2_PROCESSOPORNUMERAOCNJCAPA': { label: '📋 Processo por numeração CNJ (Capa)', price: 'R$ 3,00', fields: [{ name: 'numero', label: 'Número CNJ', placeholder: '0000000-00.0000.0.00.0000', required: true }] },
        'API_V2_DOCUMENTOSPBLICOSDEUMPROCESSO': { label: '📋 Documentos públicos de um processo', price: 'R$ 0,06', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'API_V2_ENVOLVIDOSDEUMPROCESSO': { label: '📋 Envolvidos de um processo', price: 'R$ 0,05', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'API_V2_MOVIMENTAESDEUMPROCESSO': { label: '📋 Movimentações de um processo', price: 'R$ 3,00', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'API_V2_RESUMOINTELIGENTEDEUMPROCESSO': { label: '📋 Resumo Inteligente de um processo', price: 'R$ 0,05', fields: [{ name: 'numero', label: 'Número CNJ', required: true }] },
        'TRIBUNAIS_SISTEMAS': { label: '🏛️ Retornar detalhes de um Tribunal', price: 'Grátis', fields: [
            { name: 'sigla', label: 'Sigla (Opcional)', required: false },
            { name: 'nome', label: 'Nome (Opcional)', required: false },
            { name: 'busca_processo', label: 'Busca por processo?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false },
            { name: 'busca_nome', label: 'Busca por nome?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false },
            { name: 'busca_oab', label: 'Busca por OAB?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false },
            { name: 'busca_documento', label: 'Busca por CPF/CNPJ?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false },
            { name: 'disponivel_autos', label: 'Disponibiliza autos?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false },
            { name: 'documentos_publicos', label: 'Docs Públicos?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false },
            { name: 'utilizar_certificado_digital', label: 'Usa Certificado?', type: 'select', options: ['|Ambos', 'true|Sim', 'false|Não'], required: false }
        ] },
    };

                    var currentType = '';
                    var currentBalance = 0;

                    function jsonToMarkdown(obj, depth = 0) {
                        if (obj === null || obj === undefined) return "null";
                        if (typeof obj !== 'object') return String(obj);
                        
                        let md = '';
                        let indent = '  '.repeat(depth);
                        
                        if (Array.isArray(obj)) {
                            if (obj.length === 0) return '[]';
                            obj.forEach(item => {
                                if (typeof item === 'object' && item !== null) {
                                    md += `${indent}- \n${jsonToMarkdown(item, depth + 1)}`;
                                } else {
                                    md += `${indent}- ${item}\n`;
                                }
                            });
                        } else {
                            if (Object.keys(obj).length === 0) return '{}';
                            for (let key in obj) {
                                let val = obj[key];
                                if (typeof val === 'object' && val !== null) {
                                    if (Array.isArray(val) && val.length === 0) {
                                        md += `${indent}**${key}:** []\n\n`;
                                    } else {
                                        md += `${indent}**${key}:**\n\n${jsonToMarkdown(val, depth + 1)}`;
                                    }
                                } else {
                                    md += `${indent}**${key}:** ${val}\n\n`;
                                }
                            }
                        }
                        return md;
                    }

                    function copyContent() {
                        var box = document.getElementById('lf-svc-result-content');
                        if (!box) return;
                        navigator.clipboard.writeText(box.innerText).then(function () {
                            alert("Copiado com sucesso!");
                        });
                    }

                    window.lfCopyContent = copyContent;

                    function loadBalance() {
                        fetch(ROUTE_SALDO, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                currentBalance = d.ai_tokens_balance || 0;
                                document.getElementById('lf-svc-saldo-display').textContent = '💰 Saldo: R$ ' + currentBalance.toFixed(2).replace('.', ',');
                            })
                            .catch(function () {
                                document.getElementById('lf-svc-saldo-display').textContent = '💰 Saldo: indisponível';
                            });
                    }

                    document.addEventListener('DOMContentLoaded', function () {
                        loadBalance();
                        var m = document.getElementById('lf-svc-modal');
                        if (m) document.body.appendChild(m);
                    });

                    function openSvc(type) {
                        currentType = type;
                        var info = SVC_INFO[type];
                        if (!info) return;

                        document.getElementById('lf-svc-modal-title').textContent = info.label;
                        document.getElementById('lf-svc-price-badge').textContent = '💰 ' + info.price;
                        document.getElementById('lf-svc-balance').textContent = 'Seu saldo: R$ ' + currentBalance.toFixed(2).replace('.', ',');

                        document.getElementById('lf-svc-error').style.display = 'none';
                        document.getElementById('lf-svc-success').style.display = 'none';

                        var authWarning = document.getElementById('lf-svc-auth-warning');
                        if (authWarning) {
                            authWarning.style.display = info.req_auth ? 'block' : 'none';
                        }

                        document.getElementById('lf-svc-fields').style.display = 'flex';
                        document.getElementById('lf-svc-btn-submit').style.display = 'inline-block';

                        var prevResult = document.getElementById('lf-svc-result-area');
                        if (prevResult) prevResult.style.display = 'none';

                        var container = document.getElementById('lf-svc-fields');
                        container.innerHTML = '';

                        info.fields.forEach(function (f) {
                            var wrap = document.createElement('div');
                            wrap.className = 'lf-esc-field';

                            var label = document.createElement('label');
                            label.className = 'lf-esc-label';
                            label.textContent = f.label + (f.required ? ' *' : '');
                            wrap.appendChild(label);

                            if (f.type === 'select') {
                                var select = document.createElement('select');
                                select.className = 'lf-esc-select';
                                select.id = 'lf-svc-field-' + f.name;
                                select.name = f.name;

                                (f.options || []).forEach(function (opt) {
                                    var option = document.createElement('option');
                                    if (typeof opt === 'string' && opt.indexOf('|') > -1) {
                                        var parts = opt.split('|');
                                        option.value = parts[0];
                                        option.textContent = parts[1];
                                    } else {
                                        option.value = opt;
                                        option.textContent = opt;
                                    }
                                    select.appendChild(option);
                                });
                                wrap.appendChild(select);
                            } else {
                                var input = document.createElement('input');
                                input.type = f.type || 'text';
                                input.className = 'lf-esc-input';
                                input.id = 'lf-svc-field-' + f.name;
                                input.name = f.name;
                                input.placeholder = f.placeholder || '';
                                if (f.min !== undefined) input.min = f.min;
                                if (f.max !== undefined) input.max = f.max;
                                wrap.appendChild(input);
                            }

                            if (f.hint) {
                                var hint = document.createElement('div');
                                hint.className = 'lf-esc-hint';
                                hint.style.fontSize = '0.75rem';
                                hint.style.color = '#6b7280';
                                hint.style.marginTop = '4px';
                                hint.textContent = f.hint;
                                wrap.appendChild(hint);
                            }

                            container.appendChild(wrap);
                        });

                        if (info.req_cert) {
                            if (!info.fields.find(f => f.name === 'certificado_id')) {
                                info.fields.push({ name: 'certificado_id', required: true });
                            }

                            var certWrap = document.createElement('div');
                            certWrap.className = 'lf-esc-field';
                            certWrap.innerHTML = '<label class="lf-esc-label">Certificado Digital *</label>' +
                                                 '<select id="lf-svc-field-certificado_id" name="certificado_id" class="lf-esc-select" required>' +
                                                 '<option value="">Carregando certificados...</option>' +
                                                 '</select>';
                            container.appendChild(certWrap);

                            fetch(ROUTE_CERTIFICADOS, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.json())
                                .then(res => {
                                    var select = document.getElementById('lf-svc-field-certificado_id');
                                    if (!select) return;
                                    select.innerHTML = '<option value="">Selecione um certificado</option>';
                                    var certs = [];
                                    if (res.success && res.data) {
                                        certs = Array.isArray(res.data) ? res.data : (res.data.data || []);
                                    }
                                    if (certs.length === 0) {
                                        select.innerHTML = '<option value="">Nenhum certificado cadastrado. Vá em Gerenciar Certificados.</option>';
                                    } else {
                                        certs.forEach(function(c) {
                                            var opt = document.createElement('option');
                                            opt.value = c.id;
                                            opt.textContent = c.emissor + ' (' + (c.cpf_cnpj || 'Sem doc') + ')';
                                            select.appendChild(opt);
                                        });
                                    }
                                })
                                .catch(function() {
                                    var select = document.getElementById('lf-svc-field-certificado_id');
                                    if (select) select.innerHTML = '<option value="">Erro ao carregar certificados.</option>';
                                });
                        }

                        document.getElementById('lf-svc-modal').style.display = 'block';
                    }

                    function closeSvc() {
                        document.getElementById('lf-svc-modal').style.display = 'none';
                        currentType = '';
                    }

                    function executeSvc() {
                        var info = SVC_INFO[currentType];
                        if (!info) return;

                        var data = {};
                        var valid = true;
                        info.fields.forEach(function (f) {
                            var inp = document.getElementById('lf-svc-field-' + f.name);
                            if (!inp) return;
                            var val = inp.value.trim();
                            if (f.required && !val) { valid = false; }
                            data[f.name] = val;
                        });

                        if (!valid) {
                            showSvcError('Preencha os campos obrigatórios.');
                            return;
                        }

                        var btn = document.getElementById('lf-svc-btn-submit');
                        btn.disabled = true;
                        btn.textContent = '⏳ Executando...';
                        document.getElementById('lf-svc-error').style.display = 'none';
                        document.getElementById('lf-svc-success').style.display = 'none';

                        fetch(ROUTE_SERVICO, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ service_type: currentType, data: data })
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                btn.disabled = false;
                                btn.textContent = '🚀 Executar';
                                if (d.success) {

                                    var msg = "Consulta concluída com sucesso!";
                                    if (d.async) msg = "Solicitação enviada! O resultado será processado em segundo plano e você será notificado.";

                                    document.getElementById('lf-svc-success').textContent = msg;
                                    document.getElementById('lf-svc-success').style.display = 'block';

                                    if (!d.async) {
                                        document.getElementById('lf-svc-fields').style.display = 'none';
                                        btn.style.display = 'none';

                                        var mardownStr = jsonToMarkdown(d.data || d);
                                        var htmlContent = marked.parse(mardownStr);
                                        var resultHtml = '<div id="lf-svc-result-area" style="margin-top:10px;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><h4 style="font-weight:600;color:#374151;">Resposta Formatada</h4><button type="button" onclick="window.lfCopyContent()" style="font-size:0.8rem;background:#f3f4f6;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;">📋 Copiar Resposta</button></div><div class="lf-esc-data-box markdown-body" style="display:block;max-height:400px;overflow:auto;" id="lf-svc-result-content">' + htmlContent + '</div></div>';

                                        var parent = document.getElementById('lf-svc-fields').parentElement;
                                        var prev = document.getElementById('lf-svc-result-area');
                                        if (prev) prev.remove();
                                        parent.insertAdjacentHTML('beforeend', resultHtml);
                                    }

                                    loadBalance();
                                } else {
                                    showSvcError(d.error || d.message || 'Erro ao executar o serviço.');
                                }
                            })
                            .catch(function (err) {
                                btn.disabled = false;
                                btn.textContent = '🚀 Executar';
                                showSvcError('Erro de conexão. Tente novamente.');
                            });
                    }

                    function showSvcError(msg) {
                        var box = document.getElementById('lf-svc-error');
                        box.textContent = '⚠️ ' + msg;
                        box.style.display = 'block';
                    }

                    window.lfFilterByArea = function (filterKey, btn) {
                        document.querySelectorAll('.lf-area-btn').forEach(function (b) { b.classList.remove('active'); });
                        if (btn) btn.classList.add('active');

                        var isTagFilter = ['v1', 'v2', 'gratis'].includes(filterKey);
                        var paidCards = document.querySelectorAll('.lf-esc-grid')[0]?.querySelectorAll('.lf-esc-card') || [];

                        paidCards.forEach(function (card) {
                            var cardModule = card.dataset.module || '';
                            var cardApi = card.dataset.api || '';

                            var matches = false;
                            if (filterKey === 'todas') {
                                matches = true;
                            } else if (isTagFilter) {
                                if (filterKey === 'v1' && cardApi === 'v1') matches = true;
                                if (filterKey === 'v2' && cardApi === 'v2') matches = true;
                            } else {
                                matches = (cardModule === filterKey);
                            }
                            card.style.display = matches ? '' : 'none';
                        });
                    };

                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && document.getElementById('lf-svc-modal').style.display !== 'none') closeSvc();
                    });

                    window.lfSvc = { open: openSvc, close: closeSvc, execute: executeSvc };

                })();
            </script>
        @endpush

        {{-- ── PAID SERVICE MODAL ────────────────────────────────────── --}}
        <div id="lf-svc-modal" style="display:none;" v-pre>
            <div class="lf-esc-overlay" onclick="window.lfSvc.close()"></div>
            <div class="lf-esc-dialog" style="max-width:540px;width:95%;">
                <div class="lf-esc-modal-header">
                    <h3 id="lf-svc-modal-title" class="lf-esc-modal-title">🚀 Executar Serviço</h3>
                    <button onclick="window.lfSvc.close()" class="lf-esc-close-btn">✕</button>
                </div>
                <div style="padding:20px;display:flex;flex-direction:column;gap:16px;">

                    <div
                        style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:linear-gradient(135deg,rgba(13,148,136,.06),rgba(2,132,199,.06));border:1px solid #99f6e4;border-radius:10px;">
                        <div>
                            <div
                                style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                                Custo do Serviço</div>
                            <div id="lf-svc-price-badge" style="font-size:1.3rem;font-weight:800;color:#0d9488;">R$ 0,00
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div
                                style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                                Saldo Disponível</div>
                            <div id="lf-svc-balance" style="font-size:.95rem;font-weight:700;color:#374151;"
                                class="dark:text-gray-200">R$ 0,00</div>
                        </div>
                    </div>

                    <div id="lf-svc-auth-warning"
                        style="display:none;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;margin-bottom:-4px;">
                        <div
                            style="display:flex;align-items:center;gap:8px;color:#991b1b;font-weight:700;font-size:0.85rem;margin-bottom:4px;">
                            <span>🔒 Requer Autenticação (OAB / Tribunal)</span>
                        </div>
                        <p style="margin:0;font-size:0.75rem;color:#7f1d1d;line-height:1.4;">
                            Esta operação requer privilégio processual estendido. O advogado precisará associar a
                            credencial (login/senha) da justiça estadual na aba "Minhas Credenciais".
                        </p>
                    </div>

                    <div id="lf-svc-fields" style="display:flex;flex-direction:column;gap:12px;"></div>

                    <div id="lf-svc-error" class="lf-esc-error" style="display:none;"></div>
                    <div id="lf-svc-success"
                        style="display:none;padding:12px;background:#dcfce7;color:#166534;border-radius:8px;font-size:0.9rem;font-weight:500;">
                    </div>

                </div>
                <div class="lf-esc-modal-footer"
                    style="padding:16px 20px;display:flex;gap:12px;justify-content:flex-end;">
                    <button onclick="window.lfSvc.close()" class="lf-esc-btn-secondary" type="button"
                        style="padding:10px 16px;">Cancelar</button>
                    <button id="lf-svc-btn-submit" onclick="window.lfSvc.execute()" class="lf-esc-btn" type="button"
                        style="padding:10px 30px;">🚀 Executar</button>
                </div>
            </div>{{-- /lf-esc-dialog --}}
        </div>{{-- /lf-svc-modal --}}

</x-admin::layouts>