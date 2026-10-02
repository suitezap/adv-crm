<?php

namespace SuiteZap\LawFirm\Escavador\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Escavador\Services\EscavadorService;
use Symfony\Component\HttpFoundation\Response;

class VerifyEscavadorWebhook
{
    /**
     * Valida o token de autenticação enviado pelo Escavador no callback do webhook.
     *
     * FAIL-CLOSED: Se o secret não estiver configurado no MotherShip (meta_data.webhook_token
     * do nó Escavador), a requisição é terminantemente rejeitada com HTTP 401.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $secret = EscavadorService::getWebhookToken();

        // FAIL-CLOSED: Secret ausente na infraestrutura rejeita tudo
        if (empty($secret)) {
            Log::error('EscavadorWebhook: webhook_token não configurado na infraestrutura — requisição rejeitada (fail-closed). Configure meta_data.webhook_token no nó Escavador do MotherShip.', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status'  => 'unauthorized',
                'message' => 'Webhook secret not configured',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Header primário da API Escavador é Authorization (com ou sem Bearer)
        // Suporta também fallback para X-Escavador-Token
        $received = $request->header('Authorization', '');
        if (empty($received)) {
            $received = $request->header('X-Escavador-Token', '');
        }

        if (str_starts_with($received, 'Bearer ')) {
            $received = substr($received, 7);
        }

        $received = trim((string) $received);

        // hash_equals para mitigação estrita de timing attacks
        if (empty($received) || ! hash_equals((string) $secret, $received)) {
            Log::warning('EscavadorWebhook: token de autenticação inválido ou ausente.', [
                'ip'     => $request->ip(),
                'header' => ! empty($received) ? substr($received, 0, 8).'...' : 'empty',
            ]);

            return response()->json([
                'status'  => 'unauthorized',
                'message' => 'Invalid webhook token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}