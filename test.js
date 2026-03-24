var LEAD_TITLE = "Justa causa";
            var LEAD_DESC = "";
            var TENANT_ID = "lawfirm_tenant_1";
            var LEAD_ID = 4;
            var CSRF_TOKEN = null;

            var ROUTES = {
                generate: "http://adv-crm.test/admin/juridico/assistants/__SLUG__/generate",
                execute: "http://adv-crm.test/admin/juridico/assistants/__SLUG__/execute",
                status: "http://adv-crm.test/admin/juridico/assistants/status/__ID__",
                saveNote: "http://adv-crm.test/admin/activities/create",
                stageUpdate: "http://adv-crm.test/admin/leads/stage/edit/4"
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
                        activeStageId = stageId || null;
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
                        var copyBtn = document.getElementById('lf-copy-btn');
                        try {
                            // Fallback for HTTP: use a temporary textarea + execCommand
                            var ta = document.createElement('textarea');
                            ta.value = rawResult;
                            ta.style.position = 'fixed';
                            ta.style.left = '-9999px';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);

                            copyBtn.textContent = '✅ Copiado!';
                            setTimeout(function () { copyBtn.textContent = '📋 Copiar'; }, 1500);
                        } catch (e) {
                            console.error('Copy failed:', e);
                            alert('Não foi possível copiar. Use Ctrl+C manualmente.');
                        }
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
                    fetch(ROUTES.stageUpdate, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({ lead_pipeline_stage_id: stageId })
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

            })();
    