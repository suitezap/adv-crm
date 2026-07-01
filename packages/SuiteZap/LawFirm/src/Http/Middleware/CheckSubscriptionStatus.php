<?php

namespace SuiteZap\LawFirm\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Apenas processa se o usuário estiver logado
        if (! Auth::check()) {
            return $next($request);
        }

        // 2. Só verifica as rotas de painel administrativo (admin)
        if (! $request->is('admin/*')) {
            return $next($request);
        }

        // 3. Ignora as rotas que não devem ser bloqueadas
        $ignoredRoutes = [
            'admin/login',
            'admin/logout',
            'admin/juridico/assinatura*',
            'admin/configuration*',
            'admin/lawfirm/saas*',
        ];

        foreach ($ignoredRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        $tenantKey = 'tenant_sub_'.MotherShipService::getTenantId();
        $fallbackKey = 'tenant_fallback_sub_'.MotherShipService::getTenantId();

        // 4. Busca a assinatura do MotherShip com cache (60s)
        $subscription = Cache::remember($tenantKey, 60, function () {
            return MotherShipService::getCurrentSubscription();
        });

        // 5. Tenta o fallback em caso de falha
        if (! $subscription) {
            // Tenta carregar da API diretamente (ignorando o cache para verificar)
            try {
                $subscription = MotherShipService::getCurrentSubscription();
                if ($subscription) {
                    // Atualiza o cache e fallback
                    Cache::put($tenantKey, $subscription, 60);
                    Cache::put($fallbackKey, $subscription, now()->addHours(24));
                }
            } catch (\Exception $e) {
                Log::warning('CheckSubscriptionStatus: falha ao buscar assinatura: '.$e->getMessage());
            }

            // Usa o fallback se ainda não tiver subscription
            if (! $subscription) {
                $subscription = Cache::get($fallbackKey);
            }
        } else {
            // Sempre salva o último status válido como fallback para 24h
            Cache::put($fallbackKey, $subscription, now()->addHours(24));
        }

        // 6. Se não há qualquer informação de assinatura (novo tenant ou falha total da API), libera
        if (! $subscription) {
            // Não mostramos erro aqui para não assustar o usuário com mensagens falsas
            // Apenas liberamos o acesso (fail-open approach)
            Log::info('CheckSubscriptionStatus: sem assinatura encontrada, liberando acesso temporariamente.');

            return $next($request);
        }

        // 7. Verifica o status e a data de expiração
        $status = $subscription->status ?? 'active';
        $expiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : null;

        $isExpired = false;
        $isInactive = ($status === 'inactive');

        if ($isInactive) {
            $isExpired = true;
        } elseif ($expiresAt) {
            // Tranca somente após o FIM do dia de expiração (grace até meia-noite do dia)
            if (now()->greaterThan($expiresAt->copy()->endOfDay())) {
                $isExpired = true;
            }
        }

        // 8. Se estiver expirada ou inativa, bloqueia com resposta adequada ao tipo de request
        if ($isExpired) {
            $message = 'Sua assinatura expirou. Acesse o painel de assinaturas para regularizar a situação.';

            // Para requisições AJAX/JSON, retorna JSON em vez de redirect
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error'    => 'subscription_expired',
                    'message'  => $message,
                    'redirect' => route('admin.lawfirm.saas.index'),
                ], 403);
            }

            session()->flash('error', $message);

            return redirect()->route('admin.lawfirm.saas.index');
        }

        // 9. Aviso de vencimento próximo (apenas para requisições de página HTML — NÃO bloqueia)
        if ($expiresAt) {
            $daysLeft = (int) now()->diffInDays($expiresAt, false);
            if ($daysLeft >= 0 && $daysLeft <= 7) {
                // Apenas flasha o aviso em requisições não-AJAX para não poluir a UI
                if (! $request->expectsJson() && ! $request->ajax()) {
                    // Evita repetir o aviso em todas as páginas — só a cada 6h por sessão
                    $warnKey = 'sub_warn_shown_'.Auth::id();
                    if (! Cache::has($warnKey)) {
                        session()->flash('warning', "Sua assinatura vence em {$daysLeft} dia(s). Renove para não perder o acesso.");
                        Cache::put($warnKey, true, now()->addHours(6));
                    }
                }
            }
        }

        // Tudo OK, segue com a requisição
        return $next($request);
    }
}
