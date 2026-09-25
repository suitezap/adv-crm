<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro & Envio de Arquivos - {{ $officeName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .input-field {
            width: 100%; border-radius: 0.375rem; border: 1px solid #d1d5db; padding: 0.5rem 0.75rem; font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .input-field:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
        .label-text { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem; }
    </style>
</head>
<body class="text-gray-800 antialiased h-full">

<div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-3xl">
        @if($logoUrl)
            <img class="mx-auto h-16 w-auto object-contain" src="{{ $logoUrl }}" alt="{{ $officeName }}">
        @endif
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Cadastro & Envio de Arquivos
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Mantenha seus dados atualizados e envie seus documentos para o andamento do seu processo.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-3xl">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border-t-4 border-indigo-600">

            <div id="alert-message" class="hidden mb-6 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0" id="alert-icon"></div>
                    <div class="ml-3">
                        <p class="text-sm font-medium" id="alert-text"></p>
                    </div>
                </div>
            </div>

            <!-- SELEÇÃO DE AÇÃO: 3 BOTÕES -->
            <div class="mb-8 grid grid-cols-1 sm:grid-cols-3 gap-3">

                <!-- Botão Atualizar -->
                <button id="tab-atualizar" type="button"
                    class="group flex flex-col items-center text-center p-4 rounded-xl border-2 border-indigo-600 bg-indigo-600 text-white shadow-md transition-all outline-none"
                    onclick="switchTab('atualizar')">
                    <span class="mb-2 flex items-center justify-center w-10 h-10 rounded-full bg-white/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </span>
                    <span class="text-sm font-bold leading-tight">Atualizar</span>
                    <span class="text-xs mt-1 opacity-90 leading-snug">Informação do cliente já cadastrado, quem abriu a causa&nbsp;/ processo</span>
                </button>

                <!-- Botão Cadastrar -->
                <button id="tab-novo" type="button"
                    class="group flex flex-col items-center text-center p-4 rounded-xl border-2 border-gray-300 bg-white text-gray-700 shadow-sm hover:border-indigo-400 hover:shadow-md transition-all outline-none"
                    onclick="switchTab('novo')">
                    <span class="mb-2 flex items-center justify-center w-10 h-10 rounded-full bg-white/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </span>
                    <span class="text-sm font-bold leading-tight">Cadastrar</span>
                    <span class="text-xs mt-1 opacity-80 leading-snug">Cadastre aqui suas informações se for parte integrante da causa&nbsp;/ processo</span>
                </button>

                <!-- Botão Enviar Arquivos -->
                <button id="tab-envio" type="button"
                    class="group flex flex-col items-center text-center p-4 rounded-xl border-2 border-gray-300 bg-white text-gray-700 shadow-sm hover:border-indigo-400 hover:shadow-md transition-all outline-none"
                    onclick="switchTab('envio')">
                    <span class="mb-2 flex items-center justify-center w-10 h-10 rounded-full bg-white/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </span>
                    <span class="text-sm font-bold leading-tight">Enviar Arquivos</span>
                    <span class="text-xs mt-1 opacity-80 leading-snug">Envie seus documentos para o andamento do processo</span>
                </button>

            </div>

            <!-- Formulário de Cadastro -->
            <div id="cadastro-container">
                <form id="cadastroForm" onsubmit="event.preventDefault(); salvarDados();">
                    @csrf
                <input type="hidden" name="token" value="{{ request()->query('token') }}">
                
                <!-- Client Type Selection -->
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <span class="text-sm font-medium text-gray-700">Tipo de Cliente</span>
                    <div class="mt-2 flex items-center space-x-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="client_type" value="PF" class="form-radio text-indigo-600 focus:ring-indigo-500 h-4 w-4" 
                                {{ $clientType === 'PF' || !$clientType ? 'checked' : '' }} onchange="toggleClientType('PF')">
                            <span class="ml-2 text-sm text-gray-700 cursor-pointer">Pessoa Física</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="client_type" value="PJ" class="form-radio text-indigo-600 focus:ring-indigo-500 h-4 w-4" 
                                {{ $clientType === 'PJ' ? 'checked' : '' }} onchange="toggleClientType('PJ')">
                            <span class="ml-2 text-sm text-gray-700 cursor-pointer">Pessoa Jurídica</span>
                        </label>
                    </div>
                </div>

                <!-- PESSOA FÍSICA AREA -->
                <div id="area_pf" class="{{ $clientType === 'PJ' ? 'hidden' : '' }}">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Dados Pessoais</h3>
                    <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6 mb-6">
                        <div class="sm:col-span-6">
                            <label for="name_pf" class="label-text">Nome Completo</label>
                            <input type="text" name="name_pf" id="name_pf" class="input-field" value="{{ $processo->person->name ?? '' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="cpf" class="label-text">CPF</label>
                            <input type="text" name="cpf" id="cpf" class="input-field mask-cpf" value="{{ $lawDetails->cpf ?? '' }}" placeholder="000.000.000-00">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="rg" class="label-text">RG</label>
                            <input type="text" name="rg" id="rg" class="input-field" value="{{ $lawDetails->rg ?? '' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="email_pf" class="label-text">E-mail</label>
                            <input type="email" name="email_pf" id="email_pf" class="input-field" value="{{ isset($processo->person->emails[0]['value']) ? $processo->person->emails[0]['value'] : '' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="phone_pf" class="label-text">Celular / WhatsApp</label>
                            <input type="text" name="phone_pf" id="phone_pf" class="input-field mask-phone" value="{{ isset($processo->person->contact_numbers[0]['value']) ? $processo->person->contact_numbers[0]['value'] : '' }}" placeholder="(00) 00000-0000">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="birth_date" class="label-text">Data de Nascimento <span class="text-red-500">*</span></label>
                            <input type="date" name="birth_date" id="birth_date" class="input-field bg-white" value="{{ isset($lawDetails->data_nascimento) ? \Carbon\Carbon::parse($lawDetails->data_nascimento)->format('Y-m-d') : '' }}" required>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="nationality" class="label-text">Nacionalidade</label>
                            <input type="text" name="nationality" id="nationality" class="input-field" value="{{ $lawDetails->nacionalidade ?? 'Brasileiro(a)' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="marital_status" class="label-text">Estado Civil</label>
                            <select name="marital_status" id="marital_status" class="input-field bg-white">
                                @php $estadoCivil = $lawDetails->estado_civil ?? ''; @endphp
                                <option value="" disabled {{ !$estadoCivil ? 'selected' : '' }}>Selecione...</option>
                                <option value="Solteiro(a)" {{ $estadoCivil == 'Solteiro(a)' ? 'selected' : '' }}>Solteiro(a)</option>
                                <option value="Casado(a)" {{ $estadoCivil == 'Casado(a)' ? 'selected' : '' }}>Casado(a)</option>
                                <option value="Divorciado(a)" {{ $estadoCivil == 'Divorciado(a)' ? 'selected' : '' }}>Divorciado(a)</option>
                                <option value="Viúvo(a)" {{ $estadoCivil == 'Viúvo(a)' ? 'selected' : '' }}>Viúvo(a)</option>
                                <option value="União Estável" {{ $estadoCivil == 'União Estável' ? 'selected' : '' }}>União Estável</option>
                            </select>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="profession" class="label-text">Profissão</label>
                            <input type="text" name="profession" id="profession" class="input-field" value="{{ $lawDetails->profissao ?? '' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="mother_name" class="label-text">Nome da Mãe <span class="text-red-500">*</span></label>
                            <input type="text" name="mother_name" id="mother_name" class="input-field" value="{{ $lawDetails->nome_mae ?? '' }}" required>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="father_name" class="label-text">Nome do Pai</label>
                            <input type="text" name="father_name" id="father_name" class="input-field" value="{{ $lawDetails->nome_pai ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- PESSOA JURÍDICA AREA -->
                <div id="area_pj" class="{{ $clientType === 'PF' || !$clientType ? 'hidden' : '' }}">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Dados da Empresa</h3>
                    <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6 mb-6">
                        <div class="sm:col-span-6">
                            <label for="name_pj" class="label-text">Razão Social</label>
                            <input type="text" name="name_pj" id="name_pj" class="input-field" value="{{ $processo->organization->name ?? '' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="cnpj" class="label-text">CNPJ</label>
                            <input type="text" name="cnpj" id="cnpj" class="input-field mask-cnpj" value="{{ $lawDetails->cnpj ?? '' }}" placeholder="00.000.000/0000-00">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="legal_nature" class="label-text">Natureza Jurídica (CNAE)</label>
                            <input type="text" name="legal_nature" id="legal_nature" class="input-field" value="{{ $lawDetails->cnae ?? '' }}">
                        </div>

                        <div class="sm:col-span-6">
                            <label for="legal_representative_name" class="label-text">Representante Legal (Sócio / Diretor e CPF)</label>
                            <input type="text" name="legal_representative_name" id="legal_representative_name" class="input-field" value="{{ $lawDetails->representante_legal ?? '' }}">
                        </div>

                        <div class="sm:col-span-2 hidden">
                            <label for="legal_representative_cpf" class="label-text">CPF do Representante</label>
                            <input type="text" name="legal_representative_cpf" id="legal_representative_cpf" class="input-field mask-cpf" value="">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="state_registration" class="label-text">Inscrição Estadual</label>
                            <input type="text" name="state_registration" id="state_registration" class="input-field" value="{{ $lawDetails->inscricao_estadual ?? '' }}">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="municipal_registration" class="label-text">Inscrição Municipal</label>
                            <input type="text" name="municipal_registration" id="municipal_registration" class="input-field" value="{{ $lawDetails->inscricao_municipal ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- ENDEREÇO AREA (Comum a PF e PJ) -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Endereço</h3>
                    
                    @php
                        $address = $lawDetails;
                    @endphp

                    <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-2">
                            <label for="cep" class="label-text">CEP <span class="text-red-500">*</span></label>
                            <input type="text" name="cep" id="cep" class="input-field mask-cep" onblur="buscarCep()" value="{{ $address->cep ?? '' }}" placeholder="00000-000" required>
                        </div>
                        <div class="sm:col-span-4 flex items-center">
                            <span id="cep-loader" class="hidden text-sm text-indigo-600"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Buscando endereço...</span>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="street" class="label-text">Logradouro (Rua, Av, etc) <span class="text-red-500">*</span></label>
                            <input type="text" name="street" id="street" class="input-field" value="{{ $address->logradouro ?? '' }}" required>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="number" class="label-text">Número <span class="text-red-500">*</span></label>
                            <input type="text" name="number" id="number" class="input-field" value="{{ $address->numero ?? '' }}" required>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="complement" class="label-text">Complemento (Opcional)</label>
                            <input type="text" name="complement" id="complement" class="input-field" value="{{ $address->complemento ?? '' }}">
                        </div>

                        <div class="sm:col-span-3">
                            <label for="neighborhood" class="label-text">Bairro <span class="text-red-500">*</span></label>
                            <input type="text" name="neighborhood" id="neighborhood" class="input-field" value="{{ $address->bairro ?? '' }}" required>
                        </div>

                        <div class="sm:col-span-4">
                            <label for="city" class="label-text">Cidade <span class="text-red-500">*</span></label>
                            <input type="text" name="city" id="city" class="input-field" value="{{ $address->cidade ?? '' }}" required>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="state" class="label-text">Estado <span class="text-red-500">*</span></label>
                            <select name="state" id="state" class="input-field bg-white" required>
                                <option value="" disabled>UF</option>
                                @php
                                    $states = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                    $currentState = $address->uf ?? '';
                                @endphp
                                @foreach($states as $st)
                                    <option value="{{ $st }}" {{ $currentState == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-5 text-right">
                    <button type="submit" id="btnSalvar" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-full sm:w-auto">
                        Salvar Informações
                    </button>
                </div>
                </form>
            </div>

            <!-- Formulário Novo Participante -->
            <div id="novo-container" class="hidden">
                <div class="mb-4 rounded-lg bg-indigo-50 border border-indigo-200 p-4">
                    <p class="text-sm text-indigo-800 font-medium">📋 Preencha suas informações abaixo. Seus dados serão adicionados como parte integrante deste processo.</p>
                </div>

                @if($processo->participantes->count() > 0)
                <div class="mb-6 rounded-lg bg-white border border-gray-200 p-4 shadow-sm">
                    <h3 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">Cadastros Realizados</h3>
                    <ul class="divide-y divide-gray-100">
                        @foreach($processo->participantes as $part)
                            @php
                                $isPf = $part->person_id ? true : false;
                                $nome = $isPf ? ($part->person->name ?? 'N/A') : ($part->organization->name ?? 'N/A');
                                $doc = $isPf ? ($part->person->person_details->cpf ?? '') : ($part->organization->organization_details->cnpj ?? '');
                                $email = $isPf ? ($part->person->emails[0]['value'] ?? '') : '';
                                $phone = $isPf ? ($part->person->contact_numbers[0]['value'] ?? '') : '';
                            @endphp
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $nome }}</p>
                                    <p class="text-xs text-gray-500">{{ $isPf ? 'Pessoa Física' : 'Pessoa Jurídica' }} {{ $doc ? '- '.$doc : '' }}</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" class="text-indigo-600 hover:text-indigo-800" onclick="editarParticipante({{ $part->id }}, '{{ $isPf ? 'PF' : 'PJ' }}', '{{ addslashes($nome) }}', '{{ $doc }}', '{{ $email }}', '{{ $phone }}')" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button type="button" class="text-red-500 hover:text-red-700" onclick="apagarParticipante({{ $part->id }})" title="Apagar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form id="novoForm" onsubmit="event.preventDefault(); salvarNovoParticipante();">
                    @csrf
                    <input type="hidden" name="token" value="{{ request()->query('token') }}">
                    <input type="hidden" name="participante_tipo" value="novo">
                    <input type="hidden" name="participante_id" id="participante_id" value="">

                    <!-- Tipo de Cliente -->
                    <div class="mb-6 pb-4 border-b border-gray-200">
                        <span class="text-sm font-medium text-gray-700">Tipo</span>
                        <div class="mt-2 flex items-center space-x-6">
                            <label class="inline-flex items-center">
                                <input type="radio" name="novo_client_type" value="PF" class="form-radio text-indigo-600 h-4 w-4" checked onchange="toggleNovoType('PF')">
                                <span class="ml-2 text-sm text-gray-700">Pessoa Física</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="novo_client_type" value="PJ" class="form-radio text-indigo-600 h-4 w-4" onchange="toggleNovoType('PJ')">
                                <span class="ml-2 text-sm text-gray-700">Pessoa Jurídica</span>
                            </label>
                        </div>
                    </div>

                    <!-- Pessoa Física -->
                    <div id="novo-area-pf">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Dados Pessoais</h3>
                        <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6 mb-6">
                            <div class="sm:col-span-6">
                                <label for="novo_name" class="label-text">Nome Completo <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_name" id="novo_name" class="input-field" required>
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_cpf" class="label-text">CPF <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_cpf" id="novo_cpf" class="input-field mask-cpf" placeholder="000.000.000-00" required>
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_rg" class="label-text">RG</label>
                                <input type="text" name="novo_rg" id="novo_rg" class="input-field">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_email" class="label-text">E-mail <span class="text-red-500">*</span></label>
                                <input type="email" name="novo_email" id="novo_email" class="input-field" required>
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_phone" class="label-text">Celular / WhatsApp <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_phone" id="novo_phone" class="input-field mask-phone" placeholder="(00) 00000-0000" required>
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_birth_date" class="label-text">Data de Nascimento</label>
                                <input type="date" name="novo_birth_date" id="novo_birth_date" class="input-field bg-white">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_nationality" class="label-text">Nacionalidade</label>
                                <input type="text" name="novo_nationality" id="novo_nationality" class="input-field" value="Brasileiro(a)">
                            </div>
                        </div>
                    </div>

                    <!-- Pessoa Jurídica -->
                    <div id="novo-area-pj" class="hidden">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Dados da Empresa</h3>
                        <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6 mb-6">
                            <div class="sm:col-span-6">
                                <label for="novo_name_pj" class="label-text">Razão Social <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_name_pj" id="novo_name_pj" class="input-field">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_cnpj" class="label-text">CNPJ <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_cnpj" id="novo_cnpj" class="input-field mask-cnpj" placeholder="00.000.000/0000-00">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_rep_name" class="label-text">Representante Legal <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_rep_name" id="novo_rep_name" class="input-field">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_rep_cpf" class="label-text">CPF do Representante <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_rep_cpf" id="novo_rep_cpf" class="input-field mask-cpf" placeholder="000.000.000-00">
                            </div>
                            <div class="sm:col-span-3">
                                <label for="novo_rep_phone" class="label-text">Telefone <span class="text-red-500">*</span></label>
                                <input type="text" name="novo_rep_phone" id="novo_rep_phone" class="input-field mask-phone" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-5 text-right">
                        <button type="submit" id="btnSalvarNovo" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-full sm:w-auto">
                            Cadastrar Participante
                        </button>
                    </div>
                </form>
            </div>

            <!-- Formulário de Upload -->
            <div id="envio-container" class="hidden">
                <div class="mb-6 rounded-md bg-white p-6 border border-gray-200 shadow-sm mt-4">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Envio de Arquivos</h3>
                    <p class="text-sm text-gray-500 mb-6">Arraste seus documentos (RG, CNH, Comprovante de Endereço, etc) para a área abaixo ou clique para selecionar do seu dispositivo.</p>
                    
                    <div id="upload-area" class="relative flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 hover:bg-gray-100 transition-all cursor-pointer w-full mb-4">
                        <div class="p-3 bg-white rounded-full shadow-sm mb-3">
                            <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 text-center">
                            <span class="text-indigo-600 font-bold hover:underline">Clique para anexar</span> ou arraste
                        </p>
                        <p class="text-xs text-gray-400 mt-1 pb-4">PDF, DOCX, JPG, JPEG, PNG (Mín: 1, Máx: 20MB)</p>
                        
                        <input type="file" id="fileInput" name="file" class="hidden" accept="application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" onchange="uploadFile()">
                    </div>

                    <div id="upload-progress" class="hidden w-full bg-gray-200 rounded-full h-2 mb-4">
                        <div id="progress-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
                <div id="file-list" class="mt-4 space-y-2">
                    <!-- Files will be queued here -->
                </div>
            </div>

        </div>

        <p class="mt-8 text-center text-xs text-gray-400">
            Ambiente Seguro e Criptografado &bull; Protegido pela Lei Geral de Proteção de Dados (LGPD) <br>
            @if($officeWebsite) <a href="{{ $officeWebsite }}" class="hover:text-gray-500" target="_blank">{{ $officeName }}</a> @else {{ $officeName }} @endif
        </p>

    </div>
</div>

<script>
    // Input Masking (Simple)
    document.addEventListener('DOMContentLoaded', () => {
        const masks = {
            cpf: (v) => v.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
            cnpj: (v) => v.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2'),
            cep: (v) => v.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2'),
            phone: (v) => {
                const r = v.replace(/\D/g, '');
                if (r.length > 10) return r.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
                return r.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            }
        };

        const applyMask = (selector, type) => {
            const inputs = document.querySelectorAll(selector);
            inputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    e.target.value = masks[type](e.target.value);
                });
            });
        };

        applyMask('.mask-cpf', 'cpf');
        applyMask('.mask-cnpj', 'cnpj');
        applyMask('.mask-cep', 'cep');
        applyMask('.mask-phone', 'phone');

        // Apply mandatory fields properly on start
        toggleClientType('{{ $clientType === "PJ" ? "PJ" : "PF" }}');
    });

    function toggleClientType(type) {
        const pfFields = ['name_pf', 'cpf', 'phone_pf', 'mother_name'];
        const pjFields = ['name_pj', 'cnpj', 'legal_representative_name', 'legal_representative_cpf'];

        if (type === 'PF') {
            document.getElementById('area_pf').classList.remove('hidden');
            document.getElementById('area_pj').classList.add('hidden');
            pfFields.forEach(f => {
                const el = document.getElementById(f);
                if(el) {
                    el.required = true;
                    el.previousElementSibling.innerHTML = el.previousElementSibling.innerText.replace(' *','') + ' <span class="text-red-500">*</span>';
                }
            });
            pjFields.forEach(f => {
                const el = document.getElementById(f);
                if(el) {
                    el.required = false;
                    el.previousElementSibling.innerHTML = el.previousElementSibling.innerText.replace(' *','');
                }
            });
        } else {
            document.getElementById('area_pj').classList.remove('hidden');
            document.getElementById('area_pf').classList.add('hidden');
            pjFields.forEach(f => {
                const el = document.getElementById(f);
                if(el) {
                    el.required = true;
                    el.previousElementSibling.innerHTML = el.previousElementSibling.innerText.replace(' *','') + ' <span class="text-red-500">*</span>';
                }
            });
            pfFields.forEach(f => {
                const el = document.getElementById(f);
                if(el) {
                    el.required = false;
                    el.previousElementSibling.innerHTML = el.previousElementSibling.innerText.replace(' *','');
                }
            });
        }
    }

    function buscarCep() {
        const cepRaw = document.getElementById('cep').value.replace(/\D/g, '');
        if (cepRaw.length !== 8) return;

        document.getElementById('cep-loader').classList.remove('hidden');

        fetch(`https://viacep.com.br/ws/${cepRaw}/json/`)
            .then(r => r.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('street').value = data.logradouro;
                    document.getElementById('neighborhood').value = data.bairro;
                    document.getElementById('city').value = data.localidade;
                    document.getElementById('state').value = data.uf;
                    document.getElementById('number').focus();
                }
            })
            .catch(err => console.log('Erro ao buscar CEP:', err))
            .finally(() => {
                document.getElementById('cep-loader').classList.add('hidden');
            });
    }

    function showAlert(message, type = 'success') {
        const alertBox = document.getElementById('alert-message');
        const alertIcon = document.getElementById('alert-icon');
        const alertText = document.getElementById('alert-text');
        
        alertBox.className = `mb-6 rounded-md p-4 ${type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'} block`;
        alertText.innerText = message;
        
        if (type === 'success') {
            alertIcon.innerHTML = `<svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>`;
        } else {
            alertIcon.innerHTML = `<svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>`;
        }

        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 5000);
    }

    function salvarDados() {
        const form = document.getElementById('cadastroForm');
        const btn = document.getElementById('btnSalvar');
        originalHtml = btn.innerHTML;
        
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Salvando...`;
        btn.disabled = true;

        const csrfToken = document.querySelector('input[name="_token"]').value;
        const formData = new FormData(form);
        
        const payload = Object.fromEntries(formData.entries());
        // Ajustar nome / email para o backend
        const clientType = payload.client_type;
        if(clientType === 'PF') {
            payload.name = payload.name_pf;
            payload.email = payload.email_pf;
            payload.phone = payload.phone_pf;
        } else {
            payload.name = payload.name_pj;
        }

        fetch("{{ route('lawfirm.public.portal.update', $processo->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showAlert(data.message, 'success');
            } else {
                showAlert(data.message || 'Erro ao salvar os dados.', 'error');
            }
        })
        .catch(err => {
            showAlert('Falha na comunicação com o servidor.', 'error');
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }

    // Classes dos botões de ação
    const BTN_ACTIVE   = 'group flex flex-col items-center text-center p-4 rounded-xl border-2 border-indigo-600 bg-indigo-600 text-white shadow-md transition-all outline-none';
    const BTN_INACTIVE = 'group flex flex-col items-center text-center p-4 rounded-xl border-2 border-gray-300 bg-white text-gray-700 shadow-sm hover:border-indigo-400 hover:shadow-md transition-all outline-none';

    function switchTab(tab) {
        // Ocultar todos os containers
        ['cadastro-container', 'novo-container', 'envio-container'].forEach(id => {
            document.getElementById(id).classList.add('hidden');
        });
        // Desativar todos os botões
        ['tab-atualizar', 'tab-novo', 'tab-envio'].forEach(id => {
            document.getElementById(id).className = BTN_INACTIVE;
        });

        // Ativar o container e botão selecionado
        if (tab === 'atualizar') {
            document.getElementById('cadastro-container').classList.remove('hidden');
            document.getElementById('tab-atualizar').className = BTN_ACTIVE;
        } else if (tab === 'novo') {
            document.getElementById('novo-container').classList.remove('hidden');
            document.getElementById('tab-novo').className = BTN_ACTIVE;
        } else {
            document.getElementById('envio-container').classList.remove('hidden');
            document.getElementById('tab-envio').className = BTN_ACTIVE;
        }
    }

    function toggleNovoType(type) {
        if (type === 'PF') {
            document.getElementById('novo-area-pf').classList.remove('hidden');
            document.getElementById('novo-area-pj').classList.add('hidden');
        } else {
            document.getElementById('novo-area-pf').classList.add('hidden');
            document.getElementById('novo-area-pj').classList.remove('hidden');
        }
    }

    function editarParticipante(id, tipo, nome, doc, email, phone) {
        document.getElementById('participante_id').value = id;
        
        // Seleciona o radio correspondente e aciona o toggle
        const radios = document.getElementsByName('novo_client_type');
        for (let i = 0; i < radios.length; i++) {
            if (radios[i].value === tipo) {
                radios[i].checked = true;
                toggleNovoType(tipo);
            }
        }

        if (tipo === 'PF') {
            document.getElementById('novo_name').value = nome;
            document.getElementById('novo_cpf').value = doc;
            document.getElementById('novo_email').value = email;
            document.getElementById('novo_phone').value = phone;
        } else {
            document.getElementById('novo_name_pj').value = nome;
            document.getElementById('novo_cnpj').value = doc;
            document.getElementById('novo_rep_email').value = email;
            document.getElementById('novo_rep_phone').value = phone;
        }

        document.getElementById('novoForm').scrollIntoView({ behavior: 'smooth' });
    }

    function apagarParticipante(id) {
        if (!confirm('Tem certeza que deseja apagar este participante?')) return;

        const csrfToken = document.querySelector('input[name="_token"]').value;
        const tokenUrl = new URLSearchParams(window.location.search).get('token');

        fetch(`{{ route('lawfirm.public.portal.index', $processo->id) }}/participante/${id}?token=${tokenUrl}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Apagado com sucesso', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert(data.message || 'Erro ao apagar.', 'error');
            }
        })
        .catch(() => showAlert('Falha na comunicação com o servidor.', 'error'));
    }

    function salvarNovoParticipante() {
        const form   = document.getElementById('novoForm');
        const btn    = document.getElementById('btnSalvarNovo');
        if (!btn) return;
        const origHtml = btn.innerHTML;

        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Salvando...`;
        btn.disabled = true;

        const csrfToken = document.querySelector('input[name="_token"]').value;
        const formData  = new FormData(form);
        const payload   = Object.fromEntries(formData.entries());
        const novoType  = payload.novo_client_type;
        // Normaliza campos para o contrato esperado pelo backend
        payload.client_type = novoType;
        payload.name    = novoType === 'PF' ? payload.novo_name   : payload.novo_name_pj;
        payload.email   = payload.novo_email  || '';
        payload.phone   = novoType === 'PF' ? payload.novo_phone  : payload.novo_rep_phone;
        payload.cpf     = payload.novo_cpf    || '';
        payload.rg      = payload.novo_rg     || '';
        payload.cnpj    = payload.novo_cnpj   || '';
        payload.legal_representative_name = payload.novo_rep_name || '';
        payload.legal_representative_cpf  = payload.novo_rep_cpf  || '';
        payload.birth_date   = payload.novo_birth_date   || '';
        payload.nationality  = payload.novo_nationality  || '';
        payload.participante_id = document.getElementById('participante_id').value;

        fetch("{{ route('lawfirm.public.portal.update', $processo->id) }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Participante salvo com sucesso!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert(data.message || 'Erro ao cadastrar.', 'error');
            }
        })
        .catch(() => showAlert('Falha na comunicação com o servidor.', 'error'))
        .finally(() => { btn.innerHTML = origHtml; btn.disabled = false; });
    }

    // --- Módulo de Envio de Arquivos ---
    function uploadFile() {
        const input = document.getElementById('fileInput');
        if (!input.files || input.files.length === 0) return;
        uploadFiles(Array.from(input.files));
        input.value = '';
    }

    // Drag and drop events
    const dropZone = document.getElementById('upload-area');
    dropZone.addEventListener('click', () => document.getElementById('fileInput').click());
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-indigo-500', 'bg-indigo-50');
        dropZone.classList.remove('border-gray-300', 'bg-gray-50');
    });
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
        dropZone.classList.add('border-gray-300', 'bg-gray-50');
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
        dropZone.classList.add('border-gray-300', 'bg-gray-50');
        if (e.dataTransfer.files) {
            uploadFiles(Array.from(e.dataTransfer.files));
        }
    });

    function uploadFiles(files) {
        const csrfToken = document.querySelector('input[name="_token"]').value;
        const urlParams = new URLSearchParams(window.location.search);
        const tokenStr = document.querySelector('input[name="token"]').value || urlParams.get('token');
        const list = document.getElementById('file-list');

        files.forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                showAlert(`Arquivo ${file.name} excedeu 10MB.`, 'error');
                return;
            }

            // Create UI item
            const id = 'file-' + Math.random().toString(36).substr(2, 9);
            const item = document.createElement('div');
            item.id = id;
            item.className = 'flex items-center justify-between p-3 bg-white border border-gray-200 rounded-md shadow-sm';
            item.innerHTML = `
                <div class="flex items-center space-x-3 overflow-hidden">
                    <svg class="h-6 w-6 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" /></svg>
                    <span class="text-sm font-medium text-gray-900 truncate">${file.name}</span>
                </div>
                <div class="flex items-center status-indicator ml-4 text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
            `;
            list.appendChild(item);

            // Upload via Fetch
            const formData = new FormData();
            formData.append('file', file);
            formData.append('token', tokenStr);

            fetch("{{ route('lawfirm.public.portal.upload', $processo->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const statusDiv = document.querySelector(`#${id} .status-indicator`);
                if (data.success) {
                    statusDiv.innerHTML = '<span class="text-green-600 font-medium">Enviado <svg class="inline w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg></span>';
                } else {
                    statusDiv.innerHTML = `<span class="text-red-600 font-medium text-xs border border-red-200 px-1 py-0.5 rounded">${data.message || 'Erro'}</span>`;
                }
            })
            .catch(err => {
                const statusDiv = document.querySelector(`#${id} .status-indicator`);
                statusDiv.innerHTML = '<span class="text-red-500 font-medium text-xs border border-red-200 px-1 py-0.5 rounded">Falha na rede</span>';
            });
        });
    }
</script>

</body>
</html>
