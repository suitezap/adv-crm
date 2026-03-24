<x-admin::layouts>
    <x-slot:title>
        Certificados Digitais - Escavador
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center text-gray-500">
                    <span class="text-xs font-medium">Configurações Jurídicas <span class="mx-1">/</span> Certificados Digitais</span>
                </div>
                <div class="text-xl font-bold dark:text-white flex items-center gap-2">
                    <span class="text-2xl">🔐</span> Certificados Digitais
                </div>
            </div>
        </div>

        <!-- Formulário Área (Estilo Card igual Assinatura) -->
        <div class="flex flex-1 flex-col gap-4 max-xl:flex-auto p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
            
            <!-- Card Header -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-1">
                <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex flex-col">
                    <div class="flex items-center gap-2">
                        Enviar Novo Certificado
                    </div>
                    <span class="text-sm font-normal text-gray-500 mt-1">Cadastre um certificado A1 (.pfx ou .p12) para permitir consulta de processos sigilosos.</span>
                </div>
            </div>
            
            <form id="lf-cert-form" onsubmit="window.lfCertPage.submit(event)" class="mt-2">
                <input type="text" name="username" autocomplete="username" style="display: none;">
                
                <div class="grid grid-cols-3 gap-4 items-end">
                    <!-- Arquivo (Coluna 1) -->
                    <x-admin::form.control-group class="mb-0">
                        <x-admin::form.control-group.label class="required"> 
                            Arquivo do Certificado
                        </x-admin::form.control-group.label>
                        <input type="file" id="lf-cert-file" accept=".pfx,.p12" required="" 
                               class="w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 file:mr-4 file:rounded file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                    </x-admin::form.control-group>

                    <!-- Senha (Coluna 2) -->
                    <x-admin::form.control-group class="mb-0">
                        <x-admin::form.control-group.label class="required"> 
                            Senha
                        </x-admin::form.control-group.label>
                        <input type="password" name="password" autocomplete="new-password" id="lf-cert-senha" required="" placeholder="Senha..." 
                               class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 transition-all focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </x-admin::form.control-group>

                    <!-- Botão (Coluna 3) -->
                    <x-admin::form.control-group class="mb-0">
                        <button type="submit" id="lf-cert-btn-submit" 
                                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 whitespace-nowrap" 
                                style="background-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }}; border-color: {{ core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0041FF' }};">
                            <svg class="h-4 w-4 shrink-0 border-transparent shadow-none drop-shadow-none mx-0 my-0 border-0 bg-transparent text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <span>Instalar</span>
                        </button>
                    </x-admin::form.control-group>
                </div>
            </form>
            <div id="lf-cert-msg-feedback" style="display:none;" class="mt-2"></div>
        </div>

        <!-- Tabela Área (Estilo Card igual Assinatura) -->
        <div class="flex flex-1 flex-col gap-4 max-xl:flex-auto p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
            <!-- Card Header com Borda Interna -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-1 gap-4">
                <div class="text-lg font-bold text-gray-800 dark:text-gray-200 flex flex-col">
                    <div class="flex items-center gap-2">
                        Certificados Ativos
                    </div>
                    <span class="text-sm font-normal text-gray-500 mt-1">Esses certificados serão disponibilizados durante a execução dos Assistentes Jurídicos.</span>
                </div>
                <button type="button" onclick="window.lfCertPage.load()" 
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 whitespace-nowrap">
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Atualizar Lista
                </button>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 mt-2">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-6 py-4 font-semibold">ID Escavador</th>
                            <th class="px-6 py-4 font-semibold">CPF/CNPJ</th>
                            <th class="px-6 py-4 font-semibold hidden md:table-cell">Emissor</th>
                            <th class="px-6 py-4 font-semibold">Validade</th>
                            <th class="px-6 py-4 font-semibold text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="lf-cert-list-body" class="divide-y divide-gray-200 dark:divide-gray-800">
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-blue-600"></div>
                                    <span class="font-medium text-sm">Carregando lista de certificados...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        (function() {
            var ROUTE_INDEX = "{{ route('lawfirm.escavador.certificados.index') }}";
            var ROUTE_STORE = "{{ route('lawfirm.escavador.certificados.store') }}";
            var ROUTE_DESTROY = "{{ route('lawfirm.escavador.certificados.destroy', ':id') }}";
            var ROUTE_SHOW = "{{ route('lawfirm.escavador.certificados.show', ':id') }}";
            var CSRF = '{{ csrf_token() }}';

            function resetForm() {
                var form = document.getElementById('lf-cert-form');
                if (form) form.reset();
                var feedback = document.getElementById('lf-cert-msg-feedback');
                if (feedback) feedback.style.display = 'none';
            }

            function showFeedback(msg, isError) {
                var el = document.getElementById('lf-cert-msg-feedback');
                el.style.display = 'block';
                el.innerHTML = msg;
                if (isError) {
                    el.className = 'mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-600 shadow-sm dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400';
                } else {
                    el.className = 'mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 shadow-sm dark:border-emerald-900/30 dark:bg-emerald-900/20 dark:text-emerald-400';
                }
            }

            function loadCerts() {
                var tbody = document.getElementById('lf-cert-list-body');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-blue-600"></div>
                                <span class="font-medium text-sm">Atualizando dados...</span>
                            </div>
                        </td>
                    </tr>
                `;
                
                fetch(ROUTE_INDEX, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) {
                            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center font-semibold text-red-600 dark:text-red-400">Erro ao carregar: ' + (res.error || 'Falha desconhecida') + '</td></tr>';
                            return;
                        }
                        var data = res.data || [];
                        if (!Array.isArray(data)) {
                            if (data.data && Array.isArray(data.data)) data = data.data;
                        }

                        if (data.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="text-4xl mb-3">📄</div>
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-200">Nenhum certificado cadastrado</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Utilize o formulário acima para instalar seu primeiro e-CPF ou e-CNPJ.</p>
                                    </td>
                                </tr>
                            `;
                            return;
                        }
                        
                        tbody.innerHTML = '';
                        data.forEach(function(cert) {
                            var validadeStr = cert.validade ? new Date(cert.validade).toLocaleDateString('pt-BR') : '-';
                            var tr = document.createElement('tr');
                            tr.className = "hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors";
                            tr.innerHTML = `
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-blue-100 text-blue-700 text-xs dark:bg-blue-900/30 dark:text-blue-400">#</span>
                                    ${cert.id}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">${cert.cpf_cnpj || '-'}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 max-w-[200px] truncate hidden md:table-cell" title="${cert.emissor || ''}">${cert.emissor || '-'}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        <svg class="h-3 w-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        ${validadeStr}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end gap-2">
                                    <button type="button" onclick="window.lfCertPage.show(${cert.id})" title="Ver Dados do Certificado"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 transition-all hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-600/50 dark:border-blue-900/50 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40">
                                        Detalhes
                                    </button>
                                    <button type="button" onclick="window.lfCertPage.remove(${cert.id})" title="Remover Certificado"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition-all hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600/50 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40">
                                        Remover
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    })
                    .catch(err => {
                        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center font-semibold text-red-600 dark:text-red-400">Falha de conexão com o servidor.</td></tr>';
                    });
            }

            function submitCert(e) {
                e.preventDefault();
                var fileInput = document.getElementById('lf-cert-file');
                var senhaInput = document.getElementById('lf-cert-senha');
                
                if (!fileInput.files.length) return;

                var formData = new FormData();
                formData.append('file', fileInput.files[0]);
                formData.append('senha', senhaInput.value);

                var btn = document.getElementById('lf-cert-btn-submit');
                
                var originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Processando...</span>`;

                fetch(ROUTE_STORE, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                    
                    if (res.success) {
                        showFeedback('✅ <b>Sucesso:</b> Certificado instalado corretamente! <b>(ID Escavador: ' + res.cert_id + ')</b>', false);
                        document.getElementById('lf-cert-form').reset();
                        loadCerts();
                    } else {
                        showFeedback('⚠️ <b>Falha ao instalar:</b> ' + (res.error || res.message || 'Erro desconhecido. Verifique a senha e o arquivo.'), true);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                    showFeedback('❌ <b>Erro de Conexão:</b> Falha ao comunicar com o servidor.', true);
                });
            }

            function removeCert(id) {
                if (!confirm('Deseja realmente remover permanentemente o certificado #' + id + '?\nIsso pode interromper as atualizações de processos em andamento que dependam dele.')) return;
                
                // Mostrar estado de carregamento local
                var url = ROUTE_DESTROY.replace(':id', id);
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showFeedback('✅ <b>Removido:</b> Certificado #' + id + ' excluído com sucesso.', false);
                        loadCerts();
                    } else {
                        alert('Erro ao excluir certificado: ' + (res.error || res.message || 'Falha desconhecida'));
                    }
                })
                .catch(err => {
                    alert('Erro de conexão ao tentar remover o certificado.');
                });
            }

            function showCert(id) {
                var url = ROUTE_SHOW.replace(':id', id);
                fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        var data = res.data;
                        var msg = "🛡️ DETALHES DO CERTIFICADO #" + data.id + "\n\n";
                        msg += "CPF/CNPJ: " + (data.cpf_cnpj || "-") + "\n";
                        msg += "Emissor: " + (data.emissor || "-") + "\n";
                        if (data.validade) {
                            msg += "Validade: " + new Date(data.validade).toLocaleDateString('pt-BR') + " " + new Date(data.validade).toLocaleTimeString('pt-BR') + "\n";
                        }
                        if (data.status) {
                            msg += "Status Atual: " + data.status + "\n";
                        }
                        alert(msg);
                    } else {
                        alert('Erro ao consultar detalhes: ' + (res.error || res.message || 'Falha desconhecida'));
                    }
                })
                .catch(err => {
                    alert('Erro de conexão ao tentar consultar certificado.');
                });
            }

            window.lfCertPage = {
                load: loadCerts,
                submit: submitCert,
                remove: removeCert,
                show: showCert
            };

            // Auto-load on start
            document.addEventListener('DOMContentLoaded', function() {
                loadCerts();
            });
        })();
    </script>
    @endpush

</x-admin::layouts>
