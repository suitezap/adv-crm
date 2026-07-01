<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use Webkul\Admin\Http\Controllers\Controller;

/**
 * MothershipTemplateController
 *
 * Endpoint centralizado para gestão de templates de IA e invalidação de cache.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * AUTENTICAÇÃO (Zero .env):
 * O segredo é lido da tabela `mothership.app_config` (chave: `api_secret`).
 * Isso elimina a necessidade de sincronizar variáveis de ambiente entre stacks.
 * O Mothership Panel lê o mesmo valor da mesma tabela para assinar as chamadas.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * ROTAS DISPONÍVEIS:
 *   GET  /admin/juridico/mothership/templates            → index()         Lista templates globais
 *   POST /admin/juridico/mothership/templates/upsert    → upsert()        Cria ou atualiza template
 *   PATCH /admin/juridico/mothership/templates/{slug}/deactivate → deactivate() Desativa template
 *   POST /admin/juridico/mothership/cache/invalidate    → invalidateCache() ← Chamado pelo Mothership Panel
 *
 * FLUXO DE SYNC:
 *   Mothership Panel edita template → chama invalidateCache() →
 *   cache_version incrementada → próximo acesso de tenant reconstrói cache →
 *   novo template aparece automaticamente (zero deploy)
 */
class MothershipTemplateController extends Controller
{
    /**
     * Lista todos os templates globais (tenant_id = NULL) do Mothership.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeMothershipKey($request);

        $templates = AssistantTemplate::whereNull('tenant_id')
            ->orderBy('category')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'category', 'area', 'required_module', 'is_active', 'version', 'updated_at']);

        return response()->json([
            'success' => true,
            'total'   => $templates->count(),
            'data'    => $templates,
        ]);
    }

    /**
     * Cria ou atualiza um template de IA no Mothership via API.
     *
     * Propagação Zero-Deploy:
     *   - tenant_id = NULL  → card disponível para TODOS os tenants com o módulo certo.
     *   - tenant_id = 'xyz' → card exclusivo daquele tenant (override do global).
     */
    public function upsert(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeMothershipKey($request);

        $validated = $request->validate([
            'slug'             => 'required|string|max:100',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'category'         => 'required|string|max:100',
            'area'             => 'nullable|string|max:100',
            'required_module'  => 'nullable|string|max:100',
            'prompt_structure' => 'required|string',
            'variables'        => 'nullable|array',
            'n8n_webhook_url'  => 'nullable|string|max:500',
            'icon'             => 'nullable|string|max:100',
            'is_active'        => 'boolean',
            'tenant_id'        => 'nullable|string|max:100',
        ]);

        $lookupKey = [
            'slug'      => $validated['slug'],
            'tenant_id' => $validated['tenant_id'] ?? null,
        ];

        $template = AssistantTemplate::updateOrCreate(
            $lookupKey,
            collect($validated)->except('version')->toArray()
        );

        $template->increment('version');

        $this->bumpCacheVersion();

        $scope = $template->tenant_id ? "Tenant: {$template->tenant_id}" : 'Global (todos os tenants)';

        Log::info('[Mothership] Template publicado via API', [
            'slug'    => $template->slug,
            'scope'   => $scope,
            'version' => $template->version,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Template '{$template->slug}' publicado. Escopo: {$scope}.",
            'data'    => [
                'id'              => $template->id,
                'slug'            => $template->slug,
                'title'           => $template->title,
                'required_module' => $template->required_module,
                'tenant_id'       => $template->tenant_id,
                'version'         => $template->version,
                'scope'           => $scope,
            ],
        ]);
    }

    /**
     * Desativa um template global sem excluí-lo.
     * O card desaparece imediatamente de todos os tenants (após expiração de cache).
     */
    public function deactivate(Request $request, string $slug): \Illuminate\Http\JsonResponse
    {
        $this->authorizeMothershipKey($request);

        $updated = AssistantTemplate::where('slug', $slug)
            ->whereNull('tenant_id')
            ->update(['is_active' => false]);

        if (! $updated) {
            return response()->json(['error' => "Template '{$slug}' não encontrado ou já inativo."], 404);
        }

        $this->bumpCacheVersion();

        Log::info('[Mothership] Template desativado via API', ['slug' => $slug]);

        return response()->json([
            'success' => true,
            'message' => "Template '{$slug}' desativado. Não aparecerá mais para nenhum tenant.",
        ]);
    }

