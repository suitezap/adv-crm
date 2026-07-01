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

                /* ── Print: handled via window.open popup (see lfForcePrint) ── */

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
                    padding: 0.35rem 0.85rem;
                    font-size: 0.8rem;
                    font-weight: 500;
                    border-radius: 0.5rem;
                    border: 1px solid transparent;
                    background: transparent;
                    color: #6b7280;
                    cursor: pointer;
                    transition: all 150ms;
                    white-space: nowrap;
                }

                .lf-area-btn:hover {
                    background: #f3f4f6;
                    color: #111827;
                }

                .lf-area-btn.active {
                    font-weight: 600;
                    background: linear-gradient(135deg, #0d9488, #0284c7);
                    border-color: transparent;
                    color: #fff;
                    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
                }

                .dark .lf-area-btn {
                    background: transparent;
                    border-color: transparent;
                    color: #9ca3af;
                }

                .dark .lf-area-btn:hover {
                    background: #1f2937;
                    color: #f9fafb;
                    border-color: transparent;
                }

                .dark .lf-area-btn.active {
                    background: linear-gradient(135deg, #0d9488, #0284c7);
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

                /* ── Print Styles ─────────────────────────────────────── */
                @media print {
                    body * { visibility: hidden !important; }
                    .lf-esc-overlay { display: none !important; }
                    .lf-esc-dialog, .lf-esc-dialog * { visibility: visible !important; }
                    .lf-esc-dialog {
                        position: absolute !important;
                        left: 0 !important;
                        top: 0 !important;
                        transform: none !important;
                        box-shadow: none !important;
                        border: none !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        max-height: none !important;
                        overflow: visible !important;
                        background: transparent !important;
                    }
                    #lf-svc-result-content, .scroller-custom {
                        max-height: none !important;
                        overflow: visible !important;
                    }
                    .lf-esc-close-btn, .lf-esc-modal-footer, button[onclick^="window.lfSvc.close"], button[onclick^="document.getElementById"] {
                        display: none !important;
                    }
                    button[onclick="window.print()"] {
                        display: none !important;
                    }
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
                <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
                    <button type="button" class="lf-area-btn active"
                        onclick="window.lfFilterByArea('todas', this)">Todas</button>
                    <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>

                    <button type="button" class="lf-area-btn" data-module="processo"
                        onclick="window.lfFilterByArea('processo', this)">Processo</button>
                    <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>

                    <button type="button" class="lf-area-btn" data-module="advogado"
                        onclick="window.lfFilterByArea('advogado', this)">Advogado(a)</button>
                    <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                    
                    <button type="button" class="lf-area-btn" data-module="jurisprudencia"
                        onclick="window.lfFilterByArea('jurisprudencia', this)">Jurisprudência</button>
                    <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                    
                    <button type="button" class="lf-area-btn" data-module="legislacao"
                        onclick="window.lfFilterByArea('legislacao', this)">Legislações</button>
                    <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>

                    <button type="button" class="lf-area-btn" data-module="pessoa_empresa"
                        onclick="window.lfFilterByArea('pessoa_empresa', this)">Pessoa / Empresa</button>
                    <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>

                    <button type="button" class="lf-area-btn" data-module="diario"
                        onclick="window.lfFilterByArea('diario', this)">Diários Oficiais</button>
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
                            // DATAJUD CARDS (API Pública CNJ)
                            ['DATAJUD_BUSCA_NUMERO', 'processo', 'datajud', '⚖️', 'Consulta por Número do Processo (DataJud)', 'Busca um processo específico pelo seu número único CNJ no banco de dados público de qualquer tribunal.', 'POST /{tribunal}/_search', ''],
                            ['DATAJUD_BUSCA_CLASSE_ORGAO', 'processo', 'datajud', '🏛️', 'Busca por Classe e Órgão Julgador (DataJud)', 'Realiza buscas combinadas por código de classe processual e código do órgão julgador em um tribunal específico.', 'POST /{tribunal}/_search', ''],
                            ['DATAJUD_PAGINACAO', 'processo', 'datajud', '📄', 'Busca com Paginação (DataJud)', 'Navega entre grandes volumes de resultados usando o método search_after do Elasticsearch com listagem ordenada por data.', 'POST /{tribunal}/_search', ''],

                            // V1 CARDS FROM COLLECTION (Allowed via PDF)
                            // ['API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL', 'relatorios', 'v1', '📄', 'Download do PDF da página do Diário Oficial', 'Retorna em formato PDF uma página específica de uma publicação em diário oficial.', 'GET api/v1/diarios/{id}/pdf/pagina/{pagina}/baixar', ''], // OCULTO

                            ['BUSCA_PROC_DIARIO_NUM', 'processo', 'v1', '📋', 'Buscar processos dos Diários Oficiais por número', 'Localiza processos publicados em diários oficiais a partir do número do processo.', 'GET api/v1/processos/numero/{numero}', ''],

                            // V2 CARDS FROM COLLECTION (Allowed via PDF)
                            ['API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME', 'pessoa_empresa', 'v2', '👥', 'Processos do envolvido por Nome ou CPF/CNPJ', 'Retorna os processos judiciais de um envolvido a partir do Nome completo ou CPF ou CNPJ, onde a 1ª consulta com disponibiliza até 200 processos iniciais e os demais terão custo fixo reduzido, mantendo custos reduzidos durante 24h. ❗️ Para maiores informações consultar o suporte.', 'GET api/v2/envolvido/processos', ''],
                            ['API_V2_RESUMO_ENVOLVIDO', 'pessoa_empresa', 'v2', '👥', 'Resumo de Processos do envolvido', 'Retorna um resumo consolidado dos processos do envolvido, com contagens por polo e por tribunal.', 'GET api/v2/envolvido/processos/resumo', ''],
                            ['API_V2_PROCESSOSDEUMADVOGADOPOROAB', 'advogado', 'v2', '💼', 'Processos de um advogado por OAB', 'Retorna os processos em que um advogado atua, identificado pelo número da OAB, onde a 1ª consulta com disponibiliza até 200 processos iniciais e os demais terão custo fixo reduzido, mantendo custos reduzidos durante 24h. ❗️ Para maiores informações consultar o suporte.', 'GET api/v2/advogado/processos', ''],
                            ['API_V2_RESUMO_OAB', 'advogado', 'v2', '💼', 'Resumo de processos do advogado por OAB', 'Retorna um resumo dos processos do advogado, com contagens por tipo de polo e tribunal.', 'GET api/v2/advogado/processos/resumo', ''],
                            ['API_V2_PROCESSOPORNUMERAOCNJCAPA', 'processo', 'v2', '📋', 'Processo por numeração CNJ (Capa)', 'Retorna a capa completa de um processo a partir da numeração CNJ.', 'GET api/v2/processos/numero_cnj/{numero_cnj}', ''],
                            ['API_V2_MOVIMENTAESDEUMPROCESSO', 'processo', 'v2', '📋', 'Movimentações de um processo', 'Retorna as movimentações de um processo judicial coletadas dos sistemas dos tribunais.', 'GET api/v2/processos/numero_cnj/{numero}/movimentacoes', ''],
                            ['API_V2_ENVOLVIDOS_PROCESSO', 'processo', 'v2', '📋', 'Envolvidos de um processo', 'Retorna as partes envolvidas em um processo judicial a partir do número CNJ.', 'GET api/v2/processos/numero_cnj/{numero}/envolvidos', ''],
                            ['API_V2_DOCS_PUBLICOS', 'processo', 'v2', '📄', 'Documentos públicos de um processo', 'Retorna os documentos públicos disponíveis em um processo, com links para download.', 'GET api/v2/processos/numero_cnj/{numero}/documentos-publicos', ''],
                            ['API_V2_AUTOS_PROCESSO', 'processo', 'v2', '📄', 'Autos do processo (públicos e restritos)', 'Retorna os autos completos de um processo. Requer autenticação do tribunal.', 'GET api/v2/processos/numero_cnj/{numero}/autos', '', 'cert'],
                            ['API_V2_RESUMO_IA_PROCESSO', 'processo', 'v2', '🤖', 'Geração de Resumo Inteligente por IA', 'Solicita a geração ou atualização de um resumo inteligente do processo por IA (Assíncrono).', 'POST api/v2/ia/resumo', ''],


                            // JURISPRUDÊNCIA & LEGISLAÇÃO
                            ['BUSCA_JURIS', 'jurisprudencia', 'v1', '⚖️', 'Busca por Jurisprudências', 'Traz a lista paginada de jurisprudências encontradas na busca.', 'GET api/v1/jurisprudencias/busca', ''],
                            ['BUSCA_LEGIS', 'legislacao',     'v1', '📜', 'Busca por Legislação',         'Busca termos em legislações e traz a lista paginada.',             'GET api/v1/legislacoes/busca', ''],

                            // JURISPRUDÊNCIA — documento + PDF
                            // ['DOC_JURIS',  'jurisprudencia', 'v1', '📄', 'Documento de Jurisprudência',  'Retorna o texto completo de um documento de jurisprudência pelo tipo e ID.',    'GET api/v1/jurisprudencias/documento/{tipo}/{id}', ''],
                            // ['PDF_JURIS',  'jurisprudencia', 'v1', '🖨️', 'PDF de uma Jurisprudência',    'Faz o download do PDF de um documento de jurisprudência pelo tipo e ID.',       'GET api/v1/jurisprudencias/documento/{tipo}/{id}/pdf', ''],

                            // LEGISLAÇÃO — documento + fragmentos
                            // ['DOC_LEGIS',  'legislacao',     'v1', '📄', 'Documento de Legislação',      'Retorna o texto completo de um documento de legislação pelo tipo e ID.',        'GET api/v1/legislacoes/documento/{tipo}/{id}', ''],
                            // ['FRAG_LEGIS', 'legislacao',     'v1', '📑', 'Fragmentos de uma Legislação', 'Retorna os fragmentos paginados de um documento de legislação pelo tipo e ID.', 'GET api/v1/legislacoes/documento/{tipo}/{id}/fragmentos', ''],
                        ];

                        $prices = $prices ?? [];
                    @endphp



                    @foreach($allCards as $card)
                        @if(in_array($card[1], ['monitoramento', 'callback', 'async', 'outro'])) @continue @endif
                        {{-- HTTP: {{ $card[6] ?? '???' }} --}}
                        <div class="lf-esc-card" data-module="{{ $card[1] }}" data-api="{{ $card[2] }}">
                            <div>
                                <div class="lf-esc-card-header">
                                    <div class="lf-esc-card-icon">{{ $card[3] }}</div>
                                    <div class="lf-esc-card-title">{{ $card[4] }}</div>
                                </div>
                                <div class="lf-esc-card-desc">{{ $card[5] }}</div>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:8px;">
                                    <span class="lf-esc-badge lf-esc-badge-{{ $card[2] }}">{{ strtoupper($card[2]) }}</span>
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
                                    @if((float) ($prices[$card[0]] ?? 0.00) <= 0.00)
                                        <span
                                            class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                            Grátis
                                        </span>
                                    @else
                                        @php
                                            $rawBrl   = (float) ($prices[$card[0]] ?? 0.00);
                                            // Ƶ = BRL × suitecoin_rate (10) × markup (1.25) — arredondado 2 casas
                                            $dispZ = round($rawBrl * 10 * 1.25, 2);
                                        @endphp
                                        <span
                                            class="bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-300 px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                            {{ $card[2] == 'v2' && strpos($card[0], 'ATUALIZACAO') !== false && $card[0] !== 'ATUALIZAR_PROCESSO' ? 'Min ' : ($card[0] === 'ATUALIZAR_PROCESSO' ? 'Min ' : '') }}
                                            Ƶ {{ number_format($dispZ, 2, ',', '.') }}{{ $card[7] ?? '' }}
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
            <script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
            <script>
                // Only one modal system exists now (paid)
                (function () {
                    'use strict';

                    var CSRF = '{{ csrf_token() }}';
                    var ROUTE_SERVICO = "{{ route('lawfirm.escavador.servico') }}";
                    var ROUTE_DATAJUD_SERVICO = "{{ route('lawfirm.datajud.servico') }}";
                    var ROUTE_SALDO = "{{ route('lawfirm.escavador.saldo_cliente') }}";
                    var ROUTE_CERTIFICADOS = "{{ route('lawfirm.escavador.certificados.index') }}";

                    var UF_OPTIONS = 'AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO'.split(',');

                    var DATAJUD_TRIBUNAIS = [
                        { v: 'api_publica_tst', l: 'TST - Superior do Trabalho' },
                        { v: 'api_publica_stj', l: 'STJ - Superior de Justiça' },
                        { v: 'api_publica_stm', l: 'STM - Superior Militar' },
                        { v: 'api_publica_tse', l: 'TSE - Superior Eleitoral' },
                        { v: 'api_publica_trf1', l: 'TRF1 - 1ª Região (DF/MT/...)' },
                        { v: 'api_publica_trf2', l: 'TRF2 - 2ª Região (RJ/ES)' },
                        { v: 'api_publica_trf3', l: 'TRF3 - 3ª Região (SP/MS)' },
                        { v: 'api_publica_trf4', l: 'TRF4 - 4ª Região (RS/SC/PR)' },
                        { v: 'api_publica_trf5', l: 'TRF5 - 5ª Região (NE)' },
                        { v: 'api_publica_tjsp', l: 'TJSP - São Paulo' },
                        { v: 'api_publica_tjrj', l: 'TJRJ - Rio de Janeiro' },
                        { v: 'api_publica_tjmg', l: 'TJMG - Minas Gerais' },
                        { v: 'api_publica_tjrs', l: 'TJRS - Rio Grande do Sul' },
                        { v: 'api_publica_tjpr', l: 'TJPR - Paraná' },
                        { v: 'api_publica_tjba', l: 'TJBA - Bahia' },
                        { v: 'api_publica_tjsc', l: 'TJSC - Santa Catarina' },
                        { v: 'api_publica_tjgo', l: 'TJGO - Goiás' },
                        { v: 'api_publica_tjdft', l: 'TJDFT - Distrito Federal' },
                        { v: 'api_publica_tjam', l: 'TJAM - Amazonas' },
                        { v: 'api_publica_tjpe', l: 'TJPE - Pernambuco' },
                        { v: 'api_publica_tjce', l: 'TJCE - Ceará' },
                        { v: 'api_publica_tjpa', l: 'TJPA - Pará' },
                        { v: 'api_publica_tjmt', l: 'TJMT - Mato Grosso' },
                        { v: 'api_publica_tjms', l: 'TJMS - Mato Grosso do Sul' },
                        { v: 'api_publica_tjes', l: 'TJES - Espírito Santo' },
                        { v: 'api_publica_tjma', l: 'TJMA - Maranhão' },
                        { v: 'api_publica_tjal', l: 'TJAL - Alagoas' },
                        { v: 'api_publica_tjrn', l: 'TJRN - Rio Grande do Norte' },
                        { v: 'api_publica_tjpb', l: 'TJPB - Paraíba' },
                        { v: 'api_publica_tjpi', l: 'TJPI - Piauí' },
                        { v: 'api_publica_tjse', l: 'TJSE - Sergipe' },
                        { v: 'api_publica_tjro', l: 'TJRO - Rondônia' },
                        { v: 'api_publica_tjto', l: 'TJTO - Tocantins' },
                        { v: 'api_publica_tjac', l: 'TJAC - Acre' },
                        { v: 'api_publica_tjap', l: 'TJAP - Amapá' },
                        { v: 'api_publica_tjrr', l: 'TJRR - Roraima' },
                        { v: 'api_publica_trt2', l: 'TRT2 - São Paulo Trabalhista' },
                        { v: 'api_publica_trt3', l: 'TRT3 - MG Trabalhista' },
                        { v: 'api_publica_trt4', l: 'TRT4 - RS Trabalhista' },
                        { v: 'api_publica_trt15', l: 'TRT15 - Campinas Trabalhista' }
                    ];

                    var PRICES_DB = @json($prices);

                    var SVC_INFO = {
                        // === DATAJUD ===
                        'DATAJUD_BUSCA_NUMERO': {
                            label: '⚖️ Consulta por Número do Processo', price: 'Grátis', fields: [
                                { name: 'tribunal', label: 'Tribunal', type: 'datajud_tribunal', required: true },
                                { name: 'tipo_consulta', label: '', type: 'hidden', valor: 'numero' },
                                { name: 'numero_cnj', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'DATAJUD_BUSCA_CLASSE_ORGAO': {
                            label: '🏛️ Busca por Classe e Órgão Julgador', price: 'Grátis', fields: [
                                { name: 'tribunal', label: 'Tribunal', type: 'datajud_tribunal', required: true },
                                { name: 'tipo_consulta', label: '', type: 'hidden', valor: 'classe_orgao' },
                                { name: 'codigo_classe', label: 'Código da Classe Processual', type: 'number', required: false, placeholder: 'Ex: 1116 (Inquérito Policial)' },
                                { name: 'codigo_orgao', label: 'Código do Órgão Julgador', type: 'number', required: false, placeholder: 'Ex: 5 (1ª Vara Criminal)' }
                            ]
                        },
                        'DATAJUD_PAGINACAO': {
                            label: '📄 Busca com Paginação', price: 'Grátis', fields: [
                                { name: 'tribunal', label: 'Tribunal', type: 'datajud_tribunal', required: true },
                                { name: 'tipo_consulta', label: '', type: 'hidden', valor: 'paginacao' },
                                { name: 'search_after', label: 'Página Anterior', required: true, placeholder: 'Cole o valor numérico da Página Anterior (ex: 1681408943754)' },
                                { name: 'size', label: 'Registros por página', type: 'number', required: true, placeholder: '10' }
                            ]
                        },
                        // === V1 ===
                        'API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL': {
                            label: '📄 Download do PDF da página do Diário Oficial', price: 'Ƶ 37,50', fields: [
                                { name: 'id', label: 'ID da Publicação', type: 'number', min: 1, required: true },
                                { name: 'pagina', label: 'Número da Página', type: 'number', min: 1, required: true }
                            ]
                        },
                        'API_V1_PAGINA_DIARIO': {
                            label: '📄 Página do Diário Oficial', price: 'Ƶ 37,50', fields: [
                                { name: 'id', label: 'ID do Diário Oficial', type: 'number', min: 1, required: true },
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },


                        'BUSCA_PROC_DIARIO_NUM': {
                            label: '📋 Buscar processos dos Diários Oficiais por número', price: 'Ƶ 37,50', fields: [
                                { name: 'numero', label: 'Número do Processo', placeholder: '0000000-00.0000.0.00.0000', required: false },
                                { name: 'match_exato', label: 'Busca Exata?', type: 'select', options: ['0|Não', '1|Sim'], required: false }
                            ]
                        },


                        // === JURISPRUDÊNCIA E LEGISLAÇÃO ===
                        'BUSCA_JURIS': {
                            label: '⚖️ Busca por Jurisprudências', price: 'Ƶ 0,25', fields: [
                                { name: 'q', label: 'Termo de Busca', required: true, placeholder: 'Ex: "danos morais"' },
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },
                        'DOC_JURIS': {
                            label: '📄 Documento de Jurisprudência', price: 'Ƶ 0,50', fields: [
                                { name: 'tipo_documento', label: 'Tipo de Documento', type: 'select', options: ['acordao|Acórdão', 'sumula|Súmula', 'decisao-monocratica|Decisão Monocrática', 'sentenca|Sentença', 'voto|Voto'], required: true },
                                { name: 'id_documento', label: 'ID do Documento', type: 'number', min: 1, required: true }
                            ]
                        },
                        'PDF_JURIS': {
                            label: '🖨️ PDF de uma Jurisprudência', price: 'Ƶ 0,88', fields: [
                                { name: 'tipo_documento', label: 'Tipo de Documento', type: 'select', options: ['acordao|Acórdão', 'sumula|Súmula', 'decisao-monocratica|Decisão Monocrática', 'sentenca|Sentença', 'voto|Voto'], required: true },
                                { name: 'id_documento', label: 'ID do Documento', type: 'number', min: 1, required: true }
                            ]
                        },
                        'BUSCA_LEGIS': {
                            label: '📜 Busca por Legislação', price: 'Ƶ 0,38', fields: [
                                { name: 'q', label: 'Termo de Busca', required: true, placeholder: 'Ex: "código penal"' },
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },
                        'DOC_LEGIS': {
                            label: '📄 Documento de Legislação', price: 'Ƶ 0,38', fields: [
                                { name: 'tipo_documento', label: 'Tipo de Documento', type: 'select', options: ['lei|Lei', 'decreto|Decreto', 'portaria|Portaria', 'resolucao|Resolução', 'instrucao-normativa|Instrução Normativa'], required: true },
                                { name: 'id_documento', label: 'ID do Documento', type: 'number', min: 1, required: true }
                            ]
                        },
                        'FRAG_LEGIS': {
                            label: '📑 Fragmentos da Legislação', price: 'Ƶ 0,38', fields: [
                                { name: 'tipo_documento', label: 'Tipo de Documento', type: 'select', options: ['lei|Lei', 'decreto|Decreto', 'portaria|Portaria', 'resolucao|Resolução', 'instrucao-normativa|Instrução Normativa'], required: true },
                                { name: 'id_documento', label: 'ID do Documento', type: 'number', min: 1, required: true },
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },

                        // === V2 ===
                        'API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME': {
                            label: '👥 Processos do envolvido por Nome ou CPF/CNPJ', price: 'Grátis', fields: [
                                { name: 'cpf_cnpj', label: 'CPF, CNPJ ou Nome Completo', required: true, placeholder: 'Ex: João da Silva ou 000.000.000-00' },
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },
                        'API_V2_RESUMO_ENVOLVIDO': {
                            label: '👥 Resumo de Processos do envolvido por Nome ou CPF/CNPJ', price: 'Ƶ 37,50', fields: [
                                { name: 'cpf_cnpj', label: 'CPF, CNPJ ou Nome Completo', required: true, placeholder: 'Ex: João da Silva ou 000.000.000-00' }
                            ]
                        },
                        'API_V2_PROCESSOSDEUMADVOGADOPOROAB': {
                            label: '💼 Processos de um advogado por OAB', price: 'Grátis', fields: [
                                { name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true },
                                { name: 'numero_oab', label: 'Número OAB', required: true, placeholder: 'Ex: 123456' },
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },
                        'API_V2_RESUMO_OAB': {
                            label: '💼 Resumo de processos do advogado por OAB', price: 'Ƶ 37,50', fields: [
                                { name: 'estado', label: 'UF', type: 'select', options: UF_OPTIONS, required: true },
                                { name: 'numero_oab', label: 'Número OAB', required: true, placeholder: 'Ex: 123456' }
                            ]
                        },
                        'API_V2_PROCESSOPORNUMERAOCNJCAPA': {
                            label: '📋 Processo por numeração CNJ', price: 'Ƶ 37,50', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'API_V2_MOVIMENTAESDEUMPROCESSO': {
                            label: '📋 Movimentações de um processo', price: 'Ƶ 37,50', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' },
                                { name: 'limit', label: 'Limite de Movimentações', type: 'select', options: ['50|50 (padrão)', '100|100', '500|500'], required: false }
                            ]
                        },
                        'API_V2_ENVOLVIDOS_PROCESSO': {
                            label: '📋 Envolvidos de um processo', price: 'Ƶ 0,63', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'API_V2_DOCS_PUBLICOS': {
                            label: '📄 Documentos públicos de um processo', price: 'Ƶ 0,75', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'API_V2_AUTOS_PROCESSO': {
                            label: '📄 Autos do processo (públicos e restritos)', price: 'Ƶ 2,25', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'API_V2_RESUMO_IA_PROCESSO': {
                            label: '🤖 Solicita a geração/atualização do resumo inteligente', price: 'Ƶ 1,00', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'API_V2_STATUS_RESUMO_IA_UI': {
                            label: '🤖 Status da solicitação de resumo inteligente', price: 'Grátis', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },

                        'API_V2_STATUS_ATUALIZACAO': {
                            label: '📊 Status de uma atualização de processo', price: 'Grátis', fields: [
                                { name: 'numero', label: 'Número CNJ do Processo', required: true, placeholder: '0000000-00.0000.0.00.0000' }
                            ]
                        },
                        'API_V2_TRIBUNAIS_DISPONIVEIS': { label: '🏛️ Retornar os tribunais disponíveis', price: 'Grátis', fields: [] },
                        'API_V2_SISTEMAS_DISPONIVEIS': {
                            label: '🏛️ Retornar os sistemas disponíveis', price: 'Grátis', fields: [
                                { name: 'utiliza_certificado_digital', label: 'Filtrar: usa certificado digital?', type: 'select', options: ['|Todos', '1|Sim', '0|Não'], required: false },
                                { name: 'utiliza_2fa', label: 'Filtrar: usa 2FA?', type: 'select', options: ['|Todos', '1|Sim', '0|Não'], required: false }
                            ]
                        },
                        'API_V2_CALLBACKS_LISTAR': {
                            label: '📨 Retornar os callbacks (V2)', price: 'Grátis', fields: [
                                { name: 'page', label: 'Página', type: 'number', min: 1, required: false }
                            ]
                        },
                        // BUSCA_JURIS e BUSCA_LEGIS — definições únicas mantidas acima (evitar duplicação)
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

                    window.lfForcePrint = function() {
                        var source = document.getElementById('lf-svc-result-area');
                        if (!source) { alert('Nenhum resultado disponível para salvar.'); return; }

                        // Clone and strip interactive/private elements
                        var clone = source.cloneNode(true);
                        var strip = clone.querySelectorAll('button, .lf-no-print');
                        for (var i = 0; i < strip.length; i++) strip[i].parentNode.removeChild(strip[i]);

                        // Build a self-contained print-safe HTML (NO external Krayin CSS)
                        var html = '<!DOCTYPE html><html lang="pt-BR"><head>' +
                            '<meta charset="UTF-8">' +
                            '<title>Assistente Jurídico</title>' +
                            '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">' +
                            '<style>' +
                                'html, body { margin: 0; padding: 20px; background: #fff; color: #1f2937; font-family: Inter, system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5; }' +
                                '*, *::before, *::after { box-sizing: border-box; }' +
                                'div, span, p, h1, h2, h3, h4, h5, h6, section, article { display: block; position: static; overflow: visible; max-height: none; height: auto; }' +
                                'span { display: inline; }' +
                                '.dark\\:bg-gray-800, .dark\\:bg-gray-900, .dark\\:border-gray-700, .dark\\:text-white, .dark\\:text-gray-200, .dark\\:text-gray-300, .dark\\:text-gray-400 { }' +
                                '@media print { body { margin: 12px; } @page { margin: 15mm 10mm; } }' +
                            '</style>' +
                            '</head><body>' +
                            '<div style="margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">' +
                                '<span style="font-size:1.3rem;font-weight:700;color:#0d9488;">⚖️ Assistente Jurídico</span>' +
                                '<span style="font-size:0.8rem;color:#9ca3af;">' + new Date().toLocaleDateString('pt-BR') + '</span>' +
                            '</div>' +
                            clone.outerHTML +
                            '</body></html>';

                        var printWin = window.open('', '_blank', 'width=900,height=700');
                        if (!printWin) { alert('Permita popups para salvar em PDF.'); return; }
                        printWin.document.open();
                        printWin.document.write(html);
                        printWin.document.close();

                        printWin.onload = function() {
                            setTimeout(function() {
                                printWin.focus();
                                printWin.print();
                            }, 600);
                        };
                    };
                    function formatDatebr(dtStr) {
                        if(!dtStr) return '-';
                        try {
                            var dt = new Date(dtStr);
                            var d = dt.getDate().toString().padStart(2, '0');
                            var m = (dt.getMonth() + 1).toString().padStart(2, '0');
                            var y = dt.getFullYear();
                            var h = dt.getHours().toString().padStart(2, '0');
                            var min = dt.getMinutes().toString().padStart(2, '0');
                            return d + '/' + m + '/' + y + ' às ' + h + ':' + min;
                        } catch(e) { return dtStr; }
                    }

                    function buildDataJudUi(result) {
                        if (!result || !result.hits || !result.hits.hits || result.hits.hits.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhum processo encontrado neste escopo de busca.</div>';
                        
                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:16px;max-height:550px;overflow:auto;padding-right:8px;text-align:left;">';
                        
                        result.hits.hits.forEach(function(hit) {
                            var src = hit._source || {};
                            var numStr = src.numeroProcesso ? src.numeroProcesso.replace(/^(\d{7})(\d{2})(\d{4})(\d)(\d{2})(\d{4})$/, "$1-$2.$3.$4.$5.$6") : 'N/A';
                            var tribunal = src.tribunal || '-';
                            var grau = src.grau || '-';
                            var dtAjuizamento = formatDatebr(src.dataAjuizamento);
                            var dtUltima = formatDatebr(src.dataHoraUltimaAtualizacao);
                            var classe = src.classe ? src.classe.nome : '-';
                            var sistema = src.sistema ? src.sistema.nome : '-';
                            var organ = src.orgaoJulgador ? src.orgaoJulgador.nome : '-';
                            
                            var assuntosHtml = '';
                            if(src.assuntos && src.assuntos.length > 0) {
                                assuntosHtml = src.assuntos.map(function(a) { return '<span style="display:inline-block;padding:3px 8px;background:#f3f4f6;border-radius:4px;font-size:0.75rem;font-weight:500;color:#374151;border:1px solid #e5e7eb;" class="dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">' + a.nome + '</span>'; }).join(' ');
                            } else {
                                assuntosHtml = '-';
                            }
                            
                            var movimentosHtml = '';
                            if(src.movimentos && src.movimentos.length > 0) {
                                var movs = src.movimentos.slice().sort(function(a,b) { return new Date(b.dataHora) - new Date(a.dataHora); });
                                movimentosHtml = movs.map(function(m) {
                                    return '<div style="padding:10px 12px;border-left:3px solid #0d9488;background:#f9fafb;margin-bottom:8px;border-radius:0 6px 6px 0;" class="dark:bg-gray-700 dark:border-l-teal-500">' +
                                        '<div style="font-weight:600;font-size:0.85rem;color:#1f2937;" class="dark:text-gray-100">' + (m.nome || '-') + '</div>' +
                                        '<div style="font-size:0.75rem;color:#6b7280;margin-top:4px;" class="dark:text-gray-400">⏱️ ' + formatDatebr(m.dataHora) + '</div>' +
                                    '</div>';
                                }).join('');
                            } else {
                                movimentosHtml = '<div style="font-size:0.8rem;color:#6b7280;" class="dark:text-gray-400">Nenhuma movimentação registrada.</div>';
                            }

                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="background:linear-gradient(135deg, rgba(13,148,136,0.06), rgba(2,132,199,0.06));padding:16px 20px;border-bottom:1px solid #e5e7eb;" class="dark:border-gray-700 dark:bg-gradient-to-r dark:from-teal-900/40 dark:to-cyan-900/40">' +
                                    '<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">' +
                                        '<div>' +
                                            '<h3 style="margin:0;font-size:1.15rem;font-weight:700;color:#111827;display:flex;align-items:center;gap:6px;" class="dark:text-white"><span style="font-size:1.3rem;">⚖️</span> Processo: ' + numStr + '</h3>' +
                                            '<div style="font-size:0.85rem;color:#4b5563;margin-top:6px;font-weight:500;" class="dark:text-gray-300">' + tribunal + ' — Grau: ' + grau + ' — Sistema: ' + sistema + '</div>' +
                                        '</div>' +
                                        '<div style="text-align:right;">' +
                                            (hit._score !== null && hit._score !== undefined ? '<div style="display:inline-block;padding:2px 8px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:6px;font-size:0.75rem;font-weight:600;" class="dark:bg-green-900/30 dark:text-green-300 dark:border-green-800">Score: ' + parseFloat(hit._score).toFixed(2) + '</div>' : '') +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                                '<div style="padding:20px;">' +
                                    '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:20px;margin-bottom:24px;">' +
                                        '<div style="background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                            '<div style="font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:4px;" class="dark:text-gray-400">Classe Processual</div>' +
                                            '<div style="font-size:0.85rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + classe + '</div>' +
                                        '</div>' +
                                        '<div style="background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                            '<div style="font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:4px;" class="dark:text-gray-400">Órgão Julgador</div>' +
                                            '<div style="font-size:0.85rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + organ + '</div>' +
                                        '</div>' +
                                        '<div style="background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                            '<div style="font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:4px;" class="dark:text-gray-400">Data Ajuizamento</div>' +
                                            '<div style="font-size:0.85rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + dtAjuizamento + '</div>' +
                                        '</div>' +
                                        '<div style="background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                            '<div style="font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:4px;" class="dark:text-gray-400">Última Atualização</div>' +
                                            '<div style="font-size:0.85rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + dtUltima + '</div>' +
                                        '</div>' +
                                    '</div>' +
                                    
                                    '<div style="margin-bottom:24px;">' +
                                        '<div style="font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:8px;" class="dark:text-gray-400">Assuntos (' + (src.assuntos ? src.assuntos.length : 0) + ')</div>' +
                                        '<div style="display:flex;flex-wrap:wrap;gap:6px;">' + assuntosHtml + '</div>' +
                                    '</div>' +

                                    '<div>' +
                                        '<div style="font-size:0.9rem;font-weight:700;color:#1f2937;margin-bottom:12px;display:flex;align-items:center;gap:6px;border-bottom:1px solid #e5e7eb;padding-bottom:8px;" class="dark:text-white dark:border-gray-700">' +
                                            '📄 Movimentações <span style="background:#e5e7eb;padding:2px 6px;border-radius:99px;font-size:0.75rem;color:#4b5563;" class="dark:bg-gray-700 dark:text-gray-300">' + (src.movimentos ? src.movimentos.length : 0) + '</span>' +
                                        '</div>' +
                                        '<div style="max-height:280px;overflow-y:auto;padding-right:8px;" class="scroller-custom">' +
                                            movimentosHtml +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>';
                        });
                        
                        html += '</div>';

                        return html;
                    }

                    // ── Escavador UI Builders ─────────────────────────────────────

                    function escCard(title, value) {
                        return '<div style="background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                            '<div style="font-size:0.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:4px;" class="dark:text-gray-400">' + title + '</div>' +
                            '<div style="font-size:0.85rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + (value || '-') + '</div>' +
                        '</div>';
                    }

                    function escBadge(txt, color) {
                        color = color || '#0d9488';
                        return '<span style="display:inline-block;padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;color:#fff;background:' + color + ';margin:2px;">' + txt + '</span>';
                    }

                    function escSection(title, body) {
                        return '<div style="margin-bottom:20px;">' +
                            '<div style="font-size:0.78rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e5e7eb;padding-bottom:6px;margin-bottom:10px;" class="dark:text-gray-400 dark:border-gray-700">' + title + '</div>' +
                            body +
                        '</div>';
                    }

                    function buildEscavadorProcessosUi(result) {
                        var items = (result && result.items) ? result.items : [];
                        if (items.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhum processo retornado neste exemplo.</div>';
                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:16px;max-height:560px;overflow:auto;padding-right:8px;text-align:left;">';
                        items.forEach(function(proc) {
                            var fontes = proc.fontes || [];
                            var capa = (fontes[0] && fontes[0].capa) ? fontes[0].capa : {};
                            var estOrig = proc.estado_origem ? proc.estado_origem.sigla : '-';
                            var assuntoPrincipal = capa.assunto_principal_normalizado ? capa.assunto_principal_normalizado.nome : (capa.classe || '-');
                            var orgao = capa.orgao_julgador || '-';
                            var movQtd = proc.quantidade_movimentacoes || 0;
                            var fontesHtml = fontes.map(function(f) {
                                return '<div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb;margin-bottom:6px;" class="dark:bg-gray-900/40 dark:border-gray-700">' +
                                    '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#0369a1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.65rem;font-weight:700;flex-shrink:0;">' + (f.sigla || '?') + '</div>' +
                                    '<div>' +
                                        '<div style="font-size:0.82rem;font-weight:600;color:#1f2937;" class="dark:text-gray-100">' + (f.nome || '-') + '</div>' +
                                        '<div style="font-size:0.72rem;color:#6b7280;" class="dark:text-gray-400">' + (f.grau_formatado || '-') + ' · ' + (f.sistema || '') + '</div>' +
                                    '</div>' +
                                '</div>';
                            }).join('');
                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="background:linear-gradient(135deg,rgba(13,148,136,.07),rgba(2,132,199,.07));padding:16px 20px;border-bottom:1px solid #e5e7eb;" class="dark:border-gray-700">' +
                                    '<h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#111827;display:flex;align-items:center;gap:6px;" class="dark:text-white">⚖️ ' + (proc.numero_cnj || 'N/A') + '</h3>' +
                                    '<div style="font-size:0.8rem;color:#4b5563;margin-top:4px;" class="dark:text-gray-300">Estado origem: <strong>' + estOrig + '</strong> · Movimentações: <strong>' + movQtd + '</strong>' + (proc.fontes_tribunais_estao_arquivadas ? ' · <span style="color:#dc2626;">⚠ Arquivado</span>' : '') + '</div>' +
                                '</div>' +
                                '<div style="padding:18px 20px;">' +
                                    '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px;">' +
                                        escCard('Início', formatDatebr(proc.data_inicio)) +
                                        escCard('Última Movimentação', formatDatebr(proc.data_ultima_movimentacao)) +
                                        escCard('Assunto Principal', assuntoPrincipal) +
                                        escCard('Órgão Julgador', orgao) +
                                    '</div>' +
                                    escSection('🏛 Fontes / Tribunais (' + fontes.length + ')', fontesHtml || '<div style="font-size:0.8rem;color:#6b7280;">Nenhuma fonte disponível.</div>') +
                                '</div>' +
                            '</div>';
                        });
                        html += '</div>';
                        if (result.paginator) {
                            html += '<div style="margin-top:10px;font-size:0.78rem;color:#6b7280;text-align:right;">Exibindo até ' + result.paginator.per_page + ' por página · ' + (result.links && result.links.next ? '✅ Há próxima página' : 'Última página') + '</div>';
                        }
                        return html;
                    }

                    function buildEscavadorResumoUi(result) {
                        if (!result) return '<div class="lf-esc-error" style="display:block;">Sem dados de resumo.</div>';
                        var total = result.quantidade_processos || 0;
                        var tiposHtml = (result.tipos_envolvido || []).map(function(t) {
                            var corPolo = t.polo === 'ATIVO' ? '#16a34a' : (t.polo === 'PASSIVO' ? '#dc2626' : '#6b7280');
                            return '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-radius:6px;background:#f9fafb;margin-bottom:6px;border:1px solid #e5e7eb;" class="dark:bg-gray-900/40 dark:border-gray-700">' +
                                '<div><span style="font-size:0.85rem;font-weight:600;color:#1f2937;" class="dark:text-gray-100">' + t.tipo + '</span> ' +
                                '<span style="font-size:0.72rem;padding:2px 7px;border-radius:99px;color:#fff;background:' + corPolo + ';font-weight:700;">' + (t.polo || '') + '</span></div>' +
                                '<div style="font-size:0.9rem;font-weight:700;color:#0d9488;">' + (t.quantidade || 0).toLocaleString('pt-BR') + '</div>' +
                            '</div>';
                        }).join('');
                        var tribunaisHtml = (result.tribunais || []).slice(0, 5).map(function(t) {
                            var perc = total > 0 ? Math.round((t.quantidade / total) * 100) : 0;
                            return '<div style="margin-bottom:10px;">' +
                                '<div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:3px;">' +
                                    '<span style="font-weight:600;color:#1f2937;" class="dark:text-gray-200">' + t.sigla + ' <span style="font-weight:400;color:#6b7280;">(' + t.nome + ')</span></span>' +
                                    '<span style="font-weight:700;color:#0d9488;">' + (t.quantidade || 0).toLocaleString('pt-BR') + '</span>' +
                                '</div>' +
                                '<div style="background:#e5e7eb;border-radius:99px;height:6px;"><div style="background:linear-gradient(90deg,#0d9488,#0369a1);width:' + perc + '%;height:6px;border-radius:99px;"></div></div>' +
                            '</div>';
                        }).join('');
                        var estadosHtml = (result.estados || []).slice(0, 5).map(function(e) {
                            return escBadge(e.sigla + ' ' + (e.quantidade || 0).toLocaleString('pt-BR'));
                        }).join('');
                        var anosHtml = (result.anos || []).slice(0, 5).map(function(a) {
                            return escBadge(a.ano + ': ' + (a.quantidade || 0).toLocaleString('pt-BR'), '#6b7280');
                        }).join('');
                        return '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:16px;max-height:560px;overflow:auto;padding-right:8px;text-align:left;">' +
                            '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="background:linear-gradient(135deg,rgba(13,148,136,.07),rgba(2,132,199,.07));padding:16px 20px;border-bottom:1px solid #e5e7eb;" class="dark:border-gray-700">' +
                                    '<h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#111827;" class="dark:text-white">📊 Resumo de Processos</h3>' +
                                    '<div style="font-size:0.85rem;color:#0d9488;font-weight:700;margin-top:4px;">' + total.toLocaleString('pt-BR') + ' processos encontrados</div>' +
                                    (result.quantidade_processos_como_autor !== undefined ?
                                        '<div style="font-size:0.78rem;color:#6b7280;margin-top:4px;">' +
                                        'Como Autor: ' + (result.quantidade_processos_como_autor || 0).toLocaleString('pt-BR') + ' · ' +
                                        'Como Réu: ' + (result.quantidade_processos_como_reu || 0).toLocaleString('pt-BR') + ' · ' +
                                        'Outros: ' + (result.quantidade_processos_como_outra_parte || 0).toLocaleString('pt-BR') +
                                        '</div>' : '') +
                                '</div>' +
                                '<div style="padding:18px 20px;">' +
                                    (tiposHtml ? escSection('👥 Participação por Tipo', tiposHtml) : '') +
                                    (tribunaisHtml ? escSection('🏛 Tribunais (Top 5)', tribunaisHtml) : '') +
                                    (estadosHtml ? escSection('📍 Estados', '<div style="display:flex;flex-wrap:wrap;gap:4px;">' + estadosHtml + '</div>') : '') +
                                    (anosHtml ? escSection('📅 Por Ano (Top 5)', '<div style="display:flex;flex-wrap:wrap;gap:4px;">' + anosHtml + '</div>') : '') +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    }

                    function buildEscavadorMovimentacoesUi(result) {
                        var items = (result && result.items) ? result.items : [];
                        if (items.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhuma movimentação retornada.</div>';
                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:16px;max-height:560px;overflow:auto;padding-right:8px;text-align:left;">' +
                            '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="background:linear-gradient(135deg,rgba(13,148,136,.07),rgba(2,132,199,.07));padding:16px 20px;border-bottom:1px solid #e5e7eb;" class="dark:border-gray-700">' +
                                    '<h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#111827;" class="dark:text-white">📄 Movimentações do Processo</h3>' +
                                    '<div style="font-size:0.8rem;color:#6b7280;margin-top:4px;">' + items.length + ' movimentação(ões) · ' + (result.links && result.links.next ? 'Há mais páginas' : 'Última página') + '</div>' +
                                '</div>' +
                                '<div style="padding:18px 20px;display:flex;flex-direction:column;gap:10px;">';
                        items.forEach(function(mov) {
                            var fonte = mov.fonte || {};
                            html += '<div style="padding:12px 16px;border-left:3px solid #0d9488;background:#f9fafb;border-radius:0 8px 8px 0;" class="dark:bg-gray-900/40 dark:border-l-teal-400">' +
                                '<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px;">' +
                                    '<div style="font-size:0.88rem;font-weight:700;color:#1f2937;" class="dark:text-gray-100">' + (mov.tipo || mov.conteudo || '-') + '</div>' +
                                    '<div style="font-size:0.75rem;color:#6b7280;">⏱ ' + formatDatebr(mov.data) + '</div>' +
                                '</div>' +
                                (mov.conteudo && mov.conteudo !== mov.tipo ? '<div style="font-size:0.8rem;color:#4b5563;margin-top:4px;" class="dark:text-gray-400">' + mov.conteudo + '</div>' : '') +
                                (fonte.sigla ? '<div style="margin-top:8px;">' + escBadge('🏛 ' + fonte.sigla + ' · ' + (fonte.grau_formatado || ''), '#1e40af') + '</div>' : '') +
                            '</div>';
                        });
                        html += '</div></div></div>';
                        return html;
                    }

                    function buildEscavadorEnvolvidosUi(result) {
                        var items = (result && result.items) ? result.items : [];
                        if (items.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhum envolvido retornado.</div>';
                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:14px;max-height:560px;overflow:auto;padding-right:8px;text-align:left;">';
                        items.forEach(function(env) {
                            var tipo = env.tipo_pessoa === 'FISICA' ? '👤' : '🏢';
                            var docHtml = env.cpf ? '<span style="font-size:0.75rem;color:#6b7280;">CPF: ' + env.cpf + '</span>' :
                                          (env.cnpj ? '<span style="font-size:0.75rem;color:#6b7280;">CNPJ: ' + env.cnpj + '</span>' : '');
                            var participacoes = env.participacoes_processo || [];
                            var partHtml = participacoes.map(function(p) {
                                var corPolo = p.polo === 'ATIVO' ? '#16a34a' : (p.polo === 'PASSIVO' ? '#dc2626' : '#6b7280');
                                var advs = (p.advogados || []).map(function(a) {
                                    return '<span style="font-size:0.72rem;padding:2px 8px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;color:#1e40af;margin:2px;display:inline-block;">⚖ ' + a.nome + '</span>';
                                }).join('');
                                var fonte = p.fonte || {};
                                return '<div style="padding:10px 14px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;margin-bottom:8px;" class="dark:bg-gray-900/40 dark:border-gray-700">' +
                                    '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">' +
                                        '<span style="font-size:0.82rem;font-weight:700;color:#1f2937;" class="dark:text-gray-100">' + (p.tipo_normalizado || p.tipo || '-') + '</span>' +
                                        '<span style="padding:2px 8px;border-radius:99px;font-size:0.7rem;font-weight:700;color:#fff;background:' + corPolo + ';">' + (p.polo || '') + '</span>' +
                                    '</div>' +
                                    (fonte.sigla ? '<div style="font-size:0.75rem;color:#6b7280;margin-bottom:6px;">🏛 ' + (fonte.nome || fonte.sigla) + ' · ' + (fonte.grau_formatado || '') + '</div>' : '') +
                                    (advs ? '<div style="margin-top:4px;">' + advs + '</div>' : '') +
                                '</div>';
                            }).join('');
                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="padding:14px 18px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:12px;" class="dark:border-gray-700">' +
                                    '<div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#0369a1);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">' + tipo + '</div>' +
                                    '<div>' +
                                        '<div style="font-size:0.95rem;font-weight:700;color:#111827;" class="dark:text-white">' + (env.nome || '-') + '</div>' +
                                        '<div>' + docHtml + (env.quantidade_processos ? ' · <span style="font-size:0.75rem;color:#0d9488;font-weight:600;">' + (env.quantidade_processos || 0).toLocaleString('pt-BR') + ' processos</span>' : '') + '</div>' +
                                    '</div>' +
                                '</div>' +
                                (partHtml ? '<div style="padding:14px 18px;">' + escSection('🔗 Participações neste processo', partHtml) + '</div>' : '') +
                            '</div>';
                        });
                        html += '</div>';
                        if (result.paginator) {
                            html += '<div style="margin-top:10px;font-size:0.78rem;color:#6b7280;text-align:right;">' + (result.links && result.links.next ? '📄 Há mais resultados — use o cursor de paginação' : 'Última página') + '</div>';
                        }
                        return html;
                    }

                    function buildEscavadorBuscaUi(result) {
                        var items = (result && result.items) ? result.items : [];
                        var pag = result ? result.paginator : null;
                        if (items.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhum resultado encontrado.</div>';
                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:14px;max-height:560px;overflow:auto;padding-right:8px;text-align:left;">';
                        if (pag) {
                            html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:4px;">' +
                                escCard('Total', (pag.total || 0).toLocaleString('pt-BR') + ' resultados') +
                                escCard('Página', pag.current_page + ' / ' + pag.total_pages) +
                                escCard('Por página', pag.per_page) +
                            '</div>';
                        }
                        items.forEach(function(item) {
                            var texto = item.texto ? (item.texto.trim().substring(0, 320) + (item.texto.length > 320 ? '…' : '')) : '-';
                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="background:linear-gradient(135deg,rgba(13,148,136,.07),rgba(2,132,199,.07));padding:12px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;" class="dark:border-gray-700">' +
                                    '<div>' +
                                        '<div style="font-size:0.88rem;font-weight:700;color:#111827;" class="dark:text-white">📰 ' + (item.diario_nome || item.tipo_resultado || '-') + '</div>' +
                                        '<div style="font-size:0.75rem;color:#6b7280;">Sigla: ' + (item.diario_sigla || '-') + ' · Data: ' + (item.diario_data || '-') + ' · Pág. ' + (item.numero_pagina || '-') + '</div>' +
                                    '</div>' +
                                    (item.tipo_resultado ? escBadge(item.tipo_resultado) : '') +
                                '</div>' +
                                '<div style="padding:14px 18px;font-size:0.82rem;color:#374151;line-height:1.6;" class="dark:text-gray-300">' + texto + '</div>' +
                            '</div>';
                        });
                        html += '</div>';
                        if (result.links && result.links.next) {
                            html += '<div style="margin-top:10px;font-size:0.78rem;color:#6b7280;text-align:right;">📄 Há mais páginas disponíveis</div>';
                        }
                        return html;
                    }

                    function buildEscavadorAutosUi(result) {
                        var items = (result && result.items) ? result.items : [];
                        var pag = result ? result.paginator : null;
                        if (items.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhum documento retornado.</div>';

                        var totalPublicos  = items.filter(function(i){ return i.tipo === 'PUBLICO'; }).length;
                        var totalRestritos = items.filter(function(i){ return i.tipo === 'RESTRITO'; }).length;

                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:14px;text-align:left;">';

                        // Summary header
                        html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                            '<div style="background:linear-gradient(135deg,rgba(13,148,136,.07),rgba(2,132,199,.07));padding:14px 18px;border-bottom:1px solid #e5e7eb;" class="dark:border-gray-700">' +
                                '<h3 style="margin:0;font-size:1.05rem;font-weight:700;color:#111827;" class="dark:text-white">📁 Autos do Processo</h3>' +
                                '<div style="font-size:0.8rem;color:#6b7280;margin-top:4px;">' + items.length + ' documento(s) encontrado(s)' + (pag ? ' &middot; Página ' + pag.current_page + ' de ' + pag.total_pages : '') + '</div>' +
                            '</div>' +
                            '<div style="padding:14px 18px;display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">' +
                                escCard('Total', (pag ? pag.total : items.length) + ' docs') +
                                escCard('📢 Públicos', totalPublicos) +
                                escCard('🔒 Restritos', totalRestritos) +
                            '</div>' +
                        '</div>';

                        items.forEach(function(doc) {
                            var isPublico = doc.tipo === 'PUBLICO';
                            var tipoCor   = isPublico ? '#16a34a' : '#dc2626';
                            var tipoIcon  = isPublico ? '📢' : '🔒';
                            var tipoLabel = isPublico ? 'PÚBLICO' : 'RESTRITO';

                            var extIcon = (doc.extensao_arquivo === 'pdf') ? '📄' : '📎';
                            var pagesLabel = (doc.quantidade_paginas !== null && doc.quantidade_paginas !== undefined)
                                ? doc.quantidade_paginas + (doc.quantidade_paginas === 1 ? ' página' : ' páginas')
                                : '—';

                            var dataFormatada = '';
                            if (doc.data) {
                                try {
                                    var dt = new Date(doc.data.replace(' ', 'T'));
                                    dataFormatada = dt.getDate().toString().padStart(2,'0') + '/' +
                                        (dt.getMonth()+1).toString().padStart(2,'0') + '/' + dt.getFullYear() +
                                        ' ' + dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0');
                                } catch(e){ dataFormatada = doc.data; }
                            }

                            var apiUrl = (doc.links && doc.links.api) ? doc.links.api : '';
                            var apiShort = apiUrl ? apiUrl.replace(/^https?:\/\/[^/]+/, '') : '';
                            if (apiShort.length > 64) apiShort = apiShort.substring(0, 64) + '…';
                            var apiHtml = apiShort
                                ? '<span title="Endpoint da API" style="font-size:0.75rem;color:#475569;background:#f1f5f9;padding:2px 6px;border-radius:4px;word-break:break-all;" class="dark:text-gray-300 dark:bg-gray-700">' + apiShort + '</span>'
                                : '<span style="color:#9ca3af;">—</span>';

                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="padding:12px 16px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid #f3f4f6;" class="dark:border-gray-700">' +
                                    '<div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">' +
                                        '<div style="width:40px;height:40px;border-radius:8px;background:linear-gradient(135deg,#1e40af,#0369a1);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;">' + extIcon + '</div>' +
                                        '<div style="min-width:0;">' +
                                            '<div style="font-size:0.9rem;font-weight:700;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" class="dark:text-white" title="' + (doc.titulo || '') + '">' + (doc.titulo || '-') + '</div>' +
                                            '<div style="font-size:0.75rem;color:#6b7280;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + (doc.descricao || '') + '">' + (doc.descricao || '&nbsp;') + '</div>' +
                                        '</div>' +
                                    '</div>' +
                                    '<span style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;color:#fff;background:' + tipoCor + ';flex-shrink:0;white-space:nowrap;">' + tipoIcon + ' ' + tipoLabel + '</span>' +
                                '</div>' +
                                '<div style="padding:10px 16px 4px;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;">' +
                                    '<div style="background:#f9fafb;padding:10px 12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                        '<div style="font-size:0.68rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:3px;" class="dark:text-gray-400">Data</div>' +
                                        '<div style="font-size:0.82rem;color:#1f2937;font-weight:600;word-break:break-word;" class="dark:text-gray-200">' + (dataFormatada || '-') + '</div>' +
                                    '</div>' +
                                    '<div style="background:#f9fafb;padding:10px 12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                        '<div style="font-size:0.68rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:3px;" class="dark:text-gray-400">Formato</div>' +
                                        '<div style="font-size:0.82rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + ((doc.extensao_arquivo || '-').toUpperCase()) + '</div>' +
                                    '</div>' +
                                    '<div style="background:#f9fafb;padding:10px 12px;border-radius:8px;border:1px solid #f3f4f6;" class="dark:bg-gray-900/50 dark:border-gray-700">' +
                                        '<div style="font-size:0.68rem;font-weight:600;color:#6b7280;text-transform:uppercase;margin-bottom:3px;" class="dark:text-gray-400">Páginas</div>' +
                                        '<div style="font-size:0.82rem;color:#1f2937;font-weight:600;" class="dark:text-gray-200">' + pagesLabel + '</div>' +
                                    '</div>' +
                                '</div>' +
                                (apiUrl ? '<div style="padding:6px 16px 12px;display:flex;align-items:flex-start;gap:6px;">' +
                                    '<span style="font-size:0.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;white-space:nowrap;padding-top:1px;flex-shrink:0;">🔗 API</span>' +
                                    apiHtml +
                                '</div>' : '') +
                            '</div>';
                        });

                        html += '</div>';

                        if (pag && pag.total_pages > 1) {
                            html += '<div style="margin-top:10px;font-size:0.78rem;color:#6b7280;text-align:right;">' +
                                '📄 Há mais páginas disponíveis (' + pag.total + ' documentos no total)' +
                            '</div>';
                        }
                        return html;
                    }

                    function buildEscavadorBuscaDiarioNumUi(result) {
                        var items = Array.isArray(result) ? result : [result];
                        if (!items || items.length === 0) return '<div class="lf-esc-error" style="display:block;">Nenhum processo encontrado neste escopo de busca.</div>';

                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:16px;max-height:560px;overflow:auto;padding-right:8px;text-align:left;">';

                        items.forEach(function(proc) {
                            var titulo = proc.numero_novo || proc.numero_antigo || 'Processo sem número';
                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);" class="dark:border-gray-700 dark:bg-gray-800">';
                            html += '<div style="background:linear-gradient(135deg,rgba(13,148,136,.07),rgba(2,132,199,.07));padding:14px 18px;border-bottom:1px solid #e5e7eb;display:flex;flex-direction:column;gap:8px;" class="dark:border-gray-700">';
                            
                            html += '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">';
                            html += '<div style="font-size:1.05rem;font-weight:700;color:#111827;" class="dark:text-white">⚖️ ' + titulo + '</div>';
                            html += '<div style="display:flex;gap:6px;flex-wrap:wrap;">';
                            if (proc.diario_sigla) html += escBadge(proc.diario_sigla);
                            if (proc.estado) html += escBadge(proc.estado, '#0ea5e9');
                            html += '</div>';
                            html += '</div>';

                            html += '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:10px;margin-top:6px;">';
                            html += escCard('Diário', proc.diario_nome || '-');
                            html += escCard('Movimentações', proc.quantidade_movimentacoes + ' (' + (proc.data_movimentacoes || '-') + ')');
                            if (proc.enviado_trimon_em) {
                                var dateObj = new Date(proc.enviado_trimon_em.replace(' ', 'T'));
                                html += escCard('Atualização', dateObj.toLocaleDateString('pt-BR'));
                            }
                            html += '</div>';
                            html += '</div>';

                            if (proc.ultimas_movimentacoes_resumo && proc.ultimas_movimentacoes_resumo.length > 0) {
                                html += '<div style="padding:14px 18px;background:#fff;" class="dark:bg-gray-800">';
                                html += escSection('Últimas Movimentações (' + proc.ultimas_movimentacoes_resumo.length + ')', proc.ultimas_movimentacoes_resumo.map(function(mov) {
                                    var mDate = mov.data ? new Date(mov.data).toLocaleDateString('pt-BR') : '';
                                    var mHtml = '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:8px;" class="dark:bg-gray-900 dark:border-gray-700">';
                                    mHtml += '<div style="font-size:0.8rem;color:#6b7280;margin-bottom:6px;font-weight:600;">📅 ' + mDate + '</div>';
                                    mHtml += '<div style="font-size:0.85rem;color:#374151;line-height:1.5;" class="dark:text-gray-300">' + (mov.conteudo_resumo || '-') + '</div>';
                                    
                                    if (mov.envolvidos_resumo && mov.envolvidos_resumo.length > 0) {
                                        mHtml += '<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">';
                                        mov.envolvidos_resumo.forEach(function(env) {
                                            mHtml += '<span style="font-size:0.75rem;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:12px;border:1px solid #bae6fd;" class="dark:bg-sky-900 dark:text-sky-100 dark:border-sky-800">👤 ' + (env.nome_sem_filtro || env.nome) + (env.envolvido_tipo ? ' (' + env.envolvido_tipo + ')' : '') + (env.oab ? ' - OAB: ' + env.oab : '') + '</span>';
                                        });
                                        mHtml += '</div>';
                                    }
                                    mHtml += '</div>';
                                    return mHtml;
                                }).join(''));
                                html += '</div>';
                            }

                            html += '</div>';
                        });
                        html += '</div>';
                        return html;
                    }

                    function buildEscavadorProcessoCNJUi(result) {
                        if (!result || !result.numero_cnj) return '<div class="lf-esc-error" style="display:block;">Nenhum dado de processo retornado.</div>';

                        var fontes = result.fontes || [];
                        var relac  = result.processos_relacionados || [];

                        // ── Status badges
                        var arquivado  = result.fontes_tribunais_estao_arquivadas;
                        var statusBadge = arquivado
                            ? '<span style="padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;color:#fff;background:#dc2626;">⚠ Arquivado</span>'
                            : '<span style="padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;color:#fff;background:#16a34a;">✅ Ativo</span>';

                        var unid = result.unidade_origem || {};

                        // ── Header card
                        var html = '<div id="lf-svc-result-content" style="display:flex;flex-direction:column;gap:16px;max-height:600px;overflow:auto;padding-right:8px;text-align:left;">';

                        html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                            '<div style="background:linear-gradient(135deg,rgba(13,148,136,.08),rgba(2,132,199,.08));padding:16px 20px;border-bottom:1px solid #e5e7eb;" class="dark:border-gray-700">' +
                                '<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">' +
                                    '<div>' +
                                        '<h3 style="margin:0;font-size:1.1rem;font-weight:700;color:#111827;display:flex;align-items:center;gap:6px;" class="dark:text-white">⚖️ ' + result.numero_cnj + '</h3>' +
                                        '<div style="font-size:0.8rem;color:#6b7280;margin-top:4px;">Início: <strong>' + (result.data_inicio || '-') + '</strong> · Última movimentação: <strong>' + (result.data_ultima_movimentacao || '-') + '</strong> · ' + (result.tempo_desde_ultima_verificacao || '') + '</div>' +
                                    '</div>' +
                                    '<div style="display:flex;gap:6px;flex-wrap:wrap;">' + statusBadge + '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div style="padding:16px 20px;">' +
                                '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px;">' +
                                    escCard('📋 Movimentações Total', result.quantidade_movimentacoes || 0) +
                                    escCard('📍 Estado Origem', result.estado_origem ? result.estado_origem.sigla + ' — ' + result.estado_origem.nome : '-') +
                                    escCard('⚡ Polo Ativo', result.titulo_polo_ativo || '-') +
                                    escCard('🛡 Polo Passivo', result.titulo_polo_passivo || '-') +
                                '</div>' +
                                // Unidade de origem
                                (unid.nome ? escSection('🏛 Unidade de Origem',
                                    '<div style="padding:10px 14px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;" class="dark:bg-gray-900/40 dark:border-gray-700">' +
                                        '<div style="font-size:0.88rem;font-weight:700;color:#1f2937;" class="dark:text-gray-100">' + unid.nome + '</div>' +
                                        '<div style="font-size:0.75rem;color:#6b7280;margin-top:4px;">' + (unid.classificacao || '') + ' · ' + (unid.cidade || '') + '/' + (unid.estado ? unid.estado.sigla : '') + ' · ' + (unid.tribunal_sigla || '') + '</div>' +
                                        (unid.endereco ? '<div style="font-size:0.75rem;color:#6b7280;margin-top:2px;">📍 ' + unid.endereco + '</div>' : '') +
                                    '</div>'
                                ) : '') +
                                // Processos relacionados
                                (relac.length > 0 ? escSection('🔗 Processos Relacionados (' + relac.length + ')',
                                    '<div style="display:flex;flex-wrap:wrap;gap:6px;">' +
                                    relac.map(function(r) { return escBadge('⚖️ ' + r.numero, '#1e40af'); }).join('') +
                                    '</div>'
                                ) : '') +
                            '</div>' +
                        '</div>';

                        // ── Fonte cards
                        fontes.forEach(function(fonte) {
                            var capa = fonte.capa || {};
                            var assunto = capa.assunto_principal_normalizado ? capa.assunto_principal_normalizado.path_completo : (capa.assunto || '-');
                            var valorCausa = capa.valor_causa ? capa.valor_causa.valor_formatado : null;
                            var situacao = capa.situacao || '-';
                            var sitCor = situacao === 'Baixado' ? '#dc2626' : (situacao === 'Em andamento' ? '#16a34a' : '#6b7280');
                            var statusPredito = fonte.status_predito || '-';
                            var stPCor = statusPredito === 'ATIVO' ? '#16a34a' : '#6b7280';

                            // Envolvidos
                            var envolvidos = fonte.envolvidos || [];
                            var envolvidosHtml = envolvidos.map(function(env) {
                                if (!env.nome) return '';
                                var poloCor = env.polo === 'ATIVO' ? '#16a34a' : (env.polo === 'PASSIVO' ? '#dc2626' : (env.polo === 'ADVOGADO' ? '#1e40af' : '#6b7280'));
                                var avatar  = env.polo === 'ATIVO' ? '⚡' : (env.polo === 'PASSIVO' ? '🛡' : (env.tipo === 'JUIZ' || env.tipo_normalizado === 'Juiz' ? '👨‍⚖️' : (env.polo === 'ADVOGADO' ? '⚖' : '👤')));
                                var advHtml = (env.advogados || []).filter(function(a){ return a.nome; }).map(function(a) {
                                    return '<span style="font-size:0.7rem;padding:2px 7px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;color:#1e40af;display:inline-block;margin:2px;">⚖ ' + a.nome + (a.oabs && a.oabs[0] ? ' OAB/' + a.oabs[0].uf : '') + '</span>';
                                }).join('');
                                return '<div style="padding:8px 12px;border-left:3px solid ' + poloCor + ';background:#f9fafb;border-radius:0 6px 6px 0;margin-bottom:6px;" class="dark:bg-gray-900/40">' +
                                    '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">' +
                                        '<span>' + avatar + '</span>' +
                                        '<span style="font-size:0.85rem;font-weight:700;color:#1f2937;" class="dark:text-gray-100">' + env.nome + '</span>' +
                                        '<span style="padding:2px 8px;border-radius:99px;font-size:0.68rem;font-weight:700;color:#fff;background:' + poloCor + ';">' + (env.tipo_normalizado || env.tipo || env.polo || '') + '</span>' +
                                    '</div>' +
                                    (advHtml ? '<div style="margin-top:4px;">' + advHtml + '</div>' : '') +
                                '</div>';
                            }).join('');

                            // Audiências
                            var audHtml = (fonte.audiencias || []).map(function(a) {
                                var aCor = a.situacao === 'Cancelada' ? '#dc2626' : (a.situacao === 'Realizada' ? '#16a34a' : '#f59e0b');
                                return '<div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin:3px;font-size:0.78rem;" class="dark:bg-gray-900/40 dark:border-gray-700">' +
                                    '<span>📅</span><span><strong>' + (a.tipo || '') + '</strong> — ' + (a.data || '') + '</span>' +
                                    '<span style="padding:2px 7px;border-radius:99px;font-size:0.68rem;font-weight:700;color:#fff;background:' + aCor + ';">' + (a.situacao || '') + '</span>' +
                                '</div>';
                            }).join('');

                            html += '<div style="border:1px solid #e5e7eb;border-radius:10px;background:#fff;overflow:hidden;" class="dark:border-gray-700 dark:bg-gray-800">' +
                                '<div style="padding:13px 18px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;" class="dark:border-gray-700">' +
                                    '<div style="display:flex;align-items:center;gap:10px;">' +
                                        '<div style="width:38px;height:38px;border-radius:8px;background:linear-gradient(135deg,#0d9488,#0369a1);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.7rem;font-weight:800;flex-shrink:0;">' + (fonte.sigla || '?') + '</div>' +
                                        '<div>' +
                                            '<div style="font-size:0.9rem;font-weight:700;color:#111827;" class="dark:text-white">' + (fonte.nome || '-') + '</div>' +
                                            '<div style="font-size:0.75rem;color:#6b7280;">' + (fonte.grau_formatado || '-') + ' · ' + (fonte.sistema || '') + (fonte.fisico ? ' · Físico' : ' · Eletrônico') + '</div>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div style="display:flex;gap:6px;flex-wrap:wrap;">' +
                                        '<span style="padding:3px 9px;border-radius:99px;font-size:0.7rem;font-weight:700;color:#fff;background:' + sitCor + ';">' + situacao + '</span>' +
                                        '<span style="padding:3px 9px;border-radius:99px;font-size:0.7rem;font-weight:700;color:#fff;background:' + stPCor + ';">' + statusPredito + '</span>' +
                                    '</div>' +
                                '</div>' +
                                '<div style="padding:16px 18px;">' +
                                    '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:16px;">' +
                                        escCard('Classe', capa.classe || '-') +
                                        escCard('Área', capa.area || '-') +
                                        escCard('Distribuição', capa.data_distribuicao || '-') +
                                        escCard('Valor da Causa', valorCausa || '-') +
                                    '</div>' +
                                    escSection('📌 Assunto', '<div style="font-size:0.82rem;color:#1f2937;font-weight:500;padding:8px 12px;background:#f9fafb;border-radius:6px;" class="dark:bg-gray-900/40 dark:text-gray-200">' + assunto + '</div>') +
                                    (audHtml ? escSection('🏛️ Audiências', '<div style="display:flex;flex-wrap:wrap;gap:4px;">' + audHtml + '</div>') : '') +
                                    (envolvidosHtml ? escSection('👥 Envolvidos (' + envolvidos.length + ')', envolvidosHtml) : '') +
                                '</div>' +
                            '</div>';
                        });

                        html += '</div>';
                        return html;
                    }

                    // ─────────────────────────────────────────────────────────────

                    window.lfShowEscavadorExample = function() {
                        // ── Type-aware example data ──────────────────────────────
                        var exampleData, extraHtml = '';

                        if (currentType === 'DATAJUD_BUSCA_CLASSE_ORGAO') {
                            // Exemplo real: Execução Fiscal (1116) — VARA DE EXECUÇÃO FISCAL DO DF (13597) — TJDFT
                            // Fonte: https://datajud-wiki.cnj.jus.br/api-publica/exemplos/exemplo3
                            exampleData = {
                                "took": 213,
                                "timed_out": false,
                                "_shards": { "total": 3, "successful": 3, "skipped": 0, "failed": 0 },
                                "hits": {
                                    "total": { "value": 10000, "relation": "gte" },
                                    "max_score": 2.0,
                                    "hits": [
                                        {
                                            "_index": "api_publica_tjdft",
                                            "_type": "_doc",
                                            "_id": "TJDFT_1116_G1_13597_07223914020178070001",
                                            "_score": 2.0,
                                            "_source": {
                                                "numeroProcesso": "07223914020178070001",
                                                "classe": { "codigo": 1116, "nome": "Execução Fiscal" },
                                                "sistema": { "codigo": 1, "nome": "Pje" },
                                                "formato": { "codigo": 1, "nome": "Eletrônico" },
                                                "tribunal": "TJDFT",
                                                "dataHoraUltimaAtualizacao": "2023-04-18T12:00:27.701Z",
                                                "grau": "G1",
                                                "dataAjuizamento": "2017-08-11T12:07:05.000Z",
                                                "orgaoJulgador": { "codigoMunicipioIBGE": 5300108, "codigo": 13597, "nome": "VARA DE EXECUÇÃO FISCAL DO DF" },
                                                "assuntos": [
                                                    { "codigo": 6017, "nome": "Dívida Ativa (Execução Fiscal)" },
                                                    { "codigo": 10394, "nome": "Dívida Ativa não-tributária" }
                                                ],
                                                "movimentos": [
                                                    { "codigo": 26, "nome": "Distribuição", "dataHora": "2017-08-21T10:05:32.000Z" },
                                                    { "codigo": 11382, "nome": "Bloqueio/penhora on line", "dataHora": "2022-07-13T07:25:59.000Z" },
                                                    { "codigo": 132, "nome": "Recebimento", "dataHora": "2022-07-13T07:26:00.000Z" }
                                                ]
                                            }
                                        },
                                        {
                                            "_index": "api_publica_tjdft",
                                            "_type": "_doc",
                                            "_id": "TJDFT_1116_G1_13597_00073039720138070015",
                                            "_score": 2.0,
                                            "_source": {
                                                "numeroProcesso": "00073039720138070015",
                                                "classe": { "codigo": 1116, "nome": "Execução Fiscal" },
                                                "sistema": { "codigo": 1, "nome": "Pje" },
                                                "formato": { "codigo": 1, "nome": "Eletrônico" },
                                                "tribunal": "TJDFT",
                                                "dataHoraUltimaAtualizacao": "2022-09-06T17:26:23.938Z",
                                                "grau": "G1",
                                                "@timestamp": "2023-04-13T18:02:23.754Z",
                                                "dataAjuizamento": "2019-05-30T03:17:56.000Z",
                                                "orgaoJulgador": { "codigoMunicipioIBGE": 5300108, "codigo": 13597, "nome": "VARA DE EXECUÇÃO FISCAL DO DF" },
                                                "assuntos": [
                                                    { "codigo": 6017, "nome": "Dívida Ativa (Execução Fiscal)" },
                                                    { "codigo": 10394, "nome": "Dívida Ativa não-tributária" }
                                                ],
                                                "movimentos": [
                                                    { "codigo": 26, "nome": "Distribuição", "dataHora": "2013-02-18T13:17:23.000Z" },
                                                    { "codigo": 245, "nome": "Provisório", "dataHora": "2019-05-30T11:10:02.000Z" }
                                                ]
                                            }
                                        }
                                    ]
                                }
                            };

                            // Monta cursor de paginação a partir do @timestamp do último hit
                            var lastHit = exampleData.hits.hits[exampleData.hits.hits.length - 1];
                            var lastSrc = lastHit && lastHit._source ? lastHit._source : {};
                            var tsValue = lastSrc['@timestamp'] ? new Date(lastSrc['@timestamp']).getTime() : null;
                            var sortCursor = tsValue ? '[' + tsValue + ']' : '[ ... ]';
                            var totalHits = exampleData.hits.total.value;
                            var totalRelation = exampleData.hits.total.relation || 'eq';
                            var totalLabel = (totalRelation === 'gte' ? 'mais de ' : '') + totalHits.toLocaleString('pt-BR');

                            extraHtml =
                                '<div style="margin-top:20px;padding:16px 18px;border:1px solid #99f6e4;border-radius:10px;background:linear-gradient(135deg,rgba(13,148,136,.04),rgba(2,132,199,.04));" class="dark:border-teal-800 dark:bg-teal-900/10">' +
                                    '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">' +
                                        '<span style="font-size:1.1rem;">📑</span>' +
                                        '<span style="font-size:0.9rem;font-weight:700;color:#1f2937;" class="dark:text-white">Próxima Página Disponível</span>' +
                                    '</div>' +
                                    '<p style="font-size:0.82rem;color:#4b5563;margin:0 0 12px;" class="dark:text-gray-300">' +
                                        'Esta busca encontrou <strong>' + totalLabel + ' processos</strong> no DataJud. Foram exibidos 2 nesta página. ' +
                                        'Para continuar consultando os próximos resultados, copie o valor da <strong>Página Anterior</strong> abaixo e utilize o card <strong>📄 Busca com Paginação (DataJud)</strong>.' +
                                    '</p>' +
                                    '<div style="background:#1f2937;border-radius:8px;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">' +
                                        '<div>' +
                                            '<div style="font-size:0.72rem;color:#6b7280;font-family:monospace;margin-bottom:4px;">Página Anterior</div>' +
                                            '<div style="font-size:1.05rem;font-weight:700;color:#fcd34d;font-family:monospace;letter-spacing:0.03em;">' + (tsValue || '...') + '</div>' +
                                        '</div>' +
                                        '<button type="button" onclick="window.lfCopyText(\'' + (tsValue || '') + '\', this)" data-copy-cursor style="background:#374151;color:#e5e7eb;border:1px solid #4b5563;padding:7px 14px;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;white-space:nowrap;">📋 Copiar</button>' +
                                    '</div>' +
                                    '<div style="margin-top:12px;padding:10px 14px;background:#fefce8;border:1px solid #fde68a;border-radius:8px;display:flex;align-items:flex-start;gap:8px;" class="dark:bg-yellow-900/20 dark:border-yellow-700">' +
                                        '<span style="font-size:1rem;flex-shrink:0;">💡</span>' +
                                        '<p style="font-size:0.8rem;color:#78350f;margin:0;line-height:1.5;" class="dark:text-yellow-200">' +
                                            '<strong>Como usar:</strong> Cole o valor acima no campo <strong>"Página Anterior"</strong> do card <strong>📄 Busca com Paginação (DataJud)</strong>. Selecione o mesmo tribunal e informe o número de registros desejado por página (padrão: 10). A consulta retornará os processos a partir da posição seguinte.' +
                                        '</p>' +
                                    '</div>' +
                                '</div>';

                        } else if (currentType.startsWith('DATAJUD_')) {
                            // Default: DATAJUD_BUSCA_NUMERO
                            exampleData = {
                                "took": 6679, "timed_out": false,
                                "_shards": { "total": 7, "successful": 7, "skipped": 0, "failed": 0 },
                                "hits": {
                                    "total": { "value": 1, "relation": "eq" },
                                    "max_score": 13.917725,
                                    "hits": [{
                                        "_index": "api_publica_trf1", "_type": "_doc",
                                        "_id": "TRF1_436_JE_16403_00008323520184013202",
                                        "_score": 13.917725,
                                        "_source": {
                                            "numeroProcesso": "00008323520184013202",
                                            "classe": { "codigo": 436, "nome": "Procedimento do Juizado Especial Cível" },
                                            "sistema": { "codigo": 1, "nome": "Pje" },
                                            "formato": { "codigo": 1, "nome": "Eletrônico" },
                                            "tribunal": "TRF1",
                                            "dataHoraUltimaAtualizacao": "2023-07-21T19:10:08.483Z",
                                            "grau": "JE",
                                            "dataAjuizamento": "2018-10-29T00:00:00.000Z",
                                            "movimentos": [
                                                { "codigo": 26, "nome": "Distribuição", "dataHora": "2018-10-30T14:06:24.000Z" },
                                                { "codigo": 14732, "nome": "Conversão de Autos Físicos em Eletrônicos", "dataHora": "2020-08-05T01:15:18.000Z" }
                                            ],
                                            "orgaoJulgador": { "codigo": 16403, "nome": "JEF Adj - Tefé" },
                                            "assuntos": [{ "codigo": 6177, "nome": "Concessão" }]
                                        }
                                    }]
                                }
                            };
                        } else if (currentType.includes('RESUMO_ENVOLVIDO')) {
                            exampleData = JSON.parse("{\n  \"nome\": \"Empresa Fantasia S.A\",\n  \"tipo_pessoa\": \"JURIDICA\",\n  \"quantidade_processos\": 3516803\n}");
                        } else if (currentType.includes('RESUMO_OAB')) {
                            exampleData = JSON.parse("{\n  \"nome\": \"Fulano da Silva\",\n  \"tipo\": \"ADVOGADO\",\n  \"quantidade_processos\": 153\n}");
                        } else if (currentType.includes('PROCESSOS')) {
                            exampleData = JSON.parse("{\n  \"envolvido_encontrado\": {\n    \"nome\": \"Engenharia e Construcoes Ltda\",\n    \"tipo_pessoa\": \"JURIDICA\",\n    \"quantidade_processos\": 2\n  },\n  \"items\": [\n    {\n      \"numero_cnj\": \"1060225-21.2023.5.56.0002\",\n      \"titulo_polo_ativo\": \"Joao da Silva\",\n      \"titulo_polo_passivo\": \"Empresa de Engenharia e outros\",\n      \"ano_inicio\": 2023,\n      \"data_inicio\": \"2023-03-11\",\n      \"estado_origem\": {\n        \"nome\": \"São Paulo\",\n        \"sigla\": \"SP\"\n      },\n      \"unidade_origem\": {\n        \"nome\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n        \"endereco\": \"Avenida Francisco Xavier de Arruda Camargo, 300\",\n        \"classificacao\": \"JE - Juizado Especial\",\n        \"cidade\": \"São Paulo\",\n        \"estado\": {\n          \"nome\": \"São Paulo\",\n          \"sigla\": \"SP\"\n        },\n        \"tribunal_sigla\": \"TJSP\"\n      },\n      \"data_ultima_movimentacao\": \"2023-03-11\",\n      \"quantidade_movimentacoes\": 2,\n      \"fontes_tribunais_estao_arquivadas\": false,\n      \"data_ultima_verificacao\": \"2023-03-14T19:00:14+00:00\",\n      \"tempo_desde_ultima_verificacao\": \"há 15 minutos\",\n      \"processos_relacionados\": [\n        {\n          \"numero\": \"8027909-02.2019.8.05.0000\"\n        },\n        {\n          \"numero\": \"8028150-73.2019.8.05.0000\"\n        }\n      ],\n      \"fontes\": [\n        {\n          \"id\": 47,\n          \"processo_fonte_id\": 1048903,\n          \"descricao\": \"TRT-2 - 1º grau\",\n          \"nome\": \"Tribunal Regional do Trabalho da 2ª Região\",\n          \"sigla\": \"TRT-2\",\n          \"tipo\": \"TRIBUNAL\",\n          \"data_inicio\": \"2023-03-11\",\n          \"data_ultima_movimentacao\": \"2023-03-11\",\n          \"segredo_justica\": false,\n          \"arquivado\": null,\n          \"status_predito\": \"ATIVO\",\n          \"grau\": 1,\n          \"grau_formatado\": \"Primeiro Grau\",\n          \"fisico\": false,\n          \"sistema\": \"PJE\",\n          \"capa\": {\n            \"classe\": \"ACAO TRABALHISTA - RITO ORDINARIO\",\n            \"assunto\": \"SALARIO POR FORA - INTEGRACAO\",\n            \"assuntos_normalizados\": [\n              {\n                \"id\": 6870,\n                \"nome\": \"Horas Extras\",\n                \"nome_com_pai\": \"Duração do Trabalho > Horas Extras\",\n                \"path_completo\": \"DIREITO DO TRABALHO | Direito Individual do Trabalho  | Duração do Trabalho | Horas Extras\",\n                \"bloqueado\": false\n              },\n              {\n                \"id\": 7041,\n                \"nome\": \"Salário por Fora - Integração\",\n                \"nome_com_pai\": \"Salário/Diferença Salarial > Salário por Fora - Integração\",\n                \"path_completo\": \"DIREITO DO TRABALHO | Direito Individual do Trabalho  | Verbas Remuneratórias, Indenizatórias e Benefícios | Salário/Diferença Salarial | Salário por Fora - Integração\",\n                \"bloqueado\": false\n              }\n            ],\n            \"assunto_principal_normalizado\": {\n              \"id\": 7041,\n              \"nome\": \"Salário por Fora - Integração\",\n              \"nome_com_pai\": \"Salário/Diferença Salarial > Salário por Fora - Integração\",\n              \"path_completo\": \"DIREITO DO TRABALHO | Direito Individual do Trabalho  | Verbas Remuneratórias, Indenizatórias e Benefícios | Salário/Diferença Salarial | Salário por Fora - Integração\",\n              \"bloqueado\": false\n            },\n            \"area\": \"TRABALHISTA\",\n            \"orgao_julgador\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n            \"orgao_julgador_normalizado\": {\n              \"nome\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n              \"endereco\": \"Avenida Francisco Xavier de Arruda Camargo, 300\",\n              \"classificacao\": \"JE - Juizado Especial\",\n              \"cidade\": \"São Paulo\",\n              \"estado\": {\n                \"nome\": \"São Paulo\",\n                \"sigla\": \"SP\"\n              },\n              \"tribunal_sigla\": \"TJSP\"\n            },\n            \"situacao\": \"Baixado\",\n            \"valor_causa\": {\n              \"valor\": \"310455.6100\",\n              \"moeda\": \"R$\",\n              \"valor_formatado\": \"R$ 310.455,61\"\n            },\n            \"data_distribuicao\": \"2023-03-11\",\n            \"data_arquivamento\": null,\n            \"informacoes_complementares\": null\n          },\n          \"url\": \"https://pje.trt2.jus.br/consultaprocessual/detalhe-processo/10003246720235020007\",\n          \"tribunal\": {\n            \"id\": 13,\n            \"nome\": \"Tribunal Regional do Trabalho da 2ª Região\",\n            \"sigla\": \"TRT-2\",\n            \"categoria\": null\n          },\n          \"quantidade_movimentacoes\": 2,\n          \"data_ultima_verificacao\": \"2023-03-14T19:00:14+00:00\",\n          \"envolvidos\": [\n            {\n              \"nome\": \"Joao da Silva\",\n              \"quantidade_processos\": 1,\n              \"tipo_pessoa\": \"FISICA\",\n              \"advogados\": [\n                {\n                  \"nome\": \"Paulo Roberto de Oliveira\",\n                  \"quantidade_processos\": 3,\n                  \"tipo_pessoa\": \"FISICA\",\n                  \"prefixo\": null,\n                  \"sufixo\": null,\n                  \"tipo\": \"ADVOGADO\",\n                  \"tipo_normalizado\": \"Advogado\",\n                  \"polo\": \"ADVOGADO\",\n                  \"cpf\": \"00000000000\",\n                  \"oabs\": [\n                    {\n                      \"uf\": \"SP\",\n                      \"tipo\": \"ADVOGADO\",\n                      \"numero\": 123123\n                    }\n                  ]\n                },\n                {\n                  \"nome\": \"Daniel Felipe Assis\",\n                  \"quantidade_processos\": 8,\n                  \"tipo_pessoa\": \"FISICA\",\n                  \"prefixo\": null,\n                  \"sufixo\": null,\n                  \"tipo\": \"ADVOGADO\",\n                  \"tipo_normalizado\": \"Advogado\",\n                  \"polo\": \"ADVOGADO\",\n                  \"oabs\": [\n                    {\n                      \"uf\": \"SP\",\n                      \"tipo\": \"ADVOGADO\",\n                      \"numero\": 123123\n                    }\n                  ]\n                }\n              ],\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMANTE\",\n              \"tipo_normalizado\": \"Reclamante\",\n              \"polo\": \"ATIVO\",\n              \"cpf\": \"00000000000\"\n            },\n            {\n              \"nome\": \"Empresa de Engenharia e outros\",\n              \"quantidade_processos\": 2,\n              \"tipo_pessoa\": \"JURIDICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMADO\",\n              \"tipo_normalizado\": \"Reclamado\",\n              \"polo\": \"PASSIVO\",\n              \"cnpj\": \"00000000000000\"\n            },\n            {\n              \"nome\": \"Empresa de Construcoes\",\n              \"quantidade_processos\": 2,\n              \"tipo_pessoa\": \"JURIDICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMADO\",\n              \"tipo_normalizado\": \"Reclamado\",\n              \"polo\": \"PASSIVO\",\n              \"cnpj\": \"00000000000000\"\n            },\n            {\n              \"nome\": \"Engenharia e Construcoes Ltda\",\n              \"quantidade_processos\": 66,\n              \"tipo_pessoa\": \"JURIDICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMADO\",\n              \"tipo_normalizado\": \"Reclamado\",\n              \"polo\": \"PASSIVO\",\n              \"cnpj\": \"00000000000000\"\n            },\n            {\n              \"nome\": \"Construtora e Incorporadora Ltda\",\n              \"quantidade_processos\": 1,\n              \"tipo_pessoa\": \"JURIDICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMADO\",\n              \"tipo_normalizado\": \"Reclamado\",\n              \"polo\": \"PASSIVO\",\n              \"cnpj\": \"00000000000000\"\n            }\n          ]\n        }\n      ]\n    },\n    {\n      \"numero_cnj\": \"0205615-29.2023.3.12.0026\",\n      \"titulo_polo_ativo\": \"Maria Almeida Sampaio\",\n      \"titulo_polo_passivo\": \"Engenharia e Construcoes Ltda\",\n      \"ano_inicio\": 2023,\n      \"data_inicio\": \"2023-03-10\",\n      \"data_ultima_movimentacao\": \"2023-03-10\",\n      \"quantidade_movimentacoes\": 2,\n      \"fontes_tribunais_estao_arquivadas\": false,\n      \"data_ultima_verificacao\": \"2023-03-14T19:00:14+00:00\",\n      \"tempo_desde_ultima_verificacao\": \"há 15 minutos\",\n      \"fontes\": [\n        {\n          \"id\": 355,\n          \"processo_fonte_id\": 1048904,\n          \"descricao\": \"TRT-20 - 1º grau\",\n          \"nome\": \"Tribunal Regional do Trabalho da 20ª Região\",\n          \"sigla\": \"TRT-20\",\n          \"tipo\": \"TRIBUNAL\",\n          \"data_inicio\": \"2023-03-10\",\n          \"data_ultima_movimentacao\": \"2023-03-10\",\n          \"segredo_justica\": false,\n          \"arquivado\": null,\n          \"grau\": 1,\n          \"grau_formatado\": \"Primeiro Grau\",\n          \"fisico\": false,\n          \"sistema\": \"PJE\",\n          \"capa\": {\n            \"classe\": \"ACAO TRABALHISTA - RITO ORDINARIO\",\n            \"assunto\": \"ISONOMIA/DIFERENCA SALARIAL\",\n            \"assuntos_normalizados\": [\n              {\n                \"id\": 6793,\n                \"nome\": \"isonomia/Diferença Salarial\",\n                \"nome_com_pai\": \"Enquadramento > isonomia/Diferença Salarial\",\n                \"path_completo\": \"DIREITO DO TRABALHO | Direito Individual do Trabalho  | Categoria Profissional Especial | Bancários | Enquadramento | isonomia/Diferença Salarial\",\n                \"bloqueado\": false\n              },\n              {\n                \"id\": 6978,\n                \"nome\": \"Adicional de Periculosidade\",\n                \"nome_com_pai\": \"Adicional > Adicional de Periculosidade\",\n                \"path_completo\": \"DIREITO DO TRABALHO | Direito Individual do Trabalho  | Verbas Remuneratórias, Indenizatórias e Benefícios | Adicional | Adicional de Periculosidade\",\n                \"bloqueado\": false\n              }\n            ],\n            \"assunto_principal_normalizado\": {\n              \"id\": 6793,\n              \"nome\": \"isonomia/Diferença Salarial\",\n              \"nome_com_pai\": \"Enquadramento > isonomia/Diferença Salarial\",\n              \"path_completo\": \"DIREITO DO TRABALHO | Direito Individual do Trabalho  | Categoria Profissional Especial | Bancários | Enquadramento | isonomia/Diferença Salarial\",\n              \"bloqueado\": false\n            },\n            \"area\": \"TRABALHISTA\",\n            \"orgao_julgador\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n            \"orgao_julgador_normalizado\": {\n              \"nome\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n              \"endereco\": \"Avenida Francisco Xavier de Arruda Camargo, 300\",\n              \"classificacao\": \"JE - Juizado Especial\",\n              \"cidade\": \"São Paulo\",\n              \"estado\": {\n                \"nome\": \"São Paulo\",\n                \"sigla\": \"SP\"\n              },\n              \"tribunal_sigla\": \"TJSP\"\n            },\n            \"valor_causa\": {\n              \"valor\": \"292319.7200\",\n              \"moeda\": \"R$\",\n              \"valor_formatado\": \"R$ 292.319,72\"\n            },\n            \"data_distribuicao\": \"2023-03-10\",\n            \"data_arquivamento\": null,\n            \"informacoes_complementares\": null\n          },\n          \"url\": \"https://pje.trt20.jus.br/consultaprocessual/detalhe-processo/00002054020235200002\",\n          \"tribunal\": {\n            \"id\": 31,\n            \"nome\": \"Tribunal Regional do Trabalho da 20ª Região\",\n            \"sigla\": \"TRT-20\",\n            \"categoria\": null\n          },\n          \"quantidade_movimentacoes\": 2,\n          \"data_ultima_verificacao\": \"2023-03-14T19:00:14+00:00\",\n          \"envolvidos\": [\n            {\n              \"nome\": \"Maria Almeida Sampaio\",\n              \"quantidade_processos\": 1,\n              \"tipo_pessoa\": \"FISICA\",\n              \"advogados\": [\n                {\n                  \"nome\": \"Petrucio Silveira\",\n                  \"quantidade_processos\": 16,\n                  \"tipo_pessoa\": \"FISICA\",\n                  \"prefixo\": null,\n                  \"sufixo\": null,\n                  \"tipo\": \"ADVOGADO\",\n                  \"tipo_normalizado\": \"Advogado\",\n                  \"polo\": \"ADVOGADO\",\n                  \"cpf\": \"00000000000\",\n                  \"oabs\": [\n                    {\n                      \"uf\": \"SE\",\n                      \"tipo\": \"ADVOGADO\",\n                      \"numero\": 123123\n                    }\n                  ]\n                },\n                {\n                  \"nome\": \"Kevin Correia Borges\",\n                  \"quantidade_processos\": 8,\n                  \"tipo_pessoa\": \"FISICA\",\n                  \"prefixo\": null,\n                  \"sufixo\": null,\n                  \"tipo\": \"ADVOGADO\",\n                  \"tipo_normalizado\": \"Advogado\",\n                  \"polo\": \"ADVOGADO\",\n                  \"cpf\": \"00000000000\",\n                  \"oabs\": [\n                    {\n                      \"uf\": \"SE\",\n                      \"tipo\": \"ADVOGADO\",\n                      \"numero\": 123123\n                    }\n                  ]\n                }\n              ],\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMANTE\",\n              \"tipo_normalizado\": \"Reclamante\",\n              \"polo\": \"ATIVO\",\n              \"cpf\": \"00000000000\"\n            },\n            {\n              \"nome\": \"Engenharia e Construcoes Ltda\",\n              \"quantidade_processos\": 66,\n              \"tipo_pessoa\": \"JURIDICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"RECLAMADO\",\n              \"tipo_normalizado\": \"Reclamado\",\n              \"polo\": \"PASSIVO\",\n              \"cnpj\": \"00000000000000\"\n            }\n          ]\n        }\n      ]\n    }\n  ],\n  \"links\": {\n    \"next\": \"https://api.escavador.com/api/v2/envolvido/processos?nome=Joao%20da%20Silva&cursor=eyJwcm9jZXNzby5kYXRhX2luaWNpbyI6IjIwMjItMDctMDUgMDA6MDA6MDAiLCJwcm9jZXNzby5pZCI6MTEwNjg3NSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ&li=216025845\"\n  },\n  \"paginator\": {\n    \"per_page\": 20\n  }\n}");
                        } else if (currentType === 'BUSCA_JURIS' || currentType === 'BUSCA_LEGIS') {
                            exampleData = JSON.parse("{\"paginator\":{\"total\":4,\"total_pages\":1,\"current_page\":1,\"per_page\":20},\"links\":{\"prev\":null,\"next\":null},\"items\":[{\"id\":4236069,\"numero_pagina\":31,\"diario_sigla\":\"DOMNAT-RN\",\"diario_nome\":\"Diário Oficial do Município de Natal\",\"diario_data\":\"2011-08-12\",\"texto\":\" TEXTO DO RESULTADO AQUI...\",\"tipo_resultado\":\"Diario\"}]}");
                        } else if (currentType.includes('MOVIMENTAES')) {
                            exampleData = JSON.parse("{\n  \"items\": [\n    {\n      \"id\": 853879,\n      \"data\": \"2018-07-25\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"CERTIDAO DE CARTORIO EXPEDIDA\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853877,\n      \"data\": \"2018-07-25\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"CERTIDAO DE CARTORIO EXPEDIDA\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853875,\n      \"data\": \"2018-07-25\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"ARQUIVADO DEFINITIVAMENTE\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853881,\n      \"data\": \"2018-06-05\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"SUSPENSAO DO PRAZO\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853883,\n      \"data\": \"2018-06-02\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"SUSPENSAO DO PRAZO\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853885,\n      \"data\": \"2018-05-24\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"CERTIDAO DE PUBLICACAO EXPEDIDA\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853887,\n      \"data\": \"2018-05-23\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"REMETIDO AO DJE\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853889,\n      \"data\": \"2018-05-10\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"PROFERIDO DESPACHO\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853896,\n      \"data\": \"2018-05-04\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"DECISAO DE 2ª INSTANCIA - RECURSO NAO PROVIDO - JUNTADA\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853895,\n      \"data\": \"2018-05-04\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"EMBARGOS DE DECLARACAO ACOLHIDOS\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853893,\n      \"data\": \"2018-05-04\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"TRANSITO EM JULGADO AS PARTES - PROC. EM ANDAMENTO\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853891,\n      \"data\": \"2018-05-04\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"CONCLUSOS PARA DESPACHO\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 853897,\n      \"data\": \"2018-04-26\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"RECEBIDOS OS AUTOS DO TRIBUNAL DE JUSTICA\",\n      \"fonte\": {\n        \"fonte_id\": 3,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 1,\n        \"grau_formatado\": \"Primeiro Grau\"\n      }\n    },\n    {\n      \"id\": 849990,\n      \"data\": \"2018-04-26\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"EXPEDIDO CERTIDAO\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    },\n    {\n      \"id\": 849988,\n      \"data\": \"2018-04-26\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"BAIXA DEFINITIVA\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    },\n    {\n      \"id\": 849986,\n      \"data\": \"2018-04-26\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"EXPEDIDO CERTIDAO DE BAIXA DE RECURSO\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    },\n    {\n      \"id\": 849992,\n      \"data\": \"2018-03-28\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"EXPEDIDO CERTIDAO\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    },\n    {\n      \"id\": 849994,\n      \"data\": \"2018-03-26\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"JULGADO VIRTUALMENTE\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    },\n    {\n      \"id\": 849998,\n      \"data\": \"2018-03-06\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"EXPEDIDO CERTIDAO\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    },\n    {\n      \"id\": 849996,\n      \"data\": \"2018-03-06\",\n      \"tipo\": \"ANDAMENTO\",\n      \"conteudo\": \"CONCLUSOS PARA O RELATOR (EXPEDIDO TERMO COM CONCLUSAO)\",\n      \"fonte\": {\n        \"fonte_id\": 6,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"tipo\": \"TRIBUNAL\",\n        \"sigla\": \"TJSP\",\n        \"grau\": 2,\n        \"grau_formatado\": \"Segundo Grau\"\n      }\n    }\n  ],\n  \"links\": {\n    \"next\": \"https://api.escavador.com/api/v2/processos/numero_cnj/1024000-20.2015.8.22.0000/movimentacoes?cursor=eyJkYXRhIjoiMjAxOC0wMy0wNiAwMDowMDowMCIsIm1vdmltZW50YWNhb19pZCI6ODQ5OTk2LCJfcG9pbnRzVG9OZXh0SXRlbXMiOnRydWV9&li=216029777\"\n  },\n  \"paginator\": {\n    \"per_page\": 20\n  }\n}");
                        } else if (currentType.includes('ENVOLVIDOS')) {
                            exampleData = JSON.parse("{\n  \"items\": [\n    {\n      \"nome\": \"Município de Gravataí / RS\",\n      \"quantidade_processos\": 4177,\n      \"tipo_pessoa\": \"JURIDICA\",\n      \"cpf\": null,\n      \"cnpj\": \"87.890.992/0001-58\",\n      \"participacoes_processo\": [\n        {\n          \"tipo\": \"REQUERIDO\",\n          \"tipo_normalizado\": \"Requerido\",\n          \"polo\": \"PASSIVO\",\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"advogados\": [],\n          \"fonte\": {\n            \"processo_fonte_id\": 715246906,\n            \"id\": 2827,\n            \"tipo\": \"TRIBUNAL\",\n            \"nome\": \"Tribunal de Justiça do Rio Grande do Sul\",\n            \"sigla\": \"TJRS\",\n            \"grau\": 1,\n            \"grau_formatado\": \"Primeiro Grau\"\n          }\n        }\n      ]\n    },\n    {\n      \"nome\": \"João da Silva\",\n      \"quantidade_processos\": 567,\n      \"tipo_pessoa\": \"FISICA\",\n      \"cpf\": \"123.456.789-00\",\n      \"cnpj\": null,\n      \"participacoes_processo\": [\n        {\n          \"tipo\": \"Apelado\",\n          \"tipo_normalizado\": \"Apelado\",\n          \"polo\": \"PASSIVO\",\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"advogados\": [],\n          \"fonte\": {\n            \"processo_fonte_id\": 987654321,\n            \"id\": 5678,\n            \"tipo\": \"TRIBUNAL\",\n            \"nome\": \"Supremo Tribunal Federal\",\n            \"sigla\": \"STF\",\n            \"grau\": 3,\n            \"grau_formatado\": \"Superior\"\n          }\n        }\n      ]\n    },\n    {\n      \"nome\": \"Município de Porto Alegre / RS\",\n      \"quantidade_processos\": 2345,\n      \"tipo_pessoa\": \"JURIDICA\",\n      \"cpf\": null,\n      \"cnpj\": \"98.765.432/0001-12\",\n      \"participacoes_processo\": [\n        {\n          \"tipo\": \"REQUERIDO\",\n          \"tipo_normalizado\": \"Requerido\",\n          \"polo\": \"ATIVO\",\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"advogados\": [],\n          \"fonte\": {\n            \"processo_fonte_id\": 112233445,\n            \"id\": 9101,\n            \"tipo\": \"TRIBUNAL\",\n            \"nome\": \"Tribunal Regional Federal\",\n            \"sigla\": \"TRF\",\n            \"grau\": 1,\n            \"grau_formatado\": \"Primeiro Grau\"\n          }\n        }\n      ]\n    },\n    {\n      \"nome\": \"Maria Oliveira\",\n      \"quantidade_processos\": 890,\n      \"tipo_pessoa\": \"FISICA\",\n      \"cpf\": \"987.654.321-00\",\n      \"cnpj\": null,\n      \"participacoes_processo\": [\n        {\n          \"tipo\": \"AGRAVADO\",\n          \"tipo_normalizado\": \"Agravado\",\n          \"polo\": \"PASSIVO\",\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"advogados\": [\n            {\n              \"nome\": \"Danielle Almeida\",\n              \"quantidade_processos\": 1,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"Advogado\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": null,\n              \"cnpj\": null\n            }\n          ],\n          \"fonte\": {\n            \"processo_fonte_id\": 223344556,\n            \"id\": 3344,\n            \"tipo\": \"TRIBUNAL\",\n            \"nome\": \"Tribunal de Justiça do Rio de Janeiro\",\n            \"sigla\": \"TJRJ\",\n            \"grau\": 2,\n            \"grau_formatado\": \"Segundo Grau\"\n          }\n        }\n      ]\n    },\n    {\n      \"nome\": \"Associação ABC\",\n      \"quantidade_processos\": 456,\n      \"tipo_pessoa\": \"JURIDICA\",\n      \"cpf\": null,\n      \"cnpj\": \"11.222.333/0001-44\",\n      \"participacoes_processo\": [\n        {\n          \"tipo\": \"Apelado\",\n          \"tipo_normalizado\": \"Apelado\",\n          \"polo\": null,\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"advogados\": [\n            {\n              \"nome\": \"Ana Souza\",\n              \"quantidade_processos\": 1,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"Advogado\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": null,\n              \"cnpj\": null,\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123456\n                }\n              ]\n            }\n          ],\n          \"fonte\": {\n            \"processo_fonte_id\": 445566778,\n            \"id\": 5566,\n            \"tipo\": \"TRIBUNAL\",\n            \"nome\": \"Tribunal de Justiça de Minas Gerais\",\n            \"sigla\": \"TJMG\",\n            \"grau\": 1,\n            \"grau_formatado\": \"Primeiro Grau\"\n          }\n        }\n      ]\n    }\n  ],\n  \"links\": {\n    \"next\": \"https://api.escavador.com/api/v2/processos/numero_cnj/87.890.992/0001-58/envolvidos?cursor=eyJlbnZvbHZpZG9fcHJvY2Vzc28uaWQiOjE5OSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ&li=1262\"\n  },\n  \"paginator\": {\n    \"per_page\": 20\n  }\n}");
                        } else if (currentType === 'BUSCA_PROC_DIARIO_NUM') {
                            exampleData = [{"id":1,"numero_antigo":null,"numero_novo":"0000000-00.0000.0.00.0000","is_cnj":1,"enviado_trimon_em":"2022-01-11 20:33:54","created_at":"2018-10-31 06:29:00","updated_at":"2022-08-09 09:00:22","origem_tribunal_id":16,"filtrado_em":null,"enviado_nursery_em":null,"diario_sigla":"TRT-5","diario_nome":"TRT da 5\u00aa Regi\u00e3o (Bahia)","estado":"BA","data_movimentacoes":"30\/10\/2018 a 08\/08\/2022","quantidade_movimentacoes":20,"ultimas_movimentacoes_resumo":[{"id":1,"data":"2022-08-08T00:00:00.000000Z","link_api":"https:\/\/api.escavador.com\/api\/v1\/movimentacoes\/1","envolvidos_resumo":[{"id":1,"nome":"Fulano de Tal","objeto_type":"Pessoa","pivot_tipo":"ADVOGADO","pivot_outros":"NAO","pivot_extra_nome":null,"link":"https:\/\/www.escavador.com\/sobre\/1\/fulano-de-tal","link_api":"https:\/\/api.escavador.com\/api\/v1\/pessoas\/1","nome_sem_filtro":"Fulano de Tal","envolvido_tipo":"Advogado","envolvido_extra_nome":"","oab":"00000\/BA","advogado_de":null}],"quantidade_envolvidos":9,"conteudo_resumo":"Conteudo "}]}];
                        } else if (currentType === 'API_V2_PROCESSOPORNUMERAOCNJCAPA') {
                            exampleData = JSON.parse("{\n  \"numero_cnj\": \"1024000-20.2015.2.23.0000\",\n  \"titulo_polo_ativo\": \"Maria da Conceição de Oliveira\",\n  \"titulo_polo_passivo\": \"João da Silva\",\n  \"ano_inicio\": 2015,\n  \"data_inicio\": \"2015-11-21\",\n  \"estado_origem\": {\n    \"nome\": \"São Paulo\",\n    \"sigla\": \"SP\"\n  },\n  \"unidade_origem\": {\n    \"nome\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n    \"endereco\": \"Avenida Francisco Xavier de Arruda Camargo, 300\",\n    \"classificacao\": \"JE - Juizado Especial\",\n    \"cidade\": \"São Paulo\",\n    \"estado\": {\n      \"nome\": \"São Paulo\",\n      \"sigla\": \"SP\"\n    },\n    \"tribunal_sigla\": \"TJSP\"\n  },\n  \"data_ultima_movimentacao\": \"2018-07-25\",\n  \"quantidade_movimentacoes\": 103,\n  \"fontes_tribunais_estao_arquivadas\": false,\n  \"data_ultima_verificacao\": \"2023-02-09T14:30:11+00:00\",\n  \"tempo_desde_ultima_verificacao\": \"há 1 mês\",\n  \"processos_relacionados\": [\n    {\n      \"numero\": \"8027909-02.2019.8.05.0000\"\n    },\n    {\n      \"numero\": \"8028150-73.2019.8.05.0000\"\n    }\n  ],\n  \"fontes\": [\n    {\n      \"id\": 3,\n      \"processo_fonte_id\": 14626,\n      \"descricao\": \"TJSP - 1º grau\",\n      \"nome\": \"Tribunal de Justiça de São Paulo\",\n      \"sigla\": \"TJSP\",\n      \"tipo\": \"TRIBUNAL\",\n      \"data_inicio\": \"2015-11-27\",\n      \"data_ultima_movimentacao\": \"2018-07-25\",\n      \"segredo_justica\": null,\n      \"arquivado\": null,\n      \"status_predito\": \"INATIVO\",\n      \"grau\": 1,\n      \"grau_formatado\": \"Primeiro Grau\",\n      \"fisico\": false,\n      \"sistema\": \"ESAJ\",\n      \"capa\": {\n        \"classe\": \"PROCEDIMENTO COMUM CIVEL\",\n        \"assunto\": \"RESPONSABILIDADE CIVIL\",\n        \"assuntos_normalizados\": [\n          {\n            \"id\": 3642,\n            \"nome\": \"Responsabilidade Civil\",\n            \"nome_com_pai\": \"DIREITO CIVIL > Responsabilidade Civil\",\n            \"path_completo\": \"DIREITO CIVIL | Responsabilidade Civil\",\n            \"bloqueado\": false\n          }\n        ],\n        \"assunto_principal_normalizado\": {\n          \"id\": 3642,\n          \"nome\": \"Responsabilidade Civil\",\n          \"nome_com_pai\": \"DIREITO CIVIL > Responsabilidade Civil\",\n          \"path_completo\": \"DIREITO CIVIL | Responsabilidade Civil\",\n          \"bloqueado\": false\n        },\n        \"area\": \"CIVEL\",\n        \"orgao_julgador\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n        \"orgao_julgador_normalizado\": {\n          \"nome\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n          \"endereco\": \"Avenida Francisco Xavier de Arruda Camargo, 300\",\n          \"classificacao\": \"JE - Juizado Especial\",\n          \"cidade\": \"São Paulo\",\n          \"estado\": {\n            \"nome\": \"São Paulo\",\n            \"sigla\": \"SP\"\n          },\n          \"tribunal_sigla\": \"TJSP\"\n        },\n        \"situacao\": \"Baixado\",\n        \"valor_causa\": {\n          \"valor\": \"50000.0000\",\n          \"moeda\": \"R$\",\n          \"valor_formatado\": \"R$ 50.000,00\"\n        },\n        \"data_distribuicao\": \"2015-11-27\",\n        \"data_arquivamento\": null,\n        \"informacoes_complementares\": null\n      },\n      \"audiencias\": [\n        {\n          \"tipo\": \"Instrução\",\n          \"data\": \"2024-10-17\",\n          \"quantidade_pessoas\": 2,\n          \"situacao\": \"Cancelada\"\n        }\n      ],\n      \"url\": \"https://esaj.tjsp.jus.br/cpopg/search.do?conversationId=&dadosConsulta.localPesquisa.cdLocal=-1&cbPesquisa=NUMPROC&dadosConsulta.tipoNuProcesso=UNIFICADO&numeroDigitoAnoUnificado=1024000-20.2015&foroNumeroUnificado=0000&dadosConsulta.valorConsultaNuUnificado=1024000-20.2015.2.23.0000&dadosConsulta.valorConsulta=\",\n      \"tribunal\": {\n        \"id\": 102,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"sigla\": \"TJSP\",\n        \"categoria\": null\n      },\n      \"quantidade_movimentacoes\": 68,\n      \"quantidade_envolvidos\": 7,\n      \"data_ultima_verificacao\": \"2023-02-09T14:30:11+00:00\",\n      \"envolvidos\": [\n        {\n          \"nome\": \"Maria da Conceição de Oliveira\",\n          \"quantidade_processos\": 1,\n          \"tipo_pessoa\": \"FISICA\",\n          \"advogados\": [\n            {\n              \"nome\": \"Marta Brandao de Oliveira\",\n              \"quantidade_processos\": 21,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Marta Brandao de Oliveira\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            },\n            {\n              \"nome\": \"Fernando Marçal\",\n              \"quantidade_processos\": 10,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Fernando Marçal\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            }\n          ],\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"tipo\": \"REQUERENTE\",\n          \"tipo_normalizado\": \"Requerente\",\n          \"polo\": \"ATIVO\",\n          \"cpf\": \"00000000000\",\n          \"nome_normalizado\": \"Maria da Conceição de Oliveira\"\n        },\n        {\n          \"nome\": \"Joao da Silva\",\n          \"quantidade_processos\": 97,\n          \"tipo_pessoa\": \"FISICA\",\n          \"advogados\": [\n            {\n              \"nome\": \"Antonio Carlos de Souza\",\n              \"quantidade_processos\": 37,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Antonio Carlos de Souza\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            },\n            {\n              \"nome\": \"Fabiane Santos Carvalho\",\n              \"quantidade_processos\": 33,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Fabiane Santos Carvalho\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            }\n          ],\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"tipo\": \"REQUERIDO\",\n          \"tipo_normalizado\": \"Requerido\",\n          \"polo\": \"PASSIVO\",\n          \"cpf\": \"00000000000\",\n          \"nome_normalizado\": \"Joao da Silva\"\n        },\n        {\n          \"nome\": \"Marcos Tira Teima\",\n          \"quantidade_processos\": 126,\n          \"tipo_pessoa\": \"FISICA\",\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"tipo\": \"JUIZ\",\n          \"tipo_normalizado\": \"Juiz\",\n          \"polo\": \"NENHUM\"\n        }\n      ]\n    },\n    {\n      \"id\": 6,\n      \"processo_fonte_id\": 14566,\n      \"descricao\": \"TJSP - 2º grau\",\n      \"nome\": \"Tribunal de Justiça de São Paulo\",\n      \"sigla\": \"TJSP\",\n      \"tipo\": \"TRIBUNAL\",\n      \"data_inicio\": \"2017-06-01\",\n      \"data_ultima_movimentacao\": \"2018-04-26\",\n      \"segredo_justica\": null,\n      \"arquivado\": null,\n      \"status_predito\": \"INATIVO\",\n      \"grau\": 2,\n      \"grau_formatado\": \"Segundo Grau\",\n      \"fisico\": false,\n      \"sistema\": \"ESAJ\",\n      \"capa\": {\n        \"classe\": \"APELACAO CIVEL\",\n        \"assunto\": \"DIREITO CIVIL-RESPONSABILIDADE CIVIL-INDENIZACAO POR DANO MORAL\",\n        \"assuntos_normalizados\": [\n          {\n            \"id\": 3644,\n            \"nome\": \"Indenização por Dano Moral\",\n            \"nome_com_pai\": \"Responsabilidade Civil > Indenização por Dano Moral\",\n            \"path_completo\": \"DIREITO CIVIL | Responsabilidade Civil | Indenização por Dano Moral\",\n            \"bloqueado\": false\n          }\n        ],\n        \"assunto_principal_normalizado\": {\n          \"id\": 3644,\n          \"nome\": \"Indenização por Dano Moral\",\n          \"nome_com_pai\": \"Responsabilidade Civil > Indenização por Dano Moral\",\n          \"path_completo\": \"DIREITO CIVIL | Responsabilidade Civil | Indenização por Dano Moral\",\n          \"bloqueado\": false\n        },\n        \"area\": \"CIVEL\",\n        \"orgao_julgador\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n        \"orgao_julgador_normalizado\": {\n          \"nome\": \"01 VARA JUIZADO ESPECIAL CIVEL DE CAMPINAS\",\n          \"endereco\": \"Avenida Francisco Xavier de Arruda Camargo, 300\",\n          \"classificacao\": \"JE - Juizado Especial\",\n          \"cidade\": \"São Paulo\",\n          \"estado\": {\n            \"nome\": \"São Paulo\",\n            \"sigla\": \"SP\"\n          },\n          \"tribunal_sigla\": \"TJSP\"\n        },\n        \"situacao\": \"Baixado\",\n        \"valor_causa\": {\n          \"valor\": \"50000.0000\",\n          \"moeda\": \"R$\",\n          \"valor_formatado\": \"R$ 50.000,00\"\n        },\n        \"data_distribuicao\": \"2017-06-01\",\n        \"data_arquivamento\": null,\n        \"informacoes_complementares\": null\n      },\n      \"audiencias\": [],\n      \"url\": \"https://esaj.tjsp.jus.br/cposg/search.do?conversationId=&paginaConsulta=0&cbPesquisa=NUMPROC&numeroDigitoAnoUnificado=1024000-20.2015&foroNumeroUnificado=0000&dePesquisaNuUnificado=1024000-20.2015.8.22.0000&dePesquisaNuUnificado=UNIFICADO&dePesquisa=&tipoNuProcesso=UNIFICADO&uuidCaptcha=sajcaptcha_e6c6a295c5404a6887d81483bdd96048&g-recaptcha-response=\",\n      \"tribunal\": {\n        \"id\": 102,\n        \"nome\": \"Tribunal de Justiça de São Paulo\",\n        \"sigla\": \"TJSP\",\n        \"categoria\": null\n      },\n      \"quantidade_movimentacoes\": 35,\n      \"quantidade_envolvidos\": 7,\n      \"data_ultima_verificacao\": \"2023-02-09T14:30:00+00:00\",\n      \"envolvidos\": [\n        {\n          \"nome\": \"Maria da Conceição de Oliveira\",\n          \"quantidade_processos\": 1,\n          \"tipo_pessoa\": \"FISICA\",\n          \"advogados\": [\n            {\n              \"nome\": \"Fabiane Santos Carvalho\",\n              \"quantidade_processos\": 21,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Fabiane Santos Carvalho\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            },\n            {\n              \"nome\": \"Antonio Carlos de Souza\",\n              \"quantidade_processos\": 10,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Antonio Carlos de Souza\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            }\n          ],\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"tipo\": \"APELANTE\",\n          \"tipo_normalizado\": \"Apelante\",\n          \"polo\": \"ATIVO\",\n          \"cpf\": \"00000000000\",\n          \"nome_normalizado\": \"Maria da Conceicao de Oliveira\"\n        },\n        {\n          \"nome\": \"Joao da Silva\",\n          \"quantidade_processos\": 97,\n          \"tipo_pessoa\": \"FISICA\",\n          \"advogados\": [\n            {\n              \"nome\": \"\",\n              \"quantidade_processos\": 37,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Marta Brandao de Oliveira\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            },\n            {\n              \"nome\": \"Fernando Marçal\",\n              \"quantidade_processos\": 33,\n              \"tipo_pessoa\": \"FISICA\",\n              \"prefixo\": null,\n              \"sufixo\": null,\n              \"tipo\": \"ADVOGADO\",\n              \"tipo_normalizado\": \"Advogado\",\n              \"polo\": \"ADVOGADO\",\n              \"cpf\": \"00000000000\",\n              \"nome_normalizado\": \"Fernando Marçal\",\n              \"oabs\": [\n                {\n                  \"uf\": \"SP\",\n                  \"tipo\": \"ADVOGADO\",\n                  \"numero\": 123123\n                }\n              ]\n            }\n          ],\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"tipo\": \"APELADO\",\n          \"tipo_normalizado\": \"Apelado\",\n          \"polo\": \"PASSIVO\",\n          \"cpf\": \"00000000000\",\n          \"nome_normalizado\": \"Joao da Silva\"\n        },\n        {\n          \"nome\": \"Ronaldo de Assis\",\n          \"quantidade_processos\": 86,\n          \"tipo_pessoa\": \"FISICA\",\n          \"prefixo\": null,\n          \"sufixo\": null,\n          \"tipo\": \"RELATOR\",\n          \"tipo_normalizado\": \"Juiz\",\n          \"polo\": \"NENHUM\",\n          \"cpf\": \"00000000000\",\n          \"nome_normalizado\": \"Ronaldo de Assis\"\n        }\n      ]\n    }\n  ]\n}");
                        } else if (currentType === 'API_V2_AUTOS_PROCESSO' || currentType === 'API_V2_DOCS_PUBLICOS') {
                            exampleData = {
                                "items": [
                                    {
                                        "id": 11404,
                                        "titulo": "Despacho",
                                        "descricao": "Despacho | Despacho",
                                        "data": "2024-06-17 18:02:36",
                                        "tipo": "RESTRITO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": 2,
                                        "key": "M3VLQSs0...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/M3VLQSs0..."
                                        }
                                    },
                                    {
                                        "id": 11333,
                                        "titulo": "Despacho",
                                        "descricao": "Despacho | Despacho",
                                        "data": "2024-06-05 16:06:51",
                                        "tipo": "RESTRITO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": null,
                                        "key": "N2dGS0...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/N2dGS0..."
                                        }
                                    },
                                    {
                                        "id": 10041,
                                        "titulo": "Acórdão",
                                        "descricao": "Acórdão | Acórdão",
                                        "data": "2024-02-22 22:38:37",
                                        "tipo": "RESTRITO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": 2,
                                        "key": "P0ZLVk...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/P0ZLVk..."
                                        }
                                    },
                                    {
                                        "id": 18561,
                                        "titulo": "Despacho",
                                        "descricao": "Despacho | Despacho",
                                        "data": "2024-02-07 14:54:00",
                                        "tipo": "PUBLICO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": 2,
                                        "key": "MzZWVk...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/MzZWVk..."
                                        }
                                    },
                                    {
                                        "id": 92398,
                                        "titulo": "Decisão",
                                        "descricao": "Decisão | Decisão",
                                        "data": "2023-10-30 23:57:39",
                                        "tipo": "RESTRITO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": 1,
                                        "key": "T2hWVk...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/T2hWVk..."
                                        }
                                    },
                                    {
                                        "id": 98409,
                                        "titulo": "Sentença",
                                        "descricao": "Sentença | Sentença",
                                        "data": "2023-09-26 23:01:43",
                                        "tipo": "RESTRITO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": 1,
                                        "key": "O0ZLVl...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/O0ZLVl..."
                                        }
                                    },
                                    {
                                        "id": 94210,
                                        "titulo": "Ata da Audiência",
                                        "descricao": "Ata da Audiência | Ata da Audiência",
                                        "data": "2023-09-26 11:21:40",
                                        "tipo": "RESTRITO",
                                        "extensao_arquivo": "pdf",
                                        "quantidade_paginas": 1,
                                        "key": "P1ZaVk...",
                                        "links": {
                                            "api": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos/P1ZaVk..."
                                        }
                                    }
                                ],
                                "links": {
                                    "next": null,
                                    "prev": null,
                                    "first": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos?page=1",
                                    "last": "/api/v2/processos/numero_cnj/0000000-00.0000.0.00.0000/documentos?page=1"
                                },
                                "paginator": {
                                    "current_page": 1,
                                    "per_page": 20,
                                    "total": 7,
                                    "total_pages": 1
                                }
                            };
                        } else {
                            exampleData = { "sucesso": true, "mensagem": "Exemplo genérico" };
                        }

                        // ── Render ──────────────────────────────────────────────
                        var btn = document.getElementById('lf-svc-btn-submit');
                        document.getElementById('lf-svc-fields').style.display = 'none';
                        btn.style.display = 'none';
                        document.getElementById('lf-svc-success').style.display = 'none';
                        document.getElementById('lf-svc-error').style.display = 'none';

                        var htmlContent = '';
                        if (currentType.startsWith('DATAJUD_')) {
                            htmlContent = buildDataJudUi(exampleData) + extraHtml;
                        } else if (currentType === 'BUSCA_PROC_DIARIO_NUM') {
                            htmlContent = buildEscavadorBuscaDiarioNumUi(exampleData);
                        } else if (currentType === 'API_V2_PROCESSOPORNUMERAOCNJCAPA') {
                            htmlContent = buildEscavadorProcessoCNJUi(exampleData);
                        } else if (currentType === 'API_V2_AUTOS_PROCESSO' || currentType === 'API_V2_DOCS_PUBLICOS') {
                            htmlContent = buildEscavadorAutosUi(exampleData);
                        } else if (currentType.includes('PROCESSOS')) {
                            htmlContent = buildEscavadorProcessosUi(exampleData);
                        } else if (currentType.includes('RESUMO')) {
                            htmlContent = buildEscavadorResumoUi(exampleData);
                        } else if (currentType.includes('MOVIMENTAES')) {
                            htmlContent = buildEscavadorMovimentacoesUi(exampleData);
                        } else if (currentType.includes('ENVOLVIDOS')) {
                            htmlContent = buildEscavadorEnvolvidosUi(exampleData);
                        } else if (currentType === 'BUSCA_JURIS' || currentType === 'BUSCA_LEGIS') {
                            htmlContent = buildEscavadorBuscaUi(exampleData);
                        } else {
                            htmlContent = buildEscavadorAutosUi(exampleData);
                        }
                        var resultHtml =
                            '<div id="lf-svc-result-area" style="margin-top:10px;">' +
                                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">' +
                                    '<h4 style="font-weight:600;color:#374151;font-size:1.05rem;" class="dark:text-white">💡 Exemplo de Resposta</h4>' +
                                    '<div style="display:flex;gap:8px;">' +
                                        '<button type="button" onclick="document.getElementById(\'lf-svc-result-area\').remove(); document.getElementById(\'lf-svc-fields\').style.display=\'flex\'; document.getElementById(\'lf-svc-btn-submit\').style.display=\'inline-flex\'; var ex = document.getElementById(\'lf-svc-btn-example-container\'); if(ex) ex.style.display=\'block\';" style="font-size:0.8rem;background:#f3f4f6;border:1px solid #d1d5db;padding:6px 12px;border-radius:6px;cursor:pointer;color:#374151;font-weight:600;transition:all .2s;" class="dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">🔙 Voltar para Consulta</button>' +
                                        '<button type="button" onclick="window.lfForcePrint()" style="font-size:0.8rem;background:#f3f4f6;border:1px solid #e5e7eb;padding:6px 12px;border-radius:6px;cursor:pointer;color:#374151;font-weight:600;transition:all .2s;" class="dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">📄 Salvar em PDF</button>' +
                                    '</div>' +
                                '</div>' +
                                htmlContent +
                            '</div>';

                        var parent = document.getElementById('lf-svc-fields').parentElement;
                        var prev = document.getElementById('lf-svc-result-area');
                        if (prev) prev.remove();
                        parent.insertAdjacentHTML('beforeend', resultHtml);
                    }

                    function loadBalance() {
                        fetch(ROUTE_SALDO, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(function (r) { return r.json(); })
                            .then(function (d) {
                                // suitecoin_balance está em BRL — converter para Ƶ (× suitecoin_rate = 10)
                                var balanceBrl = parseFloat(d.suitecoin_balance || d.ai_tokens_balance || 0);
                                var suitecoinsRate = parseFloat(d.suitecoin_rate || 10);
                                currentBalance = balanceBrl * suitecoinsRate;
                                document.getElementById('lf-svc-saldo-display').textContent = 'Ƶ ' + currentBalance.toFixed(2).replace('.', ',');
                            })
                            .catch(function () {
                                document.getElementById('lf-svc-saldo-display').textContent = 'Ƶ indisponível';
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

                        // Dynamically set price from backend (fixes hardcoded 3,00 issue)
                        if (PRICES_DB[type] !== undefined) {
                            var p = parseFloat(PRICES_DB[type]);
                            // Converter BRL → Ƶ para exibição (× 10, com markup 1.25 já incluso no valor BRL)
                            var pZ = p * 10 * 1.25;
                            info.price = (p <= 0) ? 'Grátis' : 'Ƶ ' + pZ.toFixed(2).replace('.', ',');
                        }

                        // DataJud must always be free
                        if (type.startsWith('DATAJUD_')) {
                            info.price = 'Grátis';
                        }

                        document.getElementById('lf-svc-modal-title').textContent = info.label;
                        document.getElementById('lf-svc-price-badge').textContent = '💰 ' + info.price;
                        document.getElementById('lf-svc-balance').textContent = 'Saldo: Ƶ ' + currentBalance.toFixed(2).replace('.', ',');

                        document.getElementById('lf-svc-error').style.display = 'none';
                        document.getElementById('lf-svc-success').style.display = 'none';

                        var authWarning = document.getElementById('lf-svc-auth-warning');
                        if (authWarning) {
                            authWarning.style.display = info.req_auth ? 'block' : 'none';
                        }

                        // Show Example button
                        var exContainer = document.getElementById('lf-svc-btn-example-container');
                        if (exContainer) {
                            exContainer.style.display = 'block';
                        }

                        document.getElementById('lf-svc-fields').style.display = 'flex';
                        document.getElementById('lf-svc-btn-submit').style.display = 'inline-flex';

                        var prevResult = document.getElementById('lf-svc-result-area');
                        if (prevResult) prevResult.style.display = 'none';

                        var container = document.getElementById('lf-svc-fields');
                        container.innerHTML = '';

                        info.fields.forEach(function (f) {
                            var wrap = document.createElement('div');
                            wrap.className = 'lf-esc-field';
                            if (f.advanced) {
                                wrap.style.display = 'none';
                                wrap.classList.add('lf-advanced-field');
                            }

                            var label = document.createElement('label');
                            label.className = 'lf-esc-label';
                            label.textContent = f.label + (f.required ? ' *' : '');
                            wrap.appendChild(label);

                            if (f.type === 'datajud_tribunal') {
                                var sel = document.createElement('select');
                                sel.className = 'lf-esc-select';
                                sel.id = 'lf-svc-field-' + f.name;
                                sel.name = f.name;
                                sel.innerHTML = '<option value="">-- Selecione o Tribunal --</option>';
                                DATAJUD_TRIBUNAIS.forEach(function (t) { var o = document.createElement('option'); o.value = t.v; o.textContent = t.l; sel.appendChild(o); });
                                wrap.appendChild(sel);
                            } else if (f.type === 'hidden') {
                                var hid = document.createElement('input');
                                hid.type = 'hidden';
                                hid.id = 'lf-svc-field-' + f.name;
                                hid.name = f.name;
                                hid.value = f.valor || '';
                                container.appendChild(hid); // no label needed
                                return; // skip the wrap.appendChild below
                            } else if (f.type === 'select') {
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
                                if (f.name === 'utilizar_operadores_logicos') {
                                    select.addEventListener('change', function () {
                                        var advFields = container.querySelectorAll('.lf-advanced-field');
                                        var isAdv = this.value === '1';
                                        advFields.forEach(function (el) {
                                            el.style.display = isAdv ? 'block' : 'none';
                                        });
                                    });
                                }

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
                                        certs.forEach(function (c) {
                                            var opt = document.createElement('option');
                                            opt.value = c.id;
                                            opt.textContent = c.emissor + ' (' + (c.cpf_cnpj || 'Sem doc') + ')';
                                            select.appendChild(opt);
                                        });
                                    }
                                })
                                .catch(function () {
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

                        if (info.price !== 'Grátis' && info.price !== 'Ƶ 0,00') {
                            if (!confirm("Esta operação consumirá " + info.price + " do seu saldo SuiteCoins.\nSaldo atual: Ƶ " + currentBalance.toFixed(2).replace('.', ',') + ".\n\nDeseja continuar?")) {
                                return;
                            }
                        }

                        var btn = document.getElementById('lf-svc-btn-submit');
                        btn.disabled = true;
                        btn.textContent = '⏳ Executando...';
                        document.getElementById('lf-svc-error').style.display = 'none';
                        document.getElementById('lf-svc-success').style.display = 'none';

                        var targetRoute = ROUTE_SERVICO;
                        if (currentType.startsWith('DATAJUD_')) {
                            targetRoute = ROUTE_DATAJUD_SERVICO;
                        }

                        fetch(targetRoute, {
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

                                    var extraBadges = '';
                                    if (d.cached) {
                                        extraBadges += ' <span style="margin-left:8px;display:inline-block;padding:2px 6px;background:#f59e0b;color:white;border-radius:4px;font-size:0.75rem;">📦 Cache 24h</span>';
                                    }
                                    if (d.request && d.request.updated_at) {
                                        var reqDate = new Date(d.request.updated_at);
                                        var today = new Date();
                                        var diffDays = Math.ceil(Math.abs(today - reqDate) / (1000 * 60 * 60 * 24));
                                        if (diffDays > 30) {
                                            extraBadges += ' <span style="margin-left:8px;display:inline-block;padding:2px 6px;background:#ef4444;color:white;border-radius:4px;font-size:0.75rem;">🔴 Atualização recomendada (>30 dias)</span>';
                                        } else if (diffDays > 7) {
                                            extraBadges += ' <span style="margin-left:8px;display:inline-block;padding:2px 6px;background:#f59e0b;color:white;border-radius:4px;font-size:0.75rem;">⚠️ Dados desatualizados (' + diffDays + ' dias)</span>';
                                        }
                                    }

                                    document.getElementById('lf-svc-success').innerHTML = msg + extraBadges;
                                    document.getElementById('lf-svc-success').style.display = 'block';

                                    if (!d.async) {
                                        document.getElementById('lf-svc-fields').style.display = 'none';
                                        btn.style.display = 'none';
                                        
                                        var exContainer = document.getElementById('lf-svc-btn-example-container');
                                        if (exContainer) exContainer.style.display = 'none';

                                        var resultData = d.data || d;
                                        var htmlContent = '';
                                        
                                        // DATAJUD PROCESS PARSER
                                        if (currentType.startsWith('DATAJUD_') && resultData && resultData.hits) {
                                            htmlContent = buildDataJudUi(resultData);
                                        } else {
                                            var mardownStr = jsonToMarkdown(resultData);
                                            var rawHtml = marked.parse(mardownStr);
                                            var safeHtml = (typeof DOMPurify !== 'undefined') ? DOMPurify.sanitize(rawHtml) : rawHtml;
                                            htmlContent = '<div class="lf-esc-data-box markdown-body" style="display:block;max-height:400px;overflow:auto;" id="lf-svc-result-content">' + safeHtml + '</div>';
                                        }

                                        var resultHtml = '<div id="lf-svc-result-area" style="margin-top:10px;">' +
                                            '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">' +
                                                '<h4 style="font-weight:600;color:#374151;font-size:1.05rem;" class="dark:text-white">Resultado da Consulta</h4>' +
                                                '<div style="display:flex;gap:8px;">' +
                                                    '<button type="button" onclick="document.getElementById(\'lf-svc-result-area\').remove(); document.getElementById(\'lf-svc-fields\').style.display=\'flex\'; document.getElementById(\'lf-svc-btn-submit\').style.display=\'inline-flex\'; var ex = document.getElementById(\'lf-svc-btn-example-container\'); if(ex) ex.style.display=\'block\';" style="font-size:0.8rem;background:#f3f4f6;border:1px solid #d1d5db;padding:6px 12px;border-radius:6px;cursor:pointer;color:#374151;font-weight:600;transition:all .2s;" class="dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">🔙 Voltar para Filtros</button>' +
                                                    '<button type="button" onclick="window.lfForcePrint()" style="font-size:0.8rem;background:#f3f4f6;border:1px solid #e5e7eb;padding:6px 12px;border-radius:6px;cursor:pointer;color:#374151;font-weight:600;transition:all .2s;" class="dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700">📄 Salvar em PDF</button>' +
                                                '</div>' +
                                            '</div>' + 
                                            htmlContent + 
                                        '</div>';

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

                        var cards = document.querySelectorAll('.lf-esc-grid')[0]?.querySelectorAll('.lf-esc-card') || [];
                        cards.forEach(function (card) {
                            var matches = filterKey === 'todas' || (card.dataset.module || '') === filterKey;
                            card.style.display = matches ? '' : 'none';
                        });
                    };

                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && document.getElementById('lf-svc-modal').style.display !== 'none') closeSvc();
                    });

                    window.lfCopyText = function(text, btn) {
                        function success() {
                            var oldText = btn.textContent;
                            btn.textContent = '✅ Copiado!';
                            setTimeout(function() { btn.textContent = '📋 Copiar'; }, 1500);
                        }
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).then(success).catch(function(e) { console.error('Clipboard error', e); });
                        } else {
                            var textArea = document.createElement("textarea");
                            textArea.value = text;
                            textArea.style.position = "fixed";
                            textArea.style.left = "-999999px";
                            textArea.style.top = "-999999px";
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            try {
                                document.execCommand('copy');
                                success();
                            } catch (err) {
                                console.error('Fallback clipboard error', err);
                            }
                            document.body.removeChild(textArea);
                        }
                    };

                    window.lfSvc = { open: openSvc, close: closeSvc, execute: executeSvc, showExample: window.lfShowEscavadorExample };

                })();
            </script>
        @endpush

        {{-- ── PAID SERVICE MODAL ────────────────────────────────────── --}}
        <div id="lf-svc-modal" style="display:none;" v-pre>
            <div class="lf-esc-overlay" onclick="window.lfSvc.close()"></div>
            <div class="lf-esc-dialog" style="max-width:850px;width:95%;">
                <div class="lf-esc-modal-header">
                    <h3 id="lf-svc-modal-title" class="lf-esc-modal-title">🚀 Executar Serviço</h3>
                    <button onclick="window.lfSvc.close()" class="lf-esc-close-btn">✕</button>
                </div>
                <div id="lf-svc-modal-body" style="padding:20px;display:flex;flex-direction:column;gap:16px;overflow-y:auto;">

                    <div class="lf-no-print print:hidden"
                        style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:linear-gradient(135deg,rgba(13,148,136,.06),rgba(2,132,199,.06));border:1px solid #99f6e4;border-radius:10px;">
                        <div>
                            <div
                                style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                                Custo do Serviço</div>
                            <div id="lf-svc-price-badge" style="font-size:1.3rem;font-weight:800;color:#0d9488;">Ƶ 0,00
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div
                                style="font-size:.72rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
                                Saldo Disponível</div>
                            <div id="lf-svc-balance" style="font-size:.95rem;font-weight:700;color:#374151;"
                                class="dark:text-gray-200">Ƶ 0,00</div>
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
                    style="padding:16px 20px;display:flex;gap:12px;justify-content:flex-end;align-items:center;">
                    
                    <div id="lf-svc-btn-example-container" style="display:none;margin-right:auto;">
                         <button onclick="window.lfSvc.showExample()" type="button" 
                             style="display:inline-flex;align-items:center;gap:6px;font-size:0.8rem;color:#0d9488;background:#f0fdfa;border:1px solid #99f6e4;padding:8px 14px;border-radius:6px;font-weight:600;cursor:pointer;transition:all 0.2s;"
                             onmouseover="this.style.background='#ccfbf1'" onmouseout="this.style.background='#f0fdfa'">
                             <span style="font-size:1rem;">💡</span> Exemplo
                         </button>
                    </div>

                    <button onclick="window.lfSvc.close()" class="lf-esc-btn-secondary" type="button"
                        style="padding:10px 16px;">Cancelar</button>
                    <button id="lf-svc-btn-submit" onclick="window.lfSvc.execute()" class="lf-esc-btn" type="button"
                        style="padding:10px 30px;">🚀 Executar</button>
                </div>
            </div>{{-- /lf-esc-dialog --}}
        </div>{{-- /lf-svc-modal --}}

</x-admin::layouts>