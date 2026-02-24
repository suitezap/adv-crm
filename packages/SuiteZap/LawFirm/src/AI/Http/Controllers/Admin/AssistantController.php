<?php

namespace SuiteZap\LawFirm\AI\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\Services\N8nService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\AI\Jobs\ProcessAiAssistant;
use Webkul\Lead\Models\Lead;

class AssistantController extends Controller
{
    protected $n8nService;

    public function __construct(N8nService $n8nService)
    {
        $this->n8nService = $n8nService;
    }

    /**
     * Display list of active assistant templates.
     */
    public function index()
    {
        // 1. Descobrir quais módulos o cliente tem
        $subscription = MotherShipService::getCurrentSubscription();
        $tenantId = MotherShipService::getTenantId();

        // Se não tiver assinatura, assume array vazio (só verá os gratuitos)
        $allowedModules = $subscription ? ($subscription->active_modules ?? []) : [];

        // 2. Filtrar Templates (usa scope MotherShip)
        $templates = AssistantTemplate::forTenant()
            ->where('is_active', true)
            ->where(function ($query) use ($allowedModules) {
                // Mostra se o módulo for NULL (Público) OU se estiver na lista permitida
                $query->whereNull('required_module')
                    ->orWhereIn('required_module', $allowedModules);
            })
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        return view('lawfirm::admin.assistants.index', compact('templates', 'tenantId'));
    }

    /**
     * Display the execution form for a specific template.
     */
    public function show($slug)
    {
        $template = AssistantTemplate::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('lawfirm::admin.assistants.show', compact('template'));
    }

    /**
     * Generate the prompt by replacing variables.
     */
    public function generate(Request $request, $slug)
    {
        // Guard: Check AI balance before allowing generation
        $subscription = MotherShipService::getCurrentSubscription();
        $aiBalance = $subscription ? (float) ($subscription->ai_tokens_balance ?? 0) : 0;
        if ($aiBalance <= 0) {
            return response()->json([
                'error' => 'Saldo de IA insuficiente. Recarregue seu saldo para continuar usando os assistentes.'
            ], 402);
        }

        $template = AssistantTemplate::where('slug', $slug)->firstOrFail();

        // Get form inputs
        $inputs = $request->except(['_token']);

        // Build the prompt by replacing variables
        $generatedPrompt = $template->prompt_structure;

        foreach ($inputs as $key => $value) {
            // Replace {{variable}} with actual value
            $generatedPrompt = str_replace('{{' . $key . '}}', $value, $generatedPrompt);
        }

        // Save to history
        $history = AssistantHistory::create([
            'user_id' => auth()->guard('user')->id(),
            'template_id' => $template->id,
            'input_data' => $inputs,
            'generated_content' => $generatedPrompt,
            'execution_mode' => 'prompt_only',
            'status' => 'completed',
        ]);

        Log::info('Assistant Prompt Generated', [
            'template' => $template->title,
            'history_id' => $history->id,
        ]);

        return response()->json([
            'success' => true,
            'generated_prompt' => $generatedPrompt,
            'history_id' => $history->id,
        ]);
    }

