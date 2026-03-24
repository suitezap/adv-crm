<?php

$f = 'c:\laragon\www\adv-crm\packages\SuiteZap\LawFirm\src\Resources\views\admin\escavador\monitoramentos\create.blade.php';
$content = file_get_contents($f);

// Extract the card and modal block from the file safely
preg_match('/\{\{-- ── ESCOLHA O TIPO DE MONITORAMENTO \(CARD\) ─────────────────────────── --\}\}.*?(?=\{\{-- ── MACRO MODAL \(NOVO\) ────────────────────────────────────── --\}\})/s', $content, $cardMatch);
preg_match('/\{\{-- ── MACRO MODAL \(NOVO\) ────────────────────────────────────── --\}\}.*?<\/template>/s', $content, $modalMatch);

if (!$cardMatch || !$modalMatch) {
    echo "Could not extract components. Check the file manually.";
    exit(1);
}

$cardHtml = trim($cardMatch[0]);
$modalHtml = trim($modalMatch[0]);

$html = <<<HTML
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
            $cardHtml
        </div>
    </div>

    $modalHtml

    @push('scripts')
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
    @endpush

    @push('styles')
    <style>
    .lf-esc-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; backdrop-filter: blur(2px); }
    .lf-esc-dialog { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 16px; z-index: 9999; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden; }
    .lf-esc-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; }
    .lf-esc-modal-title { font-size: 1.125rem; font-weight: 700; margin: 0; }
    .lf-esc-close-btn { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #6b7280; padding: 4px; border-radius: 4px; transition: background-color 0.2s; }
    .lf-esc-close-btn:hover { background-color: #f3f4f6; color: #ef4444; }
    </style>
    @endpush
</x-admin::layouts>
HTML;

file_put_contents($f, $html);
echo "File fixed inside create.blade.php\n";

