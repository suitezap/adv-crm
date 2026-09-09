<x-admin::layouts>
    <x-slot:title>
        Cobranças e Lançamentos
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-6 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <div class="text-xl font-bold dark:text-white">
                    💳 Cobranças e Lançamentos Financeiros
                </div>
                <p class="text-sm text-gray-500">Cobranças Asaas emitidas pelo escritório. Lançamentos manuais em Financeiro.</p>
            </div>
            <div class="flex items-center gap-x-2.5">
                <!-- Configurações movidas para o menu Configurações > Jurídico da plataforma -->
            </div>
        </div>

        <!-- DataGrid -->
        <x-admin::datagrid :src="route('admin.lawfirm.tenant_finance.index')" />
    </div>

    @push('scripts')
    <script>
    (function () {
        // Cobranças Asaas (tenant_invoices) — ações via rotas nomeadas (REPLACE_ID Pattern).
        // A baixa manual de law_financials (quick-pay) vive em /financial, não aqui.
        const ROUTE_CANCEL = "{{ route('admin.lawfirm.tenant_finance.cancel', ['id' => 'REPLACE_ID']) }}";
        const ROUTE_RESEND = "{{ route('admin.lawfirm.tenant_finance.resend', ['id' => 'REPLACE_ID']) }}";

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        }

        window.cancelTenantInvoice = function (id) {
            if (!confirm('Cancelar esta cobrança? Apenas pendentes ou vencidas podem ser canceladas.')) {
                return;
            }

            fetch(ROUTE_CANCEL.replace('REPLACE_ID', id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message || data.error || 'Operação concluída.');
                window.location.reload();
            })
            .catch(() => alert('Erro de rede ao cancelar a cobrança.'));
        };

        window.resendTenantInvoice = function (id) {
            fetch(ROUTE_RESEND.replace('REPLACE_ID', id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.invoice_url) {
                    prompt('Link de pagamento atualizado (copie para compartilhar):', data.invoice_url);
                } else {
                    alert(data.error || data.message || 'Falha ao reenviar notificação.');
                }
            })
            .catch(() => alert('Erro de rede ao reenviar notificação.'));
        };
    })();
    </script>
    @endpush
</x-admin::layouts>
