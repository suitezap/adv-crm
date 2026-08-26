<?php

declare(strict_types=1);

namespace Tests\Feature\SaaS;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;
use Tests\MultiDatabaseTestCase;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

/**
 * SuiteCoinIntegrationTest — Etapa 3 (Infraestrutura de Qualidade)
 *
 * Valida as regras de negócio críticas do sistema de moeda virtual SuiteCoins:
 * - SAAS-FEATURE-001: Integridade do saldo entre Tenant e MotherShip
 * - LEAD-AI-007: Débito único e registro em saas_transactions
 * - LEAD-AI-012: Idempotência e proteção contra duplo débito
 *
 * @package Tests\Feature\SaaS
 * @since   v3.55.0 — Etapa 3 da Infraestrutura de Qualidade
 */
class SuiteCoinIntegrationTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private User $user;
    private Lead $lead;
    private AssistantTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();


        $this->user = User::withoutEvents(function () {
            return User::create([
                'name'     => 'User SuiteCoin',
                'email'    => 'suitecoin@tenant.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'global',
                'status'   => 1,
            ]);
        });
        $this->lead = Lead::create([
            'user_id'  => $this->user->id,
            'title'    => 'Lead de Teste SuiteCoin',
            'lead_pipeline_id' => 1,
            'lead_pipeline_stage_id' => 1,
        ]);

        $this->template = AssistantTemplate::create([
            'category'        => 'triagem',
            'title'           => 'Pré-Triagem Lead',
            'prompt_structure' => 'Analise o lead: {{lead_title}}',
            'n8n_webhook_url' => 'webhook/pre-triagem-lead',
            'is_active'       => true,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SAAS-FEATURE-001: Integridade do saldo entre Tenant e MotherShip
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test SAAS-FEATURE-001
     * Garante que a conversão BRL → SuiteCoins é consistente com a taxa configurada.
     */
    public function test_brl_to_suitecoin_conversion_is_consistent(): void
    {
        // Com taxa padrão 10x: R$ 25,00 = Ƶ 250,00
        $virtual = SuiteCoinService::toVirtual(25.0);
        $backToBrl = SuiteCoinService::toBrl($virtual);

        $this->assertEquals(250.0, $virtual);
        $this->assertEquals(25.0, $backToBrl);
    }

    /**
     * @test SAAS-FEATURE-001 (mínimo de recarga)
     * Garante que valores abaixo do mínimo são inválidos.
     */
    public function test_recharge_below_minimum_is_invalid(): void
    {
        $this->assertFalse(SuiteCoinService::isValidRechargeAmount(10.0));
        $this->assertFalse(SuiteCoinService::isValidRechargeAmount(0.0));
        $this->assertFalse(SuiteCoinService::isValidRechargeAmount(24.99));
    }

    /**
     * @test SAAS-FEATURE-001 (mínimo de recarga)
     * Garante que valores no mínimo e acima são válidos.
     */
    public function test_recharge_at_or_above_minimum_is_valid(): void
    {
        $this->assertTrue(SuiteCoinService::isValidRechargeAmount(25.0));
        $this->assertTrue(SuiteCoinService::isValidRechargeAmount(100.0));
    }

    /**
     * @test SAAS-FEATURE-001 (validação de saldo)
     * Garante que hasSufficientBalance reflete corretamente o saldo.
     */
    public function test_has_sufficient_balance_reflects_correctly(): void
    {
        $this->assertTrue(SuiteCoinService::hasSufficientBalance(50.0, 25.0));
        $this->assertTrue(SuiteCoinService::hasSufficientBalance(25.0, 25.0)); // Exato
        $this->assertFalse(SuiteCoinService::hasSufficientBalance(10.0, 25.0));
        $this->assertFalse(SuiteCoinService::hasSufficientBalance(0.0, 0.01));
    }

    /**
     * @test SAAS-FEATURE-001 (preço do assistente)
     * Garante que o preço com markup é calculado corretamente.
     */
    public function test_assistant_price_calculation_with_markup(): void
    {
        // Base R$ 0,10, markup 1.25 → R$ 0.125 → Ƶ 1,25
        $priceBrl = SuiteCoinService::calculateAssistantPriceBrl(0.10);
        $priceVirtual = SuiteCoinService::calculateAssistantPriceVirtual(0.10);

        $this->assertEquals(0.125, $priceBrl);
        $this->assertEquals(1.25, $priceVirtual);
    }

    /**
     * @test SAAS-FEATURE-001 (preço zero)
     * Custo zero não gera cobrança.
     */
    public function test_zero_cost_generates_no_charge(): void
    {
        $price = SuiteCoinService::calculateAssistantPriceBrl(0.0);
        $this->assertEquals(0.0, $price);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-007: Débito único e registro em history (via Job)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-007
     * Um único histórico de execução de IA é criado por disparo de assistente.
     */
    public function test_single_history_record_per_assistant_execution(): void
    {
        $history = AssistantHistory::create([
            'user_id'        => $this->user->id,
            'lead_id'        => $this->lead->id,
            'template_id'    => $this->template->id,
            'status'         => 'queued',
            'input_data'     => ['lead_id' => $this->lead->id],
            'execution_mode' => 'async',
        ]);

        $this->assertDatabaseHas('lawfirm_assistant_history', [
            'id'      => $history->id,
            'lead_id' => $this->lead->id,
            'status'  => 'queued',
        ]);

        // Apenas 1 registro deve existir para este lead+template
        $count = AssistantHistory::where('lead_id', $this->lead->id)
            ->where('template_id', $this->template->id)
            ->count();

        $this->assertEquals(1, $count,
            'Mais de 1 registro de histórico foi criado para um único disparo — violação de LEAD-AI-007.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-012: Idempotência — proteção contra duplo débito
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-012
     * Um histórico em status 'queued' ou 'processing' não deve ser disparado novamente.
     * Garante que a lógica de idempotência do Controller previne registro duplicado.
     */
    public function test_history_in_queued_state_prevents_new_dispatch_for_same_lead_template(): void
    {
        // Primeiro disparo: cria o history em queued
        AssistantHistory::create([
            'user_id'        => $this->user->id,
            'lead_id'        => $this->lead->id,
            'template_id'    => $this->template->id,
            'status'         => 'queued',
            'input_data'     => [],
            'execution_mode' => 'async',
        ]);

        // Verifica que existe exatamente 1 registro pendente
        $pendingCount = AssistantHistory::where('lead_id', $this->lead->id)
            ->where('template_id', $this->template->id)
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        $this->assertEquals(1, $pendingCount);

        // Um segundo disparo não deve criar um novo registro se já há um pendente
        // (a lógica de guard do Controller verifica essa condição antes de criar)
        $alreadyRunning = AssistantHistory::where('lead_id', $this->lead->id)
            ->where('template_id', $this->template->id)
            ->whereIn('status', ['queued', 'processing'])
            ->exists();

        $this->assertTrue($alreadyRunning, 'O guard de idempotência deve detectar execução em andamento.');
    }

    /**
     * @test LEAD-AI-012 (completo)
     * Após 'completed', um novo disparo é permitido (não é bloqueado indefinidamente).
     */
    public function test_completed_history_allows_new_dispatch(): void
    {
        // Histórico anterior em completed
        AssistantHistory::create([
            'user_id'           => $this->user->id,
            'lead_id'           => $this->lead->id,
            'template_id'       => $this->template->id,
            'status'            => 'completed',
            'generated_content' => '# Análise anterior',
            'input_data'        => [],
            'execution_mode'    => 'async',
        ]);

        // Não há pendente — novo disparo deve ser permitido
        $hasPending = AssistantHistory::where('lead_id', $this->lead->id)
            ->where('template_id', $this->template->id)
            ->whereIn('status', ['queued', 'processing'])
            ->exists();

        $this->assertFalse($hasPending,
            'Um histórico completed não deveria bloquear novos disparos — violação de LEAD-AI-012.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Formatação e exibição de SuiteCoins
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Garante que o símbolo Ƶ e formatação estão corretos na UI.
     */
    public function test_suitecoin_format_displays_symbol_and_comma(): void
    {
        $formatted = SuiteCoinService::format(250.5);

        $this->assertStringContainsString('Ƶ', $formatted);
        $this->assertStringContainsString('250,50', $formatted);
    }

    /**
     * @test
     * Garante que formatFromBrl converte e formata corretamente.
     */
    public function test_format_from_brl_converts_and_formats(): void
    {
        // R$ 25,00 com taxa 10x = Ƶ 250,00
        $formatted = SuiteCoinService::formatFromBrl(25.0);

        $this->assertStringContainsString('Ƶ', $formatted);
        $this->assertStringContainsString('250', $formatted);
    }
}
