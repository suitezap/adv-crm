@push('styles')
    <style>
        .table-responsive .row.grid,
        .row.grid {
            grid-template-columns: 40px 50px 2fr 1fr 100px 100px 1fr 1fr 80px 120px 100px !important;
            column-gap: 8px !important;
        }
        .row.grid>div { display: flex; align-items: center; overflow: hidden; }
        .row.grid>div:nth-child(1), .row.grid>div:nth-child(2) { justify-content: center !important; }
    </style>
@endpush

<x-admin::layouts>
    <x-slot:title>
        Gestão de Casos
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <x-admin::breadcrumbs name="lawfirm.casos.index" />
                </div>
                <div class="text-xl font-bold dark:text-white">
                    📂 Gestão de Casos
                </div>
            </div>
            <div class="flex items-center gap-x-2.5">
                @if (bouncer()->hasPermission('lawfirm.casos.create'))
                    <a href="{{ route('admin.lawfirm.casos.create') }}" class="primary-button">
                        + Novo Caso
                    </a>
                @endif
            </div>
        </div>

        <!-- DataGrid -->
        <x-admin::datagrid :src="route('admin.lawfirm.casos.index')" />
    </div>
</x-admin::layouts>
