<x-admin::layouts>
    <x-slot:title>
        Cobrança #{{ $invoice->id }}
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-6 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    💳 Cobrança #{{ $invoice->id }}
                </div>
            </div>
            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.lawfirm.tenant_finance.index') }}" class="transparent-button">
                    ← Voltar
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 max-w-5xl">
            <!-- Dados da Cobrança -->
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-800 pb-2">
                    Dados da Cobrança
                </h3>

                <div class="flex flex-col gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Descrição:</span>
                        <span>{{ $invoice->description }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Valor:</span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">R$ {{ number_format($invoice->value, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Tipo:</span>
                        <span>
                            @switch($invoice->type)
                                @case('single') Avulsa @break
                                @case('installment') Parcelada ({{ $invoice->installment_count }}x) @break
                                @case('subscription') Recorrente @break
                            @endswitch
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Forma:</span>
                        <span>{{ $invoice->billing_type }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Vencimento:</span>
                        <span>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Status:</span>
                        <span>
                            @if($invoice->isPaid())
                                <span class="label-active">✅ Pago</span>
                            @elseif($invoice->isOverdue())
                                <span class="label-canceled">⚠️ Vencida</span>
                            @elseif($invoice->isPending())
                                <span class="label-pending">⏳ Pendente</span>
                            @else
                                <span class="label-pending">⏳ {{ $invoice->status }}</span>
                            @endif
                        </span>
                    </div>
                    @if($invoice->payment_date)
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">Pago em:</span>
                        <span>{{ $invoice->payment_date->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="font-semibold">ID Asaas:</span>
                        <span class="font-mono text-xs">{{ $invoice->asaas_payment_id ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Dados do Cliente -->
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-800 pb-2">
                    Cliente e Processo
                </h3>

                @if($invoice->customer)
                    <div class="flex flex-col gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Nome:</span>
                            <span>{{ $invoice->customer->name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">CPF/CNPJ:</span>
                            <span>{{ $invoice->customer->cpf_cnpj }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Email:</span>
                            <span>{{ $invoice->customer->email ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">ID Asaas:</span>
                            <span class="font-mono text-xs">{{ $invoice->customer->asaas_customer_id }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 text-sm italic">Cliente não vinculado.</p>
                @endif

                @if($invoice->processo)
                    <div class="mt-5 border-t border-gray-200 dark:border-gray-800 pt-4 cursor-pointer">
                        <a href="{{ route('admin.processos.edit', $invoice->processo_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold flex items-center gap-2">
                            <span class="icon-folder text-xl"></span>
                            {{ $invoice->processo->titulo ?? 'Processo #' . $invoice->processo_id }}
                        </a>
                    </div>
                @endif
                
                {{-- Link de Pagamento --}}
                @if($invoice->invoice_url && $invoice->isPending())
                    <div class="mt-5 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <p class="font-bold text-blue-800 dark:text-blue-300 text-sm mb-1">🔗 Link de Pagamento:</p>
                        <a href="{{ $invoice->invoice_url }}" target="_blank" class="text-blue-600 dark:text-blue-400 text-xs break-all hover:underline">
                            {{ $invoice->invoice_url }}
                        </a>
                    </div>
                @endif

                {{-- PIX QR Code --}}
                @if($invoice->pix_qrcode && $invoice->isPending())
                    <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                        <p class="font-bold text-purple-800 dark:text-purple-300 text-sm mb-1">📱 PIX Copia e Cola:</p>
                        <code class="block mt-2 p-2 bg-white dark:bg-gray-900 border border-purple-200 dark:border-purple-800 rounded font-mono text-[10px] break-all text-gray-700 dark:text-gray-400">
                            {{ $invoice->pix_qrcode }}
                        </code>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin::layouts>
