{{--
    lawfirm/vee-validate-phone-override.blade.php

    Override da regra 'phone' do vee-validate para o ecossistema LawFirm.

    Problema: O arquivo packages/Webkul/Admin/.../vee-validate.js foi modificado
              para aceitar telefones brasileiros com parênteses e espaços.
              Em merges futuros do Krayin core, esse arquivo pode ser sobrescrito.

    Solução: Este override é injetado via view_render_event('admin.layout.body.after')
             DEPOIS que o bundle do Krayin (app.js) termina de montar o Vue.
             Como o vee-validate já registra a regra no window, apenas a sobrescrevemos
             usando o mesmo defineRule() da lib já carregada pelo bundle.

    Regex adotada:
      /^\+?[\d\s\-\(\)]+$/
      Aceita: +55 (11) 99999-1234 | 11999991234 | (11) 4002-8922
      Rejeita: caracteres alfanuméricos, scripts, etc.

    @see SKILL.md §7 — Regras de Krayin / Merge Safety
    @since v3.54.2
--}}
@pushOnce('scripts')
<script>
    /**
     * LawFirm phone rule override — executado via window.load, APÓS o bundle do Krayin
     * ter registrado window.defineRule (packages/Webkul/Admin/js/plugins/vee-validate.js L28).
     *
     * Objetivo: blindar a regra customizada de merges upstream do Krayin core.
     */
    window.addEventListener('load', function () {
        if (typeof window.defineRule === 'function') {
            window.defineRule('phone', function (value) {
                if (!value || !value.length) {
                    return true;
                }
                // Aceita: dígitos, espaços, hífens, parênteses e sinal +
                // Ex.: +55 (11) 99999-1234 | 1199999-1234 | (11) 4002-8922
                return /^\+?[\d\s\-\(\)]+$/.test(value);
            });
        }
    });
</script>
@endPushOnce
