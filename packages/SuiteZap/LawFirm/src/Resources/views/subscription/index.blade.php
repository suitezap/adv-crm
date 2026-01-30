<x-admin::layouts>
    <x-slot:title>
        Minha Assinatura
        </x-slot>

        <div class="flex flex-col gap-4">
            <!-- Header com Breadcrumb -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <div class="flex justify-start max-lg:hidden">
                            <div class="flex items-center gap-x-3.5">
                                <nav aria-label="">
                                    <ol class="flex flex-wrap">
                                        <li
                                            class="flex items-center gap-x-1 text-sm font-normal text-brandColor dark:text-brandColor">
                                            <a href="{{ route('admin.dashboard.index') }}"> Dashboard </a>
                                            <span class="after:content-['/'] ltr:mr-1 rtl:ml-1"></span>
                                        </li>
                                        <li class="flex items-center gap-x-1 text-base text-gray-600 after:content-['/'] last:cursor-default after:last:hidden dark:text-gray-300"
                                            aria-current="page">
                                            Minha Assinatura
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Minha Assinatura
                    </div>
                </div>
            </div>

            @if(!$subscription)
                <div
                    class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    <strong>ERRO CRÍTICO:</strong> Não foi possível carregar os dados da assinatura do servidor MotherShip.
                    Verifique o TENANT_ID no arquivo .env.
                </div>
            @else
                <!-- Cards Grid - 3 colunas usando flex -->
                <div class="mt-3.5 flex gap-4 max-xl:flex-wrap">

                    <!-- CARD 1: STATUS DA ASSINATURA -->
                    <div
                        class="flex flex-1 flex-col gap-4 max-xl:flex-auto p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <span class="icon-setting text-xl"></span>
                                Status da Assinatura
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Plano Atual:</span>
                                <span class="font-bold text-gray-900 dark:text-white">LawFirm Pro</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                @if($subscription->status == 'active')
                                    <span class="px-3 py-1 rounded-md text-white text-sm font-semibold bg-green-500">
                                        Ativo
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-md text-white text-sm font-semibold bg-red-500">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Vencimento:</span>
                                <span class="text-gray-900 dark:text-white">
                                    @if($subscription->expires_at)
                                        {{ \Carbon\Carbon::parse($subscription->expires_at)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Usuários:</span>
                                <span class="font-bold text-gray-900 dark:text-white">
                                    {{ $usersCount }} / {{ $subscription->max_users }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: ESPAÇO EM DISCO (GED) -->
                    <div
                        class="flex flex-1 flex-col gap-4 max-xl:flex-auto p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <span class="icon-folder text-xl"></span>
                                Espaço em Disco (GED)
                            </div>
                        </div>

                        @php
                            $storageLimit = $subscription->storage_limit_gb ?? 4;
                            $storageLimitBytes = $storageLimit * 1024 * 1024 * 1024;
                            $storageBytes = $storageUsedBytes ?? 0;
                            $storagePercent = ($storageLimitBytes > 0) ? ($storageBytes / $storageLimitBytes) * 100 : 0;

                            // Formatar bytes de forma legível
                            if ($storageBytes < 1024) {
                                $storageFormatted = round($storageBytes) . ' B';
                            } elseif ($storageBytes < 1024 * 1024) {
                                $storageFormatted = round($storageBytes / 1024, 1) . ' KB';
                            } elseif ($storageBytes < 1024 * 1024 * 1024) {
                                $storageFormatted = round($storageBytes / 1024 / 1024, 2) . ' MB';
                            } else {
                                $storageFormatted = round($storageBytes / 1024 / 1024 / 1024, 2) . ' GB';
                            }
                        @endphp

                        <div class="text-center mb-2">
                            <h3 class="text-4xl font-bold text-gray-800 dark:text-white">
                                {{ number_format($storagePercent, 0) }}%
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $storageFormatted }} utilizados de {{ $storageLimit }} GB
                            </p>
                        </div>

                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2 overflow-hidden">
                            <div class="h-3 rounded-full transition-all duration-500 bg-green-500"
                                style="width: {{ min($storagePercent, 100) }}%;"></div>
                        </div>
                    </div>

                    <!-- CARD 3: INTELIGÊNCIA ARTIFICIAL -->
                    <div
                        class="flex flex-1 flex-col gap-4 max-xl:flex-auto p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
                        <div
                            class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <span class="icon-star text-xl"></span>
                                Inteligência Artificial
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="text-5xl font-bold text-violet-600 dark:text-violet-400 mb-2">
                                {{ $subscription->ai_tokens_balance ?? 0 }}
                            </div>
                            <div class="text-xs font-bold uppercase tracking-wider text-gray-500">
                                Tokens Disponíveis
                            </div>
                        </div>

                        <div class="mt-4 text-center bg-gray-50 dark:bg-gray-800 p-2 rounded">
                            <span class="text-gray-500 text-sm">Modalidade:</span>
                            <span class="font-bold text-gray-800 dark:text-white capitalize ml-1">prepaid</span>
                        </div>

                        <div class="mt-2 text-center">
                            <a href="#" class="text-blue-600 hover:underline text-sm font-medium">Ver histórico de consumo
                                →</a>
                        </div>
                    </div>
                </div>

                <!-- CARD 4: MÓDULOS ATIVOS (Full Width) -->
                <div
                    class="mt-4 p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <span class="icon-product text-xl"></span>
                            Módulos Ativos
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @if($subscription->active_modules && count($subscription->active_modules) > 0)
                            @foreach($subscription->active_modules as $module)
                                <span class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">
                                    {{ strtoupper(str_replace('_', ' ', $module)) }}
                                </span>
                            @endforeach
                        @else
                            <span class="text-gray-500 dark:text-gray-400">Nenhum módulo extra contratado.</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
</x-admin::layouts>