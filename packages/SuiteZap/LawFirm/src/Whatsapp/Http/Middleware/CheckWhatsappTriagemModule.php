<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * CheckWhatsappTriagemModule
 *
 * Garante que o módulo WhatsApp_Triagem está ativo na assinatura do tenant
 * antes de permitir acesso ao Messenger Inbox / Chatbot de Triagem.
 *
 * Dependência implícita: o módulo WHATSAPP também deve estar ativo
 * (a instância de atendimento requer que a instância padrão esteja configurada).
 * A validação dupla é responsabilidade do operador ao configurar os módulos
 * no painel do Mothership.
 *
 * Referência: ARCHITECTURE_LawFirm_orient.md §17
 *
 * @since v3.55 — Jul/2026
 */
class CheckWhatsappTriagemModule
{
    public function handle(Request $request, Closure $next): mixed
    {
        $subscription = MotherShipService::getCurrentSubscription();

        if (! $subscription) {
            Log::error('WhatsApp Triagem: Assinatura não encontrada ao verificar módulo WhatsApp_Triagem.');

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

        if (! in_array('WhatsApp_Triagem', $activeModules, true)) {
            Log::warning("WhatsApp Triagem: Acesso bloqueado — módulo WhatsApp_Triagem inativo para tenant {$subscription->tenant_id}.");

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'O módulo de Triagem WhatsApp não está ativo na sua assinatura. Entre em contato com o suporte.',
                ], 403);
            }

            abort(403, 'O módulo de Triagem WhatsApp não está ativo na sua assinatura.');
        }

        return $next($request);
    }
}
