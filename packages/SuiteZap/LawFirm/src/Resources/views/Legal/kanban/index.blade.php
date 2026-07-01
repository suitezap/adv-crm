@push('styles')
<style>
    /* Custom scrollbar for kanban columns */
    .lf-kanban-dropzone::-webkit-scrollbar { width: 4px; }
    .lf-kanban-dropzone::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .lf-kanban-dropzone::-webkit-scrollbar-track { background: transparent; }
    /* Drag ghost cursor */
    .lf-kanban-card[draggable="true"]:active { cursor: grabbing; }
    .lf-kanban-card.dragging { opacity: 0.5; }
    /* Drop zone highlight */
    .lf-kanban-dropzone.drag-over { background: rgba(59,130,246,.05); outline: 2px dashed #93c5fd; outline-offset: -2px; }
    /* Compact / fit-to-window mode */
    #lf-kanban-board.lf-compact { transform-origin: top left; overflow-x: hidden !important; }
    /* ── Processo tooltip ── */
    #lf-proc-tooltip {
        position: fixed;
        z-index: 9999;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        padding: 10px 12px;
        min-width: 220px;
        max-width: 320px;
        font-size: 12px;
        color: #374151;
        pointer-events: none;
        opacity: 0;
        transition: opacity .15s;
    }
    #lf-proc-tooltip.show { opacity: 1; }
    #lf-proc-tooltip .tt-title {
        font-weight: 600;
        font-size: 11px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
        padding-bottom: 4px;
        border-bottom: 1px solid #f3f4f6;
    }
    #lf-proc-tooltip .tt-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 3px 0;
        border-bottom: 1px solid #f9fafb;
    }
    #lf-proc-tooltip .tt-row:last-child { border-bottom: none; }
    #lf-proc-tooltip .tt-name { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #lf-proc-tooltip .tt-badge {
        background: #f3f4f6;
        color: #4b5563;
        border-radius: 999px;
        padding: 1px 7px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }
    #lf-proc-tooltip .tt-empty { color: #9ca3af; font-style: italic; }
</style>
@endpush

