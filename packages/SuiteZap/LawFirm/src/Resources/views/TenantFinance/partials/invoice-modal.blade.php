{{--
    Portal Dialog: Modal de criação de cobrança Asaas.
    Injetado na aba Financeiro da tela de edição do Processo.

    REGRAS:
    - Estilos 100% inline (Portal Dialog Pattern — Regra 6.6)
    - CSRF lido at event time (Regra 6.2)
    - REPLACE_ID Pattern (Regra 6.6)
    - document.body.appendChild (evita Vue interceptar)
--}}

<button type="button"
        onclick="window.lfOpenAsaasModal()"
        style="background: #8338ec; color: #fff; padding: 6px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600;">
    💳 Cobrar via Asaas
</button>

@php
    $clientCpfCnpj = '';
    if (isset($processo) && clone $processo->person) {
        $lawDetail = \SuiteZap\LawFirm\Legal\Models\LawPersonDetail::where('person_id', $processo->person->id)->first();
        if ($lawDetail && ($lawDetail->cpf || $lawDetail->cnpj)) {
            $clientCpfCnpj = $lawDetail->cpf ?: $lawDetail->cnpj;
        } elseif ($processo->person->organization_id) {
            $orgDetail = \SuiteZap\LawFirm\Legal\Models\LawOrganizationDetail::where('organization_id', $processo->person->organization_id)->first();
            if ($orgDetail && $orgDetail->cnpj) {
                $clientCpfCnpj = $orgDetail->cnpj;
            }
        }
    }
@endphp