    /**
     * Execute the assistant remotely via N8n Webhook.
     */
    public function execute(Request $request, $slug)
    {
        // Guard: Check AI balance before allowing execution
        $subscription = MotherShipService::getCurrentSubscription();
        $aiBalance = $subscription ? (float) ($subscription->ai_tokens_balance ?? 0) : 0;
        if ($aiBalance <= 0) {
            return response()->json([
                'error' => 'Saldo de IA insuficiente. Recarregue seu saldo para continuar usando os assistentes.'
            ], 402);
        }

        // 1. Validar e Buscar Template
        $template = AssistantTemplate::where('slug', $slug)->firstOrFail();

        if (empty($template->n8n_webhook_url)) {
            return response()->json(['error' => 'Este assistente não possui URL de execução configurada.'], 400);
        }

        // 2. Preparar Payload (Dados do Form + Metadados)
        $payload = [
            'inputs' => $request->all(),
            'user_id' => auth()->guard('user')->id(),
            'template' => $template->title,
            'timestamp' => now()->toIso8601String()
        ];

        // 3. Construir URL do Webhook
        $route = $template->n8n_webhook_url;
        $baseUrl = env('N8N_WEBHOOK_BASE_URL');

        // Verifica se é URL completa ou Rota
        if (filter_var($route, FILTER_VALIDATE_URL)) {
            $targetUrl = $route;
        } else {
            // Combina Base + Rota
            $targetUrl = rtrim($baseUrl, '/') . '/' . ltrim($route, '/');
        }

        // Validação Final
        if (empty($targetUrl) || $targetUrl == '/') {
            return response()->json(['error' => 'URL do n8n não pronta. Verifique o .env (N8N_WEBHOOK_BASE_URL) ou o cadastro.'], 500);
        }

        // 4. Salvar Histórico (Status: QUEUED)
        $history = AssistantHistory::create([
            'user_id' => auth()->guard('user')->id(),
            'template_id' => $template->id,
            'input_data' => $request->all(),
            'generated_content' => null, // Will be filled by Job
            'execution_mode' => 'agent_exec',
            'status' => 'queued'
        ]);

        // 5. Dispatch Job
        ProcessAiAssistant::dispatch($history, $template, $request->all());

        // 6. Retornar JSON para Polling
        return response()->json([
            'success' => true,
            'history_id' => $history->id,
            'status' => 'queued',
            'message' => 'Solicitação enviada para processamento.'
        ]);
    }

    /**
     * Check status of an AI execution.
     *
     * @param int $id History ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus($id)
    {
        $history = AssistantHistory::findOrFail($id);

        if ($history->user_id !== auth()->guard('user')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $history->id,
            'status' => $history->status,
            'generated_content' => $history->generated_content,
            'error_message' => $history->error_message
        ]);
    }

    /**
     * Processa assistente com base na ação solicitada (Preview ou Execute).
     */
    public function process(Request $request)
    {
        // 1. Validação (usa conexão 'mothership' para buscar template)
        $validated = $request->validate([
            'template_id' => 'required|exists:mothership.lawfirm_assistant_templates,id',
            'data' => 'array',
            'action' => 'required|in:preview,execute',
        ]);

        // 2. Carregar Template
        $template = AssistantTemplate::findOrFail($validated['template_id']);

        // 3. Security Check: Verificar módulo exigido
        if ($template->required_module) {
            $subscription = MotherShipService::getCurrentSubscription();
            $allowedModules = $subscription ? ($subscription->active_modules ?? []) : [];

            if (!in_array($template->required_module, $allowedModules)) {
                return response()->json([
                    'error' => 'Módulo não disponível no seu plano.'
                ], 403);
            }
        }

        $data = $validated['data'] ?? [];
        $action = $validated['action'];

        // 4. Decisão baseada na Ação
        if ($action === 'preview') {
            // Preview: Sempre executa Localmente (gera o prompt)
            return $this->executeLocal($template, $data);
        }

        if ($action === 'execute') {
            // Execute: Envia para N8N (se configurado)
            if (empty($template->n8n_webhook_url)) {
                return response()->json([
                    'error' => 'Este assistente não possui execução remota configurada.'
                ], 400);
            }

            return $this->executeRemote($template, $data);
        }

        return response()->json(['error' => 'Ação inválida.'], 400);
    }

