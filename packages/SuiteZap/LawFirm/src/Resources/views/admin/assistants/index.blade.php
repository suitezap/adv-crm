<x-admin::layouts>
    <x-slot:title>
        Assistentes Jurídicos (IA)
        </x-slot>

        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="flex cursor-pointer items-center">
                        <x-admin::breadcrumbs name="lawfirm.assistants.index" />
                    </div>
                    <div class="text-xl font-bold dark:text-white">
                        Assistentes Jurídicos (IA)
                    </div>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($templates as $template)
                    <div
                        class="bg-white dark:bg-gray-900 rounded-lg shadow border border-gray-200 dark:border-gray-700 flex flex-col justify-between hover:shadow-lg transition-shadow">
                        <div style="padding: 1.5rem;">
                            <span
                                class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 mb-3">
                                {{ ucfirst($template->category) }}
                            </span>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">
                                {{ $template->title }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                {{ $template->description ?? 'Sem descrição.' }}
                            </p>
                        </div>
                        <div class="mt-4 px-8 pb-8">
                            <a href="{{ route('lawfirm.assistants.show', $template->slug) }}"
                                class="inline-block text-center px-6 py-2 text-white rounded-lg font-semibold transition-colors"
                                style="background-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }};">
                                Acessar Assistente
                            </a>
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-full text-center py-12 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-500 dark:text-gray-400">Nenhum assistente disponível no momento.</p>
                    </div>
                @endforelse
            </div>
        </div>
</x-admin::layouts>