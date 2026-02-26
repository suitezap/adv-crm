<?php

namespace SuiteZap\LawFirm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Carbon\Carbon;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Apenas processa se o usuário estiver logado
        if (!Auth::check()) {
            return $next($request);
        }

        // 2. Só verifica as rotas de painel administrativo (admin)
        if (!$request->is('admin/*')) {
            return $next($request);
        }

        // 3. Ignora as rotas que não devem ser bloqueadas
        $ignoredRoutes = [
            'admin/login',
            'admin/logout',
            'admin/juridico/assinatura', // A assinatura deve ficar sempre disponível
        ];

        foreach ($ignoredRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        // 4. Busca a assinatura do MotherShip
        $subscription = MotherShipService::getCurrentSubscription();

        if (!$subscription) {
            // Se não encontrar assinatura, bloqueia o acesso
            session()->flash('error', trans('Sua assinatura não foi localizada ou está inativa. Regularize para acessar o sistema.'));
            return redirect()->route('admin.lawfirm.saas.index');
        }

        // 5. Verifica o status e a data de expiração
        $isExpired = false;
        if ($subscription->status === 'inactive') {
            $isExpired = true;
        } elseif ($subscription->expires_at) {
            $expiresAt = Carbon::parse($subscription->expires_at);
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                $isExpired = true;
            }
        }

        // 6. Se estiver expirada ou inativa, redireciona para a tela de assinatura
        if ($isExpired) {
            session()->flash('error', trans('Sua assinatura expirou. Acesse o painel de assinaturas para regularizar a situação.'));
            return redirect()->route('admin.lawfirm.saas.index');
        }

        // Tudo OK, segue com a requisição
        return $next($request);
    }
}
