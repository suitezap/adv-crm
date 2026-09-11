<?php

namespace SuiteZap\LawFirm\AI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\SaaS\Models\SaasTransaction;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;

class ProcessAiAssistant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $history;

    protected $template;

    protected $inputs;

    /**
     * Create a new job instance.
     */
    public function __construct(AssistantHistory $history, AssistantTemplate $template, array $inputs)
    {
        $this->history = $history;
        $this->template = $template;
        $this->inputs = $inputs;
    }

    /**
     * Valor debitado no Controller (`price_virtual` já é BRL, paridade 1:1 —
     * mesma base do `decrement` em AssistantController::execute).
     */
    private function debitCostBrl(): float
    {
        return (float) ($this->template->price_virtual ?? 0.0);
    }

    /**
     * Estorna o débito prévio quando a execução falha (GAP-001).
     * Idempotente: um crédito por history (reference assistant_history).
     */
    private function refundDebit(string $reason): void
    {
        $costBrl = $this->debitCostBrl();

        if ($costBrl <= 0) {
            return;
        }

        $subscription = MotherShipService::getCurrentSubscription();

        if (! $subscription) {
            Log::critical('[ProcessAiAssistant] Estorno impossível: assinatura não encontrada.', [
                'history_id' => $this->history->id,
                'reason'     => $reason,
            ]);

            return;
        }

        $alreadyRefunded = SaasTransaction::where('reference_type', 'assistant_history')
            ->where('reference_id', $this->history->id)
            ->where('type', 'credit')
            ->exists();

        if ($alreadyRefunded) {
            return;
        }

        $subscription->increment('suitecoin_balance', $costBrl);
        SaasTransaction::create([
            'tenant_id'      => MotherShipService::getTenantId(),
            'user_id'        => $this->history->user_id,
            'type'           => 'credit',
            'amount'         => $costBrl,
            'balance_after'  => $subscription->suitecoin_balance,
            'currency'       => SuiteCoinService::CURRENCY_CODE,
            'service_type'   => 'AI_ASSISTANT_REFUND',
            'description'    => "Estorno assistente '{$this->template->title}': {$reason}",
            'reference_type' => 'assistant_history',
            'reference_id'   => $this->history->id,
        ]);

        Log::info("[ProcessAiAssistant] Estorno de R$ {$costBrl} [HistoryID: {$this->history->id}] Motivo: {$reason}");
    }

    /**
     * Falha dura do worker (timeout, max tries): tenta estornar também.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[ProcessAiAssistant] Job falhou fora do handle [HistoryID: {$this->history->id}]", [
            'error' => $exception->getMessage(),
        ]);

        $this->history->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);
        $this->refundDebit('falha do worker');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Starting AI Assistant Job [HistoryID: {$this->history->id}]");

        try {
            // 1. Update status to processing
            $this->history->update(['status' => 'processing']);

            // 0. Validate suitecoin_balance (must have balance >= template cost)
            $subscription = MotherShipService::getCurrentSubscription();
            $balanceBrl = (float) ($subscription->suitecoin_balance ?? 0.0);
            $costVirtual = (float) ($this->template->price_virtual ?? 0.0);
            $costBrl = $costVirtual > 0
                ? SuiteCoinService::toBrl($costVirtual)
                : 0.0;

            if ($balanceBrl <= 0 || ($costBrl > 0 && $balanceBrl < $costBrl)) {
                $msg = sprintf(
                    'Saldo SuiteCoins insuficiente. Disponível: %s | Necessário: %s',
                    SuiteCoinService::formatFromBrl($balanceBrl),
                    SuiteCoinService::format($costVirtual)
                );
                $this->history->update(['status' => 'failed', 'error_message' => $msg]);
                Log::error("[ProcessAiAssistant] {$msg} [HistoryID: {$this->history->id}]");
                $this->refundDebit('saldo insuficiente no worker');

                return;
            }

            $n8nConfig = MotherShipService::getN8nConfig();

            // Regra 4 SKILL.md: Jobs nunca propagam throw — Log::error() + return gracioso
            if (! $n8nConfig) {
                $msg = 'N8N não configurado no MotherShip para este tenant.';
                Log::error("[ProcessAiAssistant] {$msg} [HistoryID: {$this->history->id}]");
                $this->history->update(['status' => 'failed', 'error_message' => $msg]);
                $this->refundDebit('N8N não configurado');

                return;
            }

            // 3. Build URL
            $baseUrl = rtrim($n8nConfig['url'], '/');
            $webhookPath = ltrim($this->template->n8n_webhook_url ?? '', '/');

            // Regra 4 SKILL.md: configuração inválida não deve derrubar o worker
            if (empty($webhookPath)) {
                $msg = 'Webhook URL não definida no template de IA.';
                Log::error("[ProcessAiAssistant] {$msg} [TemplateID: {$this->template->id}] [HistoryID: {$this->history->id}]");
                $this->history->update(['status' => 'failed', 'error_message' => $msg]);
                $this->refundDebit('webhook URL ausente');

                return;
            }

            $targetUrl = "{$baseUrl}/{$webhookPath}";

            // 4. Prepare Payload
            $payload = [
                'inputs'     => $this->inputs,
                'user_id'    => $this->history->user_id,
                'tenant_id'  => MotherShipService::getTenantId(),
                'template'   => $this->template->title,
                'timestamp'  => now()->toIso8601String(),
                'history_id' => $this->history->id,
            ];

            // 5. Execute Request
            $httpClient = Http::timeout(120); // 2 minutes timeout for AI processing

            if (! empty($n8nConfig['api_key'])) {
                $httpClient = $httpClient->withHeaders([
                    'Authorization' => 'Bearer '.$n8nConfig['api_key'],
                ]);
            }

            $response = $httpClient->post($targetUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();

                // Determine primary output field
                $output = $result['output'] ?? $result['text'] ?? $result['message'] ?? $response->body();

                // Priority 1: Direct metadata from JSON keys (New n8n pattern)
                $executionId = $result['execution_id'] ?? null;
                $nodeName = $result['node_name'] ?? null;
                $model = $result['model'] ?? null;
                $totalCost = isset($result['total_cost']) ? (float) $result['total_cost'] : null;
                $realCost = isset($result['real_cost']) ? (float) $result['real_cost'] : null;

                // Priority 2: Fallback to metadata extraction from string suffix " - [{...}]" (Legacy pattern)
                if (! $executionId && is_string($output)) {
                    $lastDashPos = strrpos($output, ' - ');

                    if ($lastDashPos !== false) {
                        $potentialJson = trim(substr($output, $lastDashPos + 3));

                        if (str_starts_with($potentialJson, '[') || str_starts_with($potentialJson, '{')) {
                            $metadataArray = json_decode($potentialJson, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($metadataArray)) {
                                // Handle if it's a non-empty array
                                $metadata = (isset($metadataArray[0]) && is_array($metadataArray[0])) ? reset($metadataArray) : $metadataArray;

                                if (is_array($metadata)) {
                                    $output = trim(substr($output, 0, $lastDashPos)); // Clean text
                                    $executionId = $metadata['execution_id'] ?? $executionId;
                                    $nodeName = $metadata['node_name'] ?? $nodeName;
                                    $model = $metadata['model'] ?? $model;
                                    $totalCost = isset($metadata['total_cost']) ? (float) $metadata['total_cost'] : $totalCost;
                                    $realCost = isset($metadata['real_cost']) ? (float) $metadata['real_cost'] : $realCost;
                                }
                            }
                        }
                    }
                }

                // 6. Update History with Success
                $this->history->update([
                    'generated_content' => $output,
                    'status'            => 'completed',
                    'execution_id'      => $executionId,
                    'node_name'         => $nodeName,
                    'model'             => $model,
                    'total_cost'        => $totalCost,
                    'real_cost'         => $realCost,
                ]);

                Log::info("AI Assistant Job Completed [HistoryID: {$this->history->id}]");

            } else {
                // Handle HTTP Error
                $errorMsg = 'N8N HTTP Error: '.$response->status();
                Log::error($errorMsg, ['body' => $response->body()]);

                $this->history->update([
                    'status'        => 'failed',
                    'error_message' => $errorMsg.' - '.substr($response->body(), 0, 200),
                ]);
                $this->refundDebit('erro HTTP do N8N');
            }

        } catch (\Exception $e) {
            // Safety-net: captura erros inesperados de rede/HTTP que escaparam da lógica acima.
            // Regra 4 SKILL.md: nunca propaga throw — falha graciosamente com Log::error() + status.
            Log::error("[ProcessAiAssistant] Unexpected error [HistoryID: {$this->history->id}]", [
                'error'   => $e->getMessage(),
                'class'   => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            $this->history->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $this->refundDebit('exceção inesperada');
        }
    }
}
