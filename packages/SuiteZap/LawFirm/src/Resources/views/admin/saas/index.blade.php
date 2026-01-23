<x-admin::layouts>
    <x-slot:title>
        Minha Assinatura
        </x-slot>

        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.saas.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Minha Assinatura
                    </div>
                </div>
            </div>

            <!-- CARDS GRID -->
            <div class="mt-3.5 flex gap-4 max-xl:flex-wrap">

                <!-- CARD 1: SUBSCRIPTION STATUS -->
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
                            <span
                                class="font-bold text-gray-900 dark:text-white">{{ $subscription['plan_name'] }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span
                                class="px-3 py-1 rounded-md text-white text-sm font-semibold {{ $subscription['status_color'] == 'success' ? 'bg-green-500' : 'bg-red-500' }}">
                                {{ $subscription['status'] }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">Vencimento:</span>
                            <span class="text-gray-900 dark:text-white">{{ $subscription['expires_at'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: STORAGE USAGE -->
                <div
                    class="flex flex-1 flex-col gap-4 max-xl:flex-auto p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
                    <div
                        class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-3">
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                            <span class="icon-folder text-xl"></span>
                            Espaço em Disco (GED)
                        </div>
                    </div>

                    <div class="text-center mb-2">
                        <h3
                            class="text-4xl font-bold {{ $storageAlert ? 'text-red-600' : 'text-gray-800 dark:text-white' }}">
                            {{ $storageSummary['percent'] }}%
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $storageSummary['used_formatted'] }} utilizados de
                            {{ $storageSummary['limit_formatted'] }}
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2 overflow-hidden">
                        <div class="h-3 rounded-full transition-all duration-500 {{ $storageSummary['percent'] > 90 ? 'bg-red-600' : ($storageSummary['percent'] > 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                            style="width: {{ $storageSummary['percent'] }}%"></div>
                    </div>

                    @if($storageAlert)
                        <div
                            class="mt-2 p-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded text-center text-sm">
                            ⚠️ Atenção: Você está atingindo o limite de armazenamento!
                        </div>
                    @endif
                </div>

                <!-- CARD 3: AI CREDITS -->
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
                            {{ number_format($aiCredits, 0, ',', '.') }}
                        </div>
                        <div class="text-xs font-bold uppercase tracking-wider text-gray-500">
                            Tokens Disponíveis
                        </div>
                    </div>

                    <div class="mt-4 text-center bg-gray-50 dark:bg-gray-800 p-2 rounded">
                        <span class="text-gray-500 text-sm">Modalidade:</span>
                        <span class="font-bold text-gray-800 dark:text-white capitalize ml-1">{{ $planType }}</span>
                    </div>

                    <div class="mt-2 text-center">
                        <a href="#" class="text-blue-600 hover:underline text-sm font-medium">Ver histórico de consumo
                            &rarr;</a>
                    </div>
                </div>

            </div>
        </div>
</x-admin::layouts>