    /**
     * Webhook de Invalidação de Cache — chamado pelo Mothership Panel.
     *
     * Após salvar/editar/toggle de qualquer template no Mothership Panel,
     * ele chama este endpoint com o X-Mothership-Key (lido de app_config.api_secret).
     * O cache de todos os tenants é invalidado, e na próxima visita os dados
     * são buscados frescos do Mothership.
     *
     * Fire-and-forget pelo Mothership Panel (timeout 5s, ignora falhas de rede).
     */
    public function invalidateCache(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeMothershipKey($request);

        $newVersion = $this->bumpCacheVersion();

        $reason = $request->input('reason', 'external_call');

        Log::info('[Mothership] Cache invalidado via webhook', [
            'reason'      => $reason,
            'new_version' => $newVersion,
            'caller_ip'   => $request->ip(),
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Cache de templates invalidado com sucesso.',
            'new_version' => $newVersion,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODOS INTERNOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Incrementa a versão global de cache de templates.
     *
     * AssistantController::index() usa essa versão na chave do cache:
     *   ai_templates:{tenantId}:{modulesHash}:v{version}
     *
     * Ao incrementar, todas as keys antigas ficam obsoletas naturalmente.
     * Sem necesidade de invalidação por pattern ou Redis SCAN.
     *
     * @return int Nova versão do cache
     */
    protected function bumpCacheVersion(): int
    {
        $current = (int) Cache::get('ai_templates_cache_version', 1);
        $next = $current + 1;
        Cache::forever('ai_templates_cache_version', $next);

        // Sincroniza também com o banco Mothership para que o Mothership Panel
        // possa exibir a versão atual sem acessar o cache do Laravel
        DB::connection('mothership')
            ->table('app_config')
            ->where('key', 'cache_version')
            ->update(['value' => $next, 'updated_at' => now()]);

        return $next;
    }

    /**
     * Valida a chave de API da requisição.
     *
     * ZERO .env: o segredo é lido diretamente da tabela `mothership.app_config`
     * (chave: `api_secret`). O mesmo valor é usado pelo Mothership Panel ao
     * assinar suas chamadas (via `db_row("SELECT value FROM app_config WHERE key='api_secret'")`).
     *
     * Fallback seguro: se a tabela não existir yet (ex: antes da migration),
     * aceita MOTHERSHIP_API_SECRET do .env para não quebrar ambientes legados.
     */
    protected function authorizeMothershipKey(Request $request): void
    {
        // Tenta ler do banco Mothership (fonte única de verdade)
        $secret = $this->getApiSecretFromDb();

        if (empty($secret)) {
            $secret = config('lawfirm.mothership_secret');
        }

        if (empty($secret)) {
            Log::error('[Mothership] api_secret não encontrado em app_config nem em MOTHERSHIP_API_SECRET');
            abort(503, 'API secret não configurado. Execute a migration Mothership e configure app_config.api_secret.');
        }

        $provided = $request->header('X-Mothership-Key', '');

        if (! hash_equals($secret, $provided)) {
            Log::warning('[Mothership] Tentativa com chave inválida', ['ip' => $request->ip()]);
            abort(403, 'Chave de API inválida.');
        }
    }

    /**
     * Lê o api_secret da tabela app_config do banco Mothership.
     * Cacheado por 5 minutos para evitar queries a cada request.
     */
    protected function getApiSecretFromDb(): ?string
    {
        return Cache::remember('mothership_api_secret', 300, function () {
            try {
                $row = DB::connection('mothership')
                    ->table('app_config')
                    ->where('key', 'api_secret')
                    ->value('value');

                return $row ?: null;
            } catch (\Exception $e) {
                Log::warning('[Mothership] Falha ao ler api_secret do banco: '.$e->getMessage());

                return null;
            }
        });
    }
}
