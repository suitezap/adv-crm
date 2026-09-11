<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * CheckWhatsappModule
 *
 * Garante que o módulo WHATSAPP está ativo na assinatura do tenant
 * antes de permitir acesso às rotas de gestão de WhatsApp (instâncias,
 * QR code, notificações, templates, importação de mensagens).
 *
 * Padrão: idêntico ao CheckTenantFinanceModule — sem hardcode,
 * lê active_modules do banco Mothership via MotherShipService.
 *
 * Referência: ARCHITECTURE_LawFirm_orient.md §17
 *
 * @since v3.55 — Jul/2026
 */
class CheckWhatsappModule
{
    public function handle(Request $request, Closure $next): mixed
    {
        $subscription = MotherShipService::getCurrentSubscription();

        if (! $subscription) {
            Log::error('WhatsApp: Assinatura não encontrada ao verificar módulo WHATSAPP.');

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

        if (! in_array('WHATSAPP', $activeModules, true)) {
            Log::warning("WhatsApp: Acesso bloqueado — módulo WHATSAPP inativo para tenant {$subscription->tenant_id}.");

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'O módulo WhatsApp não está ativo na sua assinatura. Entre em contato com o suporte.',
                ], 403);
            }

            abort(403, 'O módulo WhatsApp não está ativo na sua assinatura.');
        }

        return $next($request);
    }
}