<script>
(function() {
    // ----- Config (rotas via REPLACE_ID Pattern) -----
    const ROUTE_STORE    = "{{ route('admin.lawfirm.tenant_finance.store') }}";
    const PROCESSO_ID    = "{{ $processo->id ?? '' }}";
    const PERSON_ID      = "{{ $processo->person_id ?? '' }}";
    const PERSON_NAME    = "{{ $processo->person->name ?? '' }}";
    const PERSON_CPF     = "{{ $clientCpfCnpj }}";
    const PERSON_EMAIL   = "{{ isset($processo->person->emails) && count($processo->person->emails) > 0 ? $processo->person->emails[0]['value'] : '' }}";
    const PERSON_PHONE   = "{{ isset($processo->person->contact_numbers) && count($processo->person->contact_numbers) > 0 ? $processo->person->contact_numbers[0]['value'] : '' }}";

    window.lfOpenAsaasModal = function() {
        // Evitar duplicatas
        const existing = document.getElementById('lf-asaas-invoice-modal');
        if (existing) { existing.style.display = 'flex'; return; }

        const modal = document.createElement('div');
        modal.id = 'lf-asaas-invoice-modal';
        modal.setAttribute('style', 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;');

        modal.innerHTML = `
            <div style="background:#fff;border-radius:10px;padding:28px;max-width:520px;width:95%;max-height:85vh;overflow-y:auto;box-shadow:0 8px 30px rgba(0,0,0,0.2);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h2 style="margin:0;font-size:18px;color:#264653;">💳 Nova Cobrança Asaas</h2>
                    <button onclick="document.getElementById('lf-asaas-invoice-modal').style.display='none'"
                            style="background:none;border:none;font-size:22px;cursor:pointer;color:#999;">✕</button>
                </div>

                <div id="lf-asaas-form-area">
                    ${PERSON_NAME ? `<p style="font-size:13px;color:#555;margin-bottom:16px;">👤 Cliente Vinculado: <strong>${PERSON_NAME}</strong></p>` : '<p style="color:orange;font-size:13px;margin-bottom:16px;">⚠️ Nenhum contato vinculado ao processo.</p>'}

                    ${!PERSON_CPF ? `
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div style="grid-column: span 2;">
                            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;color:#e76f51;">⚠️ O Cliente não possui CPF/CNPJ no cadastro. Informe para a cobrança *</label>
                            <input id="lf-inv-cpf" type="text" placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                   style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                        </div>
                    </div>` : `
                    <input id="lf-inv-cpf" type="hidden" value="${PERSON_CPF}">
                    <p style="font-size:12px;color:#2a9d8f;margin-bottom:16px;">✅ Documento validado: <strong>${PERSON_CPF}</strong></p>
                    `}

                    <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Descrição da Cobrança *</label>
                    <input id="lf-inv-desc" type="text" placeholder="Ex: Honorários advocatícios"
                           style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;margin-bottom:12px;box-sizing:border-box;">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Valor (R$) *</label>
                            <input id="lf-inv-value" type="number" step="0.01" min="5" placeholder="100.00"
                                   style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Vencimento *</label>
                            <input id="lf-inv-due" type="date"
                                   style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Forma de Pagamento *</label>
                            <select id="lf-inv-billing"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                                <option value="PIX">PIX</option>
                                <option value="BOLETO">Boleto</option>
                                <option value="CREDIT_CARD">Cartão de Crédito</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Tipo</label>
                            <select id="lf-inv-type" onchange="window.lfToggleInstallments()"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                                <option value="single">Avulsa</option>
                                <option value="installment">Parcelada</option>
                            </select>
                        </div>
                    </div>

                    <div id="lf-inv-installment-area" style="display:none;margin-bottom:12px;">
                        <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;">Parcelas</label>
                        <select id="lf-inv-installments"
                                style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                            <option value="2">2x</option>
                            <option value="3">3x</option>
                            <option value="4">4x</option>
                            <option value="5">5x</option>
                            <option value="6">6x</option>
                            <option value="10">10x</option>
                            <option value="12">12x</option>
                        </select>
                    </div>

                    <button onclick="window.lfSubmitAsaasInvoice()"
                            id="lf-inv-submit-btn"
                            style="width:100%;background:#2a9d8f;color:#fff;padding:10px;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:14px;margin-top:8px;">
                        🚀 Emitir Cobrança
                    </button>
                </div>

                <div id="lf-asaas-result-area" style="display:none;"></div>
            </div>
        `;

        document.body.appendChild(modal);

        // Fechar ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.style.display = 'none';
        });
    };

    window.lfToggleInstallments = function() {
        const type = document.getElementById('lf-inv-type').value;
        document.getElementById('lf-inv-installment-area').style.display = type === 'installment' ? 'block' : 'none';
    };

    window.lfSubmitAsaasInvoice = function() {
        const btn = document.getElementById('lf-inv-submit-btn');
        btn.disabled = true;
        btn.textContent = '⏳ Processando...';

        // CSRF at event time (Regra 6.2)
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const payload = {
            person_id:       PERSON_ID,
            person_name:     PERSON_NAME,
            person_cpf_cnpj: document.getElementById('lf-inv-cpf').value || PERSON_CPF,
            person_email:    PERSON_EMAIL,
            person_phone:    PERSON_PHONE,
            processo_id:     PROCESSO_ID,
            type:            document.getElementById('lf-inv-type').value,
            value:           parseFloat(document.getElementById('lf-inv-value').value) || 0,
            due_date:        document.getElementById('lf-inv-due').value,
            billing_type:    document.getElementById('lf-inv-billing').value,
            description:     document.getElementById('lf-inv-desc').value,
            installment_count: document.getElementById('lf-inv-type').value === 'installment'
                             ? parseInt(document.getElementById('lf-inv-installments').value)
                             : null,
        };

        fetch(ROUTE_STORE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json().then(data => ({ status: r.status, ok: r.ok, data })))
        .then(res => {
            const data = res.data;
            const btn = document.getElementById('lf-inv-submit-btn');
            
            if (res.ok && data.success) {
                const result = document.getElementById('lf-asaas-result-area');
                const form   = document.getElementById('lf-asaas-form-area');

                form.style.display = 'none';
                result.style.display = 'block';

                let html = '<div style="text-align:center;padding:16px 0;">';
                html += '<p style="font-size:40px;margin:0;">✅</p>';
                html += '<p style="font-size:16px;font-weight:600;color:#155724;">Cobrança criada com sucesso!</p>';

                if (data.invoice_url) {
                    html += '<a href="' + data.invoice_url + '" target="_blank" style="display:inline-block;background:#2a9d8f;color:#fff;padding:8px 20px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:12px;">🔗 Abrir Link de Pagamento</a>';
                }

                if (data.pix_qrcode) {
                    html += '<div style="margin-top:16px;padding:12px;background:#f3e5f5;border-radius:6px;text-align:left;">';
                    html += '<strong>📱 PIX Copia e Cola:</strong>';
                    html += '<code style="display:block;margin-top:6px;font-size:11px;word-break:break-all;background:#fff;padding:8px;border-radius:4px;">' + data.pix_qrcode + '</code>';
                    html += '</div>';
                }

                html += '</div>';
                result.innerHTML = html;
            } else {
                btn.disabled = false;
                btn.textContent = '🚀 Emitir Cobrança';
                
                if (res.status === 422 && data.errors) {
                    const msg = Object.values(data.errors).map(e => e.join('\\n')).join('\\n');
                    alert('⚠️ Verifique os campos:\\n\\n' + msg);
                } else {
                    alert('Erro: ' + (data.error || data.message || 'Falha ao criar cobrança.'));
                }
            }
        })
        .catch(err => {
            const btn = document.getElementById('lf-inv-submit-btn');
            btn.disabled = false;
            btn.textContent = '🚀 Emitir Cobrança';
            alert('Erro de conexão: ' + err.message);
        });
    };
})();
</script>
