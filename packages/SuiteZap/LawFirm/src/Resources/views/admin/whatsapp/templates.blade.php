<x-admin::layouts>
    <x-slot:title>Templates WhatsApp</x-slot>

    @push('styles')
        <style>
            /* ── Filter bar ── */
            .lf-area-btn {
                display: inline-flex; align-items: center; gap: .3rem;
                padding: .35rem .85rem; font-size: .8rem; font-weight: 500;
                border-radius: .5rem; border: 1px solid transparent;
                background: transparent; color: #6b7280;
                cursor: pointer; transition: all 150ms; white-space: nowrap;
            }
            .lf-area-btn:hover { background: #f3f4f6; color: #111827; }
            .lf-area-btn.active {
                font-weight: 600;
                background: linear-gradient(135deg, #25d366, #128c7e);
                color: #fff; box-shadow: 0 2px 8px rgba(37,211,102,.3);
            }
            .dark .lf-area-btn { color: #9ca3af; }
            .dark .lf-area-btn:hover { background: #1f2937; color: #f9fafb; }
            .dark .lf-area-btn.active { background: linear-gradient(135deg, #25d366, #128c7e); color: #fff; }

            /* ── Section header ── */
            .lf-cat-label {
                font-size: .7rem; font-weight: 700; letter-spacing: .08em;
                text-transform: uppercase; color: #9ca3af;
                display: flex; align-items: center; gap: .5rem;
                padding-bottom: .5rem; margin-bottom: 1rem;
                border-bottom: 1px solid #e5e7eb;
            }
            .dark .lf-cat-label { border-color: #374151; }
            .lf-cat-label::before {
                content: ''; width: 3px; height: .9rem; border-radius: 2px;
                background: var(--cat-color, #25d366); flex-shrink: 0; display: block;
            }

            /* ── Template card ── */
            .lf-tpl-card {
                position: relative; overflow: hidden; cursor: pointer;
                border: 1px solid #e5e7eb; border-radius: .75rem;
                background: #fff; padding: 1rem 1rem .85rem;
                transition: border-color .15s, box-shadow .18s, transform .18s;
                user-select: none;
            }
            .lf-tpl-card::before {
                content: ''; position: absolute; top: 0; left: 0; right: 0;
                height: 3px; background: var(--cat-color, #25d366);
                transition: height .18s;
            }
            .lf-tpl-card:hover {
                border-color: #86efac !important;
                box-shadow: 0 6px 20px rgba(37,211,102,.14);
                transform: translateY(-2px);
            }
            .lf-tpl-card:hover::before { height: 4px; }
            .dark .lf-tpl-card { background: #111827; border-color: #1f2937; }
            .dark .lf-tpl-card:hover { border-color: #25d366 !important; }

            .lf-card-edit-icon {
                position: absolute; top: .7rem; right: .75rem;
                font-size: .75rem; color: #9ca3af; opacity: 0; transition: opacity .15s;
            }
            .lf-tpl-card:hover .lf-card-edit-icon { opacity: 1; }

            .lf-tpl-preview {
                font-size: .72rem; color: #6b7280; line-height: 1.5; margin-top: .4rem;
                display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
                overflow: hidden; font-family: 'Courier New', monospace;
            }

            .lf-var-badge {
                display: inline-block;
                background: #f0fdf4; border: 1px solid #bbf7d0;
                color: #166534; border-radius: 4px; padding: 1px 6px;
                font-family: monospace; font-size: .65rem;
                cursor: pointer; transition: background .15s;
            }
            .lf-var-badge:hover { background: #dcfce7; }
            .dark .lf-var-badge { background: rgba(22,163,74,.15); border-color: rgba(22,163,74,.3); color: #4ade80; }

            .lf-badge-custom {
                display: inline-block; font-size: .6rem; font-weight: 700;
                letter-spacing: .04em; text-transform: uppercase;
                background: #fef9c3; border: 1px solid #fde047;
                color: #854d0e; border-radius: 4px; padding: 1px 6px;
            }
            .dark .lf-badge-custom { background: rgba(250,204,21,.15); border-color: rgba(250,204,21,.3); color: #fde047; }

            /* ── Modal ── */
            #lf-modal-overlay {
                display: none; position: fixed; inset: 0; z-index: 9000;
                background: rgba(0,0,0,.45);
                align-items: center; justify-content: center;
            }
            #lf-modal-overlay.open { display: flex; }

            #lf-modal {
                background: #fff; border-radius: 1rem;
                width: min(680px, 96vw); max-height: 90vh; overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0,0,0,.25);
                display: flex; flex-direction: column;
            }
            .dark #lf-modal { background: #111827; }

            .lf-modal-stripe { height: 4px; border-radius: 1rem 1rem 0 0; }

            .lf-modal-header {
                padding: 1.1rem 1.25rem .9rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
            }
            .dark .lf-modal-header { border-color: #1f2937; }
            .lf-modal-title { font-size: 1rem; font-weight: 700; color: #111827; line-height: 1.3; }
            .dark .lf-modal-title { color: #f9fafb; }
            .lf-modal-close {
                flex-shrink: 0; background: #f3f4f6; border: none; border-radius: 50%;
                width: 28px; height: 28px; cursor: pointer; font-size: 1rem;
                display: flex; align-items: center; justify-content: center;
                color: #6b7280; transition: background .15s;
            }
            .lf-modal-close:hover { background: #e5e7eb; color: #111827; }
            .dark .lf-modal-close { background: #1f2937; color: #9ca3af; }
            .dark .lf-modal-close:hover { background: #374151; color: #f9fafb; }

            .lf-modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }

            .lf-modal-textarea {
                width: 100%; padding: 10px 12px;
                border: 1px solid #d1d5db; border-radius: .5rem;
                font-size: .82rem; line-height: 1.65; color: #1f2937;
                background: #f9fafb; font-family: 'Courier New', monospace;
                box-sizing: border-box; resize: vertical; min-height: 120px;
                transition: border-color .2s, box-shadow .2s;
            }
            .dark .lf-modal-textarea { background: #0f172a; border-color: #374151; color: #e5e7eb; }
            .lf-modal-textarea:focus {
                outline: none; border-color: #25d366;
                box-shadow: 0 0 0 3px rgba(37,211,102,.15);
            }

            .lf-modal-footer {
                padding: .9rem 1.25rem; border-top: 1px solid #e5e7eb;
                display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            }
            .dark .lf-modal-footer { border-color: #1f2937; }

            .lf-btn-save {
                display: inline-flex; align-items: center; gap: .4rem;
                padding: .5rem 1.25rem; font-weight: 600; font-size: .825rem;
                border-radius: .375rem;
                background: linear-gradient(135deg, #25d366, #128c7e);
                color: #fff; border: none; cursor: pointer; transition: all .15s;
            }
            .lf-btn-save:hover { opacity: .9; transform: translateY(-1px); }
            .lf-btn-save:disabled { opacity: .6; cursor: not-allowed; transform: none; }

            .lf-btn-secondary {
                display: inline-flex; align-items: center; gap: .4rem;
                padding: .5rem .9rem; font-weight: 500; font-size: .8rem;
                border-radius: .375rem; background: #f3f4f6;
                border: 1px solid #d1d5db; color: #374151;
                cursor: pointer; transition: all .15s;
            }
            .lf-btn-secondary:hover { background: #e5e7eb; }
            .dark .lf-btn-secondary { background: #374151; border-color: #4b5563; color: #e5e7eb; }

            /* ── Toast ── */
            #lf-toast {
                position: fixed; bottom: 24px; right: 24px;
                background: #166534; color: #fff;
                padding: 10px 20px; border-radius: 8px;
                font-size: .85rem; font-weight: 600; z-index: 99999;
                opacity: 0; transform: translateY(10px);
                transition: opacity .3s, transform .3s; pointer-events: none;
            }
            #lf-toast.show { opacity: 1; transform: translateY(0); }
            .lf-cards-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 1rem;
            }
            @media (max-width: 1200px) {
                .lf-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (max-width: 768px) {
                .lf-cards-grid { grid-template-columns: 1fr; }
            }
        </style>
    @endpush

    {{-- ── Global JS data map (SKILL.md §6 — Global JS Map Injection) ── --}}
    <script>
        window.__LF_WA_TPL_MAP = {!! json_encode($tplMap) !!};
    </script>

    <div class="flex flex-col gap-4">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" onclick="window.history.back()">
                    <span>&larr; Voltar</span>
                </div>
                <div class="text-xl font-bold dark:text-white">💬 Templates de Mensagens WhatsApp</div>
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500 text-right leading-relaxed hidden sm:block">
                Clique em um template para editar.<br>
                Variáveis <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">{...}</code> são substituídas automaticamente.
            </div>
        </div>

        {{-- INFO BAR --}}
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-900/20 dark:text-green-300">
            ✏️ Cada escritório pode personalizar os textos. O padrão global é definido pelo MotherShip — templates personalizados ficam marcados com <span class="lf-badge-custom">✎ personalizado</span>.
        </div>

        {{-- FILTER BAR --}}
        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
            <button type="button" class="lf-area-btn active" id="lf-btn-todas" onclick="lfFilterGroup('todas', this)">Todas</button>
            @foreach($grouped as $key => $group)
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-area-btn" data-group="{{ $key }}"
                    id="lf-filter-{{ $key }}"
                    onclick="lfFilterGroup('{{ $key }}', this)">
                    {{ $group['emoji'] }} {{ $group['label'] }}
                </button>
            @endforeach
        </div>

        {{-- GROUPS + CARDS --}}
        @foreach($grouped as $groupKey => $group)
            <div class="lf-group-section rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                 data-group="{{ $groupKey }}"
                 style="--cat-color: {{ $group['color'] }}">

                <div class="lf-cat-label" style="--cat-color: {{ $group['color'] }}">
                    {{ $group['emoji'] }} {{ $group['label'] }}
                    <span class="ml-1 text-xs font-normal normal-case text-gray-400">
                        ({{ count($group['templates']) }} template{{ count($group['templates']) !== 1 ? 's' : '' }})
                    </span>
                </div>

                <div class="lf-cards-grid">
                    @foreach($group['templates'] as $tpl)
                        @php
                            preg_match_all('/\{([a-z_]+)\}/', $tpl['info'] . ' ' . $tpl['default'], $varMatches);
                            $vars     = array_unique($varMatches[0]);
                            $isCustom = trim($tpl['value']) !== trim($tpl['default']);
                        @endphp

                        {{-- Card — usa data-tpl-name, sem onclick inline com dados (SKILL.md §6) --}}
                        <div class="lf-tpl-card"
                             id="lf-card-{{ $tpl['name'] }}"
                             data-tpl-name="{{ $tpl['name'] }}"
                             style="--cat-color: {{ $group['color'] }}"
                             onclick="lfOpenModal(this.dataset.tplName)">

                            <span class="lf-card-edit-icon">✏️</span>

                            <div class="flex items-start gap-2 pr-5">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-snug flex-1">
                                    {{ $tpl['title'] }}
                                </p>
                                @if($isCustom)
                                    <span class="lf-badge-custom flex-shrink-0">✎ personalizado</span>
                                @endif
                            </div>

                            <p class="lf-tpl-preview" id="lf-preview-{{ $tpl['name'] }}">{{ $tpl['value'] }}</p>

                            @if(!empty($vars))
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($vars as $var)
                                        <span class="lf-var-badge">{{ $var }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>

    {{-- Toast --}}
    <div id="lf-toast"></div>

    {{-- Modal --}}
    <div id="lf-modal-overlay" onclick="lfCloseOnOverlay(event)">
        <div id="lf-modal" role="dialog" aria-modal="true" aria-labelledby="lf-modal-label">

            <div class="lf-modal-stripe" id="lf-modal-stripe"></div>

            <div class="lf-modal-header">
                <div>
                    <div class="lf-modal-title" id="lf-modal-label">—</div>
                    <div id="lf-modal-info" class="text-xs text-gray-400 dark:text-gray-500 mt-1 leading-relaxed"></div>
                </div>
                <button class="lf-modal-close" onclick="lfCloseModal()" title="Fechar">✕</button>
            </div>

            <div class="lf-modal-body">
                <div id="lf-modal-vars" class="flex flex-wrap gap-1.5 items-center min-h-5">
                    <span class="text-xs text-gray-400">Variáveis disponíveis:</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
                        Texto do Template
                    </label>
                    <textarea id="lf-modal-textarea" class="lf-modal-textarea" rows="6"></textarea>
                </div>

                <details class="text-xs text-gray-500 dark:text-gray-600">
                    <summary class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-400 select-none">
                        📋 Ver texto padrão
                    </summary>
                    <div id="lf-modal-default"
                         class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs text-gray-500 font-mono leading-relaxed border border-gray-200 dark:border-gray-700 whitespace-pre-wrap break-words"></div>
                </details>
            </div>

            <div class="lf-modal-footer">
                <button class="lf-btn-secondary" onclick="lfModalRestoreDefault()">
                    ↩️ Restaurar padrão
                </button>
                <div class="flex gap-2">
                    <button class="lf-btn-secondary" onclick="lfCloseModal()">Cancelar</button>
                    <button class="lf-btn-save" id="lf-modal-save-btn" onclick="lfModalSave()">
                        <span id="lf-modal-save-text">💾 Salvar</span>
                        <span id="lf-modal-save-loading" style="display:none">⏳ Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            'use strict';

            var SAVE_ROUTE = @json($saveRoute);
            var CSRF       = @json($csrfToken);
            var TPL_MAP    = window.__LF_WA_TPL_MAP || {};

            var _currentName    = null;
            var _currentDefault = '';

            /* ── Filter ─────────────────────────────────────── */
            window.lfFilterGroup = function (group, btn) {
                document.querySelectorAll('.lf-area-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                document.querySelectorAll('.lf-group-section').forEach(function (sec) {
                    sec.style.display = (group === 'todas' || sec.dataset.group === group) ? '' : 'none';
                });
            };

            /* ── Open modal ──────────────────────────────────── */
            window.lfOpenModal = function (name) {
                var tpl = TPL_MAP[name];
                if (!tpl) return;

                _currentName    = name;
                _currentDefault = tpl.default || '';

                // Find group color from the card
                var card  = document.getElementById('lf-card-' + name);
                var color = card ? getComputedStyle(card).getPropertyValue('--cat-color').trim() : '#25d366';

                document.getElementById('lf-modal-stripe').style.background = color;
                document.getElementById('lf-modal-label').textContent = tpl.title || name;
                document.getElementById('lf-modal-info').textContent  = tpl.info  || '';
                document.getElementById('lf-modal-default').textContent = tpl.default || '';

                var ta  = document.getElementById('lf-modal-textarea');
                ta.value = tpl.value || '';
                ta.rows  = Math.max(tpl.rows || 4, 5);

                // Variable badges
                var varsEl = document.getElementById('lf-modal-vars');
                varsEl.innerHTML = '<span class="text-xs text-gray-400 dark:text-gray-500 self-center">Variáveis:</span>';
                var vars = tpl.vars || [];
                if (vars.length) {
                    vars.forEach(function (v) {
                        var badge = document.createElement('span');
                        badge.className   = 'lf-var-badge';
                        badge.textContent = v;
                        badge.title       = 'Clique para inserir';
                        badge.onclick     = function () { lfInsertVar(v); };
                        varsEl.appendChild(badge);
                    });
                } else {
                    varsEl.innerHTML += '<span class="text-xs text-gray-400 italic">nenhuma</span>';
                }

                document.getElementById('lf-modal-overlay').classList.add('open');
                setTimeout(function () { ta.focus(); }, 50);
            };

            /* ── Close ───────────────────────────────────────── */
            window.lfCloseModal = function () {
                document.getElementById('lf-modal-overlay').classList.remove('open');
                _currentName = null;
            };
            window.lfCloseOnOverlay = function (e) {
                if (e.target === document.getElementById('lf-modal-overlay')) lfCloseModal();
            };

            /* ── Insert variable at cursor ───────────────────── */
            window.lfInsertVar = function (v) {
                var ta    = document.getElementById('lf-modal-textarea');
                var s     = ta.selectionStart, e = ta.selectionEnd;
                ta.value  = ta.value.substring(0, s) + v + ta.value.substring(e);
                ta.selectionStart = ta.selectionEnd = s + v.length;
                ta.focus();
            };

            /* ── Restore default ─────────────────────────────── */
            window.lfModalRestoreDefault = function () {
                if (!confirm('Restaurar o texto padrão para este template?')) return;
                document.getElementById('lf-modal-textarea').value = _currentDefault;
            };

            /* ── Save ────────────────────────────────────────── */
            window.lfModalSave = function () {
                if (!_currentName) return;

                var btn  = document.getElementById('lf-modal-save-btn');
                var txt  = document.getElementById('lf-modal-save-text');
                var load = document.getElementById('lf-modal-save-loading');
                btn.disabled    = true;
                txt.style.display  = 'none';
                load.style.display = 'inline';

                var newValue = document.getElementById('lf-modal-textarea').value;
                var body = new FormData();
                body.append(_currentName, newValue);

                fetch(SAVE_ROUTE, {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body:    body,
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btn.disabled    = false;
                    txt.style.display  = 'inline';
                    load.style.display = 'none';

                    if (res.success) {
                        // Update local map so next open reflects the new value
                        if (TPL_MAP[_currentName]) TPL_MAP[_currentName].value = newValue;

                        // Update card preview
                        var preview = document.getElementById('lf-preview-' + _currentName);
                        if (preview) preview.textContent = newValue;

                        lfShowToast(res.message || 'Template salvo!');
                        lfCloseModal();
                    } else {
                        alert('Erro ao salvar: ' + (res.message || 'Tente novamente.'));
                    }
                })
                .catch(function (err) {
                    btn.disabled    = false;
                    txt.style.display  = 'inline';
                    load.style.display = 'none';
                    alert('Erro de conexão. Tente novamente.');
                    console.error('[WA-TPL]', err);
                });
            };

            /* ── Toast ───────────────────────────────────────── */
            function lfShowToast(msg) {
                var t = document.getElementById('lf-toast');
                t.textContent = '✅ ' + msg;
                t.classList.add('show');
                setTimeout(function () { t.classList.remove('show'); }, 3000);
            }

            /* ── ESC ─────────────────────────────────────────── */
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') lfCloseModal();
            });

        })();
        </script>
    @endpush
</x-admin::layouts>
