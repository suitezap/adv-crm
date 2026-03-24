<?php
$f = 'c:/laragon/www/adv-crm/packages/SuiteZap/LawFirm/src/Resources/views/admin/escavador/monitoramentos/create.blade.php';
$c = file_get_contents($f);
$c = str_replace('<template id="tpl-macro">', '<div id="lf-macro-templates" style="display:none;" v-pre>', $c);
$c = str_replace('</template>', '</div>', $c);

$js_old = <<<EOT
    function openMacroModal(tipo) {
        document.getElementById('lf-macro-modal').style.display = 'block';
        var contentId = '#macro-html-' + tipo;
        var template = document.getElementById('tpl-macro');
        var content = template.content.querySelector(contentId);
        if (content) {
            document.getElementById('lf-macro-modal-content').innerHTML = content.innerHTML;
        }
    }
EOT;

$js_new = <<<EOT
    function openMacroModal(tipo) {
        document.getElementById('lf-macro-modal').style.display = 'block';
        var contentId = 'macro-html-' + tipo;
        var content = document.getElementById(contentId);
        
        if (content) {
            // Because Vue doesn't own this cloned HTML, and 'v-pre' stopped compilation, 
            // the raw HTML should be fully intact.
            document.getElementById('lf-macro-modal-content').innerHTML = content.innerHTML;
        } else {
            console.error('Buscando template html = ' + contentId);
            document.getElementById('lf-macro-modal-content').innerHTML = '<div style="color:red;padding:20px;">Formulário indisponível (ID: ' + contentId + ')</div>';
        }
    }
EOT;

// Normalize line endings for reliable replacement
$c = str_replace("\r\n", "\n", $c);
$js_old = str_replace("\r\n", "\n", $js_old);
$c = str_replace($js_old, $js_new, $c);

file_put_contents($f, $c);
echo "OK replacement completed";
