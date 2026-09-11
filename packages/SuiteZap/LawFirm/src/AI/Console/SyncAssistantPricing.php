<?php

namespace SuiteZap\LawFirm\AI\Console;

use Illuminate\Console\Command;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;

/**
 * Recalcula price_virtual de todos os templates baseado em base_cost_brl × markup_factor.
 *
 * Uso:
 *   php artisan lawfirm:sync-assistant-pricing          # Aplica mudanças
 *   php artisan lawfirm:sync-assistant-pricing --dry-run # Preview sem salvar
 */
class SyncAssistantPricing extends Command
{
    protected $signature = 'lawfirm:sync-assistant-pricing
                            {--dry-run : Preview sem gravar no banco}
                            {--id= : Recalcular somente template com este ID}';

    protected $description = 'Recalcula price_virtual de todos os assistentes (base_brl × markup)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $targetId = $this->option('id');

        $query = AssistantTemplate::query();
        if ($targetId) {
            $query->where('id', (int) $targetId);
        }

        $templates = $query->orderBy('id')->get();

        if ($templates->isEmpty()) {
            $this->warn('Nenhum template encontrado.');

            return self::SUCCESS;
        }

        $this->info(($isDryRun ? '[DRY-RUN] ' : '')."Processando {$templates->count()} templates de IA...");
        $this->newLine();

        $headers = ['ID', 'Título', 'Módulo', 'base_cost_brl', 'markup', 'price_virtual (BRL)', 'Display Ƶ'];
        $rows = [];

        foreach ($templates as $template) {
            $base = (float) $template->base_cost_brl;
            $markup = (float) ($template->markup_factor ?: 1.25);

            // Fórmula: ceil(base × markup × 10000) / 10000 → precisão 4 casas BRL
            $newPriceVirtual = SuiteCoinService::calculateAssistantPriceBrl($base, $markup);
            $displaySuiteCoins = SuiteCoinService::toVirtual($newPriceVirtual);

            $rows[] = [
                $template->id,
                mb_substr($template->title, 0, 30),
                $template->required_module ?? '(livre)',
                number_format($base, 4, ',', '.'),
                number_format($markup, 4, ',', '.'),
                'R$ '.number_format($newPriceVirtual, 4, ',', '.'),
                SuiteCoinService::format($displaySuiteCoins),
            ];

            if (! $isDryRun && abs($newPriceVirtual - (float) $template->price_virtual) > 0.0001) {
                $template->price_virtual = $newPriceVirtual;
                $template->save();
            }
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠️  Dry-run: nenhum valor foi alterado no banco.');
        } else {
            $this->info('✅  price_virtual atualizado com sucesso para todos os templates.');
        }

        return self::SUCCESS;
    }
}
