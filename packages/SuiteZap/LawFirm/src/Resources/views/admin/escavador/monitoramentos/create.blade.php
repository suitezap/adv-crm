<x-admin::layouts>
    <x-slot:title>
        Criar Novo Monitoramento
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    Novo Monitoramento
                </div>
                <div class="text-sm text-gray-500">
                    Crie um novo robô para acompanhamento ativo de Termos, Processos, Pessoas ou Empresas.
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('lawfirm.escavador.monitoramentos.index') }}" class="text-blue-600 hover:underline text-sm font-semibold">
                    ← Voltar para Monitoramentos
                </a>
            </div>
        </div>

        <div class="page-content">
            {{-- ── ESCOLHA O TIPO DE MONITORAMENTO ─────────────────────────── --}}
            <div class="w-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 mb-4"
                 style="padding: 32px 24px;">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold mb-1 dark:text-white" style="color: #1f2937;">Escolha o tipo do monitoramento</h2>
                    <p class="text-sm" style="color: #6b7280;">No Escavador os monitoramentos são categorizados para ajudar na sua organização.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-5">

                    {{-- Processo --}}
                    <div onclick="window.openMacroModal('processo')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #facc15; width: 120px; height: 130px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 32 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15.5295 5.58824C16.7965 5.58824 17.8236 4.56113 17.8236 3.29412C17.8236 2.02711 16.7965 1 15.5295 1C14.2625 1 13.2354 2.02711 13.2354 3.29412C13.2354 4.56113 14.2625 5.58824 15.5295 5.58824Z"></path><path d="M15.5295 6.35291V27"></path><path d="M11.7059 27H19.353"></path><path d="M15.5294 6.34997C11.1119 4.58012 6.18228 4.58012 1.76471 6.34997"></path><path d="M29.2942 6.34997C24.8766 4.58012 19.9471 4.58012 15.5295 6.34997"></path><path d="M6.31319 9.18127L11.7059 18.4908C11.3319 19.3187 10.6042 20.0305 9.6361 20.5155C8.66799 21.0004 7.51375 21.2313 6.35295 21.1722C5.19215 21.2313 4.03791 21.0004 3.06981 20.5155C2.10171 20.0305 1.37403 19.3187 1 18.4908L6.31319 9.18127Z"></path><path d="M24.6662 9.18127L30.0589 18.4908C29.6848 19.3187 28.9572 20.0305 27.9891 20.5155C27.021 21.0004 25.8667 21.2313 24.7059 21.1722C23.5451 21.2313 22.3909 21.0004 21.4228 20.5155C20.4547 20.0305 19.727 19.3187 19.353 18.4908L24.6662 9.18127Z"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Processo</span>
                    </div>

                    {{-- Pessoa --}}
                    <div onclick="window.openMacroModal('pessoa')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #3b82f6; width: 120px; height: 130px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 26 28" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="13" cy="7" r="6"></circle>
                            <path d="M23.4285 23.6307C22.0023 24.5167 18.7615 26 13 26C7.23852 26 3.99774 24.5167 2.57147 23.6307" stroke-linecap="round"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Pessoa</span>
                    </div>

                    {{-- Empresa --}}
                    <div onclick="window.openMacroModal('empresa')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #34d399; width: 120px; height: 130px;">
                        <svg style="width: 36px; height: 36px; color: white;" fill="none" viewBox="0 0 42 48" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M38 5v36c0 3.3-2.7 6-6 6H10c-3.3 0-6-2.7-6-6V5"></path>
                            <path d="M23 11h-4v8h4v-8zM23 25h-4v8h4v-8zM14 11h-4v8h4v-8zM32 11h-4v8h4v-8zM14 25h-4v8h4v-8zM32 25h-4v8h4v-8z"></path>
                            <path d="M17 47v-8h8v8M40 1H2v4h38V1z"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Empresa</span>
                    </div>

                    {{-- Advogado(a) --}}
                    <div onclick="window.openMacroModal('advogado')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #6366f1; width: 120px; height: 130px;">
                        <svg style="width: 36px; height: 36px; color: white;" fill="none" viewBox="0 0 22 19">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.25 3C6.25 1.48122 7.48122 0.25 9 0.25H13C14.5188 0.25 15.75 1.48122 15.75 3H19C20.6569 3 22 4.34315 22 6V16C22 17.6569 20.6569 19 19 19H3C1.34315 19 0 17.6569 0 16V6C0 4.34315 1.34315 3 3 3H6.25ZM13 1.75C13.6904 1.75 14.25 2.30964 14.25 3H7.75C7.75 2.30964 8.30964 1.75 9 1.75H13ZM3 4.5H19C19.8284 4.5 20.5 5.17157 20.5 6V6.43138L16.2294 9.25L5.77064 9.25L1.5 6.43138V6C1.5 5.17157 2.17157 4.5 3 4.5ZM1.5 8.22862V16C1.5 16.8284 2.17157 17.5 3 17.5H19C19.8284 17.5 20.5 16.8284 20.5 16V8.22863L16.8677 10.626C16.7451 10.7069 16.6014 10.75 16.4545 10.75L5.54545 10.75C5.39857 10.75 5.25492 10.7069 5.13232 10.626L1.5 8.22862ZM9.75 11.5C9.33579 11.5 9 11.8358 9 12.25C9 12.6642 9.33579 13 9.75 13H12.25C12.6642 13 13 12.6642 13 12.25C13 11.8358 12.6642 11.5 12.25 11.5H9.75Z" fill="white"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Advogado</span>
                    </div>

                    {{-- Outro --}}
                    <div onclick="window.openMacroModal('outro')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #64748b; width: 120px; height: 130px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Outro</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── MACRO MODAL ─────────────────────────────────────────────── --}}
    <div id="lf-macro-modal" style="display:none;">
        <div class="lf-esc-overlay" onclick="window.closeMacroModal()"></div>
        <div class="lf-esc-dialog">
            <div class="lf-esc-modal-header">
                <h3 class="lf-esc-modal-title">Monitorar</h3>
                <button onclick="window.closeMacroModal()" class="lf-esc-close-btn">✕</button>
            </div>
            <div style="padding:24px;">

                {{-- PROCESSO --}}
                <div id="macro-html-processo" class="macro-form-panel" style="display:none;">
                    <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 4px;">Dados do Processo</h2>
                    <p style="font-size:.875rem;color:#6b7280;margin:0 0 16px;">Informe o número CNJ do processo a ser monitorado.</p>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Número do processo (CNJ)</label>
                        <input type="text" id="lf-numero" placeholder="0000000-00.0000.0.00.0000" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Nome do processo (opcional)</label>
                        <input type="text" id="lf-nome" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Frequência de Monitoramento</label>
                        <select id="lf-freq-processo" class="lf-esc-select" onchange="updateModalPrice('processo', this)">
                            <option value="diario" data-price="Ƶ 22,00/mês">Diário (Ƶ 22,00/mês)</option>
                            <option value="semanal" data-price="Ƶ 10,63/mês">Semanal (Ƶ 10,63/mês)</option>
                            <option value="mensal" data-price="Ƶ 5,63/mês">Mensal (Ƶ 5,63/mês)</option>
                        </select>
                    </div>
                    <div class="macro-msg-error" style="display:none;color:#dc2626;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;"></div>
                    <div class="macro-msg-success" style="display:none;color:#059669;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#ecfdf5;border-radius:6px;border:1px solid #a7f3d0;"></div>
                    <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                        <button onclick="submitMonitoramento('processo')" class="lf-esc-btn macro-submit-btn">✅ Finalizar (<span class="btn-price-display">Ƶ 22,00/mês</span>)</button>
                    </div>
                </div>

                {{-- PESSOA --}}
                <div id="macro-html-pessoa" class="macro-form-panel" style="display:none;">
                    <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 4px;">Dados da Pessoa</h2>
                    <p style="font-size:.875rem;color:#6b7280;margin:0 0 16px;">Informe o nome completo da pessoa a ser monitorada.</p>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Nome completo</label>
                        <input type="text" id="lf-termo-pessoa" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">CPF (opcional)</label>
                        <input type="text" id="lf-cpf" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Frequência de Monitoramento</label>
                        <select id="lf-freq-pessoa" class="lf-esc-select" onchange="updateModalPrice('pessoa', this)">
                            <option value="diario" data-price="Ƶ 22,00/mês">Diário (Ƶ 22,00/mês)</option>
                            <option value="semanal" data-price="Ƶ 10,63/mês">Semanal (Ƶ 10,63/mês)</option>
                            <option value="mensal" data-price="Ƶ 5,63/mês">Mensal (Ƶ 5,63/mês)</option>
                        </select>
                    </div>
                    <div class="macro-msg-error" style="display:none;color:#dc2626;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;"></div>
                    <div class="macro-msg-success" style="display:none;color:#059669;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#ecfdf5;border-radius:6px;border:1px solid #a7f3d0;"></div>
                    <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                        <button onclick="submitMonitoramento('pessoa')" class="lf-esc-btn macro-submit-btn">✅ Finalizar (<span class="btn-price-display">Ƶ 22,00/mês</span>)</button>
                    </div>
                </div>

                {{-- EMPRESA --}}
                <div id="macro-html-empresa" class="macro-form-panel" style="display:none;">
                    <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 4px;">Dados da Empresa</h2>
                    <p style="font-size:.875rem;color:#6b7280;margin:0 0 16px;">Informe o nome da empresa a ser monitorada.</p>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Nome da empresa</label>
                        <input type="text" id="lf-termo-empresa" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">CNPJ (opcional)</label>
                        <input type="text" id="lf-cnpj" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Frequência de Monitoramento</label>
                        <select id="lf-freq-empresa" class="lf-esc-select" onchange="updateModalPrice('empresa', this)">
                            <option value="diario" data-price="Ƶ 22,00/mês">Diário (Ƶ 22,00/mês)</option>
                            <option value="semanal" data-price="Ƶ 10,63/mês">Semanal (Ƶ 10,63/mês)</option>
                            <option value="mensal" data-price="Ƶ 5,63/mês">Mensal (Ƶ 5,63/mês)</option>
                        </select>
                    </div>
                    <div class="macro-msg-error" style="display:none;color:#dc2626;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;"></div>
                    <div class="macro-msg-success" style="display:none;color:#059669;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#ecfdf5;border-radius:6px;border:1px solid #a7f3d0;"></div>
                    <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                        <button onclick="submitMonitoramento('empresa')" class="lf-esc-btn macro-submit-btn">✅ Finalizar (<span class="btn-price-display">Ƶ 22,00/mês</span>)</button>
                    </div>
                </div>

                {{-- ADVOGADO --}}
                <div id="macro-html-advogado" class="macro-form-panel" style="display:none;">
                    <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 4px;">Dados do(a) Advogado(a)</h2>
                    <p style="font-size:.875rem;color:#6b7280;margin:0 0 16px;">Monitore pelo nome completo e/ou número da OAB.</p>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Nome completo</label>
                        <input type="text" id="lf-termo-adv" class="lf-esc-input">
                    </div>
                    <div style="display:flex;gap:12px;margin-bottom:14px;">
                        <div style="flex:1;">
                            <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">OAB</label>
                            <input type="text" id="lf-oab" class="lf-esc-input">
                        </div>
                        <div style="width:120px;">
                            <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">UF</label>
                            <select id="lf-uf" class="lf-esc-select">
                                <option value="">UF</option>
                                <option>AC</option><option>AL</option><option>AP</option><option>AM</option><option>BA</option><option>CE</option><option>DF</option><option>ES</option><option>GO</option><option>MA</option><option>MT</option><option>MS</option><option>MG</option><option>PA</option><option>PB</option><option>PR</option><option>PE</option><option>PI</option><option>RJ</option><option>RN</option><option>RS</option><option>RO</option><option>RR</option><option>SC</option><option>SP</option><option>SE</option><option>TO</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Frequência de Monitoramento</label>
                        <select id="lf-freq-advogado" class="lf-esc-select" onchange="updateModalPrice('advogado', this)">
                            <option value="diario" data-price="Ƶ 22,00/mês">Diário (Ƶ 22,00/mês)</option>
                            <option value="semanal" data-price="Ƶ 10,63/mês">Semanal (Ƶ 10,63/mês)</option>
                            <option value="mensal" data-price="Ƶ 5,63/mês">Mensal (Ƶ 5,63/mês)</option>
                        </select>
                    </div>
                    <div class="macro-msg-error" style="display:none;color:#dc2626;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;"></div>
                    <div class="macro-msg-success" style="display:none;color:#059669;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#ecfdf5;border-radius:6px;border:1px solid #a7f3d0;"></div>
                    <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                        <button onclick="submitMonitoramento('advogado')" class="lf-esc-btn macro-submit-btn">✅ Finalizar (<span class="btn-price-display">Ƶ 22,00/mês</span>)</button>
                    </div>
                </div>

                {{-- OUTRO --}}
                <div id="macro-html-outro" class="macro-form-panel" style="display:none;">
                    <h2 style="font-size:1.2rem;font-weight:700;margin:0 0 4px;">Termo Livre / Outro</h2>
                    <p style="font-size:.875rem;color:#6b7280;margin:0 0 16px;">Vigie qualquer termo em novas autuações dos Diários Oficiais.</p>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Termo a ser monitorado</label>
                        <input type="text" id="lf-termo-outro" class="lf-esc-input">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:4px;">Frequência de Monitoramento</label>
                        <select id="lf-freq-outro" class="lf-esc-select" onchange="updateModalPrice('outro', this)">
                            <option value="diario" data-price="Ƶ 22,00/mês">Diário (Ƶ 22,00/mês)</option>
                            <option value="semanal" data-price="Ƶ 10,63/mês">Semanal (Ƶ 10,63/mês)</option>
                            <option value="mensal" data-price="Ƶ 5,63/mês">Mensal (Ƶ 5,63/mês)</option>
                        </select>
                    </div>
                    <div class="macro-msg-error" style="display:none;color:#dc2626;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#fef2f2;border-radius:6px;border:1px solid #fecaca;"></div>
                    <div class="macro-msg-success" style="display:none;color:#059669;font-size:.875rem;margin-top:8px;padding:8px 12px;background:#ecfdf5;border-radius:6px;border:1px solid #a7f3d0;"></div>
                    <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                        <button onclick="submitMonitoramento('outro')" class="lf-esc-btn macro-submit-btn">✅ Finalizar (<span class="btn-price-display">Ƶ 22,00/mês</span>)</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    var CSRF_TOKEN = '{{ csrf_token() }}';
    var ROUTE_EXECUTAR = "{{ route('lawfirm.escavador.servico') }}";
    var ROUTE_INDEX = "{{ route('lawfirm.escavador.monitoramentos.index') }}";

    function openMacroModal(tipo) {
        var modal = document.getElementById('lf-macro-modal');
        if (!modal) return;
        modal.style.display = 'block';

        document.querySelectorAll('.macro-form-panel').forEach(function(el) {
            el.style.display = 'none';
        });

        var panel = document.getElementById('macro-html-' + tipo);
        if (panel) {
            panel.style.display = 'block';
            // Reset the frequency select to daily and update price button
            var freqSelect = panel.querySelector('.lf-esc-select[id^="lf-freq-"]');
            if (freqSelect) {
                freqSelect.value = 'diario';
                updateModalPrice(tipo, freqSelect);
            }
        }

        document.querySelectorAll('.macro-msg-success,.macro-msg-error').forEach(function(el){
            el.style.display = 'none'; el.textContent = '';
        });
    }

    function updateModalPrice(tipo, selectElem) {
        var panel = document.getElementById('macro-html-' + tipo);
        if (!panel) return;
        var btnPriceSpan = panel.querySelector('.btn-price-display');
        if (btnPriceSpan) {
            var selectedOption = selectElem.options[selectElem.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                btnPriceSpan.textContent = selectedOption.dataset.price;
            }
        }
    }

    function closeMacroModal() {
        var modal = document.getElementById('lf-macro-modal');
        if (!modal) return;
        modal.style.display = 'none';
        document.querySelectorAll('.macro-form-panel input, .macro-form-panel select').forEach(function(inp) {
            inp.value = '';
        });
        document.querySelectorAll('.macro-msg-success,.macro-msg-error').forEach(function(el){
            el.style.display = 'none'; el.textContent = '';
        });
    }

    function submitMonitoramento(tipo) {
        var serviceType, data;
        var errorMsg = '';

        if (tipo === 'processo') {
            var numero = (document.getElementById('lf-numero').value || '').trim();
            var nome   = (document.getElementById('lf-nome').value || '').trim();
            if (!numero) { errorMsg = 'Informe o número do processo.'; }
            serviceType = 'CRIAR_MON_PROCESSO_V2';
            data = { numero_cnj: numero };
            if (nome) data.nome = nome;

        } else if (tipo === 'pessoa') {
            var termo = (document.getElementById('lf-termo-pessoa').value || '').trim();
            var cpf   = (document.getElementById('lf-cpf').value || '').trim();
            if (!termo) { errorMsg = 'Informe o nome da pessoa.'; }
            serviceType = 'CRIAR_MON_NOVOS_PROCESSO_V2';
            data = { tipo: 'pessoa', termo: termo };
            if (cpf) data.cpf = cpf;

        } else if (tipo === 'empresa') {
            var termo = (document.getElementById('lf-termo-empresa').value || '').trim();
            var cnpj  = (document.getElementById('lf-cnpj').value || '').trim();
            if (!termo) { errorMsg = 'Informe o nome da empresa.'; }
            serviceType = 'CRIAR_MON_NOVOS_PROCESSO_V2';
            data = { tipo: 'empresa', termo: termo };
            if (cnpj) data.cnpj = cnpj;

        } else if (tipo === 'advogado') {
            var termo = (document.getElementById('lf-termo-adv').value || '').trim();
            var oab   = (document.getElementById('lf-oab').value || '').trim();
            var uf    = (document.getElementById('lf-uf').value || '').trim();
            if (!termo && !oab) { errorMsg = 'Informe o nome ou o número da OAB do(a) advogado(a).'; }
            serviceType = 'CRIAR_MON_DIARIOS';
            data = { tipo: 'termo', termo: termo || oab };
            if (oab) { data.oab = oab; data.estado_oab = uf; }

        } else if (tipo === 'outro') {
            var termo = (document.getElementById('lf-termo-outro').value || '').trim();
            if (!termo) { errorMsg = 'Informe o termo a ser monitorado.'; }
            serviceType = 'CRIAR_MON_DIARIOS';
            data = { tipo: 'termo', termo: termo };
        }

        // Add frequency
        var freqSelect = document.getElementById('lf-freq-' + tipo);
        if (freqSelect && data) {
            data.frequencia = freqSelect.value;
        }

        var panel  = document.getElementById('macro-html-' + tipo);
        var msgErr = panel.querySelector('.macro-msg-error');
        var msgOk  = panel.querySelector('.macro-msg-success');

        if (errorMsg) {
            if (msgErr) { msgErr.textContent = errorMsg; msgErr.style.display = 'block'; }
            return;
        }
        if (msgErr) msgErr.style.display = 'none';

        var btn = panel.querySelector('.macro-submit-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Enviando...';

        fetch(ROUTE_EXECUTAR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ service_type: serviceType, data: data })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            btn.disabled = false;
            btn.textContent = '✅ Finalizar';
            if (d.success) {
                if (msgOk) { msgOk.textContent = 'Monitoramento criado com sucesso! Redirecionando...'; msgOk.style.display = 'block'; }
                setTimeout(function() { window.location.href = ROUTE_INDEX; }, 2000);
            } else {
                if (msgErr) { msgErr.textContent = d.error || 'Erro ao criar monitoramento.'; msgErr.style.display = 'block'; }
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = '✅ Finalizar';
            if (msgErr) { msgErr.textContent = 'Erro de conexão. Tente novamente.'; msgErr.style.display = 'block'; }
        });
    }

    window.openMacroModal = openMacroModal;
    window.closeMacroModal = closeMacroModal;
    </script>
    @endpush

    @push('styles')
    <style>
        .lf-esc-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9000; }
        .lf-esc-dialog  { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 12px; z-index: 9001; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); width: 95%; max-width: 580px; }
        .dark .lf-esc-dialog { background: #1f2937; color: #e5e7eb; }
        .lf-esc-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; border-bottom: 1px solid #f3f4f6; }
        .dark .lf-esc-modal-header { border-bottom-color: #374151; }
        .lf-esc-modal-title { font-size: 1.125rem; font-weight: 700; margin: 0; color: #1f2937; }
        .dark .lf-esc-modal-title { color: #f9fafb; }
        .lf-esc-close-btn { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280; padding: 4px; }
        .lf-esc-close-btn:hover { color: #374151; }
        .lf-esc-btn { background: #0d9488; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .lf-esc-btn:hover { background: #0f766e; }
        .lf-esc-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .lf-esc-input, .lf-esc-select { border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 14px; width: 100%; font-size: 0.95rem; box-sizing: border-box; }
        .lf-esc-input:focus, .lf-esc-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
        .dark .lf-esc-input, .dark .lf-esc-select { background: #374151; border-color: #4b5563; color: #e5e7eb; }
    </style>
    @endpush

</x-admin::layouts>
