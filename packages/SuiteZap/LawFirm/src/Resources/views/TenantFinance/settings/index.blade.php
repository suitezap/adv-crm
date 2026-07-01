<x-admin::layouts>
    <x-slot:title>
        Configurações do Asaas — Cobranças
    </x-slot:title>

    <!-- Cabeçalho idêntico à interface de Configuração nativa -->
    <div class="mt-3.5 flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Cobranças Asaas
        </p>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.lawfirm.tenant_finance.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                ← Voltar
            </a>

            <button type="submit" form="asaas-settings-form" class="primary-button">
                💾 Salvar Configurações
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-lg p-4 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <!-- Krayin Native Two-Column Configuration Grid -->
    <div class="grid grid-cols-[1fr_2fr] gap-10 max-lg:grid-cols-1 max-lg:gap-4 lg:mt-6">
        
        <!-- Coluna Esquerda: Título / Info -->
        <div class="grid content-start gap-2.5 max-lg:mt-6">
            <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                Cobranças Asaas V3
            </p>
            <p class="leading-[140%] text-gray-600 dark:text-gray-300 text-sm">
                Gestão das chaves de API, webhook e ambiente para a emissão de cobranças diretamente para os seus clientes pelo módulo TenantFinance.
            </p>
        </div>

        <!-- Coluna Direita: Box do Formulário com Margens Generosas -->
        <div class="box-shadow rounded bg-white p-6 dark:bg-gray-900 border border-gray-200 dark:border-gray-800" style="padding: 1.5rem;">
            <form id="asaas-settings-form" method="POST" action="{{ route('admin.lawfirm.tenant_finance.settings.store') }}">
                @csrf

                <!-- API Key -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                        🔑 API Key do Asaas <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="api_key"
                           value="{{ old('api_key', $settings->api_key ?? '') }}"
                           required
                           placeholder="$aact_prod_... ou $aact_hmlg_..."
                           class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 font-mono">
                    <p class="text-xs text-gray-500 mt-1.5">Token de acesso à API V3 do Asaas do seu escritório.</p>
                    @error('api_key')
                        <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Wallet ID -->
                <div class="mb-4 mt-6">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                        💼 Wallet ID (Opcional)
                    </label>
                    <input type="text"
                           name="wallet_id"
                           value="{{ old('wallet_id', $settings->wallet_id ?? '') }}"
                           placeholder="Usado para Split de Pagamento"
                           class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                </div>

                <!-- Ambiente -->
                <div class="mb-4 mt-6">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                        🌍 Ambiente <span class="text-red-500">*</span>
                    </label>
                    <select name="environment" required class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <option value="sandbox" {{ old('environment', $settings->environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>
                            🧪 Sandbox (Testes)
                        </option>
                        <option value="production" {{ old('environment', $settings->environment ?? '') === 'production' ? 'selected' : '' }}>
                            🚀 Produção
                        </option>
                    </select>
                </div>

                <!-- Webhook Token -->
                <div class="mb-4 mt-6">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1.5">
                        🔒 Webhook Token (Opcional)
                    </label>
                    <input type="text"
                           name="webhook_token"
                           value="{{ old('webhook_token', $settings->webhook_token ?? '') }}"
                           placeholder="Token para validar webhooks recebidos"
                           class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 font-mono">
                    <p class="text-xs text-gray-500 mt-1.5">
                        Configure este token no painel do Asaas → Webhooks → Access Token.<br>
                        URL do webhook: <code>{{ url('/api/webhooks/tenant-asaas') }}</code>
                    </p>
                </div>

                <!-- Ativo -->
                <div class="mb-6 mt-8">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $settings->is_active ?? false) ? 'checked' : '' }}
                               class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:ring-offset-gray-900">
                        <span class="text-sm font-semibold text-gray-800 dark:text-white">Ativar módulo de cobranças</span>
                    </label>
                </div>

                <!-- Status Badge -->
                @if($settings && $settings->is_active)
                    <div class="mt-8 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 text-sm border-l-4 border-green-500 uppercase tracking-wide font-bold">
                        ✅ Conectado — {{ $settings->environment === 'production' ? '🚀 Produção' : '🧪 Sandbox' }}
                    </div>
                @elseif($settings && !$settings->is_active)
                    <div class="mt-8 p-3 rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 text-sm border-l-4 border-yellow-500">
                        ⚠️ <strong>Desativado</strong> — Salve com "Ativar" marcado para começar a emitir cobranças.
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-admin::layouts>
