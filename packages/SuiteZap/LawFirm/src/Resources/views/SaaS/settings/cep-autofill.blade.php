{{-- ─────────────────────────────────────────────────────────────────── --}}
{{-- CEP Auto-fill — Configurações do LawFirm                          --}}
{{-- Injetado via LawFirmServiceProvider somente em /configuration/lawfirm --}}
{{-- Autossuficiente: não depende de window.lawFirmFetchCep             --}}
{{-- ─────────────────────────────────────────────────────────────────── --}}
@push('scripts')
<script>
(function () {
    'use strict';

    // ── helpers ────────────────────────────────────────────────────────────
    // Busca inputs/selects pelo name do Krayin
    function q(fieldSuffix) {
        return document.querySelector(
            'input[name="lawfirm[settings][general][' + fieldSuffix + ']"],' +
            'select[name="lawfirm[settings][general][' + fieldSuffix + ']"]'
        );
    }

    // Aplica máscara de CEP XXXXX-XXX
    function maskCep(raw) {
        raw = raw.replace(/\D/g, '').substring(0, 8);
        return raw.length > 5 ? raw.substring(0, 5) + '-' + raw.substring(5) : raw;
    }

    // Preenche um campo de texto (dispara evento 'input' para o Vue perceber)
    function fill(el, value) {
        if (!el || value === undefined) return;
        el.value = value || '';
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Marca campo como readonly com estilo cinza
    function freeze(el) {
        if (!el) return;
        el.setAttribute('readonly', 'readonly');
        el.classList.add('bg-gray-100', 'cursor-not-allowed', 'dark:bg-gray-800');
    }

    // Libera campo para edição manual
    function thaw(el) {
        if (!el) return;
        el.removeAttribute('readonly');
        el.classList.remove('bg-gray-100', 'cursor-not-allowed', 'dark:bg-gray-800');
    }

    // ── busca de CEP ────────────────────────────────────────────────────────
    function fetchCep(cep) {
        cep = cep.replace(/\D/g, '');
        if (cep.length !== 8) return;

        var spinner = document.getElementById('lf_cfg_cep_loading');
        if (spinner) spinner.classList.remove('hidden');

        fetch('https://opencep.com/v1/' + cep + '.json')
            .then(function (res) {
                if (!res.ok) throw new Error('not_found');
                return res.json();
            })
            .then(function (data) {
                if (data.error) throw new Error('invalid');

                var street   = q('address_street');
                var province = q('address_province');
                var city     = q('city');
                var state    = q('address_state');
                var num      = q('address_number');

                fill(street,   data.logradouro);
                fill(province, data.bairro);
                fill(city,     data.localidade);
                fill(state,    data.uf);

                // Congela campos preenchidos automaticamente
                [street, province, city, state].forEach(freeze);

                // Foca no número para o usuário continuar
                if (num) num.focus();
            })
            .catch(function () {
                // Em caso de erro, libera todos para preenchimento manual
                [q('address_street'), q('address_province'), q('city'), q('address_state')]
                    .forEach(thaw);
            })
            .finally(function () {
                if (spinner) spinner.classList.add('hidden');
            });
    }

    // ── inicialização ────────────────────────────────────────────────────────
    function init() {
        var cepEl = q('address_cep');
        if (!cepEl) return false; // Ainda não renderizou

        // Evita registrar múltiplas vezes
        if (cepEl.dataset.lfBound) return true;
        cepEl.dataset.lfBound = '1';

        // Adiciona spinner ao lado do campo CEP
        if (!document.getElementById('lf_cfg_cep_loading')) {
            var spinner = document.createElement('span');
            spinner.id        = 'lf_cfg_cep_loading';
            spinner.className = 'text-xs text-blue-500 hidden mt-1 block';
            spinner.innerHTML = '<i class="icon-loader animate-spin"></i> Buscando CEP...';
            cepEl.insertAdjacentElement('afterend', spinner);
        }

        // Máscara ao digitar
        cepEl.addEventListener('keyup', function () {
            this.value = maskCep(this.value);
            if (this.value.replace(/\D/g, '').length === 8) {
                fetchCep(this.value);
            }
        });

        // Busca ao sair do campo
        cepEl.addEventListener('blur', function () {
            fetchCep(this.value);
        });

        // Re-congela campos já preenchidos (reload / modo edição)
        var logr = q('address_street');
        if (logr && logr.value.trim() !== '') {
            [logr, q('address_province'), q('city'), q('address_state')].forEach(freeze);
        }

        return true;
    }

    // Tenta inicializar imediatamente e via MutationObserver (Vue é assíncrono)
    if (!init()) {
        var obs = new MutationObserver(function () {
            if (init()) obs.disconnect();
        });
        obs.observe(document.body, { childList: true, subtree: true });
    }

    // Fallbacks adicionais
    document.addEventListener('DOMContentLoaded', function () { setTimeout(init, 500); });
    setTimeout(init, 1200);
    setTimeout(init, 2500);
})();
</script>
@endpush

