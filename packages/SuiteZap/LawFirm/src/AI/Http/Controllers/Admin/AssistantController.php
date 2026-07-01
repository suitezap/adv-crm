<?php

namespace SuiteZap\LawFirm\AI\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\AI\Jobs\ProcessAiAssistant;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\AI\Models\LeadTriagem;
use SuiteZap\LawFirm\AI\Services\N8nService;
use SuiteZap\LawFirm\SaaS\Models\SaasTransaction;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;
use Webkul\Admin\Http\Controllers\Controller;
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

        // 2. Cache de Templates com invalidação automática via versão global.
        //    Quando um template é publicado/atualizado via MothershipTemplateController::upsert(),
        //    a `ai_templates_cache_version` é incrementada, tornando esta key obsoleta.
        //    Na próxima visita, o cache é reconstruído com os dados novos do Mothership.
        $cacheVersion = (int) Cache::get('ai_templates_cache_version', 1);
        $cacheKey = 'ai_templates:'.$tenantId.':'.md5(implode(',', $allowedModules)).':v'.$cacheVersion;

        $templates = Cache::remember($cacheKey, 3600, function () use ($allowedModules) {
            return AssistantTemplate::forTenant()
                ->where('is_active', true)
                ->where(function ($query) use ($allowedModules) {
                    // Mostra se o módulo for NULL (Público) OU se estiver na lista permitida
                    $query->whereNull('required_module')
                        ->orWhereIn('required_module', $allowedModules);
                })
                ->orderBy('category')
                ->orderBy('title')
                ->get()
                ->unique('slug')  // Card tenant-específico (mesmo slug) tem prioridade sobre global
                ->values();
        });

        // 3. Extrair módulos de área únicos (IA-Trabalhista, IA-Civil, etc.) para o filtro
        $areas = $templates
            ->pluck('required_module')
            ->filter(fn ($m) => $m && str_starts_with((string) $m, 'IA-'))
            ->unique()
            ->sort()
            ->values();

        return view('lawfirm::admin.assistants.index', compact('templates', 'tenantId', 'areas'));
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
        // 1. Guard: saldo e custo
        $subscription = MotherShipService::getCurrentSubscription();
        $aiBalance = $subscription ? (float) ($subscription->suitecoin_balance ?? 0) : 0;
        if ($aiBalance <= 0) {
            return response()->json([
                'error' => 'Atenção⁉️ Não será possível realizar essa operação. Acesse o menu **Minha Assinatura** e consulte seu saldo.',
            ], 402);
        }

        $template = AssistantTemplate::where('slug', $slug)->firstOrFail();
        $cost = (float) ($template->price_virtual ?? 0);

        if ($cost > 0 && ! SuiteCoinService::hasSufficientBalance($aiBalance, $cost)) {
            return response()->json([
                'error' => SuiteCoinService::insufficientBalanceMessage($aiBalance, $cost),
            ], 402);
        }

        // 2. Get form inputs
        $inputs = $request->except(['_token']);

        // 3. Build prompt
        $generatedPrompt = $template->prompt_structure;
        foreach ($inputs as $key => $value) {
            $generatedPrompt = str_replace('{{'.$key.'}}', $value, $generatedPrompt);
        }

        // 4. Debitar saldo (apenas se custo > 0)
        if ($cost > 0 && $subscription) {
            $tenantId = MotherShipService::getTenantId();
            $subscription->decrement('suitecoin_balance', $cost);
            SaasTransaction::create([
                'tenant_id'      => $tenantId,
                'user_id'        => auth()->guard('user')->id(),
                'type'           => 'debit',
                'amount'         => $cost,
                'balance_after'  => $subscription->suitecoin_balance,
                'currency'       => SuiteCoinService::CURRENCY_CODE,
                'service_type'   => 'AI_ASSISTANT',
                'description'    => "Assistente: {$template->title} — ".SuiteCoinService::format(SuiteCoinService::toVirtual($cost)),
                'reference_type' => 'assistant_template',
                'reference_id'   => $template->id,
            ]);
        }

        // 5. Salvar histórico
        $history = AssistantHistory::create([
            'user_id'           => auth()->guard('user')->id(),
            'lead_id'           => $request->input('lead_id') ?: null,
            'template_id'       => $template->id,
            'input_data'        => $inputs,
            'generated_content' => $generatedPrompt,
            'execution_mode'    => 'prompt_only',
            'status'            => 'completed',
        ]);

        Log::info('Assistant Prompt Generated', [
            'template'   => $template->title,
            'history_id' => $history->id,
            'cost_brl'   => $cost,
        ]);

        return response()->json([
            'success'          => true,
            'generated_prompt' => $generatedPrompt,
            'history_id'       => $history->id,
        ]);
    }

    /**
     * Execute the assistant remotely via N8n Webhook.
     */
    public function execute(Request $request, $slug)
    {
        // 1. Guard: saldo
        $subscription = MotherShipService::getCurrentSubscription();
        $aiBalance = $subscription ? (float) ($subscription->suitecoin_balance ?? 0) : 0;
        if ($aiBalance <= 0) {
            return response()->json([
                'error' => 'Atenção⁉️ Não será possível realizar essa operação. Acesse o menu **Minha Assinatura** e consulte seu saldo.',
            ], 402);
        }

        // 2. Validar e Buscar Template
        $template = AssistantTemplate::where('slug', $slug)->firstOrFail();
        $cost = (float) ($template->price_virtual ?? 0);

        if ($cost > 0 && ! SuiteCoinService::hasSufficientBalance($aiBalance, $cost)) {
            return response()->json([
                'error' => SuiteCoinService::insufficientBalanceMessage($aiBalance, $cost),
            ], 402);
        }

        if (empty($template->n8n_webhook_url)) {
            return response()->json(['error' => 'Este assistente não possui URL de execução configurada.'], 400);
        }

        // 3. Preparar Payload
        $payload = [
            'inputs'    => $request->all(),
            'user_id'   => auth()->guard('user')->id(),
            'template'  => $template->title,
            'timestamp' => now()->toIso8601String(),
        ];

        // 4. Construir URL do Webhook
        $n8nConfig = MotherShipService::getN8nConfig();
        if (! $n8nConfig) {
            Log::warning('AssistantController::execute — N8N não configurado para este Tenant.', [
                'template_slug' => $slug,
                'tenant_id'     => MotherShipService::getTenantId(),
            ]);

            return response()->json(['error' => 'Serviço N8N não configurado para sua conta. Contate o suporte.'], 503);
        }

        $route = $template->n8n_webhook_url;
        $baseUrl = $n8nConfig['url'];
        $targetUrl = filter_var($route, FILTER_VALIDATE_URL)
            ? $route
            : rtrim($baseUrl, '/').'/'.ltrim($route, '/');

        if (empty($targetUrl) || $targetUrl === '/') {
            return response()->json(['error' => 'URL do n8n inválida. Verifique o cadastro do template.'], 500);
        }

        // 5. Debitar saldo antes de enfileirar (estorno ocorrê no Job se falhar)
        $tenantId = MotherShipService::getTenantId();
        if ($cost > 0 && $subscription) {
            $subscription->decrement('suitecoin_balance', $cost);
            SaasTransaction::create([
                'tenant_id'      => $tenantId,
                'user_id'        => auth()->guard('user')->id(),
                'type'           => 'debit',
                'amount'         => $cost,
                'balance_after'  => $subscription->suitecoin_balance,
                'currency'       => SuiteCoinService::CURRENCY_CODE,
                'service_type'   => 'AI_ASSISTANT',
                'description'    => "Assistente IA: {$template->title} — ".SuiteCoinService::format(SuiteCoinService::toVirtual($cost)),
                'reference_type' => 'assistant_template',
                'reference_id'   => $template->id,
            ]);
        }

        // 6. Salvar Histórico (Status: QUEUED)
        $history = AssistantHistory::create([
            'user_id'           => auth()->guard('user')->id(),
            'lead_id'           => $request->input('lead_id') ?: null,
            'template_id'       => $template->id,
            'input_data'        => $request->all(),
            'generated_content' => null,
            'execution_mode'    => 'agent_exec',
            'status'            => 'queued',
        ]);

        // 7. Dispatch Job
        ProcessAiAssistant::dispatch($history, $template, $request->all());

        return response()->json([
            'success'    => true,
            'history_id' => $history->id,
            'status'     => 'queued',
            'message'    => 'Solicitação enviada para processamento.',
        ]);
    }

    /**
     * Check status of an AI execution.
     *
     * @param  int  $id  History ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus($id)
    {
        $history = AssistantHistory::findOrFail($id);

        if ($history->user_id !== auth()->guard('user')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id'                => $history->id,
            'status'            => $history->status,
            'generated_content' => $history->generated_content,
            'error_message'     => $history->error_message,
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
            'data'        => 'array',
            'action'      => 'required|in:preview,execute',
        ]);

        // 2. Carregar Template
        $template = AssistantTemplate::findOrFail($validated['template_id']);

        // 3. Security Check: Verificar módulo exigido
        if ($template->required_module) {
            $subscription = MotherShipService::getCurrentSubscription();
            $allowedModules = $subscription ? ($subscription->active_modules ?? []) : [];

            if (! in_array($template->required_module, $allowedModules)) {
                return response()->json([
                    'error' => 'Módulo não disponível no seu plano.',
                ], 403);
            }
        }

        // Guard: Check AI balance before allowing generation/execution
        $subscription = MotherShipService::getCurrentSubscription();
        $aiBalance = $subscription ? (float) ($subscription->suitecoin_balance ?? 0) : 0;
        if ($aiBalance <= 0) {
            return response()->json([
                'error' => 'Atenção⁉️ Não será possível realizar essa operação. Acesse o menu **Minha Assinatura** e consulte seu saldo.',
            ], 402);
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
                    'error' => 'Este assistente não possui execução remota configurada.',
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

        // Guard: Check AI balance before allowing generation/execution
        $subscription = MotherShipService::getCurrentSubscription();
        $aiBalance = $subscription ? (float) ($subscription->suitecoin_balance ?? 0) : 0;
        if ($aiBalance <= 0) {
            return response()->json([
                'error' => 'Atenção⁉️ Não será possível realizar essa operação. Acesse o menu **Minha Assinatura** e consulte seu saldo.',
            ], 402);
        }

        // 2. Validar Ação
        $action = $request->input('action', 'preview'); // preview | execute

        // 3. Preparar Dados
        $data = [
            'title'       => $lead->title,
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
                if (! in_array($template->required_module, $allowedModules)) {
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
            $generatedPrompt = str_replace('{{'.$key.'}}', $value ?? '', $generatedPrompt);
        }

        // Salvar histórico
        AssistantHistory::create([
            'user_id'           => auth()->guard('user')->id(),
            'lead_id'           => $leadId,
            'template_id'       => $template->id,
            'input_data'        => $data,
            'generated_content' => $generatedPrompt,
            'execution_mode'    => 'local',
            'status'            => 'completed',
        ]);

        return response()->json([
            'success'          => true,
            'execution_mode'   => 'local',
            'generated_prompt' => $generatedPrompt,
        ]);
    }

    /**
     * Executa remotamente via N8N configurado no MotherShip.
     */
    protected function executeRemote(AssistantTemplate $template, array $data, $leadId = null)
    {
        $n8nConfig = MotherShipService::getN8nConfig();

        if (! $n8nConfig) {
            Log::warning('N8N não configurado para este tenant', [
                'template_id' => $template->id,
            ]);

            return response()->json([
                'error' => 'Serviço N8N não configurado para sua conta.',
            ], 503);
        }

        // Montar URL
        $targetUrl = rtrim($n8nConfig['url'], '/').'/'.ltrim($template->n8n_webhook_url, '/');

        // Payload
        $payload = [
            'inputs'    => $data,
            'user_id'   => auth()->guard('user')->id(),
            'tenant_id' => MotherShipService::getTenantId(),
            'lead_id'   => $leadId,
            'template'  => $template->title,
            'timestamp' => now()->toIso8601String(),
        ];

        // Chamada HTTP (com API Key se disponível)
        try {
            $httpClient = Http::timeout(60);

            if (! empty($n8nConfig['api_key'])) {
                $httpClient = $httpClient->withHeaders([
                    'Authorization' => 'Bearer '.$n8nConfig['api_key'],
                ]);
            }

            $response = $httpClient->post($targetUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                $output = $result['output'] ?? $result['text'] ?? $result['message'] ?? $response->body();

                AssistantHistory::create([
                    'user_id'           => auth()->guard('user')->id(),
                    'lead_id'           => $leadId,
                    'template_id'       => $template->id,
                    'input_data'        => $data,
                    'generated_content' => $output,
                    'execution_mode'    => 'n8n_remote',
                    'status'            => 'completed',
                ]);

                return response()->json([
                    'success'          => true,
                    'execution_mode'   => 'n8n_remote',
                    'generated_prompt' => $output,
                ]);
            }

            Log::error("N8N Error [{$targetUrl}]", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'error' => 'Erro no serviço N8N: '.($response->json()['message'] ?? $response->status()),
            ], 502);

        } catch (\Exception $e) {
            Log::error("N8N Exception [{$targetUrl}]", ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Falha de conexão com N8N: '.$e->getMessage(),
            ], 503);
        }
    }

    /**
     * Slug → lead_triagem column mapping for cross-assistant context.
     */
    private const TRIAGEM_SLUG_MAP = [
        'pre-triagem-lead'      => 'viabilidade',
        'pre-triagem-checklist' => 'qualificacao',
        'gerador-proposta'      => 'proposta',
        'script-vendas'         => 'negociacao',
    ];

    /**
     * Save AI assistant output to lead_triagem for cross-context usage.
     *
     * POST assistants/lead/{leadId}/triagem/save
     * Body: { slug: string, content: string }
     */
    public function saveTriagem(Request $request, $leadId)
    {
        $request->validate([
            'slug'    => 'required|string',
            'content' => 'required|string',
        ]);

        $slug = $request->input('slug');
        $column = self::TRIAGEM_SLUG_MAP[$slug] ?? null;

        if (! $column) {
            return response()->json(['error' => 'Slug não mapeado para triagem.'], 422);
        }

        LeadTriagem::updateOrCreate(
            ['lead_id' => $leadId],
            [$column => $request->input('content')]
        );

        return response()->json(['success' => true, 'column' => $column]);
    }

    /**
     * Return the 4 assistant output fields for a lead.
     *
     * GET assistants/lead/{leadId}/triagem
     */
    public function getTriagem($leadId)
    {
        $triagem = LeadTriagem::where('lead_id', $leadId)
            ->select(['viabilidade', 'qualificacao', 'proposta', 'negociacao'])
            ->first();

        return response()->json([
            'viabilidade'  => $triagem?->viabilidade,
            'qualificacao' => $triagem?->qualificacao,
            'proposta'     => $triagem?->proposta,
            'negociacao'   => $triagem?->negociacao,
        ]);
    }

    /**
     * Exibe o chat do Escavador (EscavAI) em uma janela incorporada.
     */
    public function escavai()
    {
        return view('lawfirm::admin.assistants.escavai');
    }

    /**
     * Exibe o Chatwoot (SAC/Atendimento) em uma janela dedicada.
     * Injeta credenciais via localStorage para auto-login.
     */
    public function chatwoot()
    {
        $chatwootUrl = 'https://whats.suitezap.com.br';
        $user = auth()->guard('user')->user();
        $sacEmail = $user ? $user->email : 'sac@suitezap.com.br';
        $sacPassword = 'Eu&m2k2x';

        return view('lawfirm::admin.assistants.chatwoot', compact(
            'chatwootUrl',
            'sacEmail',
            'sacPassword'
        ));
    }
}
