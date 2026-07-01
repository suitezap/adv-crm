<x-admin::layouts>
    <x-slot:title>
        Editar Modelo: {{ $template->titulo }}
    </x-slot>

    <x-admin::form :action="route('admin.modelos.update', $template->id)" method="PUT">
        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="text-xl font-bold dark:text-white">📄 Editar Modelo: {{ $template->titulo }}</div>
                </div>
                <div class="flex items-center gap-x-2.5">
                    <a href="{{ route('admin.modelos.index') }}" class="transparent-button">← Voltar</a>
                    <button type="submit" class="primary-button">Salvar Alterações</button>
                </div>
            </div>

            <!-- Card -->
            <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">Título do Modelo</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        name="titulo"
                        rules="required"
                        :value="old('titulo') ?: $template->titulo" />
                    <x-admin::form.control-group.error control-name="titulo" />
                </x-admin::form.control-group>

                <div class="grid grid-cols-2 gap-4">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Tipo</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            name="tipo"
                            rules="required"
                            :value="old('tipo') ?: $template->tipo" />
                        <x-admin::form.control-group.error control-name="tipo" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Área do Direito</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            name="area_direito"
                            :value="old('area_direito') ?: $template->area_direito" />
                        <x-admin::form.control-group.error control-name="area_direito" />
                    </x-admin::form.control-group>
                </div>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>Descrição</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        name="descricao"
                        :value="old('descricao') ?: $template->descricao" />
                    <x-admin::form.control-group.error control-name="descricao" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">Conteúdo do Modelo</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="textarea"
                        name="conteudo"
                        id="conteudo-editor"
                        tinymce="true"
                        hide-placeholders="true"
                        rules="required"
                        :value="old('conteudo') ?: $template->conteudo" />
                    <x-admin::form.control-group.error control-name="conteudo" />
                </x-admin::form.control-group>

                @include('lawfirm::Legal.modelos.partials.variaveis')

            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