@pushOnce('scripts')
<script>
    /**
     * Legal Kanban — Event-Delegated Drag & Drop
     *
     * Krayin calls app.mount('#app') on window.load (layouts/index.blade.php:114).
     * We must initialize AFTER that mount completes.
     */
    (function() {
        function boot() {
            setTimeout(initLegalKanbanDragDrop, 150);
        }

        if (document.readyState === 'complete') {
            boot();
        } else {
            window.addEventListener('load', boot);
        }

        function initLegalKanbanDragDrop() {
            var UPDATE_ROUTE = "{{ route('admin.lawfirm.legal.kanban.update', 'REPLACE_ID') }}";
            var CSRF_TOKEN = "{{ csrf_token() }}";
            var board = document.getElementById('lf-kanban-board');

            if (!board) {
                console.warn('[LegalKanban] #lf-kanban-board not found.');
                return;
            }

            var draggedCard = null;
            var sourceDz = null;

            board.addEventListener('dragstart', function(e) {
                var card = e.target.closest('.lf-kanban-card');
                if (!card) return;
                draggedCard = card;
                sourceDz = card.closest('.lf-kanban-dropzone');
                card.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.dataset.casoId);
            });

            board.addEventListener('dragend', function(e) {
                var card = e.target.closest('.lf-kanban-card');
                if (card) card.classList.remove('dragging');
                draggedCard = null;
                sourceDz = null;
                board.querySelectorAll('.lf-kanban-dropzone.drag-over').forEach(function(dz) {
                    dz.classList.remove('drag-over');
                });
            });

            board.addEventListener('dragover', function(e) {
                var dz = e.target.closest('.lf-kanban-dropzone');
                if (!dz) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                dz.classList.add('drag-over');
            });

            board.addEventListener('dragleave', function(e) {
                var dz = e.target.closest('.lf-kanban-dropzone');
                if (!dz) return;
                if (!dz.contains(e.relatedTarget)) {
                    dz.classList.remove('drag-over');
                }
            });

            board.addEventListener('drop', function(e) {
                var dz = e.target.closest('.lf-kanban-dropzone');
                if (!dz) return;
                e.preventDefault();
                dz.classList.remove('drag-over');

                if (!draggedCard || !sourceDz) return;

                var caseId = draggedCard.dataset.casoId;
                var newStageId = dz.dataset.stageId;
                var origSource = sourceDz;
                var movedCard = draggedCard; // save ref before dragend nullifies it

                if (origSource === dz) return;

                dz.prepend(movedCard);
                updateCounts(origSource, dz);

                var url = UPDATE_ROUTE.replace('REPLACE_ID', caseId);

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ _method: 'PUT', stage_id: parseInt(newStageId, 10) }),
                })
                .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        showToast('Caso movido para "' + data.stage_name + '"', 'success');
                    } else {
                        throw new Error(data.error || 'Erro');
                    }
                })
                .catch(function(err) {
                    origSource.appendChild(movedCard);
                    updateCounts(dz, origSource);
                    showToast('Erro ao mover: ' + err.message, 'error');
                });
            });

            function updateCounts(fromDz, toDz) {
                [fromDz, toDz].forEach(function(d) {
                    if (!d) return;
                    var col = d.closest('.lf-kanban-column');
                    if (!col) return;
                    var el = col.querySelector('.lf-kanban-count');
                    if (el) el.textContent = d.querySelectorAll('.lf-kanban-card').length;
                });
            }

            function showToast(msg, type) {
                var t = document.createElement('div');
                t.textContent = msg;
                t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:500;color:#fff;opacity:0;transition:opacity .3s;box-shadow:0 4px 12px rgba(0,0,0,.15);';
                t.style.backgroundColor = type === 'success' ? '#059669' : '#dc2626';
                document.body.appendChild(t);
                requestAnimationFrame(function() { t.style.opacity = '1'; });
                setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
            }

            console.log('[LegalKanban] Drag-and-drop initialized. Cards:', board.querySelectorAll('.lf-kanban-card').length);

            // ── Processo tooltip (reads from JS global map) ────────────
            // window.__LF_PROCESSOS_MAP is set by an inline script tag at the bottom of the page

            var tip = document.createElement('div');
            tip.id = 'lf-proc-tooltip';
            document.body.appendChild(tip);

            var _tipTimeout = null;

            board.addEventListener('mouseenter', function(e) {
                var trigger = e.target.closest('.lf-proc-trigger');
                if (!trigger) return;
                clearTimeout(_tipTimeout);

                // Find the parent card to get caso_id
                var card = trigger.closest('.lf-kanban-card');
                var casoId = card ? card.getAttribute('data-caso-id') : null;
                var procs = (window.__LF_PROCESSOS_MAP && casoId) ? (window.__LF_PROCESSOS_MAP[casoId] || []) : [];

                // Build inner HTML
                var html = '<div class="tt-title">📁 Processos vinculados (' + procs.length + ')</div>';
                if (!procs.length) {
                    html += '<div class="tt-empty">Nenhum processo vinculado</div>';
                } else {
                    procs.forEach(function(p) {
                        html += '<div class="tt-row">'
                              + '<span class="tt-name">' + escHtml(p.titulo) + '</span>'
                              + '<span class="tt-badge">' + escHtml(p.status) + '</span>'
                              + '</div>';
                    });
                }
                tip.innerHTML = html;

                // Position near cursor
                positionTip(e);
                tip.classList.add('show');
            }, true);

            board.addEventListener('mousemove', function(e) {
                if (tip.classList.contains('show')) positionTip(e);
            }, true);

            board.addEventListener('mouseleave', function(e) {
                var trigger = e.target.closest('.lf-proc-trigger');
                if (!trigger) return;
                _tipTimeout = setTimeout(function() { tip.classList.remove('show'); }, 80);
            }, true);

            function positionTip(e) {
                var x = e.clientX + 12;
                var y = e.clientY + 12;
                var tw = tip.offsetWidth  || 260;
                var th = tip.offsetHeight || 100;
                if (x + tw > window.innerWidth  - 8) x = e.clientX - tw - 10;
                if (y + th > window.innerHeight - 8) y = e.clientY - th - 10;
                tip.style.left = x + 'px';
                tip.style.top  = y + 'px';
            }

            function escHtml(s) {
                return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            }
        }

        // ── Fit-to-window toggle (responsive column resize) ─────────
        var _compact = false;
        var _origColStyles = [];

        function applyCompact() {
            var boardEl = document.getElementById('lf-kanban-board');
            if (!boardEl) return;
            var btn    = document.getElementById('lf-kanban-toggle');
            var cols   = boardEl.querySelectorAll('.lf-kanban-column');
            var zones  = boardEl.querySelectorAll('.lf-kanban-dropzone');
            var cards  = boardEl.querySelectorAll('.lf-kanban-card');

            if (!_compact) {
                // Save originals
                _origColStyles = [];
                cols.forEach(function(c) {
                    _origColStyles.push({
                        minWidth : c.style.minWidth,
                        maxWidth : c.style.maxWidth,
                        width    : c.style.width,
                    });
                });

                var numCols   = cols.length || 1;
                var gap       = 10; // px gap between cols (gap-2.5 ≈ 10px)
                var available = boardEl.parentElement.clientWidth - (gap * (numCols - 1)) - 2;
                var colW      = Math.floor(available / numCols);

                // Compact columns
                cols.forEach(function(c) {
                    c.style.minWidth = colW + 'px';
                    c.style.maxWidth = colW + 'px';
                    c.style.width    = colW + 'px';
                });

                // Reduce board gap and disable horizontal scroll
                boardEl.style.gap      = '4px';
                boardEl.style.flexWrap = 'nowrap';
                boardEl.style.overflowX = 'hidden';

                // Shrink dropzone height and card text to save space
                zones.forEach(function(z) { z.style.height = '300px'; z.style.maxHeight = '300px'; });
                cards.forEach(function(card) { card.style.fontSize = '11px'; card.style.padding = '4px 6px'; });

                _compact = true;
                if (btn) btn.textContent = '⛶ Ampliar Kanban';
            } else {
                // Restore columns
                cols.forEach(function(c, i) {
                    var s = _origColStyles[i] || {};
                    c.style.minWidth = s.minWidth || '';
                    c.style.maxWidth = s.maxWidth || '';
                    c.style.width    = s.width    || '';
                });

                boardEl.style.gap       = '';
                boardEl.style.flexWrap  = '';
                boardEl.style.overflowX = '';

                zones.forEach(function(z) { z.style.height = ''; z.style.maxHeight = ''; });
                cards.forEach(function(card) { card.style.fontSize = ''; card.style.padding = ''; });

                _compact = false;
                if (btn) btn.textContent = '⊟ Reduzir Kanban';
            }
        }

        // Re-apply on window resize when in compact mode
        window.addEventListener('resize', function() {
            if (_compact) {
                _compact = false; // reset flag so applyCompact re-enters the "compact" branch
                applyCompact();
            }
        });

        // Expose so the inline onclick can reach it
        window.lfToggleKanban = applyCompact;
    })();
