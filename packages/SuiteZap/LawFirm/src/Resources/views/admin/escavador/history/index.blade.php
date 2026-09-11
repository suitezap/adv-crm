<x-admin::layouts>
    <x-slot:title>
        Histórico Assistente Jurídico
        </x-slot>

        <div class="flex flex-col gap-4">
            {{-- Header --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.escavador.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Histórico dos Assistentes Jurídicos
                    </div>
                </div>
            </div>

            {{-- DataGrid --}}
            <div
                class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <x-admin::datagrid :src="route('lawfirm.escavador.history')"></x-admin::datagrid>
            </div>
        </div>
</x-admin::layouts>