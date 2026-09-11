<?php

namespace SuiteZap\LawFirm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;

/**
 * PublishAiTemplatesCommand
 *
 * Artisan Command para publicar ou listar templates de IA no Mothership.
 * Útil para desenvolvimento, migração em lote e CI/CD.
 *
 * Uso:
 *   php artisan mothership:publish-templates --list
 *   php artisan mothership:publish-templates --slug=calculista_expresso
 *   php artisan mothership:publish-templates --all
 *   php artisan mothership:publish-templates --deactivate=slug_do_template
 *   php artisan mothership:publish-templates --sync-modules
 */
class PublishAiTemplatesCommand extends Command
{
    protected $signature = 'mothership:publish-templates
                            {--list         : Lista todos os templates globais do Mothership}
                            {--all          : Republica (updateOrCreate) todos os templates dos seeders}
                            {--slug=        : Republica um template específico pelo slug}
                            {--deactivate=  : Desativa um template pelo slug}
                            {--sync-modules : Exibe quais tenants precisam de atualização nos módulos}';

    protected $description = 'Gerencia templates de IA no Mothership. Zero-deploy para todos os tenants.';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listTemplates();
        }

        if ($slug = $this->option('deactivate')) {
            return $this->deactivateTemplate($slug);
        }

        if ($slug = $this->option('slug')) {
            return $this->republishBySlug($slug);
        }

        if ($this->option('all')) {
            return $this->republishAll();
        }

        if ($this->option('sync-modules')) {
            return $this->syncModulesReport();
        }

        $this->info('Use uma das opções: --list, --all, --slug=<slug>, --deactivate=<slug>, --sync-modules');
        $this->line('Rode com --help para mais detalhes.');

        return self::SUCCESS;
    }

    /**
     * Lista todos os templates globais do Mothership em tabela formatada.
     */
    protected function listTemplates(): int
    {
        $this->info('📋 Templates Globais no Mothership (tenant_id = NULL):');

        $templates = AssistantTemplate::whereNull('tenant_id')
            ->orderBy('category')
            ->get(['slug', 'title', 'category', 'area', 'required_module', 'is_active', 'version']);

        if ($templates->isEmpty()) {
            $this->warn('Nenhum template global encontrado.');

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Título', 'Categoria', 'Área', 'Módulo Req.', 'Ativo', 'Versão'],
            $templates->map(fn ($t) => [
                $t->slug,
                str($t->title)->limit(40),
                $t->category,
                $t->area ?? '—',
                $t->required_module ?? 'NULL (Público)',
                $t->is_active ? '✅' : '❌',
                $t->version,
            ])
        );

        $this->info("Total: {$templates->count()} templates.");

        return self::SUCCESS;
    }

    /**
     * Desativa um template pelo slug — desaparece de todos os tenants imediatamente.
     */
    protected function deactivateTemplate(string $slug): int
    {
        $template = AssistantTemplate::where('slug', $slug)->whereNull('tenant_id')->first();

        if (! $template) {
            $this->error("Template '{$slug}' não encontrado no Mothership (global).");

            return self::FAILURE;
        }

        $template->update(['is_active' => false]);
        $this->invalidateCache();

        $this->info("✅ Template '{$slug}' desativado. Não aparecerá mais para nenhum tenant.");

        return self::SUCCESS;
    }

    /**
     * Republica um template específico pelo slug (toca o updated_at e incrementa version).
     * Útil para forçar invalidação de cache sem alterar o conteúdo.
     */
    protected function republishBySlug(string $slug): int
    {
        $template = AssistantTemplate::where('slug', $slug)->first();

        if (! $template) {
            $this->error("Template '{$slug}' não encontrado.");

            return self::FAILURE;
        }

        $template->increment('version');
        $template->touch();
        $this->invalidateCache();

        $this->info("✅ Template '{$slug}' republicado. Versão: {$template->fresh()->version}");

        return self::SUCCESS;
    }

    /**
     * Republica todos os templates (toca updated_at de todos).
     * Invalida cache global. Útil após grandes atualizações de prompts.
     */
    protected function republishAll(): int
    {
        if (! $this->confirm('Isso incrementará a versão de TODOS os templates globais. Confirmar?')) {
            return self::SUCCESS;
        }

        $count = AssistantTemplate::whereNull('tenant_id')->count();
        AssistantTemplate::whereNull('tenant_id')->increment('version');

        $this->invalidateCache();
        $this->info("✅ {$count} templates atualizados. Cache invalidado para todos os tenants.");

        return self::SUCCESS;
    }

    /**
     * Exibe relatório de quais módulos existem nos templates vs quais tenants os possuem.
     * Ajuda a identificar tenants que precisam ter módulos adicionados à assinatura.
     */
    protected function syncModulesReport(): int
    {
        $this->info('📊 Relatório: Módulos de IA nos Templates vs Tenants');

        $modulesInTemplates = AssistantTemplate::whereNull('tenant_id')
            ->whereNotNull('required_module')
            ->where('is_active', true)
            ->pluck('required_module')
            ->unique()
            ->sort()
            ->values();

        $this->line("\n🔷 Módulos de IA ativos nos templates:");
        foreach ($modulesInTemplates as $module) {
            $count = AssistantTemplate::whereNull('tenant_id')
                ->where('required_module', $module)
                ->where('is_active', true)
                ->count();
            $this->line("  • {$module} → {$count} card(s)");
        }

        $this->line("\n💡 Para um tenant receber os cards de um módulo, adicione o módulo ao campo");
        $this->line('   `active_modules` da sua subscription no banco Mothership:');
        $this->line('   UPDATE subscriptions SET active_modules = JSON_ARRAY("IA-TRABALHISTA", "IA-FAMILIA") WHERE tenant_id = "lawfirm_xxx";');

        return self::SUCCESS;
    }

    /**
     * Invalida o cache de templates de todos os tenants via versão global.
     */
    protected function invalidateCache(): void
    {
        $currentVersion = (int) Cache::get('ai_templates_cache_version', 1);
        Cache::forever('ai_templates_cache_version', $currentVersion + 1);
        $this->line('🔄 Cache invalidado. Nova versão: '.($currentVersion + 1));
    }
}
