<?php

namespace SuiteZap\LawFirm\Escavador\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Escavador\Models\EscavadorMonitoramento;
use SuiteZap\LawFirm\Escavador\Models\EscavadorProcesso;
use SuiteZap\LawFirm\Escavador\Models\EscavadorRequest;
use SuiteZap\LawFirm\Escavador\Services\EscavadorCacheService;
use SuiteZap\LawFirm\Escavador\Services\EscavadorService;
use SuiteZap\LawFirm\SaaS\Models\Subscription;

/**
 * WebhookController — Receptor de callbacks assíncronos do Escavador (V2).
 *
 * Rota pública: POST /api/webhooks/escavador
 * Isenta de CSRF (adicionado em VerifyCsrfToken::$except).
 *
 * Fluxo:
 *   1. Recebe o payload JSON do Escavador.
 *   2. Localiza o EscavadorRequest pelo external_id.
 *   3. Se houver erro: marca 'failed' e estorna o saldo.
 *   4. Se sucesso: marca 'completed' e salva o payload.
 */
class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('EscavadorWebhook: payload recebido', ['payload' => $payload]);

        // ── Extrair external_id ─────────────────────────────────────────────
        $externalId = $payload['id']
            ?? $payload['request_id']
            ?? $payload['external_id']
            ?? null;

        if (! $externalId) {
            Log::warning('EscavadorWebhook: external_id não encontrado no payload.');

            return response()->json(['error' => 'external_id ausente'], 400);
        }

        // ── Localizar o registro (sem escopo global: webhook público resolve
        // o tenant pelo próprio registro e só muta dentro dele) ──────────────
        $escavadorRequest = EscavadorRequest::withoutGlobalScopes()
            ->where('external_id', $externalId)->first();

        if (! $escavadorRequest) {
            // Se não for um EscavadorRequest assíncrono, tenta como Monitoramento
            $monitoramento_id = $payload['monitoramento_id'] ?? $payload['monitoramento'] ?? $externalId;
            $monitoramento = EscavadorMonitoramento::withoutGlobalScopes()
                ->where('external_id', $monitoramento_id)->first();

            if ($monitoramento) {
                // Notifica
                $this->handleMonitoramentoWebhook($monitoramento, $payload);

                return response()->json(['status' => 'monitoramento_processed'], 200);
            }

            Log::warning("EscavadorWebhook: Nenhum Request ou Monitoramento encontrado para external_id/monitoramento_id={$externalId}");

            // Retorna 200 para o Escavador não re-tentar indefinidamente
            return response()->json(['status' => 'not_found'], 200);
        }

        if (! $escavadorRequest->isPending()) {
            // Já processado (duplicata de webhook)
            return response()->json(['status' => 'already_processed'], 200);
        }

        // ── Determinar sucesso ou falha ─────────────────────────────────────
        $isFailure = $this->isFailurePayload($payload);

        if ($isFailure) {
            // Marcar como falha
            $escavadorRequest->markFailed($payload);
            $this->refundBalance($escavadorRequest->tenant_id, $escavadorRequest->cost);
            Log::warning('EscavadorWebhook: requisição falhou, saldo estornado.', [
                'external_id' => $externalId,
                'tenant_id'   => $escavadorRequest->tenant_id,
            ]);

            // Se falhou uma atualização assincona de processo (escopado ao tenant do request)
            if ($escavadorRequest->processo_id) {
                $ep = EscavadorProcesso::where('processo_id', $escavadorRequest->processo_id)
                    ->where('tenant_id', $escavadorRequest->tenant_id)->first();
                if ($ep) {
                    $ep->update(['status_atualizacao' => 'erro']);
                }
            }

            return response()->json(['status' => 'failed_and_refunded'], 200);
        }

        // ── Sucesso: atualizar registro ─────────────────────────────────────
        $escavadorRequest->markCompleted($payload);

        // Processar os callbacks assíncronos do Refactoring (Resumo IA, Atualização)
        if ($escavadorRequest->processo_id) {
            $ep = EscavadorProcesso::where('processo_id', $escavadorRequest->processo_id)
                ->where('tenant_id', $escavadorRequest->tenant_id)->first();

            if ($ep) {
                if ($escavadorRequest->type === 'RESUMO_IA') {
                    $ep->update(['resumo_ia' => $payload['resumo'] ?? ($payload['data']['resumo'] ?? 'Resumo não localizado no payload.')]);
                } elseif ($escavadorRequest->type === 'ATUALIZACAO_PROCESSO_PUB') {
                    $ep->update([
                        'status_atualizacao'      => 'atualizado',
                        'data_ultima_verificacao' => now(),
                    ]);
                    // Idealmente aqui disparamos o syncMovimentacoes do CacheService de novo
                    $apiService = app(EscavadorService::class);
                    $cacheService = new EscavadorCacheService($apiService);
                    $cacheService->syncMovimentacoes($ep);
                }
            }
        }

        Log::info('EscavadorWebhook: requisição concluída.', [
            'external_id' => $externalId,
            'tenant_id'   => $escavadorRequest->tenant_id,
        ]);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Determina se o payload representa uma falha.
     * O Escavador geralmente sinaliza via campo 'status', 'error' ou 'sucesso'.
     */
    private function isFailurePayload(array $payload): bool
    {
        // Verificar campo 'status' (ex: 'error', 'failed', 'falhou')
        if (isset($payload['status'])) {
            $status = strtolower((string) $payload['status']);
            if (in_array($status, ['error', 'failed', 'falhou', 'erro'], true)) {
                return true;
            }
        }

        // Verificar presença de campo 'error' ou 'mensagem_erro'
        if (! empty($payload['error']) || ! empty($payload['mensagem_erro'])) {
            return true;
        }

        // Verificar 'sucesso' = false (padrão Escavador)
        if (isset($payload['sucesso']) && $payload['sucesso'] === false) {
            return true;
        }

        return false;
    }

    /**
     * Estorna o valor cobrado de volta ao saldo de IA do tenant.
     */
    private function refundBalance(string $tenantId, float $cost): void
    {
        if ($cost <= 0) {
            return;
        }

        $subscription = Subscription::where('tenant_id', $tenantId)->first();

        if ($subscription) {
            $subscription->increment('suitecoin_balance', $cost);
        } else {
            Log::error("EscavadorWebhook: Subscription não encontrada para estorno. tenant_id={$tenantId}");
        }
    }

    /**
     * Manipula Webhooks de Monitoramentos e despacha WhatsApp se ativo
     */
    private function handleMonitoramentoWebhook(EscavadorMonitoramento $monitoramento, array $payload): void
    {
        Log::info("EscavadorWebhook: Processando atualização de monitoramento ID: {$monitoramento->id}");

        if (! $monitoramento->notify_whatsapp) {
            return;
        }

        // SUSPENSO (AGENTS.md §8 — Alertas de Prazo): envio WhatsApp desabilitado.
        // O monitoramento é processado normalmente; só o disparo fica retido.
        // Bloco de envio removido em PRIV-AUDIT-001 (reativar só com aprovação explícita).
        Log::info('EscavadorWebhook: disparo WhatsApp retido (módulo suspenso §8).', [
            'monitoramento_id' => $monitoramento->id,
        ]);
    }
}
