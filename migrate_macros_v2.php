<?php

$fileIndex = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/index.blade.php';
$fileMon = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/monitoramentos/index.blade.php';

$idxContent = file_get_contents($fileIndex);
$monContent = file_get_contents($fileMon);

// 1. Extract Card
$cardPattern = '/\{\{-- ── ESCOLHA O TIPO DE MONITORAMENTO \(CARD\) ─────────────────────────── --\}\}.*?(?=\{\{-- ── AREA FILTER BAR ── --\}\})/s';
if (preg_match($cardPattern, $idxContent, $matchesCard)) {
    $cardHtml = trim($matchesCard[0]);
    $idxContent = preg_replace($cardPattern, '', $idxContent);
} else {
    echo "ERROR: Card not found in index.blade.php\n";
    exit(1);
}

// 2. Extract Modal & Script
$modalPattern = '/\{\{-- ── MACRO MODAL \(NOVO\) ────────────────────────────────────── --\}\}.*?function closeMacroModal\(\) \{\s*document\.getElementById\(\'lf-macro-modal\'\)\.style\.display = \'none\';\s*\}\s*<\/script>/s';
if (preg_match($modalPattern, $idxContent, $matchesModal)) {
    $modalHtml = trim($matchesModal[0]);
    $idxContent = preg_replace($modalPattern, '', $idxContent);
} else {
    // If not found, it might have been extracted in a previous failed run? No, the previous run didn't write if it failed.
    echo "ERROR: Modal not found in index.blade.php\n";
    exit(1);
}

// 3. Inject Card into Monitoramentos
if (strpos($monContent, 'ESCOLHA O TIPO DE MONITORAMENTO') === false) {
    $monContent = str_replace('<div class="page-content">', $cardHtml . "\n\n        <div class=\"page-content\">", $monContent);
}

// 4. Inject Modal & Script into Monitoramentos
if (strpos($monContent, 'MACRO MODAL (NOVO)') === false) {
    $monContent = str_replace('@push(\'scripts\')', $modalHtml . "\n\n    @push('scripts')", $monContent);
}

// 5. Add required styles for the modal overlay/dialog
$cssSnippet = <<<CSS
        <style>
            .lf-esc-overlay {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
            }
            .lf-esc-dialog {
                position: fixed;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                background: #fff;
                border-radius: 12px;
                z-index: 1001;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            }
            .dark .lf-esc-dialog {
                background: #1f2937;
                color: #e5e7eb;
            }
            .lf-esc-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 24px;
            }
            .dark .lf-esc-modal-header {
                background: #374151;
                border-bottom: 1px solid #4b5563;
            }
            .lf-esc-close-btn {
                background: none;
                border: none;
                font-size: 1.25rem;
                cursor: pointer;
                color: #9ca3af;
                padding: 4px;
            }
            .lf-esc-close-btn:hover {
                color: #4b5563;
            }
            .lf-esc-modal-title {
                font-size: 1.25rem;
                font-weight: 700;
                margin: 0;
            }
            .dark .lf-esc-modal-title {
                color: #f9fafb;
            }
            .lf-esc-btn {
                background: #0d9488;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            .lf-esc-btn:hover {
                background: #0f766e;
            }
            .lf-esc-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .lf-esc-input, .lf-esc-select {
                border: 1px solid #d1d5db;
                border-radius: 8px;
                padding: 10px 14px;
                width: 100%;
                font-size: 0.95rem;
                transition: border-color 0.2s;
            }
            .lf-esc-input:focus, .lf-esc-select:focus {
                outline: none;
                border-color: #0d9488;
                box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            }
            .dark .lf-esc-input, .dark .lf-esc-select {
                background: #374151;
                border-color: #4b5563;
                color: #e5e7eb;
            }
            .dark .lf-esc-input:focus, .dark .lf-esc-select:focus {
                border-color: #14b8a6;
            }
        </style>
CSS;
if (strpos($monContent, '.lf-esc-overlay') === false) {
    $monContent = str_replace('<!-- Switch style -->', $cssSnippet . "\n\n    <!-- Switch style -->", $monContent);
}

// Clean up any double blank spaces created
$idxContent = preg_replace('/\n\s*\n\s*\n/', "\n\n", $idxContent);

file_put_contents($fileIndex, $idxContent);
file_put_contents($fileMon, $monContent);

echo "Successfully migrated macro buttons to Monitoramentos page.\n";
