<x-admin::layouts>
    <x-slot:title>
        Histórico IA
        </x-slot>

        <div class="flex flex-col gap-4">
            {{-- Header --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.assistants.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Histórico de Assistentes IA
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div
                class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <x-admin::datagrid :src="route('lawfirm.assistants.history.index')"></x-admin::datagrid>
            </div>
        </div>
</x-admin::layouts>