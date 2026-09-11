<?php

namespace SuiteZap\LawFirm\TenantFinance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * CheckTenantFinanceModule
 *
 * Garante que o módulo TENANT_FINANCE está ativo na assinatura do tenant
 * antes de permitir acesso a qualquer rota de cobranças (TenantFinance).
 *
 * Regra SKILL.md §6 — TenantFinance Module Gate:
 * "O módulo requer a chave TENANT_FINANCE em active_modules (subscriptions Mothership).
 *  Sem ela, menu e rotas devem retornar 403."
 *
 * @since v3.48 — Auditoria 2026-05-15
 */
class CheckTenantFinanceModule
{
    public function handle(Request $request, Closure $next): mixed
    {
        $subscription = MotherShipService::getCurrentSubscription();

        if (! $subscription) {
            Log::error('TenantFinance: Assinatura não encontrada ao verificar módulo TENANT_FINANCE.');

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Assinatura não encontrada. Contate o suporte.',
                ], 403);
            }

            abort(403, 'Assinatura não encontrada. Contate o suporte.');
        }

        // Verifica se o módulo TENANT_FINANCE está na lista de módulos ativos
        $activeModules = is_array($subscription->active_modules)
            ? $subscription->active_modules
            : json_decode($subscription->active_modules ?? '[]', true);

        if (! in_array('TENANT_FINANCE', $activeModules, true)) {
            Log::warning("TenantFinance: Acesso bloqueado — módulo TENANT_FINANCE inativo para tenant {$subscription->tenant_id}.");

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'O módulo de Cobranças (TenantFinance) não está ativo na sua assinatura. Entre em contato com o suporte.',
                ], 403);
            }

            abort(403, 'O módulo de Cobranças não está ativo na sua assinatura.');
        }

        return $next($request);
    }
}
