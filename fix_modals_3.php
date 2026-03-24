<?php

$file = 'c:/laragon/www/adv-crm/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/monitoramentos/create.blade.php';
$content = file_get_contents($file);

// 1. Remove the help icons (svg with help-circle and the button wrapping it)
// The structure is roughly <div ... class="c-field-base_btn-control ..."> <button ...> ... <svg ... feather-help-circle> ... </svg> ... </button> </div>
$content = preg_replace('/<div[^>]*class="c-field-base_btn-control[^>]+>.*?<\/div>\s*<!---->\s*<!---->/s', '', $content);
$content = preg_replace('/<div[^>]*class="c-field-base_btn-control[^>]+>.*?<\/div>/s', '', $content);

// 2. Extract the templates and move them inside the modal-content div.
// Currently it's <div id="lf-macro-templates" style="display:none;" v-pre> ... </div>
// And <div id="lf-macro-modal-content" style="padding:24px;"> <!-- content injected via JS --> </div>

// Get everything from <div id="macro-html-processo"> to the end of <div id="macro-html-outro"> ... </div>
preg_match('/<div id="macro-html-processo">.*<div id="macro-html-outro">.*?<\/div>\s*<\/div>/s', $content, $matches);
if (isset($matches[0])) {
    $macroHtml = $matches[0];
    
    // Add class="macro-form-panel" style="display:none;" to all macro-html-* divs
    $macroHtml = str_replace('<div id="macro-html-processo">', '<div id="macro-html-processo" class="macro-form-panel" style="display:none;">', $macroHtml);
    $macroHtml = str_replace('<div id="macro-html-pessoa">', '<div id="macro-html-pessoa" class="macro-form-panel" style="display:none;">', $macroHtml);
    $macroHtml = str_replace('<div id="macro-html-empresa">', '<div id="macro-html-empresa" class="macro-form-panel" style="display:none;">', $macroHtml);
    $macroHtml = str_replace('<div id="macro-html-advogado">', '<div id="macro-html-advogado" class="macro-form-panel" style="display:none;">', $macroHtml);
    $macroHtml = str_replace('<div id="macro-html-outro">', '<div id="macro-html-outro" class="macro-form-panel" style="display:none;">', $macroHtml);

    // Remove the old templates block wrapper
    $content = preg_replace('/<div id="lf-macro-templates"[^>]*>.*?<\/div>(\s*@push\(\'scripts\'\))/s', '$1', $content);
    
    // Sometimes it might not have matched the whole div perfectly depending on closing tags. 
    // Let's strip the old block strictly.
    $posTemplates = strpos($content, '<div id="lf-macro-templates"');
    if ($posTemplates !== false) {
        $posScripts = strpos($content, "@push('scripts')", $posTemplates);
        if ($posScripts !== false) {
            $content = substr($content, 0, $posTemplates) . substr($content, $posScripts);
        }
    }

    // Inject into lf-macro-modal-content
    $emptyContentDiv = '<div id="lf-macro-modal-content" style="padding:24px;">
                <!-- content injected via JS -->
            </div>';
    
    $filledContentDiv = '<div id="lf-macro-modal-content" style="padding:24px;">' . "\n" . $macroHtml . "\n" . '</div>';
    
    $content = str_replace($emptyContentDiv, $filledContentDiv, $content);
}

// 3. Update the JS
$oldJs = "function openMacroModal(tipo) {
        document.getElementById('lf-macro-modal').style.display = 'block';
        var contentId = 'macro-html-' + tipo;
        var content = document.getElementById(contentId);
        
        if (content) {
            // Because Vue doesn't own this cloned HTML, and 'v-pre' stopped compilation, 
            // the raw HTML should be fully intact.
            document.getElementById('lf-macro-modal-content').innerHTML = content.innerHTML;
        } else {
            console.error('Buscando template html = ' + contentId);
            document.getElementById('lf-macro-modal-content').innerHTML = '<div style=\"color:red;padding:20px;\">Formulário indisponível (ID: ' + contentId + ')</div>';
        }
    }
    function closeMacroModal() {
        document.getElementById('lf-macro-modal').style.display = 'none';
        document.getElementById('lf-macro-modal-content').innerHTML = '';
    }";

$newJs = "function openMacroModal(tipo) {
        document.getElementById('lf-macro-modal').style.display = 'block';
        
        // Hide all
        document.querySelectorAll('.macro-form-panel').forEach(function(el) {
            el.style.display = 'none';
        });

        // Show the one we want
        var contentId = 'macro-html-' + tipo;
        var content = document.getElementById(contentId);
        if (content) {
            content.style.display = 'block';
        }
    }
    function closeMacroModal() {
        document.getElementById('lf-macro-modal').style.display = 'none';
        // optionally hide panels again
        document.querySelectorAll('.macro-form-panel').forEach(function(el) {
            el.style.display = 'none';
        });
    }";

$oldJsAlt = "function openMacroModal(tipo) {
        document.getElementById('lf-macro-modal').style.display = 'block';
        var contentId = '#macro-html-' + tipo;
        var template = document.getElementById('tpl-macro');
        var content = template.content.querySelector(contentId);
        if (content) {
            document.getElementById('lf-macro-modal-content').innerHTML = content.innerHTML;
        }
    }
    function closeMacroModal() {
        document.getElementById('lf-macro-modal').style.display = 'none';
        document.getElementById('lf-macro-modal-content').innerHTML = '';
    }";

$content = str_replace($oldJs, $newJs, $content);
$content = str_replace($oldJsAlt, $newJs, $content);
$content = str_replace("\r\n", "\n", $content);
$oldJs = str_replace("\r\n", "\n", $oldJs);
$oldJsAlt = str_replace("\r\n", "\n", $oldJsAlt);
$newJs = str_replace("\r\n", "\n", $newJs);
$content = str_replace($oldJs, $newJs, $content);
$content = str_replace($oldJsAlt, $newJs, $content);

file_put_contents($file, $content);

echo "File updated successfully.";
