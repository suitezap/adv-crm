<x-admin::layouts>
    <x-slot:title>
        Modelos de Documentos
    </x-slot>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">Modelos de Documentos</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Gerencie seus modelos locais. Modelos <span class="inline-flex items-center gap-1 font-medium text-blue-600 dark:text-blue-400">🌐 Padrão</span> são fornecidos pela plataforma e não podem ser editados aqui.
            </p>
        </div>
        <a href="{{ route('admin.modelos.create') }}" class="primary-button">
            Criar Modelo
        </a>
    </div>

    {{-- Global Templates (read-only) --}}
    @php
        $globalTemplates = $allTemplates->filter(fn($t) => $t->is_global);
    @endphp

    {{-- Local Templates (editable) --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-800 mb-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">📁 Meus Modelos Locais</span>
            @if($localTemplates->count() > 0)
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    {{ $localTemplates->count() }} cadastrados
                </span>
            @endif
        </div>

        @if($localTemplates->count() > 0)
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b dark:border-gray-800 text-gray-500 text-sm">
                        <th class="pb-2">Título</th>
                        <th class="pb-2">Tipo</th>
                        <th class="pb-2">Área do Direito</th>
                        <th class="pb-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($localTemplates as $t)
                        <tr class="border-b dark:border-gray-800 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="py-3 font-semibold dark:text-gray-200">{{ $t->titulo }}</td>
                            <td class="py-3 text-gray-600 dark:text-gray-400">{{ $t->tipo ?? '-' }}</td>
                            <td class="py-3 text-gray-600 dark:text-gray-400">{{ $t->area_direito ?? '-' }}</td>
                            <td class="py-3 flex justify-end gap-2">
                                <a href="{{ route('admin.modelos.edit', $t->id) }}" class="text-blue-600 hover:underline">Editar</a>
                                <form action="{{ route('admin.modelos.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este modelo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Deletar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500 text-center py-8">
                Nenhum modelo local cadastrado.
                <a href="{{ route('admin.modelos.create') }}" class="text-blue-600 hover:underline">Crie o primeiro!</a>
            </p>
        @endif
    </div>

    {{-- Global Templates (read-only) --}}
    @if($globalTemplates->count() > 0)
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800/60 p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">🌐 Modelos Padrão da Plataforma</span>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                    {{ $globalTemplates->count() }} disponíveis
                </span>
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-blue-200 dark:border-blue-800/60 text-gray-500 text-sm">
                        <th class="pb-2">Título</th>
                        <th class="pb-2">Tipo</th>
                        <th class="pb-2">Área do Direito</th>
                        <th class="pb-2 text-right">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($globalTemplates as $t)
                        <tr class="border-b border-blue-100 dark:border-blue-900/30 last:border-0">
                            <td class="py-2.5 font-medium text-gray-900 dark:text-gray-200 flex items-center gap-2">
                                <span class="text-xs text-blue-500">🌐</span>
                                {{ $t->titulo }}
                            </td>
                            <td class="py-2.5 text-gray-600 dark:text-gray-400">{{ $t->tipo ?? '-' }}</td>
                            <td class="py-2.5 text-gray-600 dark:text-gray-400">{{ $t->area_direito ?? 'Geral' }}</td>
                            <td class="py-2.5 text-right">
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    Ativo
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin::layouts>
