<?php

$file = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$content = file_get_contents($file);

$genOut = file_get_contents(__DIR__ . '/gen_out.txt');
$parts = explode("\n\n", $genOut);
$newAllCards = trim($parts[0]);
$newSvcInfo = trim($parts[1]);

// 1. Replace $allCards array
$content = preg_replace('/\$allCards\s*=\s*\[.*?(?= {20}\];)/s', '$allCards = ' . $newAllCards . "\n", $content);

// 2. Replace SVC_INFO JS dict
$content = preg_replace('/var SVC_INFO\s*=\s*\{.*?(?= {20}\};)/s', $newSvcInfo . "\n", $content);

// 3. Replace Filter Bar Categories
$newFilterBar = <<<HTML
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
HTML;
$content = preg_replace('/<div class="lf-area-filter-bar flex gap-2 w-full mt-2 lg:mt-0 flex-wrap">.*?<\/div>/s', $newFilterBar, $content, 1);

// 4. Inject Top Macro Buttons section right below the INFO BAR
$macroSection = <<<HTML
            {{-- ── ESCOLHA O TIPO DE MONITORAMENTO ──────────────────── --}}
            <div class="mb-4 mt-2">
                <h2 class="text-2xl font-bold mb-1 dark:text-white" style="color: #1f2937;">Escolha o tipo do monitoramento</h2>
                <p class="text-sm mb-4" style="color: #6b7280;">No Escavador os monitoramentos são categorizados para ajudar na sua organização.</p>
                <div class="flex flex-wrap gap-4">
                    <div onclick="window.openMacroModal('processo')" class="cursor-pointer flex flex-col items-center justify-center p-6 rounded-2xl shadow-sm hover:shadow-md transition-all" style="background-color: #facc15; width: 140px; height: 140px;">
                        <svg style="width: 48px; height: 48px; color: white;" viewBox="0 0 32 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15.5295 5.58824C16.7965 5.58824 17.8236 4.56113 17.8236 3.29412C17.8236 2.02711 16.7965 1 15.5295 1C14.2625 1 13.2354 2.02711 13.2354 3.29412C13.2354 4.56113 14.2625 5.58824 15.5295 5.58824Z"></path><path d="M15.5295 6.35291V27"></path><path d="M11.7059 27H19.353"></path><path d="M15.5294 6.34997C11.1119 4.58012 6.18228 4.58012 1.76471 6.34997"></path><path d="M29.2942 6.34997C24.8766 4.58012 19.9471 4.58012 15.5295 6.34997"></path><path d="M6.31319 9.18127L11.7059 18.4908C11.3319 19.3187 10.6042 20.0305 9.6361 20.5155C8.66799 21.0004 7.51375 21.2313 6.35295 21.1722C5.19215 21.2313 4.03791 21.0004 3.06981 20.5155C2.10171 20.0305 1.37403 19.3187 1 18.4908L6.31319 9.18127Z"></path><path d="M24.6662 9.18127L30.0589 18.4908C29.6848 19.3187 28.9572 20.0305 27.9891 20.5155C27.021 21.0004 25.8667 21.2313 24.7059 21.1722C23.5451 21.2313 22.3909 21.0004 21.4228 20.5155C20.4547 20.0305 19.727 19.3187 19.353 18.4908L24.6662 9.18127Z"></path>
                        </svg>
                        <span class="font-bold mt-4 text-white">Processo</span>
                    </div>
                    <div onclick="window.openMacroModal('pessoa')" class="cursor-pointer flex flex-col items-center justify-center p-6 rounded-2xl shadow-sm hover:shadow-md transition-all" style="background-color: #3b82f6; width: 140px; height: 140px;">
                        <svg style="width: 48px; height: 48px; color: white;" viewBox="0 0 26 28" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="13" cy="7" r="6"></circle>
                            <path d="M23.4285 23.6307C22.0023 24.5167 18.7615 26 13 26C7.23852 26 3.99774 24.5167 2.57147 23.6307" stroke-linecap="round"></path>
                        </svg>
                        <span class="font-bold mt-4 text-white">Pessoa</span>
                    </div>
                    <div onclick="window.openMacroModal('empresa')" class="cursor-pointer flex flex-col items-center justify-center p-6 rounded-2xl shadow-sm hover:shadow-md transition-all" style="background-color: #34d399; width: 140px; height: 140px;">
                        <svg style="width: 48px; height: 48px; color: white;" fill="none" viewBox="0 0 42 48" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M38 5v36c0 3.3-2.7 6-6 6H10c-3.3 0-6-2.7-6-6V5"></path>
                            <path d="M23 11h-4v8h4v-8zM23 25h-4v8h4v-8zM14 11h-4v8h4v-8zM32 11h-4v8h4v-8zM14 25h-4v8h4v-8zM32 25h-4v8h4v-8z"></path>
                            <path d="M17 47v-8h8v8M40 1H2v4h38V1z"></path>
                        </svg>
                        <span class="font-bold mt-4 text-white">Empresa</span>
                    </div>
                    <div onclick="window.openMacroModal('advogado')" class="cursor-pointer flex flex-col items-center justify-center p-6 rounded-2xl shadow-sm hover:shadow-md transition-all" style="background-color: #1f2937; width: 140px; height: 140px;">
                        <svg style="width: 48px; height: 48px; color: white;" viewBox="0 0 22 19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6.25 3C6.25 1.48122 7.48122 0.25 9 0.25H13C14.5188 0.25 15.75 1.48122 15.75 3H19C20.6569 3 22 4.34315 22 6V16C22 17.6569 20.6569 19 19 19H3C1.34315 19 0 17.6569 0 16V6C0 4.34315 1.34315 3 3 3H6.25ZM13 1.75C13.6904 1.75 14.25 2.30964 14.25 3H7.75C7.75 2.30964 8.30964 1.75 9 1.75H13ZM3 4.5H19C19.8284 4.5 20.5 5.17157 20.5 6V6.43138L16.2294 9.25L5.77064 9.25L1.5 6.43138V6C1.5 5.17157 2.17157 4.5 3 4.5ZM1.5 8.22862V16C1.5 16.8284 2.17157 17.5 3 17.5H19C19.8284 17.5 20.5 16.8284 20.5 16V8.22863L16.8677 10.626C16.7451 10.7069 16.6014 10.75 16.4545 10.75L5.54545 10.75C5.39857 10.75 5.25492 10.7069 5.13232 10.626L1.5 8.22862ZM9.75 11.5C9.33579 11.5 9 11.8358 9 12.25C9 12.6642 9.33579 13 9.75 13H12.25C12.6642 13 13 12.6642 13 12.25C13 11.8358 12.6642 11.5 12.25 11.5H9.75Z"></path>
                        </svg>
                        <span class="font-bold mt-4 text-white">Advogado(a)</span>
                    </div>
                    <div onclick="window.openMacroModal('outro')" class="cursor-pointer flex flex-col items-center justify-center p-6 rounded-2xl shadow-sm hover:shadow-md transition-all" style="background-color: #9ca3af; width: 140px; height: 140px;">
                        <svg style="width: 48px; height: 48px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span class="font-bold mt-4 text-white">Outro</span>
                    </div>
                </div>
            </div>
            
