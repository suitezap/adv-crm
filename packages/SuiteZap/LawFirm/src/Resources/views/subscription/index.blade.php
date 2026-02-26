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
                        <span class="icon-star text-base"></span>
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
                            {{ $subscription->ai_modality ?? 'prepaid' }}
                        </span>
                    </div>
                </div>

            </div>{{-- /grid --}}

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

        @endif
    </div>

</x-admin::layouts>