</script>
@endPushOnce

<x-admin::layouts>
    <x-slot:title>
        Kanban Jurídico
    </x-slot>

    <div class="flex flex-col gap-4">

        {{-- ═══ Header (Lead Kanban pattern) ═══ --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    📋 Kanban Jurídico
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-400">
                    Arraste os Casos entre as colunas para atualizar o status. Os Processos vinculados serão atualizados automaticamente.
                </p>
            </div>
            <div class="flex items-center gap-x-2.5">
                <button id="lf-kanban-toggle"
                        onclick="lfToggleKanban()"
                        class="secondary-button flex items-center gap-1">
                    ⊟ Reduzir Kanban
                </button>
                <a href="{{ route('admin.lawfirm.casos.index') }}"
                   class="secondary-button">
                    ← Voltar para Casos
                </a>
            </div>
        </div>

        {{-- ═══ Kanban Board ═══ --}}
        <div id="lf-kanban-board" class="flex gap-2.5 overflow-x-auto">

            @forelse ($stages as $stage)
                <div class="lf-kanban-column flex min-w-[275px] max-w-[275px] flex-col gap-1 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
                     data-stage-id="{{ $stage->id }}"
                     data-stage-name="{{ $stage->name }}">

                    {{-- Column Header (Lead Kanban pattern) --}}
                    <div class="flex flex-col px-2 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium dark:text-white">
                                {{ $stage->name }} (<span class="lf-kanban-count">{{ isset($casosByStage[$stage->id]) ? $casosByStage[$stage->id]->count() : 0 }}</span>)
                            </span>
                        </div>
                    </div>

                    {{-- Drop Zone --}}
                    <div class="lf-kanban-dropzone flex h-[calc(100vh-317px)] flex-col gap-2 overflow-y-auto p-2"
                         data-stage-id="{{ $stage->id }}">

                        @if (isset($casosByStage[$stage->id]))
                            @foreach ($casosByStage[$stage->id] as $caso)
                                @php
                                    // Area color map (hex)
                                    $areaColors = [
                                        'Administrativo'   => '#A9CCE3',
                                        'Ambiental'        => '#A3E4D7',
                                        'Bancário'         => '#D4E6B5',
                                        'Consumidor'       => '#F8CBA6',
                                        'Cível'            => '#C7D3DD',
                                        'Digital / LGPD'   => '#C5CAE9',
                                        'Empresarial'      => '#D7BDE2',
                                        'Família'          => '#F5B7B1',
                                        'Imobiliário'      => '#E8D8C3',
                                        'Penal'            => '#F4A7A7',
                                        'Previdenciário'   => '#A8D5BA',
                                        'Trabalhista'      => '#A7C7E7',
                                        'Tributário'       => '#F9E79F',
                                    ];

                                    // Priority color map (hex)
                                    $prioridadeColors = [
                                        'Alta'    => '#E89B4D',
                                        'Baixa'   => '#7BC67B',
                                        'Crítica' => '#D96B6B',
                                        'Média'   => '#E6C15A',
                                    ];

                                    $areaBg = $areaColors[$caso->area] ?? '#E5E7EB';
                                    $prioKey = ucfirst(mb_strtolower($caso->prioridade ?? ''));
                                    $prioBg = $prioridadeColors[$prioKey] ?? '#E5E7EB';
                                @endphp

                                <div class="lf-kanban-card lead-item flex cursor-grab flex-col gap-2 rounded-md border border-gray-100 bg-gray-50 p-2 dark:border-gray-400 dark:bg-gray-400"
                                     draggable="true"
                                     data-caso-id="{{ $caso->id }}">

                                    {{-- Person / Organization --}}
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-1">
                                            <x-admin::avatar ::name="'{{ addslashes(optional($caso->person)->name ?? optional($caso->organization)->name ?? $caso->titulo) }}'" />

                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-medium">
                                                    {{ Str::limit(optional($caso->person)->name ?? optional($caso->organization)->name ?? '—', 22) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Title (Lead Kanban pattern: text-xs font-medium) --}}
                                    <a href="{{ route('admin.lawfirm.casos.show', $caso->id) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300" title="{{ $caso->titulo }}">
                                        {{ Str::limit($caso->titulo, 45) }}
                                    </a>

                                    {{-- Tags row (Lead Kanban pattern: rounded-xl px-2 py-1) --}}
                                    <div class="flex flex-wrap gap-1">
                                        {{-- Responsible --}}
                                        @if ($caso->responsavel)
                                            <div class="flex items-center gap-1 rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium dark:bg-gray-800 dark:text-white">
                                                <span class="icon-settings-user text-sm"></span>
                                                {{ Str::limit($caso->responsavel->name, 14) }}
                                            </div>
                                        @endif

                                        {{-- Processos count — tooltip data comes from JS global --}}
                                        <div class="lf-proc-trigger relative inline-flex rounded-xl bg-gray-200 px-2 py-1 text-xs font-medium cursor-pointer dark:bg-gray-800 dark:text-white">
                                            📁 {{ $caso->processos_count }}
                                        </div>

                                        {{-- Area (colored tag) --}}
                                        @if ($caso->area)
                                            <div class="rounded-xl px-2 py-1 text-xs font-medium"
                                                 style="background-color: {{ $areaBg }}; color: #333;">
                                                {{ $caso->area }}
                                            </div>
                                        @endif

                                        {{-- Priority (colored tag) --}}
                                        @if ($caso->prioridade)
                                            <div class="rounded-xl px-2 py-1 text-xs font-medium"
                                                 style="background-color: {{ $prioBg }}; color: #fff;">
                                                {{ $caso->prioridade }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="flex flex-col items-center justify-center h-full">
                                <p class="text-xs text-gray-400 dark:text-gray-400 text-center">
                                    Nenhum caso nesta etapa
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center w-full py-20">
                    <div class="text-center">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">Nenhum pipeline configurado.</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Entre em contato com o suporte para configurar o Kanban Jurídico.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

{{-- Inline script for processos tooltip data (not @push — avoids Blade stack errors with Krayin layouts) --}}
<script>
    window.__LF_PROCESSOS_MAP = {!! $processosTooltipJson ?? '{}' !!};
</script>

</x-admin::layouts>
