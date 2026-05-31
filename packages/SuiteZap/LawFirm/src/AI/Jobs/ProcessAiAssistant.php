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
     *
     * @param AssistantHistory $history
     * @param AssistantTemplate $template
     * @param array $inputs
     */
    public function __construct(AssistantHistory $history, AssistantTemplate $template, array $inputs)
    {
        $this->history = $history;
        $this->template = $template;
        $this->inputs = $inputs;
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
            $subscription   = MotherShipService::getCurrentSubscription();
            $balanceBrl     = (float) ($subscription->suitecoin_balance ?? 0.0);
            $costVirtual    = (float) ($this->template->price_virtual ?? 0.0);
            $costBrl        = $costVirtual > 0
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
                return;
            }

            $n8nConfig = MotherShipService::getN8nConfig();

            if (!$n8nConfig) {
                throw new \Exception('N8N não configurado para este tenant.');
            }

            // 3. Build URL
            // Ensure URL validation
            $baseUrl = rtrim($n8nConfig['url'], '/');
            $webhookPath = ltrim($this->template->n8n_webhook_url, '/');

            // Basic validation to avoid double slashes or missing parts
            if (empty($webhookPath)) {
                throw new \Exception('Webhook URL não definida no template.');
            }

            $targetUrl = "{$baseUrl}/{$webhookPath}";

            // 4. Prepare Payload
            $payload = [
                'inputs' => $this->inputs,
                'user_id' => $this->history->user_id,
                'tenant_id' => MotherShipService::getTenantId(),
                'template' => $this->template->title,
                'timestamp' => now()->toIso8601String(),
                'history_id' => $this->history->id
            ];

            // 5. Execute Request
            $httpClient = Http::timeout(120); // 2 minutes timeout for AI processing

            if (!empty($n8nConfig['api_key'])) {
                $httpClient = $httpClient->withHeaders([
                    'Authorization' => 'Bearer ' . $n8nConfig['api_key'],
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
                if (!$executionId && is_string($output)) {
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
                    'status' => 'completed',
                    'execution_id' => $executionId,
                    'node_name' => $nodeName,
                    'model' => $model,
                    'total_cost' => $totalCost,
                    'real_cost' => $realCost,
                ]);

                Log::info("AI Assistant Job Completed [HistoryID: {$this->history->id}]");

            } else {
                // Handle HTTP Error
                $errorMsg = "N8N HTTP Error: " . $response->status();
                Log::error($errorMsg, ['body' => $response->body()]);

                $this->history->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg . " - " . substr($response->body(), 0, 200)
                ]);
            }

        } catch (\Exception $e) {
            Log::error("AI Assistant Job Failed [HistoryID: {$this->history->id}]", ['error' => $e->getMessage()]);

            $this->history->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            // Rethrow to fail the job in Queue (optional, depending on retry policy)
            // throw $e; 
        }
    }
}
