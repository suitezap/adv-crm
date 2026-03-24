<?php
$f = 'c:\laragon\www\adv-crm\packages\SuiteZap\LawFirm\src\Resources\views\admin\escavador\monitoramentos\create.blade.php';
$content = file_get_contents($f);
$lines = explode("\n", $content);

// The lines we want to keep are 0 to 168 (which is line 1 to 169)
// The HTML for "Outros" ends correctly at 169
// Then we want to append the closing </div> and </template> and the rest of the file from 362 to the end.

$newContent = implode("\n", array_merge(
    array_slice($lines, 0, 169),
    [
        '        </p></div> <div data-v-245e22ea="" data-v-2ce45374="" class="c-hide-at v--mediumAndAbove" bis_skin_checked="1"><div data-v-2ce45374="" class="c-spacing" data-v-245e22ea="" bis_skin_checked="1" style="margin: 16px 0px;"><span data-v-2ce45374="" class="c-form-monitoramento_card-title v--text-overflow-elipses">Outros</span></div></div></div></div> <div data-v-2ce45374="" class="c-spacing c-form-monitoramento__input-wrapper" style="margin: 32px 0px 0px;" bis_skin_checked="1"><div data-v-fda819ba="" data-v-2ce45374="" class="c-field-base mb-4" bis_skin_checked="1"><div data-v-fda819ba="" class="c-field-base_container" bis_skin_checked="1"><div data-v-fda819ba="" class="c-field-base_label mb-1" bis_skin_checked="1"><label class="text-sm font-semibold text-gray-700" data-v-fda819ba="" for="termo">Qual é o termo a ser monitorado?</label></div>  <div data-v-fda819ba="" class="c-field-base_box v--medium" bis_skin_checked="1"><!----> <div data-v-fda819ba="" bis_skin_checked="1"><div data-v-fda819ba="" class="c-field__container v--flex v--flex-align-middle" bis_skin_checked="1"><!----> <input data-v-fda819ba="" id="termo_outro" name="termo" autofocus="autofocus" mask="" type="text" placeholder="" autocomplete="" maxlength="Infinity" autocaptalize="none" class="lf-esc-input w-full"></div> <!----> <span data-v-fda819ba="" class="c-field__contador" style="display: none;">Infinity</span></div> <div data-v-fda819ba="" bis_skin_checked="1"><!----> <!----> <!----></div></div></div> <!----></div> <div data-v-2ce45374="" class="c-spacing v--fixed-on-mobile flex justify-end" style="margin: 24px 0px 10px;" bis_skin_checked="1"><button data-v-2ce45374="" disabled="disabled" class="lf-esc-btn opacity-50 cursor-not-allowed"><!----> <div class="c-btn__content-box" bis_skin_checked="1">Avançar</div></button></div></div></div></div>',
        '</template>'
    ],
    array_slice($lines, 363)
));

file_put_contents($f, $newContent);
echo "File fixed inside create.blade.php\n";
