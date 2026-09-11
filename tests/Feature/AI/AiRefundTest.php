<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SuiteZap\LawFirm\AI\Jobs\ProcessAiAssistant;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use SuiteZap\LawFirm\SaaS\Models\SaasTransaction;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Models\Tenant;
use Tests\MultiDatabaseTestCase;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

/**
 * AiRefundTest — GAP-001 (LEAD-AI-013)
 *
 * Estorno automático do débito prévio quando o Job de IA falha.
 */
class AiRefundTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private User $user;

    private Lead $lead;

    private AssistantTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::withoutEvents(fn () => User::create([
            'name' => 'User Refund', 'email' => 'refund@tenant.test',
            'password' => bcrypt('password'), 'role_id' => 1,
            'view_permission' => 'global', 'status' => 1,
        ]));
        $this->lead = Lead::create([
            'user_id' => $this->user->id, 'title' => 'Lead Refund',
            'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1,
        ]);

        $node = InfrastructureNode::on('mothership')->updateOrCreate(
            ['base_url' => 'http://n8n-mock.test', 'type' => 'n8n'],
            ['name' => 'Mock N8N', 'api_key' => 'k', 'status' => 'active']
        );
        Tenant::on('mothership')->updateOrCreate(
            ['id' => 'tenant_a'],
            ['name' => 'Tenant A', 'n8n_node_id' => $node->id]
        );
        Subscription::on('mothership')->updateOrCreate(
            ['tenant_id' => 'tenant_a'],
            ['status' => 'active', 'suitecoin_balance' => 100.0,
             'active_modules' => ['LEGAL', 'AI']]
        );
        config(['lawfirm.tenant_id' => 'tenant_a']);

        // Template com custo (price_virtual em SuiteCoins → débito em BRL).
        $this->template = AssistantTemplate::create([
            'category' => 'triagem', 'title' => 'Refund Probe',
            'prompt_structure' => 'P', 'n8n_webhook_url' => 'webhook/x',
            'is_active' => true, 'price_virtual' => 10.0,
        ]);
    }

    private function balance(): float
    {
        Cache::forget('tenant_tenant_a_subscription');

        return (float) Subscription::on('mothership')
            ->where('tenant_id', 'tenant_a')->first()->suitecoin_balance;
    }

    private function debitLikeController(float $brl): void
    {
        $sub = Subscription::on('mothership')->where('tenant_id', 'tenant_a')->first();
        $sub->decrement('suitecoin_balance', $brl);
        SaasTransaction::create([
            'tenant_id' => 'tenant_a', 'user_id' => $this->user->id,
            'type' => 'debit', 'amount' => $brl,
            'balance_after' => $sub->suitecoin_balance,
            'currency' => 'SUITECOIN', 'service_type' => 'AI_ASSISTANT',
            'description' => 'Débito prévio (teste)',
            'reference_type' => 'assistant_template', 'reference_id' => $this->template->id,
        ]);
        Cache::forget('tenant_tenant_a_subscription');
    }

    public function test_failed_job_refunds_prior_debit_once(): void
    {
        Http::fake(['http://n8n-mock.test/*' => Http::response('boom', 500)]);

        // Débito igual ao do Controller: price_virtual direto (BRL, paridade 1:1).
        $this->debitLikeController(10.0);
        $this->assertEquals(90.0, $this->balance());

        $history = AssistantHistory::create([
            'user_id' => $this->user->id, 'lead_id' => $this->lead->id,
            'template_id' => $this->template->id, 'status' => 'queued',
            'input_data' => [], 'execution_mode' => 'async',
        ]);

        (new ProcessAiAssistant($history, $this->template, []))->handle();

        // GAP-001: falha → failed + saldo restaurado + 1 crédito de estorno.
        $this->assertEquals('failed', $history->fresh()->status);
        $this->assertEquals(100.0, $this->balance());
        $this->assertEquals(1, SaasTransaction::where('reference_type', 'assistant_history')
            ->where('reference_id', $history->id)->where('type', 'credit')->count());

        // Idempotência: segunda passagem não duplica o crédito.
        (new ProcessAiAssistant($history->fresh(), $this->template, []))->handle();
        $this->assertEquals(100.0, $this->balance());
        $this->assertEquals(1, SaasTransaction::where('reference_type', 'assistant_history')
            ->where('reference_id', $history->id)->where('type', 'credit')->count());
    }

    public function test_successful_job_does_not_refund(): void
    {
        Http::fake(['http://n8n-mock.test/*' => Http::response(['output' => 'ok'], 200)]);

        $history = AssistantHistory::create([
            'user_id' => $this->user->id, 'lead_id' => $this->lead->id,
            'template_id' => $this->template->id, 'status' => 'queued',
            'input_data' => [], 'execution_mode' => 'async',
        ]);

        (new ProcessAiAssistant($history, $this->template, []))->handle();

        $this->assertEquals('completed', $history->fresh()->status);
        $this->assertEquals(0, SaasTransaction::where('reference_type', 'assistant_history')
            ->where('reference_id', $history->id)->count());
    }
}
