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

            // 2. Get N8n Config
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
                // Determine output field
                $output = $result['output'] ?? $result['text'] ?? $result['message'] ?? $response->body();

                // 6. Update History with Success
                $this->history->update([
                    'generated_content' => $output,
                    'status' => 'completed'
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
