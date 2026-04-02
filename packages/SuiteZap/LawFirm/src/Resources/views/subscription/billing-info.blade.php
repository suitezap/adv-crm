<x-admin::layouts>
    <x-slot:title>
        Dados de Faturamento
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header / Breadcrumbs -->
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
                                        <a href="{{ route('admin.configuration.index') }}">Configurações</a>
                                        <span class="after:content-['/'] ltr:mr-1 rtl:ml-1"></span>
                                    </li>
                                    <li class="flex items-center gap-x-1 text-base text-gray-600 after:content-['/'] last:cursor-default after:last:hidden dark:text-gray-300" aria-current="page">
                                        Dados de Faturamento
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="text-xl font-bold dark:text-white">Dados de Faturamento</div>
            </div>
        </div>

        {{-- ── DADOS DO COMPRADOR / FATURAMENTO (SAAS) ─────────────────────── --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 mb-4 transition-all" id="billingInfoCard">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4 dark:border-gray-800">
                <div class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-gray-200">
                    <span class="icon-user text-xl"></span>
                    Dados do Comprador (Faturamento)
                </div>
                <button type="button" onclick="window.toggleBillingForm()" class="text-sm font-semibold text-brandColor hover:underline dark:text-blue-400">
                    Editar Dados
                </button>
            </div>

            @php
                $info = $billingInfo ?? null;
                $hasData = $info !== null;
                // Detecta o tipo de pessoa pelo preenchimento das colunas novas
                $isPJ = $hasData && !empty($info->cnpj);
            @endphp

            {{-- Visão de Leitura --}}
            <div id="billingInfoRead" class="{{ $hasData ? 'block' : 'hidden' }}">
                @if($hasData)
                    <div class="grid grid-cols-2 gap-4 text-sm max-md:grid-cols-1">
                        @if($isPJ)
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Razão Social</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->company_name ?: $info->name }}</strong>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">CNPJ</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->cnpj ?: $info->cpf_cnpj }}</strong>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Responsável (Nome)</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->name }}</strong>
                        </div>
                        @else
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Nome Completo</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->name }}</strong>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">CPF</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->cpf ?: $info->cpf_cnpj }}</strong>
                        </div>
                        @endif
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">E-mail de Faturamento</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->email }}</strong>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Telefone</span>
                            <strong class="text-gray-800 dark:text-gray-200">{{ $info->phone ?? '—' }}</strong>
                        </div>
                        <div class="col-span-2 max-md:col-span-1">
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Endereço</span>
                            <strong class="text-gray-800 dark:text-gray-200">
                                {{ $info->address }}, {{ $info->address_number }} {{ $info->complement ? "({$info->complement})" : '' }} - {{ $info->province }}<br>
                                {{ $info->city }} / {{ $info->state }} - CEP: {{ $info->postal_code }}
                            </strong>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Formulário de Edição --}}
            <div id="billingInfoForm" class="{{ !$hasData ? 'block' : 'hidden' }}">
                @if(!$hasData)
                    <div class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        <span class="font-bold">Atenção!</span> Você precisa preencher seus dados de faturamento para realizar compras ou emissão de notas fiscais pelo Asaas.
                    </div>
                @endif

                <form onsubmit="window.saveBillingInfo(event)" id="formBillingInfo">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">

                        {{-- Toggle Tipo de Pessoa --}}
                        <div class="col-span-2 flex items-center gap-4 mb-1">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tipo de Pessoa:</span>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="pessoa_tipo" value="PF" id="tipo_pf"
                                    {{ (!$isPJ) ? 'checked' : '' }}
                                    onchange="window.togglePessoaTipo('PF')"
                                    class="accent-brandColor">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Pessoa Física (PF)</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="pessoa_tipo" value="PJ" id="tipo_pj"
                                    {{ $isPJ ? 'checked' : '' }}
                                    onchange="window.togglePessoaTipo('PJ')"
                                    class="accent-brandColor">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Pessoa Jurídica (PJ)</span>
                            </label>
                        </div>

                        {{-- [PJ] Razão Social --}}
                        <div class="flex flex-col gap-1 col-span-2 pj-field {{ $isPJ ? '' : 'hidden' }}" id="fieldCompanyName">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Razão Social *</label>
                            <input type="text" name="company_name" id="billing_company_name"
                                value="{{ $info->company_name ?? '' }}"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        </div>

                        {{-- Nome do Responsável / Nome Completo --}}
                        <div class="flex flex-col gap-1" id="fieldName">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300" id="labelName">
                                {{ $isPJ ? 'Nome do Responsável *' : 'Nome Completo *' }}
                            </label>
                            <input type="text" name="name" value="{{ $info->name ?? '' }}" required
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        </div>

                        {{-- [PF] CPF --}}
                        <div class="flex flex-col gap-1 pf-field {{ $isPJ ? 'hidden' : '' }}" id="fieldCpf">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">CPF *</label>
                            <input type="text" name="cpf" id="billing_cpf"
                                value="{{ $info->cpf ?? ($info->cpf_cnpj ?? '') }}"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                placeholder="000.000.000-00">
                        </div>

                        {{-- [PJ] CNPJ --}}
                        <div class="flex flex-col gap-1 pj-field {{ $isPJ ? '' : 'hidden' }}" id="fieldCnpj">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">CNPJ *</label>
                            <input type="text" name="cnpj" id="billing_cnpj"
                                value="{{ $info->cnpj ?? '' }}"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                placeholder="00.000.000/0000-00">
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">E-mail *</label>
                            <input type="email" name="email" value="{{ $info->email ?? '' }}" required class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Telefone / WhatsApp</label>
                            <input type="text" name="phone" value="{{ $info->phone ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        </div>

                        <div class="col-span-2 border-t border-gray-100 dark:border-gray-800 my-2"></div>

                        <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Linha 1: CEP (1/3) e Logradouro (2/3) -->
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">CEP *</label>
                                <div class="relative">
                                    <input type="text" id="billing_postal_code" name="postal_code" value="{{ $info->postal_code ?? '' }}" required onblur="window.autofillBillingCep(this.value)" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition-all">
                                </div>
                            </div>
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Logradouro (Rua/Av) *</label>
                                <input type="text" id="billing_address" name="address" value="{{ $info->address ?? '' }}" required class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition-all text-ellipsis">
                            </div>

                            <!-- Linha 2: Número, Complemento e Bairro -->
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Número *</label>
                                <input type="text" name="address_number" value="{{ $info->address_number ?? '' }}" required class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Complemento</label>
                                <input type="text" name="complement" value="{{ $info->complement ?? '' }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bairro *</label>
                                <input type="text" id="billing_province" name="province" value="{{ $info->province ?? '' }}" required class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition-all">
                            </div>

                            <!-- Linha 3: Cidade e UF -->
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Cidade *</label>
                                <input type="text" id="billing_city" name="city" value="{{ $info->city ?? '' }}" required class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition-all">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">UF *</label>
                                <input type="text" id="billing_state" name="state" value="{{ $info->state ?? '' }}" maxlength="2" required class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 uppercase transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        @if($hasData)
                            <button type="button" onclick="window.toggleBillingForm()" class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                                Cancelar
                            </button>
                        @endif
                        <button type="submit" id="btnSaveBilling" class="rounded-md bg-brandColor px-4 py-2 text-sm font-semibold text-white hover:bg-brandColor/90 transition shadow-sm flex items-center gap-2">
                            <span class="icon-save text-lg"></span> Salvar Dados
                        </button>
                    </div>
                    <div id="billingMessage" class="mt-3 text-sm font-medium hidden"></div>
                </form>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
    <script>
        window.billingHasData = {{ $hasData ? 'true' : 'false' }};
        window.billingIsPJ    = {{ $isPJ ? 'true' : 'false' }};

        // --- MÁSCARAS ---
        const masks = {
            cpf: (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                return v.substring(0, 14);
            },
            cnpj: (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                v = v.replace(/(\d{4})(\d)/, "$1-$2");
                return v.substring(0, 18);
            },
            cep: (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{5})(\d)/, "$1-$2");
                return v.substring(0, 9);
            },
            phone: (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                v = v.replace(/(\d)(\d{4})$/, "$1-$2");
                return v.substring(0, 15);
            }
        };

        function applyMask(input, maskFn) {
            input.addEventListener('input', (e) => {
                e.target.value = maskFn(e.target.value);
            });
            input.value = maskFn(input.value);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const cpfInput  = document.getElementById('billing_cpf');
            const cnpjInput = document.getElementById('billing_cnpj');
            const cepInput  = document.getElementById('billing_postal_code');
            const phoneInput = document.querySelector('input[name="phone"]');

            if (cpfInput)  applyMask(cpfInput,  masks.cpf);
            if (cnpjInput) applyMask(cnpjInput, masks.cnpj);
            if (cepInput)  applyMask(cepInput,  masks.cep);
            if (phoneInput) applyMask(phoneInput, masks.phone);

            // Aplica blur para validação
            if (cpfInput)  cpfInput.addEventListener('blur',  (e) => checkDocUx(e.target, 'cpf'));
            if (cnpjInput) cnpjInput.addEventListener('blur', (e) => checkDocUx(e.target, 'cnpj'));
        });

        // --- TOGGLE TIPO DE PESSOA PF/PJ ---
        window.togglePessoaTipo = function(tipo) {
            const isPJ = (tipo === 'PJ');
            document.querySelectorAll('.pj-field').forEach(el => el.classList.toggle('hidden', !isPJ));
            document.querySelectorAll('.pf-field').forEach(el => el.classList.toggle('hidden', isPJ));

            const labelName = document.getElementById('labelName');
            if (labelName) labelName.textContent = isPJ ? 'Nome do Responsável *' : 'Nome Completo *';

            // Limpa campos opostos para não enviar dado inválido
            if (isPJ) {
                const cpfEl = document.getElementById('billing_cpf');
                if (cpfEl) cpfEl.value = '';
            } else {
                const cnpjEl = document.getElementById('billing_cnpj');
                const compEl = document.getElementById('billing_company_name');
                if (cnpjEl) cnpjEl.value = '';
                if (compEl) compEl.value = '';
            }
        };

        // --- VALIDAÇÃO CPF/CNPJ ---
        function isValidCpf(cpf) {
            if (/^(\d)\1{10}$/.test(cpf)) return false;
            let sum = 0, rest;
            for (let i = 1; i <= 9; i++) sum += parseInt(cpf.substring(i-1, i)) * (11 - i);
            rest = (sum * 10) % 11;
            if ((rest === 10) || (rest === 11)) rest = 0;
            if (rest !== parseInt(cpf.substring(9, 10))) return false;
            sum = 0;
            for (let i = 1; i <= 10; i++) sum += parseInt(cpf.substring(i-1, i)) * (12 - i);
            rest = (sum * 10) % 11;
            if ((rest === 10) || (rest === 11)) rest = 0;
            return rest === parseInt(cpf.substring(10, 11));
        }

        function isValidCnpj(cnpj) {
            if (/^(\d)\1{13}$/.test(cnpj)) return false;
            let size = cnpj.length - 2;
            let numbers = cnpj.substring(0, size);
            let digits = cnpj.substring(size);
            let sum = 0, pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (result !== parseInt(digits.charAt(0))) return false;
            size = size + 1;
            numbers = cnpj.substring(0, size);
            sum = 0; pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            return result === parseInt(digits.charAt(1));
        }

        function checkDocUx(input, tipo) {
            const val = input.value.replace(/\D/g, '');
            const errorId = tipo === 'cpf' ? 'cpfError' : 'cnpjError';
            const msgEl = document.getElementById(errorId) || createErrorEl(input, errorId);
            const valid = tipo === 'cpf' ? (val.length === 11 && isValidCpf(val)) : (val.length === 14 && isValidCnpj(val));
            if (val.length > 0 && !valid) {
                input.classList.add('border-red-500');
                msgEl.textContent = tipo === 'cpf' ? 'CPF inválido.' : 'CNPJ inválido.';
                msgEl.classList.remove('hidden');
                return false;
            }
            input.classList.remove('border-red-500');
            msgEl.classList.add('hidden');
            return true;
        }

        function createErrorEl(inputNode, id) {
            const el = document.createElement('span');
            el.id = id;
            el.className = 'text-xs text-red-500 mt-1 hidden';
            inputNode.parentNode.appendChild(el);
            return el;
        }

        // --- CEP AUTOPREENCHIMENTO COM UX ---
        window.autofillBillingCep = function(cep) {
            if (!cep) return;
            cep = cep.replace(/\D/g, '');
            if (cep.length !== 8) return;

            const cepInput = document.getElementById('billing_postal_code');
            const msgEl = document.getElementById('cepError') || createErrorEl(cepInput, 'cepError');
            
            cepInput.classList.add('opacity-50');
            msgEl.textContent = 'Buscando CEP...';
            msgEl.classList.remove('hidden', 'text-red-500');
            msgEl.classList.add('text-brandColor');

            fetch(`https://opencep.com/v1/${cep}`)
                .then(res => res.json())
                .then(data => {
                    cepInput.classList.remove('opacity-50');
                    if (data.error) {
                        cepInput.classList.add('border-red-500');
                        msgEl.textContent = 'CEP não encontrado.';
                        msgEl.classList.remove('text-brandColor');
                        msgEl.classList.add('text-red-500');
                        return;
                    }
                    cepInput.classList.remove('border-red-500');
                    msgEl.classList.add('hidden');

                    let inputAddr  = document.getElementById('billing_address');
                    let inputProv  = document.getElementById('billing_province');
                    let inputCity  = document.getElementById('billing_city');
                    let inputState = document.getElementById('billing_state');
                    
                    if (inputAddr)  { inputAddr.value  = data.logradouro; inputAddr.classList.add('bg-green-50'); }
                    if (inputProv)  { inputProv.value  = data.bairro;     inputProv.classList.add('bg-green-50'); }
                    if (inputCity)  { inputCity.value  = data.localidade; inputCity.classList.add('bg-green-50'); }
                    if (inputState) { inputState.value = data.uf;         inputState.classList.add('bg-green-50'); }

                    setTimeout(() => {
                        [inputAddr, inputProv, inputCity, inputState].forEach(el => {
                            if(el) el.classList.remove('bg-green-50');
                        });
                        document.querySelector('input[name="address_number"]')?.focus();
                    }, 800);
                })
                .catch(() => {
                    cepInput.classList.remove('opacity-50');
                    msgEl.textContent = 'Erro ao buscar o CEP.';
                    msgEl.classList.remove('text-brandColor');
                    msgEl.classList.add('text-red-500');
                });
        };

        // --- FORM E SUBMISSÃO ---
        window.toggleBillingForm = function() {
            const readEl = document.getElementById('billingInfoRead');
            const formEl = document.getElementById('billingInfoForm');
            
            if (formEl.classList.contains('hidden')) {
                formEl.classList.remove('hidden');
                if (readEl) readEl.classList.add('hidden');
            } else {
                if (window.billingHasData) {
                    formEl.classList.add('hidden');
                    if (readEl) readEl.classList.remove('hidden');
                }
            }
        };

        window.saveBillingInfo = function(e) {
            e.preventDefault();
            const form = e.target;

            // Valida o campo do tipo de pessoa ativo
            const tipoPJ = document.getElementById('tipo_pj')?.checked;
            if (tipoPJ) {
                const cnpjEl = document.getElementById('billing_cnpj');
                if (cnpjEl && !checkDocUx(cnpjEl, 'cnpj')) { cnpjEl.focus(); return; }
            } else {
                const cpfEl = document.getElementById('billing_cpf');
                if (cpfEl && !checkDocUx(cpfEl, 'cpf')) { cpfEl.focus(); return; }
            }

            const btn = document.getElementById('btnSaveBilling');
            const msg = document.getElementById('billingMessage');
            const formData = new FormData(form);

            btn.disabled = true;
            btn.innerHTML = '<span class="icon-loader animate-spin text-lg"></span> Salvando...';
            msg.className = 'mt-3 text-sm font-medium hidden';

            fetch('{{ route("admin.lawfirm.saas.billing-info.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    msg.textContent = 'Dados salvos com sucesso! Atualizando...';
                    msg.className = 'mt-3 text-sm font-medium text-green-600 dark:text-green-400 block p-3 bg-green-50 rounded border border-green-200';
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    msg.textContent = data.message || 'Erro desconhecido.';
                    msg.className = 'mt-3 text-sm font-medium text-red-600 dark:text-red-400 block p-3 bg-red-50 rounded border border-red-200';
                    btn.disabled = false;
                    btn.innerHTML = '<span class="icon-save text-lg"></span> Salvar Dados';
                }
            })
            .catch(err => {
                msg.textContent = 'Falha na comunicação com o servidor.';
                msg.className = 'mt-3 text-sm font-medium text-red-600 dark:text-red-400 block p-3 bg-red-50 rounded border border-red-200';
                btn.disabled = false;
                btn.innerHTML = '<span class="icon-save text-lg"></span> Salvar Dados';
            });
        };
    </script>
    @endPushOnce
</x-admin::layouts>
