<x-admin::layouts>
    <x-slot:title>Templates WhatsApp</x-slot>

    @push('styles')
        <style>
            /* ── Filter bar (mesmo padrão dos Assistentes) ── */
            .lf-area-btn {
                display: inline-flex;
                align-items: center;
                gap: .3rem;
                padding: .35rem .85rem;
                font-size: .8rem;
                font-weight: 500;
                border-radius: .5rem;
                border: 1px solid transparent;
                background: transparent;
                color: #6b7280;
                cursor: pointer;
                transition: all 150ms;
                white-space: nowrap;
            }
            .lf-area-btn:hover { background: #f3f4f6; color: #111827; }
            .lf-area-btn.active {
                font-weight: 600;
                background: linear-gradient(135deg, #25d366, #128c7e);
                border-color: transparent;
                color: #fff;
                box-shadow: 0 2px 8px rgba(37, 211, 102, .3);
            }
            .dark .lf-area-btn { color: #9ca3af; }
            .dark .lf-area-btn:hover { background: #1f2937; color: #f9fafb; }
            .dark .lf-area-btn.active { background: linear-gradient(135deg, #25d366, #128c7e); color: #fff; }

            /* ── Category label ── */
            .lf-cat-label {
                font-size: .7rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #9ca3af;
                display: flex;
                align-items: center;
                gap: .5rem;
                padding-bottom: .5rem;
                margin-bottom: .75rem;
                border-bottom: 1px solid #e5e7eb;
            }
            .dark .lf-cat-label { border-color: #374151; }
            .lf-cat-label::before {
                content: '';
                width: 3px;
                height: .9rem;
                border-radius: 2px;
                background: var(--cat-color, #25d366);
                flex-shrink: 0;
                display: block;
            }

            /* ── Template card ── */
            .lf-tpl-card {
                position: relative;
                overflow: hidden;
                transition: border-color .15s, box-shadow .15s, transform .18s;
            }
            .lf-tpl-card::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 3px;
                background: var(--cat-color, #25d366);
                transition: height .18s;
            }
            .lf-tpl-card:hover { border-color: #86efac !important; box-shadow: 0 4px 16px rgba(37,211,102,.12); transform: translateY(-2px); }
            .lf-tpl-card:hover::before { height: 4px; }
            .dark .lf-tpl-card:hover { border-color: #25d366 !important; }

            /* ── Textarea ── */
            .lf-tpl-textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: .8rem;
                line-height: 1.6;
                color: #1f2937;
                background: #f9fafb;
                font-family: 'Courier New', monospace;
                box-sizing: border-box;
                resize: vertical;
                min-height: 90px;
                transition: border-color .2s, box-shadow .2s;
            }
            .dark .lf-tpl-textarea { background: #111827; border-color: #374151; color: #e5e7eb; }
            .lf-tpl-textarea:focus { outline: none; border-color: #25d366; box-shadow: 0 0 0 2px rgba(37,211,102,.15); }

            /* ── Info tip ── */
            .lf-tpl-info {
                font-size: .72rem;
                color: #9ca3af;
                margin-top: 6px;
                line-height: 1.4;
            }
            .lf-tpl-vars {
                font-size: .68rem;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                margin-top: 6px;
            }
            .lf-var-badge {
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                color: #166534;
                border-radius: 4px;
                padding: 1px 6px;
                font-family: monospace;
                cursor: pointer;
                transition: background .15s;
            }
            .lf-var-badge:hover { background: #dcfce7; }
            .dark .lf-var-badge { background: rgba(22,163,74,.15); border-color: rgba(22,163,74,.3); color: #4ade80; }

            /* ── Save bar ── */
            .lf-save-bar {
                position: sticky;
                bottom: 1rem;
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                padding: 12px 16px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                box-shadow: 0 4px 24px rgba(0,0,0,.08);
                z-index: 20;
            }
            .dark .lf-save-bar { background: #111827; border-color: #374151; }

            .lf-btn-save {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                padding: .45rem 1.1rem;
                font-weight: 600;
                font-size: .8125rem;
                border-radius: .375rem;
                background: linear-gradient(135deg, #25d366, #128c7e);
                color: #fff;
                border: none;
                cursor: pointer;
                transition: all .15s;
            }
            .lf-btn-save:hover { opacity: .9; transform: translateY(-1px); }
            .lf-btn-save:disabled { opacity: .6; cursor: not-allowed; transform: none; }

            .lf-btn-reset {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                padding: .45rem 1.1rem;
                font-weight: 500;
                font-size: .8125rem;
                border-radius: .375rem;
                background: #f3f4f6;
                border: 1px solid #d1d5db;
                color: #374151;
                cursor: pointer;
                transition: all .15s;
            }
            .lf-btn-reset:hover { background: #e5e7eb; }
            .dark .lf-btn-reset { background: #374151; border-color: #4b5563; color: #e5e7eb; }

            /* ── Saved toast ── */
            #lf-toast {
                position: fixed;
                bottom: 80px;
                right: 24px;
                background: #166534;
                color: #fff;
                padding: 10px 20px;
                border-radius: 8px;
                font-size: .85rem;
                font-weight: 600;
                z-index: 9999;
                opacity: 0;
                transform: translateY(10px);
                transition: opacity .3s, transform .3s;
                pointer-events: none;
            }
            #lf-toast.show { opacity: 1; transform: translateY(0); }
        </style>
    @endpush

    <div class="flex flex-col gap-4">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" onclick="window.history.back()">
                    <span>&larr; Voltar</span>
                </div>
                <div class="text-xl font-bold dark:text-white">💬 Templates de Mensagens WhatsApp</div>
            </div>
        </div>

        {{-- INFO BAR --}}
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-900/20 dark:text-green-300">
            ✏️ Personalize os textos enviados automaticamente. Clique em uma variável <code>{...}</code> para copiá-la. Salve ao final.
        </div>

        {{-- FILTER BAR --}}
        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900 overflow-x-auto">
            <button type="button" class="lf-area-btn active" onclick="lfFilterGroup('todas', this)">Todas</button>
            @foreach($grouped as $key => $group)
                <span class="text-gray-200 dark:text-gray-700 select-none px-1">|</span>
                <button type="button" class="lf-area-btn" data-group="{{ $key }}"
                    onclick="lfFilterGroup('{{ $key }}', this)">
                    {{ $group['emoji'] }} {{ $group['label'] }}
                </button>
            @endforeach
        </div>

        {{-- FORM --}}
        <form id="lf-tpl-form">
            @csrf

            @foreach($grouped as $groupKey => $group)
                <div class="lf-group-section mb-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                     data-group="{{ $groupKey }}"
                     style="--cat-color: {{ $group['color'] }}">

                    {{-- Category label --}}
                    <div class="lf-cat-label" style="--cat-color: {{ $group['color'] }}">
                        {{ $group['emoji'] }} {{ $group['label'] }}
                        <span class="ml-1 text-xs font-normal normal-case text-gray-400">
                            ({{ count($group['templates']) }} templates)
                        </span>
                    </div>

                    {{-- Grid 2 cols --}}
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @foreach($group['templates'] as $tpl)
                            @php
                                // extrai as variáveis do texto {var}
                                preg_match_all('/\{([a-z_]+)\}/', $tpl['info'] . ' ' . $tpl['default'], $varMatches);
                                $vars = array_unique($varMatches[0]);
                            @endphp
                            <div class="lf-tpl-card rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                                 style="--cat-color: {{ $group['color'] }}">

                                {{-- Title --}}
                                <div class="mb-3 flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-snug">
                                        {{ $tpl['title'] }}
                                    </p>
                                </div>

                                {{-- Textarea --}}
                                <textarea
                                    class="lf-tpl-textarea"
                                    name="{{ $tpl['name'] }}"
                                    rows="{{ $tpl['rows'] }}"
                                    id="tpl-{{ $tpl['name'] }}"
                                    data-default="{{ htmlspecialchars($tpl['default']) }}"
                                >{{ $tpl['value'] }}</textarea>

                                {{-- Variable badges --}}
                                @if(!empty($vars))
                                    <div class="lf-tpl-vars">
                                        <span class="text-gray-400" style="font-size:.68rem;align-self:center;">Vars:</span>
                                        @foreach($vars as $var)
                                            <span class="lf-var-badge" onclick="lfCopyVar('{{ $var }}', this)" title="Clique para copiar">{{ $var }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Info --}}
                                @if($tpl['info'])
                                    <p class="lf-tpl-info">ℹ️ {{ $tpl['info'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- SAVE BAR --}}
            <div class="lf-save-bar">
                <button type="button" class="lf-btn-reset" onclick="lfResetGroup()">↩️ Restaurar Padrões</button>
                <button type="button" id="lf-btn-save" class="lf-btn-save" onclick="lfSaveTemplates()">
                    <span id="lf-save-text">💾 Salvar Templates</span>
                    <span id="lf-save-loading" style="display:none">⏳ Salvando...</span>
                </button>
            </div>
        </form>

    </div>

    {{-- Toast --}}
    <div id="lf-toast">✅ Templates salvos com sucesso!</div>

    @push('scripts')
        <script>
        (function () {
            'use strict';

            var SAVE_ROUTE = @json($saveRoute);
            var CSRF       = @json($csrfToken);

            /* ── Filter by group ── */
            window.lfFilterGroup = function (group, btn) {
                document.querySelectorAll('.lf-area-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');

                document.querySelectorAll('.lf-group-section').forEach(function (sec) {
                    if (group === 'todas' || sec.dataset.group === group) {
                        sec.style.display = '';
                    } else {
                        sec.style.display = 'none';
                    }
                });
            };

            /* ── Copy variable badge to clipboard ── */
            window.lfCopyVar = function (varName, el) {
                navigator.clipboard.writeText(varName).then(function () {
                    var orig = el.textContent;
                    el.textContent = '✓ copiado';
                    setTimeout(function () { el.textContent = orig; }, 1200);
                });
            };

            /* ── Reset visible group to defaults ── */
            window.lfResetGroup = function () {
                if (!confirm('Restaurar todos os templates visíveis para o texto padrão?')) return;
                document.querySelectorAll('.lf-group-section:not([style*="none"]) .lf-tpl-textarea').forEach(function (ta) {
                    ta.value = ta.dataset.default;
                });
            };

            /* ── Save all templates ── */
            window.lfSaveTemplates = function () {
                var btn     = document.getElementById('lf-btn-save');
                var saveText    = document.getElementById('lf-save-text');
                var saveLoading = document.getElementById('lf-save-loading');

                btn.disabled        = true;
                saveText.style.display    = 'none';
                saveLoading.style.display = 'inline';

                var form    = document.getElementById('lf-tpl-form');
                var data    = new FormData(form);

                fetch(SAVE_ROUTE, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN'   : CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: data,
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btn.disabled        = false;
                    saveText.style.display    = 'inline';
                    saveLoading.style.display = 'none';

                    if (res.success) {
                        lfShowToast(res.message || 'Salvo!');
                    } else {
                        alert('Erro ao salvar: ' + (res.message || 'Tente novamente.'));
                    }
                })
                .catch(function (err) {
                    btn.disabled        = false;
                    saveText.style.display    = 'inline';
                    saveLoading.style.display = 'none';
                    alert('Erro de conexão. Tente novamente.');
                    console.error(err);
                });
            };

            function lfShowToast(msg) {
                var t = document.getElementById('lf-toast');
                t.textContent = '✅ ' + msg;
                t.classList.add('show');
                setTimeout(function () { t.classList.remove('show'); }, 3000);
            }
        })();
        </script>
    @endpush
</x-admin::layouts>
