<x-admin::layouts>
    <x-slot:title>
        Meus Pedidos
    </x-slot>

    <div class="flex flex-col gap-4">

        {{-- ── PAGE HEADER ──────────────────────────────── --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <div class="flex justify-start max-lg:hidden">
                        <div class="flex items-center gap-x-3.5">
                            <nav>
                                <ol class="flex flex-wrap">
                                    <li class="flex items-center gap-x-1 text-sm font-normal text-brandColor dark:text-brandColor">
                                        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
                                        <span class="after:content-['/'] ltr:mr-1 rtl:ml-1"></span>
                                    </li>
                                    <li class="flex items-center gap-x-1 text-sm font-normal text-brandColor dark:text-brandColor">
                                        <a href="{{ route('admin.lawfirm.saas.index') }}">Minha Assinatura</a>
                                        <span class="after:content-['/'] ltr:mr-1 rtl:ml-1"></span>
                                    </li>
                                    <li class="flex items-center gap-x-1 text-base text-gray-600 after:content-['/'] last:cursor-default after:last:hidden dark:text-gray-300" aria-current="page">
                                        Meus Pedidos
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="text-xl font-bold dark:text-white">Meus Pedidos</div>
            </div>

            <a href="{{ route('admin.lawfirm.saas.index') }}" class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <span class="icon-arrow-left text-base"></span>
                Voltar
            </a>
        </div>

        {{-- ── INFO BANNER ──────────────────────────────── --}}
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 flex items-center gap-2">
            <span class="icon-info text-base"></span>
            <span>Histórico de todas as intenções de compra. Pedidos com status <strong>Pendente</strong> aguardam confirmação do gateway de pagamento.</span>
        </div>

        {{-- ── DATAGRID ─────────────────────────────────── --}}
        <x-admin::datagrid :src="route('admin.lawfirm.saas.orders.index')" />

    </div>

</x-admin::layouts>
