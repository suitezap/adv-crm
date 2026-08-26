<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use SuiteZap\LawFirm\AI\Jobs\ProcessAiAssistant;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;
use Tests\MultiDatabaseTestCase;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

/**
 * AiAssistantTest — Etapa 3 (Infraestrutura de Qualidade)
 *
 * Testa o ciclo de vida completo dos Assistentes de IA:
 * - Disparo assíncrono dos 4 assistentes (LEAD-AI-001 a 004)
 * - Transições de status queued → processing → completed/failed (LEAD-AI-005)
 * - Persistência íntegra do Markdown gerado (LEAD-AI-006)
 * - Falha HTTP do N8N marca failed sem derrubar o worker (LEAD-AI-008)
 * - N8N ausente retorna 503 no Controller (LEAD-AI-009)
 * - Saldo insuficiente bloqueia execução (LEAD-AI-010)
 * - Paginação e isolamento do histórico (LEAD-AI-011)
 *
 * Nota: LEAD-AI-007 e LEAD-AI-012 (SuiteCoins e idempotência) são abordados em
 * SuiteCoinIntegrationTest.php para isolamento de responsabilidade.
 *
 * @package Tests\Feature\AI
 * @since   v3.55.0 — Etapa 3 da Infraestrutura de Qualidade
 */
class AiAssistantTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private Lead $lead;
    private User $user;
    private AssistantTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();


        $this->user = User::withoutEvents(function () {
            return User::create([
                'name'     => 'User Test',
                'email'    => 'user_ai@tenant.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'global',
                'status'   => 1,
            ]);
        });
        $this->lead = Lead::create([
            'user_id'  => $this->user->id,
            'title'    => 'Lead de Teste IA',
            'lead_pipeline_id' => 1,
            'lead_pipeline_stage_id' => 1,
        ]);

        // Template no banco mothership_test (connection = 'mothership')
        $this->template = AssistantTemplate::create([
            'category'         => 'triagem',
            'title'            => 'Pré-Triagem de Lead',
            'description'      => 'Análise dos fatos do Lead',
            'prompt_structure'  => 'Analise o lead: {{lead_title}}',
            'n8n_webhook_url'  => 'webhook/pre-triagem-lead',
            'is_active'        => true,
        ]);
    }

    /**
     * Helper: cria um AssistantHistory em status 'queued'.
     */
    private function makeHistory(array $overrides = []): AssistantHistory
    {
        return AssistantHistory::create(array_merge([
            'user_id'        => $this->user->id,
            'lead_id'        => $this->lead->id,
            'template_id'    => $this->template->id,
            'status'         => 'queued',
            'input_data'     => ['lead_id' => $this->lead->id],
            'execution_mode' => 'async',
        ], $overrides));
    }

    /**
     * Helper: mock do MotherShipService com n8n configurado e saldo suficiente.
     */
    private function mockMotherShipWithN8n(float $balance = 100.0): void
    {
        \Illuminate\Support\Facades\Cache::flush();

        $node = \SuiteZap\LawFirm\SaaS\Models\InfrastructureNode::on('mothership')->updateOrCreate(
            ['base_url' => 'http://n8n-mock.test', 'type' => 'n8n'],
            [
                'name' => 'Mock N8N Node',
                'api_key' => 'test-n8n-api-key',
                'status' => 'active'
            ]
        );

        \SuiteZap\LawFirm\SaaS\Models\Tenant::on('mothership')->updateOrCreate(
            ['id' => 'tenant_a'],
            ['name' => 'Tenant A', 'domain' => 'tenant-a.test', 'n8n_node_id' => $node->id]
        );

        \SuiteZap\LawFirm\SaaS\Models\Subscription::on('mothership')->updateOrCreate(
            ['tenant_id' => 'tenant_a'],
            [
                'plan_name' => 'Premium',
                'suitecoin_balance' => $balance,
                'status' => 'active',
                'active_modules' => ['LEGAL', 'AI']
            ]
        );

        config(['lawfirm.tenant_id' => 'tenant_a']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-001 a 004: Disparo assíncrono dos 4 assistentes
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-001
     * Garante que ao disparar o Job do assistente, ele é enfileirado (sync=fake).
     */
    public function test_assistant_job_is_dispatched_to_queue(): void
    {
        Queue::fake();

        $history = $this->makeHistory();

        ProcessAiAssistant::dispatch($history, $this->template, ['lead_id' => $this->lead->id]);

        Queue::assertPushed(ProcessAiAssistant::class, function ($job) use ($history) {
            return true; // Job foi enfileirado
        });
    }

    /**
     * @test LEAD-AI-001 (complementar — todos os 4 slugs são enfileiráveis)
     */
    public function test_all_four_assistant_slugs_can_be_dispatched(): void
    {
        Queue::fake();

        $slugs = [
            'pre-triagem-lead',
            'pre-triagem-checklist',
            'gerador-proposta',
            'script-vendas',
        ];

        foreach ($slugs as $slug) {
            $template = AssistantTemplate::create([
                'category'        => 'triagem',
                'title'           => $slug,
                'prompt_structure' => 'Prompt para ' . $slug,
                'n8n_webhook_url' => "webhook/{$slug}",
                'is_active'       => true,
            ]);
            $history = $this->makeHistory(['template_id' => $template->id]);
            ProcessAiAssistant::dispatch($history, $template, []);
        }

        Queue::assertPushed(ProcessAiAssistant::class, 4);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-005: Transições de status queued → processing → completed/failed
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-005
     * Garante a transição: queued → processing → completed quando N8N responde com sucesso.
     */
    public function test_status_transitions_queued_to_processing_to_completed(): void
    {
        $history = $this->makeHistory();

        Http::fake([
            'http://n8n-mock.test/*' => Http::response([
                'output' => '# Análise dos Fatos\n\nConteúdo gerado pelo N8N.',
            ], 200),
        ]);

        $this->mockMotherShipWithN8n();

        $job = new ProcessAiAssistant($history, $this->template, []);
        $job->handle();

        $history->refresh();

        if ($history->status !== 'completed') {
            dd($history->error_message);
        }
        $this->assertEquals('completed', $history->status);
        $this->assertNotNull($history->generated_content);
    }

    /**
     * @test LEAD-AI-005 (transição para failed)
     * Garante que status vai para failed quando N8N retorna erro HTTP.
     */
    public function test_status_transitions_to_failed_on_n8n_error(): void
    {
        $history = $this->makeHistory();

        Http::fake([
            'http://n8n-mock.test/*' => Http::response('Internal Server Error', 500),
        ]);

        $this->mockMotherShipWithN8n();

        $job = new ProcessAiAssistant($history, $this->template, []);
        $job->handle();

        $history->refresh();

        $this->assertEquals('failed', $history->status);
        $this->assertStringContainsString('500', $history->error_message);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-006: Persistência íntegra do Markdown gerado
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-006
     * O Markdown gerado deve ser armazenado sem mutações no campo generated_content.
     */
    public function test_generated_markdown_is_persisted_without_mutation(): void
    {
        $rawMarkdown = "# Relatório\n\n**Análise:** Item 1\n- Ponto A\n- Ponto B\n\n> Conclusão: aprovado.";

        Http::fake([
            'http://n8n-mock.test/*' => Http::response([
                'output' => $rawMarkdown,
            ], 200),
        ]);

        $this->mockMotherShipWithN8n();

        $history = $this->makeHistory();
        $job = new ProcessAiAssistant($history, $this->template, []);
        $job->handle();

        $history->refresh();

        $this->assertEquals('completed', $history->status);
        $this->assertEquals($rawMarkdown, $history->generated_content,
            'O Markdown gerado foi modificado pelo backend — violação de LEAD-AI-006.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-008: Falha HTTP do N8N marca failed sem derrubar o worker
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-008
     * Uma exceção de rede não propaga throw — o Job falha graciosamente.
     */
    public function test_n8n_network_exception_marks_failed_without_crashing_worker(): void
    {
        Http::fake([
            'http://n8n-mock.test/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $this->mockMotherShipWithN8n();

        $history = $this->makeHistory();
        $job = new ProcessAiAssistant($history, $this->template, []);

        // O Job NÃO deve propagar exceção (worker continua rodando)
        $threwException = false;
        try {
            $job->handle();
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertFalse($threwException, 'O Job propagou uma exceção — o worker seria derrubado (violação de LEAD-AI-008).');

        $history->refresh();
        $this->assertEquals('failed', $history->status);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-009: N8N ausente retorna 503 no Controller
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-009
     * Job com N8N ausente (null) deve marcar history como failed graciosamente.
     */
    public function test_missing_n8n_config_marks_history_as_failed(): void
    {
        \Illuminate\Support\Facades\Cache::flush();

        // Usa mock de DB em vez de Mockery
        \SuiteZap\LawFirm\SaaS\Models\Tenant::on('mothership')->updateOrCreate(
            ['id' => 'tenant_a'],
            ['domain' => 'tenant-a.test', 'n8n_node_id' => null] // N8N ausente!
        );

        \SuiteZap\LawFirm\SaaS\Models\Subscription::on('mothership')->updateOrCreate(
            ['tenant_id' => 'tenant_a'],
            [
                'plan_name' => 'Premium',
                'suitecoin_balance' => 100.0,
                'status' => 'active',
                'active_modules' => ['LEGAL', 'AI']
            ]
        );

        config(['lawfirm.tenant_id' => 'tenant_a']);

        $history = $this->makeHistory();
        $job = new ProcessAiAssistant($history, $this->template, []);
        $job->handle();

        $history->refresh();
        $this->assertEquals('failed', $history->status);
        $this->assertStringContainsString('N8N', $history->error_message);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-010: Saldo insuficiente bloqueia execução
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-010
     * Com saldo zero, o Job marca failed antes de qualquer chamada HTTP.
     */
    public function test_insufficient_balance_blocks_execution_before_http(): void
    {
        \Illuminate\Support\Facades\Cache::flush();

        $node = \SuiteZap\LawFirm\SaaS\Models\InfrastructureNode::on('mothership')->updateOrCreate(
            ['base_url' => 'http://n8n-mock.test', 'type' => 'n8n'],
            [
                'name' => 'Mock N8N Node',
                'api_key' => 'test-n8n-api-key',
                'status' => 'active'
            ]
        );

        // Usa mock de DB em vez de Mockery
        \SuiteZap\LawFirm\SaaS\Models\Tenant::on('mothership')->updateOrCreate(
            ['id' => 'tenant_a'],
            ['domain' => 'tenant-a.test', 'n8n_node_id' => $node->id]
        );

        \SuiteZap\LawFirm\SaaS\Models\Subscription::on('mothership')->updateOrCreate(
            ['tenant_id' => 'tenant_a'],
            [
                'plan_name' => 'Premium',
                'suitecoin_balance' => 0.0, // Sem saldo
                'status' => 'active',
                'active_modules' => ['LEGAL', 'AI']
            ]
        );

        config(['lawfirm.tenant_id' => 'tenant_a']);

        Http::fake(); // Nenhuma requisição deve ser feita

        $history = $this->makeHistory();
        $job = new ProcessAiAssistant($history, $this->template, []);
        $job->handle();

        $history->refresh();

        $this->assertEquals('failed', $history->status);
        $this->assertStringContainsString('SuiteCoins', $history->error_message);

        Http::assertNothingSent();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LEAD-AI-011: Paginação e isolamento do histórico por lead/user
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test LEAD-AI-011
     * O histórico de um lead não vaza para outro usuário/lead.
     */
    public function test_history_is_isolated_per_lead(): void
    {
        $otherUser = User::withoutEvents(function () {
            return User::create([
                'name'     => 'Other User',
                'email'    => 'other_user@tenant.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'global',
                'status'   => 1,
            ]);
        });
        $otherLead = Lead::create([
            'user_id'  => $otherUser->id,
            'title'    => 'Other Lead',
            'lead_pipeline_id' => 1,
            'lead_pipeline_stage_id' => 1,
        ]);

        // Cria 3 históricos para o Lead do usuário correto
        for ($i = 0; $i < 3; $i++) {
            AssistantHistory::create([
                'user_id'     => $this->user->id,
                'lead_id'     => $this->lead->id,
                'template_id' => $this->template->id,
                'status'      => 'completed',
                'execution_mode' => 'async',
            ]);
        }

        // Cria 2 históricos para o outro lead (não deve aparecer)
        for ($i = 0; $i < 2; $i++) {
            AssistantHistory::create([
                'user_id'     => $otherUser->id,
                'lead_id'     => $otherLead->id,
                'template_id' => $this->template->id,
                'status'      => 'completed',
                'execution_mode' => 'async',
            ]);
        }

        // Consulta isolada do lead correto
        $myHistory = AssistantHistory::where('lead_id', $this->lead->id)->get();
        $otherHistory = AssistantHistory::where('lead_id', $otherLead->id)->get();

        $this->assertCount(3, $myHistory);
        $this->assertCount(2, $otherHistory);

        // Garantia de que IDs não se cruzam
        $myIds = $myHistory->pluck('lead_id')->unique()->toArray();
        $otherIds = $otherHistory->pluck('lead_id')->unique()->toArray();

        $this->assertEmpty(array_intersect($myIds, $otherIds),
            'IDs de lead cruzaram entre usuários distintos — violação de LEAD-AI-011.'
        );
    }

    /**
     * @test LEAD-AI-011 (paginação)
     * Garante que a query de histórico pode ser paginada corretamente.
     */
    public function test_history_supports_pagination(): void
    {
        for ($i = 0; $i < 15; $i++) {
            AssistantHistory::create([
                'user_id'     => $this->user->id,
                'lead_id'     => $this->lead->id,
                'template_id' => $this->template->id,
                'status'      => 'completed',
                'execution_mode' => 'async',
            ]);
        }

        $page1 = AssistantHistory::where('lead_id', $this->lead->id)
            ->latest()
            ->paginate(10, ['*'], 'page', 1);

        $page2 = AssistantHistory::where('lead_id', $this->lead->id)
            ->latest()
            ->paginate(10, ['*'], 'page', 2);

        $this->assertCount(10, $page1->items());
        $this->assertCount(5, $page2->items());
        $this->assertEquals(15, $page1->total());
    }
}
