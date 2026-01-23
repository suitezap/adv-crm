<?php

namespace SuiteZap\LawFirm\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\Models\AssistantTemplate;
use SuiteZap\LawFirm\Models\AssistantHistory;
use SuiteZap\LawFirm\Services\N8nService;

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
        $templates = AssistantTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        return view('lawfirm::admin.assistants.index', compact('templates'));
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

        // 4. Chamar Service N8n
        $result = $this->n8nService->executeWebhook($targetUrl, $payload);

        // 4. Salvar Histórico
        AssistantHistory::create([
            'user_id' => auth()->guard('user')->id(),
            'template_id' => $template->id,
            'input_data' => $request->all(),
            'generated_content' => $result,
            'execution_mode' => 'agent_exec',
            'status' => 'completed'
        ]);

        // 5. Retornar JSON
        return response()->json(['result' => $result, 'success' => true, 'generated_prompt' => $result]);
    }
}
