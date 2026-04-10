{{-- LEAD TOOLS PANEL - AI-Powered Actions --}}
{{-- Injected via: admin.leads.view.stages.after --}}
{{-- Uses vanilla JS to avoid Vue/Alpine conflicts in Krayin CRM --}}

@php
    $tenantId = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getTenantId();
    $leadId = $lead->id;

    $tenantConfig = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getTenantConfig();
    $isTrial = $tenantConfig && stripos($tenantConfig->classification ?? '', 'trial') !== false;

    // Load templates from DB for dynamic labels
    $toolTemplates = \SuiteZap\LawFirm\AI\Models\AssistantTemplate::whereIn('slug', [
        'pre-triagem-checklist',
        'pre-triagem-lead',
        'gerador-proposta',
        'script-vendas'
    ])->get()->keyBy('slug');

    // Button config: order, slug, icon, color, stageId
    $tools = [
        ['slug' => 'pre-triagem-lead', 'icon' => '🧠', 'color' => 'purple', 'stageId' => null],
        ['slug' => 'pre-triagem-checklist', 'icon' => '📋', 'color' => 'amber', 'stageId' => 2],
        ['slug' => 'gerador-proposta', 'icon' => '📄', 'color' => 'blue', 'stageId' => 3],
        ['slug' => 'script-vendas', 'icon' => '💬', 'color' => 'green', 'stageId' => 4],
    ];
@endphp

@if($lead->stage->code == 'won')
    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🎉</span>
            <div>
                <h3 class="font-bold text-green-800 dark:text-green-300">Lead Ganho!</h3>
                <p class="text-sm text-green-700 dark:text-green-400">
                    O Processo Judicial foi criado automaticamente e está disponível na aba "Processos".
                </p>
            </div>
        </div>
    </div>
@elseif($lead->stage->code == 'lost')
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center gap-3">
            <span class="text-2xl">❌</span>
            <div>
                <h3 class="font-bold text-red-800 dark:text-red-300">Lead Perdido</h3>
                <p class="text-sm text-red-700 dark:text-red-400">
                    Este lead foi marcado como perdido. O painel de qualificação está oculto.
                </p>
            </div>
        </div>
    </div>
@else
    <div id="lf-tools-panel" class="mt-4 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">

        {{-- Header — same pattern as assistants page --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <span class="icon-settings-flow text-base text-violet-600 dark:text-violet-400"></span>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Qualificação Jurídica &amp; Negociação</h3>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ferramentas de apoio à qualificação</p>
        </div>

        {{-- Tools Grid — 2 cols, compact card style matching /admin/juridico/assistants --}}
        <div class="grid grid-cols-2 gap-3 p-4 max-sm:grid-cols-1">
            @foreach($tools as $tool)
                @php
                    $tpl = $toolTemplates[$tool['slug']] ?? null;
                    $btnTitle = $tpl->title ?? $tool['slug'];
                    $btnDesc = $tpl->description ?? '';
                    $stageId = $tool['stageId'] ?? 'null';
                @endphp
                <div
                    class="flex flex-col justify-between rounded-lg border border-gray-200 bg-white p-3 transition-all hover:border-violet-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-violet-600">
                    <div>
                        {{-- Category badge --}}
                        <span
                            class="mb-2 inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-semibold text-violet-700 dark:bg-violet-900/20 dark:text-violet-300">
                            &#9679; IA
                        </span>

                        {{-- Icon + Title --}}
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                            {{ $tool['icon'] }} {{ $btnTitle }}
                        </h4>

                        {{-- Description --}}
                        @if($btnDesc)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($btnDesc, 60) }}</p>
                        @endif
                    </div>

                    {{-- Action button --}}
                    <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <button type="button" data-slug="{{ $tool['slug'] }}" data-title="{{ $btnTitle }}"
                            data-stage="{{ $stageId }}"
                            onclick="window.lfToolsPanel.open(this.dataset.slug, this.dataset.title, this.dataset.stage)"
                            class="lf-btn-primary">
                            ✨ Usar Assistente
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Footer Warning --}}
        <div class="border-t border-gray-200 px-4 py-2 dark:border-gray-800">
            <p class="flex items-center gap-1 text-[11px] text-orange-600 dark:text-orange-400">
                ⚠️ Marque este Lead como GANHO no Pipeline para iniciar o Processo Judicial.
            </p>
        </div>
    </div>
