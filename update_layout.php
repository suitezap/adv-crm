<?php

$file = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$content = file_get_contents($file);

$newHtml = <<<'HTML'
            {{-- ── ESCOLHA O TIPO DE MONITORAMENTO (CARD) ─────────────────────────── --}}
            <div class="w-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 mb-4"
                 style="padding: 32px 24px;">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold mb-1 dark:text-white" style="color: #1f2937;">Escolha o tipo do monitoramento</h2>
                    <p class="text-sm" style="color: #6b7280;">No Escavador os monitoramentos são categorizados para ajudar na sua organização.</p>
                </div>
                <div class="flex flex-wrap justify-center gap-5">
                    <div onclick="window.openMacroModal('processo')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #facc15; width: 110px; height: 110px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 32 28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15.5295 5.58824C16.7965 5.58824 17.8236 4.56113 17.8236 3.29412C17.8236 2.02711 16.7965 1 15.5295 1C14.2625 1 13.2354 2.02711 13.2354 3.29412C13.2354 4.56113 14.2625 5.58824 15.5295 5.58824Z"></path><path d="M15.5295 6.35291V27"></path><path d="M11.7059 27H19.353"></path><path d="M15.5294 6.34997C11.1119 4.58012 6.18228 4.58012 1.76471 6.34997"></path><path d="M29.2942 6.34997C24.8766 4.58012 19.9471 4.58012 15.5295 6.34997"></path><path d="M6.31319 9.18127L11.7059 18.4908C11.3319 19.3187 10.6042 20.0305 9.6361 20.5155C8.66799 21.0004 7.51375 21.2313 6.35295 21.1722C5.19215 21.2313 4.03791 21.0004 3.06981 20.5155C2.10171 20.0305 1.37403 19.3187 1 18.4908L6.31319 9.18127Z"></path><path d="M24.6662 9.18127L30.0589 18.4908C29.6848 19.3187 28.9572 20.0305 27.9891 20.5155C27.021 21.0004 25.8667 21.2313 24.7059 21.1722C23.5451 21.2313 22.3909 21.0004 21.4228 20.5155C20.4547 20.0305 19.727 19.3187 19.353 18.4908L24.6662 9.18127Z"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Processo</span>
                    </div>
                    <div onclick="window.openMacroModal('pessoa')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #3b82f6; width: 110px; height: 110px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 26 28" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="13" cy="7" r="6"></circle>
                            <path d="M23.4285 23.6307C22.0023 24.5167 18.7615 26 13 26C7.23852 26 3.99774 24.5167 2.57147 23.6307" stroke-linecap="round"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Pessoa</span>
                    </div>
                    <div onclick="window.openMacroModal('empresa')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #34d399; width: 110px; height: 110px;">
                        <svg style="width: 36px; height: 36px; color: white;" fill="none" viewBox="0 0 42 48" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M38 5v36c0 3.3-2.7 6-6 6H10c-3.3 0-6-2.7-6-6V5"></path>
                            <path d="M23 11h-4v8h4v-8zM23 25h-4v8h4v-8zM14 11h-4v8h4v-8zM32 11h-4v8h4v-8zM14 25h-4v8h4v-8zM32 25h-4v8h4v-8z"></path>
                            <path d="M17 47v-8h8v8M40 1H2v4h38V1z"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Empresa</span>
                    </div>
                    <div onclick="window.openMacroModal('advogado')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #1f2937; width: 110px; height: 110px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 22 19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6.25 3C6.25 1.48122 7.48122 0.25 9 0.25H13C14.5188 0.25 15.75 1.48122 15.75 3H19C20.6569 3 22 4.34315 22 6V16C22 17.6569 20.6569 19 19 19H3C1.34315 19 0 17.6569 0 16V6C0 4.34315 1.34315 3 3 3H6.25ZM13 1.75C13.6904 1.75 14.25 2.30964 14.25 3H7.75C7.75 2.30964 8.30964 1.75 9 1.75H13ZM3 4.5H19C19.8284 4.5 20.5 5.17157 20.5 6V6.43138L16.2294 9.25L5.77064 9.25L1.5 6.43138V6C1.5 5.17157 2.17157 4.5 3 4.5ZM1.5 8.22862V16C1.5 16.8284 2.17157 17.5 3 17.5H19C19.8284 17.5 20.5 16.8284 20.5 16V8.22863L16.8677 10.626C16.7451 10.7069 16.6014 10.75 16.4545 10.75L5.54545 10.75C5.39857 10.75 5.25492 10.7069 5.13232 10.626L1.5 8.22862ZM9.75 11.5C9.33579 11.5 9 11.8358 9 12.25C9 12.6642 9.33579 13 9.75 13H12.25C12.6642 13 13 12.6642 13 12.25C13 11.8358 12.6642 11.5 12.25 11.5H9.75Z"></path>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Advogado(a)</span>
                    </div>
                    <div onclick="window.openMacroModal('outro')" class="cursor-pointer flex flex-col items-center justify-center p-4 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all" style="background-color: #9ca3af; width: 110px; height: 110px;">
                        <svg style="width: 36px; height: 36px; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                        </svg>
                        <span class="font-bold mt-2 text-white text-sm">Outro</span>
                    </div>
                </div>
            </div>
HTML;

$pattern = '/\{\{-- ── ESCOLHA O TIPO DE MONITORAMENTO ──────────────────── --\}\}.*?\{\{-- Conteúdo do card em branco ficará aqui --\}\}\s*<\/div>/s';

$count = 0;
$c = preg_replace($pattern, $newHtml, $content, -1, $count);

if ($count > 0) {
    file_put_contents($file, $c);
    echo "Successfully replaced the macro buttons and blank card!\n";
} else {
    echo "Could not match the replacement pattern.\n";
}

