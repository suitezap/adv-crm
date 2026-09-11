<x-admin::layouts>
    <x-slot:title>
        Uso de Créditos em Assistentes
        </x-slot>

        <div class="flex flex-col gap-4">
            {{-- Header --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-3">
                        <div class="text-xl font-bold dark:text-white">
                            Uso de Créditos em Assistentes
                        </div>
                        <span class="font-mono text-[10px] text-gray-400 uppercase bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700" title="Tenant ID">
                            {{ \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getTenantId() }}
                        </span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">
                        Histórico completo de débitos e créditos na sua assinatura.
                    </p>
                </div>
            </div>

            {{-- Additions DataGrid --}}
            <div
                class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Histórico de Adição de Créditos (Asaas, etc.)</div>
                <x-admin::datagrid :src="route('lawfirm.saas.additions')"></x-admin::datagrid>
            </div>

            {{-- Usage DataGrid --}}
            <div
                class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Histórico de Consumo (Uso em Assistentes)</div>
                <x-admin::datagrid :src="route('lawfirm.saas.transactions')"></x-admin::datagrid>
            </div>
        </div>
</x-admin::layouts>