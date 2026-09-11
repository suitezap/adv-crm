<x-admin::layouts>
    <x-slot:title>
        Novo Caso
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">📂 Novo Caso</div>
            </div>
            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.lawfirm.casos.index') }}" class="transparent-button">← Voltar</a>
            </div>
        </div>

        <!-- Form -->
        <x-admin::form
            method="POST"
            :action="route('admin.lawfirm.casos.store')"
        >
            <div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">
                <h3 class="text-lg font-semibold tracking-tight border-b pb-3 dark:text-white">
                    📋 Dados do Caso
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Título -->
                    <x-admin::form.control-group class="md:col-span-2">
                        <x-admin::form.control-group.label class="required">Título do Caso</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            name="titulo"
                            :value="old('titulo')"
                            rules="required"
                            label="Título do Caso"
                            placeholder="Ex: Revisão de Contrato de Locação"
                        />
                        <x-admin::form.control-group.error control-name="titulo" />
                    </x-admin::form.control-group>

                    <!-- Área -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Área do Direito</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="area"
                            :value="old('area')"
                            label="Área do Direito"
                        >
                            <option value="">— Selecione —</option>
                            <option value="Administrativo">Administrativo</option>
                            <option value="Ambiental">Ambiental</option>
                            <option value="Bancário">Bancário</option>
                            <option value="Consumidor">Consumidor</option>
                            <option value="Cível">Cível</option>
                            <option value="Digital / LGPD">Digital / LGPD</option>
                            <option value="Empresarial">Empresarial</option>
                            <option value="Família">Família</option>
                            <option value="Imobiliário">Imobiliário</option>
                            <option value="Penal">Penal</option>
                            <option value="Previdenciário">Previdenciário</option>
                            <option value="Trabalhista">Trabalhista</option>
                            <option value="Tributário">Tributário</option>
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="area" />
                    </x-admin::form.control-group>

                    <!-- Status -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Status</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="status"
                            :value="old('status', 'Novo Caso')"
                            label="Status"
                        >
                            @foreach(\SuiteZap\LawFirm\Legal\Services\LegalOrchestrator::VALID_STATUSES as $s)
                                <option value="{{ $s }}" {{ old('status', 'Novo Caso') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>


                    <!-- Prioridade -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Prioridade</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="prioridade"
                            :value="old('prioridade')"
                            label="Prioridade"
                        >
                            <option value="">— Selecione —</option>
                            <option value="baixa">Baixa</option>
                            <option value="media">Média</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="prioridade" />
                    </x-admin::form.control-group>

                    <!-- Responsável -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Responsável</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="select"
                            name="user_id"
                            :value="old('user_id', auth()->id())"
                            label="Responsável"
                        >
                            <option value="">— Selecione —</option>
                            @foreach (\Webkul\User\Models\User::where('status', 1)->get() as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="user_id" />
                    </x-admin::form.control-group>

                    <!-- Pessoa (PF) - Lookup -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Cliente (Pessoa Física) — Opcional</x-admin::form.control-group.label>

                        {{-- Trigger v-lookup-component registration --}}
                        <x-admin::attributes.edit.lookup />

                        <v-lookup-component
                            :attribute="{{ json_encode(['code' => 'person_id', 'name' => 'Pessoa', 'lookup_type' => 'persons']) }}"
                            :value="{{ json_encode(null) }}"
                            validations=""
                        ></v-lookup-component>
                    </x-admin::form.control-group>

                    <!-- Organização (PJ) - Lookup -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Cliente (Pessoa Jurídica) — Opcional</x-admin::form.control-group.label>

                        <v-lookup-component
                            :attribute="{{ json_encode(['code' => 'organization_id', 'name' => 'Empresa', 'lookup_type' => 'organizations']) }}"
                            :value="{{ json_encode(null) }}"
                            validations=""
                        ></v-lookup-component>
                    </x-admin::form.control-group>

                    <!-- Descrição -->
                    <x-admin::form.control-group class="md:col-span-2">
                        <x-admin::form.control-group.label>Descrição</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="textarea"
                            name="descricao"
                            :value="old('descricao')"
                            label="Descrição"
                            rows="4"
                            placeholder="Descreva o caso e suas particularidades..."
                        />
                        <x-admin::form.control-group.error control-name="descricao" />
                    </x-admin::form.control-group>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 border-t pt-4">
                    <a href="{{ route('admin.lawfirm.casos.index') }}" class="transparent-button">Cancelar</a>
                    <button type="submit" class="primary-button">💾 Criar Caso</button>
                </div>
            </div>
        </x-admin::form>
    </div>
</x-admin::layouts>