@endif

{{-- =============== MODAL (moved to body via JS) =============== --}}
<div id="lf-tools-modal" style="display:none;">
    {{-- Overlay --}}
    <div class="lf-modal-overlay" onclick="window.lfToolsPanel.close()"></div>

    {{-- Dialog --}}
    <div class="lf-modal-dialog">
        {{-- Header --}}
        <div class="lf-modal-header">
            <h3 id="lf-modal-title" class="lf-modal-title">🤖 Assistente IA</h3>
            <button onclick="window.lfToolsPanel.close()" class="lf-modal-close-btn">✕</button>
        </div>

        {{-- Body: Two Columns --}}
        <div class="lf-modal-body">
            {{-- LEFT: Form --}}
            <div class="lf-modal-form">
                <h4 class="lf-section-title">Dados do Lead</h4>

                <div class="lf-field">
                    <label class="lf-label">Título</label>
                    <input type="text" id="lf-form-title" class="lf-input lf-input-editable">
                </div>

                <div class="lf-field">
                    <label class="lf-label">Descrição</label>
                    <textarea id="lf-form-description" rows="5"
                        class="lf-input lf-textarea lf-input-editable"></textarea>
                </div>

                <div class="lf-field">
                    <label class="lf-label">Observações</label>
                    <textarea id="lf-form-observacoes" rows="5"
                        placeholder="Adicione observações ou informações adicionais sobre o caso..."
                        class="lf-input lf-textarea lf-input-editable"></textarea>
                </div>

                {{-- tenant_id hidden but sent in payload --}}
                <input type="hidden" id="lf-form-tenant">
            </div>

            {{-- RIGHT: Result --}}
            <div class="lf-modal-result">
                <div class="lf-result-header">
                    <h4 class="lf-section-title">Resultado</h4>
                    <div class="lf-result-actions" id="lf-result-actions" style="display:none;">
                        <button id="lf-save-note-btn" onclick="window.lfToolsPanel.saveAsNote(this)"
                            class="lf-save-note-btn">
                            <span class="icon-note"></span> Salvar como Nota
                        </button>
                        <button id="lf-pdf-btn" onclick="window.lfToolsPanel.pdf()" class="lf-copy-btn">
                            📄 Salvar PDF
                        </button>
                        <button id="lf-copy-btn" onclick="window.lfToolsPanel.copy()" class="lf-copy-btn">
                            📋 Copiar
                        </button>
                    </div>
                </div>

                <div class="lf-result-body">
                    {{-- Placeholder --}}
                    <div id="lf-result-placeholder" class="lf-result-placeholder">
                        <p>Preencha o formulário e clique em "Executar com IA".</p>
                    </div>

                    {{-- Loading --}}
                    <div id="lf-result-loading" style="display:none;" class="lf-result-loading">
                        <div class="lf-spinner"></div>
                        <span>🧠 Conectando ao Cérebro IA... aguarde...</span>
                    </div>

                    {{-- Result --}}
                    <div id="lf-result-content" style="display:none;" class="ai-result-box"></div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="lf-modal-footer">
            @if($isTrial)
                <button id="lf-btn-generate" type="button" onclick="alert('Na versão Trial essa opção não está disponível, para maiores detalhes contate o Suporte.')" class="secondary-button" style="border-color:#fbd38d; color:#dd6b20; background-color:#fffaf0;" title="Na versão Trial essa opção não está disponível">
                    <span id="lf-gen-text">🚫 Gerar Prompt (Trial)</span>
                    <span id="lf-gen-loading" style="display:none;">⏳ Gerando...</span>
                </button>
            @else
                <button id="lf-btn-generate" onclick="window.lfToolsPanel.generate()" class="secondary-button">
                    <span id="lf-gen-text">📝 Gerar Prompt</span>
                    <span id="lf-gen-loading" style="display:none;">⏳ Gerando...</span>
                </button>
            @endif
            <button id="lf-btn-execute" onclick="window.lfToolsPanel.execute()" class="primary-button">
                <span id="lf-exec-text">✨ Executar com IA</span>
                <span id="lf-exec-loading" style="display:none;">🧠 Processando...</span>
            </button>
        </div>
    </div>
