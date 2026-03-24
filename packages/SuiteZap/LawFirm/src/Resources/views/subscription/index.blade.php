<x-admin::layouts>
    <x-slot:title>
        Minha Assinatura
    </x-slot>

    @push('styles')
    <style>
        /* ── Subscription Page: minimal overrides ──────────────
           Only styles that Krayin doesn't already provide.
           Grid uses Krayin's own max-* breakpoint convention.
        ──────────────────────────────────────────────────────── */

        /* Native CSS grid for AI assistants (max-* convention like Krayin) */
        .lf-assistants-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (max-width: 900px) {
            .lf-assistants-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .lf-assistants-grid { grid-template-columns: 1fr; }
        }

        /* Check badge */
        .lf-module-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 3px;
            background-color: #7c3aed;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        /* Progress bar track */
        .lf-progress-track {
            width: 100%;
            background: #e5e7eb;
            border-radius: 999px;
            height: 6px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .dark .lf-progress-track { background: #374151; }
        .lf-progress-bar {
            height: 100%;
            border-radius: 999px;
            background: #7c3aed;
        }
        .lf-progress-bar.warning { background: #f59e0b; }
        .lf-progress-bar.danger  { background: #ef4444; }

        /* AI assistant card hover */
        .lf-assistant-card {
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .lf-assistant-card:hover {
            border-color: #c4b5fd !important;
            box-shadow: 0 2px 8px rgba(124,58,237,0.1);
        }

        /* "Disponível" label */
        .lf-available-label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #d1d5db;
            transition: color 0.15s;
        }
        .lf-assistant-card:hover .lf-available-label { color: #7c3aed; }

        /* Category divider */
        .lf-category-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .lf-category-label { border-color: #374151; }
        .lf-category-label::before {
            content: '';
            width: 3px;
            height: 0.9rem;
            border-radius: 2px;
            background: #7c3aed;
            display: block;
        }

        /* Expiration warning banner */
        .lf-warning-banner {
            background-color: #fffbc8 !important; /* Yellow pastel */
            border-color: #fde047 !important;
            color: #a16207 !important;
        }
        .dark .lf-warning-banner {
            background-color: rgba(253, 224, 71, 0.15) !important;
            border-color: rgba(253, 224, 71, 0.3) !important;
            color: #fde047 !important;
        }
    </style>
    @endpush

    <div class="flex flex-col gap-4">

        {{-- ── PAGE HEADER (Krayin standard) ──────────────── --}}
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
                                    <li class="flex items-center gap-x-1 text-base text-gray-600 after:content-['/'] last:cursor-default after:last:hidden dark:text-gray-300" aria-current="page">
                                        Minha Assinatura
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="text-xl font-bold dark:text-white">Minha Assinatura</div>
            </div>

            @if($subscription)
                <div>
                    @if($subscription->status == 'active')
                        <span class="label-active">Ativa</span>
                    @else
                        <span class="label-inactive">{{ ucfirst($subscription->status) }}</span>
                    @endif
                </div>
            @endif
        </div>

        @if(!$subscription)
            {{-- ── ERRO ────────────────────────────────────── --}}
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                <strong>Erro:</strong> Não foi possível carregar a assinatura. Verifique o <code>TENANT_ID</code> no <code>.env</code>.
            </div>
        @else

            @php
                $expiresAt     = $subscription->expires_at ? \Carbon\Carbon::parse($subscription->expires_at) : null;
                $daysLeft      = $expiresAt ? max(0, \Carbon\Carbon::now()->diffInDays($expiresAt, false)) : null;
                $contractDays  = 365;
                $daysUsed      = $expiresAt ? max(0, $contractDays - ($daysLeft ?? 0)) : 0;
                $daysProgress  = min(100, ($contractDays > 0 ? $daysUsed / $contractDays * 100 : 0));

                $storageLimit  = $subscription->storage_limit_gb ?? 4;
                $storageLimitB = $storageLimit * 1024 * 1024 * 1024;
                $storageBytes  = $storageUsedBytes ?? 0;
                $storagePct    = $storageLimitB > 0 ? ($storageBytes / $storageLimitB) * 100 : 0;
                $storageClass  = $storagePct >= 90 ? 'danger' : ($storagePct >= 70 ? 'warning' : '');

                if      ($storageBytes < 1024)       $storageFmt = round($storageBytes) . ' B';
                elseif  ($storageBytes < 1024**2)    $storageFmt = round($storageBytes / 1024, 1) . ' KB';
                elseif  ($storageBytes < 1024**3)    $storageFmt = round($storageBytes / 1024 / 1024, 2) . ' MB';
                else                                  $storageFmt = round($storageBytes / 1024 / 1024 / 1024, 2) . ' GB';
            @endphp

            {{-- ── AVISOS DE ASSINATURA ────────────────────── --}}
            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 flex items-center gap-3">
                    <span class="icon-warning text-xl"></span>
                    <div>
                        <strong>Acesso Restrito:</strong> {{ session('error') }}
                    </div>
                </div>
            @endif

            @if($subscription->status === 'inactive' || ($expiresAt && now()->greaterThanOrEqualTo($expiresAt)))
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 flex items-center gap-3">
                    <span class="icon-warning text-xl"></span>
                    <div>
                        <strong>Assinatura Inativa/Expirada:</strong> O acesso aos recursos do sistema foi bloqueado. Por favor, regularize sua assinatura.
                    </div>
                </div>
            @elseif($daysLeft !== null && $daysLeft <= 7)
                <div class="lf-warning-banner rounded-lg border p-4 flex items-center gap-3">
                    <span class="icon-info text-xl"></span>
                    <div>
                        <strong>Aviso de Vencimento:</strong> Sua assinatura vence em {{ $daysLeft }} dia(s) ({{ $expiresAt->format('d/m/Y') }}).
                    </div>
                </div>
            @endif

            {{-- ── TOP METRIC CARDS (Krayin grid pattern) ─── --}}
            {{-- Krayin uses: grid grid-cols-N gap-4 max-md:grid-cols-M max-sm:grid-cols-1 --}}
            <div class="grid grid-cols-3 gap-4 max-md:grid-cols-2 max-sm:grid-cols-1">

                {{-- CARD 1: STATUS DA ASSINATURA --}}
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                        <span class="icon-setting text-base"></span>
                        Status da Assinatura
                    </div>

                    <div class="flex flex-col gap-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Plano</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $tenantClassification ?? 'LawFirm Pro' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Usuários</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $usersCount }} / {{ $subscription->max_users }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Vencimento</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $expiresAt ? $expiresAt->format('d/m/Y') : '—' }}
                            </span>
                        </div>
                    </div>

                    @if($expiresAt)
                        <div>
                            <div class="lf-progress-track">
                                <div class="lf-progress-bar {{ $daysProgress >= 90 ? 'danger' : '' }}" style="width: {{ $daysProgress }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                @if($daysLeft !== null && $daysLeft > 0)
                                    {{ $daysLeft }} dias restantes
                                @else
                                    Assinatura expirada
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                {{-- CARD 2: ARMAZENAMENTO --}}
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                        <span class="icon-folder text-base"></span>
                        Armazenamento (GED)
                    </div>

                    <div>
                        <p class="text-xl font-bold dark:text-gray-300">
                            {{ number_format($storagePct, 0) }}<span class="text-sm font-normal text-gray-400 ml-0.5">%</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $storageFmt }} de {{ $storageLimit }} GB utilizados</p>
                    </div>

                    <div>
                        <div class="lf-progress-track">
                            <div class="lf-progress-bar {{ $storageClass }}" style="width: {{ min($storagePct, 100) }}%"></div>
                        </div>
                        @if($storagePct >= 80)
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Espaço quase esgotado</p>
                        @endif
                    </div>
                </div>

                {{-- CARD 3: INTELIGÊNCIA ARTIFICIAL --}}
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                        <span class="icon-settings-sources text-base"></span>
                        Inteligência Artificial
                    </div>

                    @php
                        $aiBalance = $subscription->ai_tokens_balance ?? 0;
                        $aiBalanceBrl = number_format((float)$aiBalance, 2, ',', '.');
                        $aiHasBalance = $aiBalance > 0;
                    @endphp

                    <div>
                        <p class="text-xl font-bold {{ $aiHasBalance ? 'dark:text-gray-300' : 'text-red-500 dark:text-red-400' }}">
                            R$ {{ $aiBalanceBrl }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($aiHasBalance)
                                Saldo disponível para IA
                            @else
                                <span class="text-red-500 dark:text-red-400 font-medium">Saldo esgotado — IA bloqueada</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-sm border-t border-gray-100 dark:border-gray-800 pt-2">
                        <span class="text-gray-500 dark:text-gray-400">Modalidade</span>
                        <span class="font-semibold capitalize text-gray-900 dark:text-white">
                            {{ ($subscription->ai_modality ?? 'prepaid') === 'prepaid' ? 'Pré-pago' : ucfirst($subscription->ai_modality) }}
                        </span>

                    </div>
                </div>

            </div>{{-- /grid --}}

            {{-- ── CARDS DE UPGRADE / COMPRA DE CRÉDITOS ─────────────────────── --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 mb-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4 dark:border-gray-800">
                    <div class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-gray-200">
                        <span class="icon-cart text-xl"></span>
                        Upgrade & Créditos (Integração Asaas)
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                    {{-- Plano PRO --}}
                    <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lf-assistant-card">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold dark:text-white">Plano PRO Anual</h3>
                            <span class="rounded bg-violet-100 px-2 py-1 text-xs font-bold text-violet-800">R$ 500,00</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tenha acesso a todos os módulos, integrações premium e suporte estendido.</p>
                        <button onclick="window.asaasCheckoutPlan('pro_anual', 500.00, 'Plano PRO Anual')" class="mt-auto rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 transition">
                            Atualizar Assinatura
                        </button>
                    </div>

                    {{-- Créditos de IA --}}
                    <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lf-assistant-card">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold dark:text-white">Créditos de IA</h3>
                            <span class="rounded bg-blue-100 px-2 py-1 text-xs font-bold text-blue-800">Avulso</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Recarregue instantaneamente a cota de uso dos nossos assistentes especializados.</p>
                        
                        <div class="mt-auto flex flex-col gap-2">
                            <button onclick="window.openPaymentModal(500, 5.00)" class="flex justify-between items-center rounded-lg bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 transition border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800/50 dark:text-blue-400">
                                <span>Pacote Básico</span>
                                <span class="font-bold">R$ 5,00</span>
                            </button>
                            <button onclick="window.openPaymentModal(1500, 10.00)" class="flex justify-between items-center rounded-lg bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 transition border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800/50 dark:text-blue-400">
                                <span>Pacote Intermediário</span>
                                <span class="font-bold">R$ 10,00</span>
                            </button>
                            <button onclick="window.openPaymentModal(2500, 15.00)" class="flex justify-between items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition">
                                <span>Pacote Avançado</span>
                                <span class="font-bold">R$ 15,00</span>
                            </button>
                        </div>
                        
                        {{-- Valor Personalizado --}}
                        <div class="mt-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 block">Outro Valor (R$)</label>
                            <div class="flex gap-2 items-center">
                                <div class="relative flex-1">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">R$</span>
                                    <input type="number" id="customCreditInput" min="5" step="1" value="20" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:border-brandColor focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition-all">
                                </div>
                                <button onclick="window.openCustomPaymentModal()" class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-bold text-white hover:bg-gray-900 transition dark:bg-gray-700 dark:hover:bg-gray-600">
                                    Gerar
                                </button>
                            </div>
                            <p class="text-[10.5px] text-gray-400 mt-1.5 dark:text-gray-500"><i class="icon-info"></i> Taxa: R$ 1,00 = 100 Créditos de IA</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Estilos Pastéis Personalizados Independentes do Tailwind JIT --}}
            <style>
                .btn-pastel-pix { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
                .btn-pastel-pix:hover { background-color: #dcfce7; }
                .dark .btn-pastel-pix { background-color: rgba(22, 101, 52, 0.25); color: #86efac; border-color: #14532d; }
                .dark .btn-pastel-pix:hover { background-color: rgba(22, 101, 52, 0.45); }

                .btn-pastel-card { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
                .btn-pastel-card:hover { background-color: #dbeafe; }
                .dark .btn-pastel-card { background-color: rgba(30, 64, 175, 0.25); color: #93c5fd; border-color: #1e3a8a; }
                .dark .btn-pastel-card:hover { background-color: rgba(30, 64, 175, 0.45); }
                
                .btn-pastel-card-inst { background-color: #f5f3ff; color: #3730a3; border: 1px solid #ddd6fe; }
                .btn-pastel-card-inst:hover { background-color: #ede9fe; }
                .dark .btn-pastel-card-inst { background-color: rgba(49, 46, 129, 0.4); color: #a78bfa; border-color: #312e81; }
                .dark .btn-pastel-card-inst:hover { background-color: rgba(49, 46, 129, 0.6); }
            </style>

            {{-- Modal de Escolha de Pagamento (Compacto) --}}
            <div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                <div class="w-full max-w-xs rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Forma de Pagamento</h3>
                        <button onclick="window.closePaymentModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none transition-colors">&times;</button>
                    </div>
                    
                    <div class="mb-5 rounded-lg bg-gray-50 border border-gray-100 p-3 dark:bg-gray-800 dark:border-gray-700 flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300" id="modalCreditsText"></span>
                        <span class="text-base font-bold text-gray-900 dark:text-white" id="modalPriceText"></span>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <button id="btnPayPix" onclick="window.asaasCheckoutCredits('PIX')" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition-all btn-pastel-pix">
                            <i class="icon-pix text-lg"></i> Pagar com PIX
                        </button>
                        <button id="btnPayCard" onclick="window.asaasCheckoutCredits('CREDIT_CARD')" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition-all btn-pastel-card">
                            <i class="icon-credit-card text-lg"></i> Cartão de Crédito
                        </button>
                        <button id="btnPayCardInst" onclick="window.asaasCheckoutCredits('CREDIT_CARD_INSTALLMENT')" class="flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition-all btn-pastel-card-inst">
                            <i class="icon-credit-card text-lg"></i> Cartão de Crédito Parcelado
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── MÓDULOS ATIVOS ────────────────────────── --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4 dark:border-gray-800">
                    <div class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-gray-200">
                        <span class="icon-product text-xl"></span>
                        Módulos Ativos
                    </div>
                    @if($subscription->active_modules)
                        <span class="text-xs text-gray-400">{{ count($subscription->active_modules) }} módulo(s)</span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    @if($subscription->active_modules && count($subscription->active_modules) > 0)
                        @foreach($subscription->active_modules as $module)
                            <div class="flex items-center gap-2 rounded-md border border-violet-200 bg-violet-50 px-3 py-1.5 dark:border-violet-800/50 dark:bg-violet-900/20">
                                <span class="lf-module-check">&#10003;</span>
                                <span class="text-sm font-semibold text-violet-800 dark:text-violet-300">
                                    {{ strtoupper(str_replace('_', ' ', $module)) }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum módulo extra contratado.</p>
                    @endif
                </div>
            </div>

            {{-- ── ASSISTENTES DE IA ─────────────────────── --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4 dark:border-gray-800">
                    <div class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-gray-200">
                        <span class="icon-settings-flow text-xl"></span>
                        Assistentes de IA Disponíveis
                    </div>
                    <span class="text-xs text-gray-400">{{ $availableAssistants->count() }} assistente(s)</span>
                </div>

                @if($availableAssistants->count() > 0)
                    @foreach($availableAssistants->groupBy('category') as $category => $assistants)
                        <div class="mb-6 last:mb-0">
                            <div class="lf-category-label">{{ $category ?: 'Geral' }}</div>

                            <div class="lf-assistants-grid">
                                @foreach($assistants as $assistant)
                                    <div class="lf-assistant-card flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">

                                        {{-- Icon + Title --}}
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-violet-50 text-xl dark:bg-violet-900/20">
                                                @if($assistant->icon && str_starts_with($assistant->icon, 'fa-'))
                                                    <i class="fa {{ $assistant->icon }} text-violet-600 dark:text-violet-400"></i>
                                                @else
                                                    {{ $assistant->icon ?? '🤖' }}
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-200">
                                                    {{ $assistant->title }}
                                                </p>
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                                                    {{ $assistant->description ?? 'Sem descrição disponível.' }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Footer --}}
                                        <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-2 dark:border-gray-800">
                                            @if($assistant->required_module)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                    &#9679; {{ ucfirst(strtolower(str_replace('_', ' ', $assistant->required_module))) }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                    &#9679; Livre
                                                </span>
                                            @endif
                                            <span class="lf-available-label">Disponível</span>
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="flex flex-col items-center gap-2 py-8 text-center text-gray-400">
                        <span class="text-4xl">🤖</span>
                        <p class="text-sm">Nenhum assistente de IA disponível para sua assinatura.</p>
                    </div>
                @endif
            </div>

            </div>

        @endif
    </div>

    @push('scripts')
    <script>
        /**
         * Asaas Checkout — Vanilla JS (Sem Vue)
         */

        // CSRF token embutido pelo Blade — mais confiável que leitura de cookie
        var _csrfToken = '{{ csrf_token() }}';

        window.asaasCheckoutPlan = function(planId, price, planName) {
            var btn = event.currentTarget;
            var originalText = btn.innerText;
            btn.innerText = 'Processando...';
            btn.disabled = true;

            fetch('{{ route("admin.lawfirm.saas.checkout.plan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': _csrfToken
                },
                body: JSON.stringify({
                    plan_id: planId,
                    price: price,
                    plan_name: planName || 'Plano LawFirm CRM'
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.checkoutUrl) {
                    window.location.href = data.checkoutUrl;
                } else {
                    alert('Erro ao gerar checkout: ' + (data.message || 'Erro desconhecido'));
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(function() {
                alert('Falha de rede. Verifique sua conexão e tente novamente.');
                btn.innerText = originalText;
                btn.disabled = false;
            });
        };

        // Variáveis de estado do modal
        var selectedCredits = 0;
        var selectedPrice = 0;

        window.openPaymentModal = function(credits, price) {
            selectedCredits = credits;
            selectedPrice = price;
            
            document.getElementById('modalCreditsText').innerText = credits.toLocaleString('pt-BR') + ' Créditos de IA';
            document.getElementById('modalPriceText').innerText = 'R$ ' + price.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            document.getElementById('paymentModal').classList.remove('hidden');
        };

        window.openCustomPaymentModal = function() {
            var rawValue = document.getElementById('customCreditInput').value;
            var price = parseFloat(rawValue);
            if (isNaN(price) || price < 1) {
                alert('Por favor, informe um valor válido (mínimo R$ 1,00).');
                return;
            }
            var credits = Math.floor(price * 100);
            window.openPaymentModal(credits, price);
        };

        window.closePaymentModal = function() {
            document.getElementById('paymentModal').classList.add('hidden');
        };

        window.asaasCheckoutCredits = function(paymentMethod) {
            var btnEvent = event.currentTarget; // btnPix ou btnCard
            var originalText = btnEvent.innerHTML;
            btnEvent.innerHTML = '<i class="fa fa-spinner fa-spin text-lg"></i> Processando...';
            btnEvent.disabled = true;

            // Desabilita todos os botões de pagamento
            document.getElementById('btnPayPix').disabled = true;
            document.getElementById('btnPayCard').disabled = true;
            if(document.getElementById('btnPayCardInst')) {
                document.getElementById('btnPayCardInst').disabled = true;
            }

            fetch('{{ route("admin.lawfirm.saas.checkout.credits") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': _csrfToken
                },
                body: JSON.stringify({
                    credits: selectedCredits,
                    price: selectedPrice,
                    payment_method: paymentMethod
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.checkoutUrl) {
                    window.location.href = data.checkoutUrl;
                    alert('Erro ao gerar checkout: ' + (data.message || 'Erro desconhecido'));
                    btnEvent.innerHTML = originalText;
                    document.getElementById('btnPayPix').disabled = false;
                    document.getElementById('btnPayCard').disabled = false;
                    if(document.getElementById('btnPayCardInst')) {
                        document.getElementById('btnPayCardInst').disabled = false;
                    }
                }
            })
            .catch(function() {
                alert('Falha de rede. Verifique sua conexão e tente novamente.');
                btnEvent.innerHTML = originalText;
                document.getElementById('btnPayPix').disabled = false;
                document.getElementById('btnPayCard').disabled = false;
                if(document.getElementById('btnPayCardInst')) {
                    document.getElementById('btnPayCardInst').disabled = false;
                }
            });
        };
    </script>
    @endpush

</x-admin::layouts>
