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
                <p class="text-sm text-gray-500">Todos os lançamentos do escritório — manuais e via Asaas.</p>
            </div>
            <div class="flex items-center gap-x-2.5">
                <!-- Configurações movidas para o menu Configurações > Jurídico da plataforma -->
            </div>
        </div>

        <!-- DataGrid -->
        <x-admin::datagrid :src="route('admin.lawfirm.tenant_finance.index')" />
    </div>
</x-admin::layouts>