</div>

@push('styles')
    <style>
        /* ============================================================
                                                               LF TOOLS MODAL — Matches Krayin AI Assistant System Styles
                                                               ============================================================ */

        /* Compact button — matches /admin/juridico/assistants style */
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

        /* Overlay */
        .lf-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
        }

        /* Dialog */
        .lf-modal-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10001;
            width: 95%;
            max-width: 1200px;
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

        /* Header */
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

        /* Body */
        .lf-modal-body {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            padding: 24px;
        }

        @media (max-width: 768px) {
            .lf-modal-body {
                grid-template-columns: 1fr;
            }
        }

        /* Form Section */
        .lf-modal-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .lf-section-title {
            font-size: 0.95rem;
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
            font-size: 0.8rem;
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
        }

        .dark .lf-input {
            background: #1f2937;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        .lf-textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        /* Result Section */
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
            color: #7B2CBF;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .lf-copy-btn:hover {
            background: #f3f0ff;
        }

        /* Result Actions Group */
        .lf-result-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Save as Note Button */
        .lf-save-note-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #9a3412;
            background: #fed7aa;
            border: 1px solid transparent;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .lf-save-note-btn:hover {
            border-color: #fb923c;
            background: #fdba74;
        }

        .lf-save-note-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Editable Input Styling */
        .lf-input-editable {
            background: #fff !important;
            cursor: text;
        }

        .lf-input-editable:focus {
            outline: none;
            border-color: #7B2CBF;
            box-shadow: 0 0 0 2px rgba(123, 44, 191, 0.15);
        }

        .dark .lf-input-editable {
            background: #111827 !important;
        }

        .dark .lf-input-editable:focus {
            border-color: #9D4EDD;
            box-shadow: 0 0 0 2px rgba(157, 78, 221, 0.2);
        }

        .lf-result-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            max-height: 400px;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            height: 100%;
            min-height: 200px;
            color: #7B2CBF;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .lf-spinner {
            width: 36px;
            height: 36px;
            border: 4px solid rgba(123, 44, 191, 0.2);
            border-top-color: #7B2CBF;
            border-radius: 50%;
            animation: lf-spin 0.8s linear infinite;
        }

        @keyframes lf-spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Footer */
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

        /* Krayin Standard Buttons (from assistant show.blade.php) */
        .primary-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            background: linear-gradient(135deg, #7B2CBF 0%, #9D4EDD 100%);
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .primary-button:hover {
            background: linear-gradient(135deg, #6A24A8 0%, #8B3FCC 100%);
            transform: translateY(-1px);
        }

        .primary-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            cursor: pointer;
        }

        .secondary-button:hover {
            background: #e5e7eb;
        }

        .secondary-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .dark .secondary-button {
            background: #374151;
            color: #e5e7eb;
            border-color: #4b5563;
        }

        .dark .secondary-button:hover {
            background: #4b5563;
        }

        /* AI Result Box (from assistant show.blade.php) */
        .ai-result-box {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            background: #fff;
            min-height: 200px;
            line-height: 1.6;
            font-family: 'Inter', sans-serif;
            color: #333;
        }

        .ai-result-box h1,
        .ai-result-box h2,
        .ai-result-box h3,
        .ai-result-box h4 {
            color: #2c3e50;
            margin-top: 15px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .ai-result-box h1 {
            font-size: 1.5em;
        }

        .ai-result-box h2 {
            font-size: 1.3em;
        }

        .ai-result-box h3 {
            font-size: 1.1em;
        }

        .ai-result-box ul,
        .ai-result-box ol {
            margin-left: 20px;
            margin-bottom: 10px;
        }

        .ai-result-box li {
            margin-bottom: 5px;
        }

        .ai-result-box p {
            margin-bottom: 10px;
        }

        .ai-result-box strong {
            color: #000;
            font-weight: 700;
        }

        .ai-result-box blockquote {
            border-left: 4px solid #7B2CBF;
            padding-left: 10px;
            color: #666;
            font-style: italic;
            margin: 10px 0;
        }

        .dark .ai-result-box {
            background: #1f2937;
            border-color: #374151;
            color: #e5e7eb;
        }

        .dark .ai-result-box h1,
        .dark .ai-result-box h2,
        .dark .ai-result-box h3,
        .dark .ai-result-box h4 {
            color: #fff;
        }

        .dark .ai-result-box strong {
            color: #fff;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        (function () {
            'use strict';

            var LEAD_TITLE = @json($lead->title ?? '');
            var LEAD_DESC = @json($lead->description ?? 'Sem descrição.');
            var TENANT_ID = @json($tenantId);
            var LEAD_ID = @json($leadId);
            var CSRF_TOKEN = @json(csrf_token());

            var ROUTES = {
                generate: "{{ route('lawfirm.assistants.generate', '__SLUG__') }}",
                execute: "{{ route('lawfirm.assistants.execute', '__SLUG__') }}",
                status: "{{ route('lawfirm.assistants.check_status', '__ID__') }}",
                saveNote: "{{ route('admin.activities.store') }}",
                stageUpdate: "{{ route('admin.leads.stage.update', $leadId) }}"
            };

            // State
            var activeSlug = '';
            var activeStageId = null;
            var rawResult = '';

            // -------------------------------------------------------------------------
            // AUTO-RELOAD ON STAGE CHANGE (WON/LOST)
            // -------------------------------------------------------------------------
            // Since Krayin uses Vue to update stages without reload, we watch the DOM
            // for the specific classes applied to the Won/Lost dropdown toggle.
            // If detected, we reload to apply the PHP-side visibility logic.
            // -------------------------------------------------------------------------
            var stageObserver = new MutationObserver(function (mutations) {
                // We target the LAST stage item (Won/Lost dropdown) which has 'rounded-r-lg'.
                // Need to escape the exclamation mark in the class name for querySelector.
                var wonIndicator = document.querySelector('.rounded-r-lg.\\!bg-green-500');
                var lostIndicator = document.querySelector('.rounded-r-lg.\\!bg-red-500');
                var panelVisible = document.getElementById('lf-tools-panel');

                // Helper to verify it's the dropdown toggle (has arrow icon)
                // This prevents false positives in RTL where first stage might be rounded-r-lg
                var isDropdown = function (el) {
                    return el && (el.querySelector('.icon-down-arrow') || el.querySelector('.icon-up-arrow'));
                };

                // If we see a Won/Lost indicator AND the panel is still visible
                if (panelVisible && ((wonIndicator && isDropdown(wonIndicator)) || (lostIndicator && isDropdown(lostIndicator)))) {
                    console.log('LF Tools Panel: Stage change detected (Won/Lost). Reloading...');
                    window.location.reload();
                }
            });

            // Start observing the body for class changes (subtree)
            // narrowed to .lead-stages if possible, but body is safer for the Vue mount
            var observerTarget = document.body;
            if (observerTarget) {
                stageObserver.observe(observerTarget, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['class']
                });
            }

            // DOM Refs (populated after DOMContentLoaded)
            var modal, modalTitle, formTitle, formDesc, formTenant, formObservacoes;
            var resultPlaceholder, resultLoading, resultContent, resultActions;
            var btnGenerate, btnExecute, saveNoteBtn;
            var genText, genLoading, execText, execLoading;

            document.addEventListener('DOMContentLoaded', function () {
                // Move modal to body to escape Vue
                modal = document.getElementById('lf-tools-modal');
                if (modal) document.body.appendChild(modal);

                modalTitle = document.getElementById('lf-modal-title');
                formTitle = document.getElementById('lf-form-title');
                formDesc = document.getElementById('lf-form-description');
                formTenant = document.getElementById('lf-form-tenant');
                formObservacoes = document.getElementById('lf-form-observacoes');
                resultPlaceholder = document.getElementById('lf-result-placeholder');
                resultLoading = document.getElementById('lf-result-loading');
                resultContent = document.getElementById('lf-result-content');
                resultActions = document.getElementById('lf-result-actions');
                btnGenerate = document.getElementById('lf-btn-generate');
                 btnExecute = document.getElementById('lf-btn-execute');
                    genText = document.getElementById('lf-gen-text');
                    genLoading = document.getElementById('lf-gen-loading');
                    execText = document.getElementById('lf-exec-text');
                    execLoading = document.getElementById('lf-exec-loading');

                    function showModal() { if (modal) modal.style.display = ''; }
                    function hideModal() { if (modal) modal.style.display = 'none'; }

                    function showState(state) {
                        resultPlaceholder.style.display = state === 'placeholder' ? '' : 'none';
                        resultLoading.style.display = state === 'loading' ? '' : 'none';
                        resultContent.style.display = state === 'result' ? '' : 'none';
                        resultActions.style.display = state === 'result' ? '' : 'none';

                        // Buttons
                        btnGenerate.disabled = state === 'loading';
                        btnExecute.disabled = state === 'loading';
                        genText.style.display = state === 'loading' ? 'none' : '';
                        genLoading.style.display = state === 'loading' ? '' : 'none';
                        execText.style.display = state === 'loading' ? 'none' : '';
                        execLoading.style.display = state === 'loading' ? '' : 'none';
                    }

                    function renderMd(text) {
                        if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                            try { return marked.parse(text); } catch (e) { return text; }
                        }
                        return text.replace(/\n/g, '<br>');
                    }

                    window.lfToolsPanel = {
                        open: function (slug, title, stageId) {
                            activeSlug = slug;
                            activeStageId = (stageId && stageId !== 'null' && stageId !== '') ? stageId : null;
                            rawResult = '';
                            modalTitle.textContent = '🤖 ' + title;
                            formTitle.value = LEAD_TITLE;
                            formDesc.value = LEAD_DESC;
                            formTenant.value = TENANT_ID;
                            formObservacoes.value = '';
                            resultContent.innerHTML = '';
                            showState('placeholder');
                            showModal();
                            console.log('LF Tools Panel: opened slug=' + slug + ' stageId=' + stageId);
                        },

                        close: function () {
                            hideModal();
                        },

                        generate: async function () {
                            showState('loading');
                            try {
                                var url = ROUTES.generate.replace('__SLUG__', activeSlug);
                                var res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': CSRF_TOKEN
                                    },
                                    body: JSON.stringify({
                                        title: formTitle.value,
                                        description: formDesc.value,
                                        observacoes: formObservacoes.value,
                                        tenant_id: TENANT_ID
                                    })
                                });
                                var data = await res.json();
                                if (data.generated_prompt) {
                                    rawResult = data.generated_prompt;
                                    resultContent.innerHTML = renderMd(rawResult);
                                    showState('result');
                                } else {
                                    alert('Erro: ' + JSON.stringify(data));
                                    showState('placeholder');
                                }
                            } catch (e) {
                                alert('Erro: ' + e.message);
                                showState('placeholder');
                            }
                        },

                        execute: async function () {
                            showState('loading');
                            try {
                                var url = ROUTES.execute.replace('__SLUG__', activeSlug);
                                var res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': CSRF_TOKEN
                                    },
                                    body: JSON.stringify({
                                        title: formTitle.value,
                                        description: formDesc.value,
                                        observacoes: formObservacoes.value,
                                        tenant_id: TENANT_ID
                                    })
                                });

                                if (!res.ok) {
                                    var err = await res.json();
                                    throw new Error(err.error || 'Erro na execução');
                                }

                                var data = await res.json();
                                console.log('LF Tools Panel: execute response', data);

                                // Update pipeline stage if configured
                                if (activeStageId) {
                                    updatePipelineStage(activeStageId);
                                }

                                if (data.status === 'queued' && data.history_id) {
                                    pollStatus(data.history_id);
                                } else {
                                    throw new Error('Resposta inesperada');
                                }
                            } catch (e) {
                                alert('Erro: ' + e.message);
                                showState('placeholder');
                            }
                        },

                        copy: function () {
                            if (!rawResult) return;
                            var ta = document.createElement('textarea');
                            ta.value = rawResult;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);

                            var btn = document.getElementById('lf-copy-btn');
                            var oldText = btn.innerHTML;
                            btn.innerHTML = '✅ Copiado!';
                            setTimeout(function () { btn.innerHTML = oldText; }, 2000);
                        },

                        pdf: function () {
                            if (!rawResult) return;
                            var printWindow = window.open('', '', 'height=600,width=800');
                            printWindow.document.write('<html><head><title>Resultado IA</title>');
                            printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; color: #333; } h1, h2, h3 { color: #111; }</style>');
                            printWindow.document.write('</head><body>');
                            printWindow.document.write(renderMd(rawResult));
                            printWindow.document.write('</body></html>');
                            printWindow.document.close();
                            printWindow.focus();
                            setTimeout(function() {
                                printWindow.print();
                            }, 250);
                        },

                        saveAsNote: async function (btn) {
                            if (!rawResult) return;
                            btn.disabled = true;
                            btn.innerHTML = '⏳ Salvando...';

                            try {
                                var formData = new FormData();
                                formData.append('_token', CSRF_TOKEN);
                                formData.append('type', 'note');
                                formData.append('comment', rawResult);
                                formData.append('lead_id', LEAD_ID);

                                var res = await fetch(ROUTES.saveNote, {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                    body: formData
                                });

                                if (!res.ok) throw new Error('Erro ao salvar nota');

                                var data = await res.json();
                                btn.innerHTML = '✅ Nota Salva!';
                                console.log('LF Tools Panel: note saved', data);

                                setTimeout(function () {
                                    btn.innerHTML = '<span class="icon-note"></span> Salvar como Nota';
                                    btn.disabled = false;
                                }, 2000);

                            } catch (e) {
                                alert('Erro ao salvar nota: ' + e.message);
                                btn.innerHTML = '<span class="icon-note"></span> Salvar como Nota';
                                btn.disabled = false;
                            }
                        }
                    };

                    // Update lead pipeline stage (fire-and-forget)
                    function updatePipelineStage(stageId) {
                        var formData = new FormData();
                        formData.append('_method', 'PUT');
                        formData.append('_token', CSRF_TOKEN); // Alternativa robusta
                        formData.append('lead_pipeline_stage_id', stageId);

                        fetch(ROUTES.stageUpdate, {
                            method: 'POST', // Mudado de PUT para POST (Spoofing)
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN
                            },
                            body: formData

                        }).then(function (res) {
                            if (res.ok) {
                                console.log('LF Tools Panel: pipeline stage updated to ' + stageId);
                            } else {
                                console.warn('LF Tools Panel: failed to update stage', res.status);
                            }
                        }).catch(function (e) {
                            console.warn('LF Tools Panel: stage update error', e);
                        });
                    }

                    function pollStatus(historyId) {
                        var attempts = 0;
                        var maxAttempts = 60;
                        var interval = setInterval(async function () {
                            attempts++;
                            try {
                                var url = ROUTES.status.replace('__ID__', historyId);
                                var res = await fetch(url);
                                var data = await res.json();

                                console.log('Poll Status:', data.status, 'Attempt:', attempts);

                                if (data.status === 'completed') {
                                    clearInterval(interval);
                                    rawResult = data.generated_content;
                                    resultContent.innerHTML = renderMd(rawResult);
                                    showState('result');
                                } else if (data.status === 'failed') {
                                    clearInterval(interval);
                                    alert('Erro: ' + (data.error_message || 'Falha desconhecida'));
                                    showState('placeholder');
                                } else if (attempts >= maxAttempts) {
                                    clearInterval(interval);
                                    alert('Tempo limite excedido.');
                                    showState('placeholder');
                                }
                            } catch (e) {
                                if (attempts >= maxAttempts) {
                                    clearInterval(interval);
                                    showState('placeholder');
                                }
                            }
                        }, 2000);
                    }

                }); // Fim do DOMContentLoaded listener

            })();
        </script>
@endpush