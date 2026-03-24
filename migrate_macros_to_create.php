<?php

$fileMon = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/monitoramentos/index.blade.php';
$fileCreate = __DIR__ . '/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/monitoramentos/create.blade.php';

$monContent = file_get_contents($fileMon);
$createContent = file_get_contents($fileCreate);

// 1. Extract Card UI
$cardPattern = '/\{\{-- ── ESCOLHA O TIPO DE MONITORAMENTO \(CARD\) ─────────────────────────── --\}\}.*?(?=<div class="page-content">)/s';
if (preg_match($cardPattern, $monContent, $matchesCard)) {
    $cardHtml = trim($matchesCard[0]);
    $monContent = preg_replace($cardPattern, '', $monContent);
} else {
    echo "ERROR: Card not found in monitoramentos/index.blade.php\n";
    exit(1);
}

// 2. Extract Modal & Script
$modalPattern = '/\{\{-- ── MACRO MODAL \(NOVO\) ────────────────────────────────────── --\}\}.*?function closeMacroModal\(\) \{\s*document\.getElementById\(\'lf-macro-modal\'\)\.style\.display = \'none\';\s*\}\s*<\/script>/s';
if (preg_match($modalPattern, $monContent, $matchesModal)) {
    $modalHtml = trim($matchesModal[0]);
    $monContent = preg_replace($modalPattern, '', $monContent);
} else {
    echo "ERROR: Modal not found in monitoramentos/index.blade.php\n";
    exit(1);
}

// 3. Extract Modal/Macro CSS
$cssPattern = '/<!-- Switch style -->\s*@push\(\'styles\'\)\s*<style>\s*\.lf-esc-overlay.*?<\/style>\s*@endpush/s';
if (preg_match($cssPattern, $monContent, $matchesCss)) {
    $cssHtml = trim($matchesCss[0]);
    
    // We remove the specific `.lf-esc-overlay` block, keeping the switch style intact.
    // The switch style has NO `.lf-esc-overlay` matching. The above regex captures too broadly if we aren't careful.
    // Let's do a more precise replacement for the extracted CSS block.
    $cssExtractionPattern = '/<style>\s*\.lf-esc-overlay.*?<\/style>/s';
    if(preg_match($cssExtractionPattern, $monContent, $exactCssMatch)) {
       $exactCss = $exactCssMatch[0];
       $monContent = str_replace($exactCss, '', $monContent);
       
       // Clean empty @push if it exists wrapper
       $monContent = preg_replace('/@push\(\'styles\'\)\s*@endpush/s', '', $monContent);
       
       $cleanCssBlock = "@push('styles')\n" . $exactCss . "\n@endpush";
    }
}

// Ensure clean format
$monContent = str_replace('<!-- Switch style -->', "<!-- Switch style -->", $monContent);

// 4. Inject Card into Create view
$createContent = str_replace('<!-- The macro card will be injected here by the PHP script -->', $cardHtml, $createContent);

// 5. Inject Modal, Script, and CSS into Create view
$injectionBlock = "\n\n" . $modalHtml . "\n\n" . ($cleanCssBlock ?? "");
$createContent = str_replace('</x-admin::layouts>', $injectionBlock . "\n</x-admin::layouts>", $createContent);

// Clean up any double blank spaces created
$monContent = preg_replace('/\n\s*\n\s*\n/', "\n\n", $monContent);

file_put_contents($fileMon, $monContent);
file_put_contents($fileCreate, $createContent);

echo "Successfully migrated macro buttons to monitoramentos/create.blade.php.\n";