    /**
     * Processa assistente especificamente para um Lead (Pré-Triagem).
     */
    public function processForLead(Request $request, $leadId)
    {
        // 1. Carregar Lead e Template
        $lead = Lead::findOrFail($leadId);
        $template = AssistantTemplate::where('slug', 'pre-triagem-lead')->firstOrFail();

        // 2. Validar Ação
        $action = $request->input('action', 'preview'); // preview | execute

        // 3. Preparar Dados
        $data = [
            'title' => $lead->title,
            'description' => $lead->description ?? 'Sem descrição.',
        ];

        // 4. Delegar Execução (passando o contexto do Lead)
        if ($action === 'preview') {
            return $this->executeLocal($template, $data, $lead->id);
        }

        if ($action === 'execute') {
            // Security Check: Verificar módulo exigido (se houver)
            if ($template->required_module) {
                $subscription = MotherShipService::getCurrentSubscription();
                $allowedModules = $subscription ? ($subscription->active_modules ?? []) : [];
                if (!in_array($template->required_module, $allowedModules)) {
                    return response()->json(['error' => 'Módulo não disponível.'], 403);
                }
            }

            if (empty($template->n8n_webhook_url)) {
                return response()->json(['error' => 'Execução remota não configurada.'], 400);
            }
            return $this->executeRemote($template, $data, $lead->id);
        }

        return response()->json(['error' => 'Ação inválida.'], 400);
    }

    /**
     * Executa localmente substituindo variáveis no prompt.
     */
    protected function executeLocal(AssistantTemplate $template, array $data, $leadId = null)
    {
        $generatedPrompt = $template->prompt_structure;

        foreach ($data as $key => $value) {
            $generatedPrompt = str_replace('{{' . $key . '}}', $value ?? '', $generatedPrompt);
        }

        // Salvar histórico
        AssistantHistory::create([
            'user_id' => auth()->guard('user')->id(),
            'lead_id' => $leadId,
            'template_id' => $template->id,
            'input_data' => $data,
            'generated_content' => $generatedPrompt,
            'execution_mode' => 'local',
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'execution_mode' => 'local',
            'generated_prompt' => $generatedPrompt,
        ]);
    }

    /**
     * Executa remotamente via N8N configurado no MotherShip.
     */
    protected function executeRemote(AssistantTemplate $template, array $data, $leadId = null)
    {
        $n8nConfig = MotherShipService::getN8nConfig();

        if (!$n8nConfig) {
            Log::warning('N8N não configurado para este tenant', [
                'template_id' => $template->id
            ]);
            return response()->json([
                'error' => 'Serviço N8N não configurado para sua conta.'
            ], 503);
        }

        // Montar URL
        $targetUrl = rtrim($n8nConfig['url'], '/') . '/' . ltrim($template->n8n_webhook_url, '/');

        // Payload
        $payload = [
            'inputs' => $data,
            'user_id' => auth()->guard('user')->id(),
            'tenant_id' => MotherShipService::getTenantId(),
            'lead_id' => $leadId,
            'template' => $template->title,
            'timestamp' => now()->toIso8601String(),
        ];

        // Chamada HTTP (com API Key se disponível)
        try {
            $httpClient = Http::timeout(60);

            if (!empty($n8nConfig['api_key'])) {
                $httpClient = $httpClient->withHeaders([
                    'Authorization' => 'Bearer ' . $n8nConfig['api_key'],
                ]);
            }

            $response = $httpClient->post($targetUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                $output = $result['output'] ?? $result['text'] ?? $result['message'] ?? $response->body();

                AssistantHistory::create([
                    'user_id' => auth()->guard('user')->id(),
                    'lead_id' => $leadId,
                    'template_id' => $template->id,
                    'input_data' => $data,
                    'generated_content' => $output,
                    'execution_mode' => 'n8n_remote',
                    'status' => 'completed',
                ]);

                return response()->json([
                    'success' => true,
                    'execution_mode' => 'n8n_remote',
                    'generated_prompt' => $output,
                ]);
            }

            Log::error("N8N Error [{$targetUrl}]", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'error' => 'Erro no serviço N8N: ' . ($response->json()['message'] ?? $response->status())
            ], 502);

        } catch (\Exception $e) {
            Log::error("N8N Exception [{$targetUrl}]", ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Falha de conexão com N8N: ' . $e->getMessage()
            ], 503);
        }
    }
}
