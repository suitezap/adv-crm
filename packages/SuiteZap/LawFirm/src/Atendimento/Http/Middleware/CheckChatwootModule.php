<?php

namespace SuiteZap\LawFirm\Atendimento\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * CheckChatwootModule
 *
 * Garante que o módulo CHATWOOT (add-on) está ativo na assinatura do tenant
 * antes de permitir acesso ao menu SAC e às rotas de atendimento via Chatwoot.
 *
 * O CHATWOOT é um ADD-ON comprado separadamente — não está incluído nos planos
 * base. Quando ativo, habilita no CRM:
 *   - Menu "SAC" no sidebar (rota lawfirm.assistants.chatwoot)
 *   - Painel de configuração e status da integração Chatwoot
 *   - Webhooks de entrada processados pelo ChatwootWebhookController
 *
 * Referência: ARCHITECTURE_LawFirm_orient.md §18
 *
 * @since v3.56 — Jul/2026
 */
class CheckChatwootModule
{
    public function handle(Request $request, Closure $next): mixed
    {
        $subscription = MotherShipService::getCurrentSubscription();

        if (! $subscription) {
            Log::error('SAC/Chatwoot: Assinatura não encontrada ao verificar módulo CHATWOOT.');

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Assinatura não encontrada. Contate o suporte.',
                ], 403);
            }

            abort(403, 'Assinatura não encontrada. Contate o suporte.');
        }

        $activeModules = is_array($subscription->active_modules)
            ? $subscription->active_modules
            : json_decode($subscription->active_modules ?? '[]', true);

        if (! in_array('CHATWOOT', $activeModules, true)) {
            Log::info("SAC/Chatwoot: Acesso bloqueado — módulo CHATWOOT não contratado para tenant {$subscription->tenant_id}.");

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'O módulo SAC (Chatwoot) não está ativo na sua assinatura. Entre em contato com o suporte para contratar.',
                ], 403);
            }

            abort(403, 'O módulo SAC (Chatwoot) não está disponível na sua assinatura.');
        }

        return $next($request);
    }
}