HTML;

$content = str_replace(
    '{{-- ── AREA FILTER BAR ── --}}', 
    $macroSection . "\n            {{-- ── AREA FILTER BAR ── --}}", 
    $content
);

// Read HTML templates for modals
$modals = file_get_contents(__DIR__ . '/escavador_html.txt');

// 5. Append the global Modal container and templates for the Modals at the end of the file right before </x-admin::layouts>
$modalHtml = <<<HTML

        {{-- ── MACRO MODAL (NOVO) ────────────────────────────────────── --}}
        <div id="lf-macro-modal" style="display:none;" v-pre>
            <div class="lf-esc-overlay" onclick="window.closeMacroModal()"></div>
            <div class="lf-esc-dialog" style="max-width:640px;width:95%;">
                <div class="lf-esc-modal-header" style="background:#fff;border-bottom:1px solid #f3f4f6;">
                    <h3 class="lf-esc-modal-title" style="color:#1f2937;">Monitorar</h3>
                    <button onclick="window.closeMacroModal()" class="lf-esc-close-btn">✕</button>
                </div>
                <div id="lf-macro-modal-content" style="padding:24px;">
                    <!-- content injected via JS -->
                </div>
            </div>
        </div>

        <template id="tpl-macro">
$modals
        </template>
        
        <script>
        function openMacroModal(type) {
            var m = document.getElementById('lf-macro-modal');
            var c = document.getElementById('lf-macro-modal-content');
            var t = document.getElementById('tpl-macro');
            
            // Simple parsing to extract the correct div based on the ID we'll assign
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = t.innerHTML;
            
            var targetContent = tempDiv.querySelector('#macro-html-' + type);
            if(targetContent) {
                c.innerHTML = targetContent.innerHTML;
            } else {
                c.innerHTML = '<p>Configuração para ' + type + ' em breve.</p>';
            }
            m.style.display = 'block';
        }
        function closeMacroModal() {
            document.getElementById('lf-macro-modal').style.display = 'none';
        }
        </script>
        
HTML;

$content = preg_replace('/<\/x-admin::layouts>/i', $modalHtml . "\n</x-admin::layouts>", $content, -1, $count);

file_put_contents($file, $content);
echo "Patched successfully! Modified layouts tag: $count times.\n";
