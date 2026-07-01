<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos de precificação SuiteCoins (Ƶ) à tabela de templates de assistentes.
 *
 * IMPORTANTE: Os valores são armazenados em BRL (paridade 1:1 com saldo do tenant).
 *             A exibição em Ƶ é feita somente na camada de UI (×10).
 *
 * @since v3.48 — SuiteCoins Assistants Pricing
 */
return new class extends Migration
{
    protected $connection = 'mothership';

    public function up(): void
    {
        Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
            // Custo técnico base estimado em BRL (ex: custo de tokens LLM)
            if (! Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'base_cost_brl')) {
                $table->decimal('base_cost_brl', 8, 4)->default(0.0000)->after('is_active')
                    ->comment('Custo técnico base em BRL (tokens LLM + infra)');
            }

            // Multiplicador de markup específico por template (padrão 1.25 = +25%)
            if (! Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'markup_factor')) {
                $table->decimal('markup_factor', 5, 4)->default(1.2500)->after('base_cost_brl')
                    ->comment('Markup de operação (1.25 = +25%). Cobre taxas Asaas diluídas.');
            }

            // Preço final em BRL cobrado do saldo do tenant (base_cost_brl × markup_factor, arredondado para cima)
            // Para exibir em Ƶ: price_virtual × 10
            if (! Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'price_virtual')) {
                $table->decimal('price_virtual', 8, 4)->default(0.0000)->after('markup_factor')
                    ->comment('Preço final BRL debitado do saldo. Display Ƶ = price_virtual × suitecoin_rate');
            }
        });

        // ── Popula preços iniciais por módulo/categoria ──────────────────────────
        //
        // Estratégia de precificação por complexidade:
        // - Lead (null):  R$ 0,05 base → price_virtual R$ 0,0625 → Ƶ 0,63
        // - Verif:        R$ 0,10 base → price_virtual R$ 0,1250 → Ƶ 1,25
        // - IA-Módulos:   R$ 0,15 base → price_virtual R$ 0,1875 → Ƶ 1,88
        // - IAWhatsApp:   R$ 0,10 base → price_virtual R$ 0,1250 → Ƶ 1,25

        $updates = [
            // Lead (sem required_module) – Triagem, Qualificação, Proposta, Negociação
            [
                'module'        => null,
                'base_cost_brl' => 0.0500,
                'markup_factor' => 1.2500,
            ],
            // Verif – Assistentes processuais (Resumo Decisão, Petição, Prazo, LGPD, Triagem)
            [
                'module'        => 'verif',
                'base_cost_brl' => 0.1000,
                'markup_factor' => 1.2500,
            ],
            // IA Especializada – Trabalhista
            [
                'module'        => 'IA-Trabalhista',
                'base_cost_brl' => 0.1500,
                'markup_factor' => 1.2500,
            ],
            // IA Especializada – Previdência
            [
                'module'        => 'IA-Previdencia',
                'base_cost_brl' => 0.1500,
                'markup_factor' => 1.2500,
            ],
            // IA Especializada – Família e Sucessões
            [
                'module'        => 'IA-Familia_Sucessoes',
                'base_cost_brl' => 0.1500,
                'markup_factor' => 1.2500,
            ],
            // IA Especializada – Civil
            [
                'module'        => 'IA-Civil',
                'base_cost_brl' => 0.1500,
                'markup_factor' => 1.2500,
            ],
            // WhatsApp Triagem  – custo médio
            [
                'module'        => 'IAWhatsApp',
                'base_cost_brl' => 0.1000,
                'markup_factor' => 1.2500,
            ],
        ];

        foreach ($updates as $config) {
            // price_virtual = ceil(base × markup × 10000) / 10000  (precisão 4 casas)
            $priceVirtual = ceil($config['base_cost_brl'] * $config['markup_factor'] * 10000) / 10000;

            $query = DB::connection('mothership')->table('lawfirm_assistant_templates');

            if (is_null($config['module'])) {
                $query->whereNull('required_module');
            } else {
                $query->where('required_module', $config['module']);
            }

            $query->update([
                'base_cost_brl'  => $config['base_cost_brl'],
                'markup_factor'  => $config['markup_factor'],
                'price_virtual'  => $priceVirtual,
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
            $table->dropColumn(['base_cost_brl', 'markup_factor', 'price_virtual']);
        });
    }
};
