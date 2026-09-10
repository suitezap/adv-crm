<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MultiDatabaseTestCase;

/**
 * WebhookAuthTest — PRIV-AUDIT-001 Onda 1b (WEBHOOK-SEC-001)
 *
 * Webhooks públicos negam eventos forjados (fail-closed).
 */
class WebhookAuthTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    public function test_asaas_webhook_without_valid_token_is_denied_without_credit(): void
    {
        // Sem token válido → negado, sem crédito, sem exceção visível (200 obscuro)
        $response = $this->postJson(route('webhooks.asaas'), [
            'event'   => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_FORGED', 'value' => 100.00],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseMissing('saas_transactions', ['reference_id' => 'pay_FORGED']);
    }

    public function test_escavador_webhook_with_unknown_external_id_changes_nothing(): void
    {
        $response = $this->postJson(route('webhooks.escavador'), ['id' => 'req_UNKNOWN']);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'not_found']);
    }

    public function test_whatsapp_webhook_with_unknown_tenant_is_rejected(): void
    {
        $response = $this->postJson(route('webhooks.whatsapp_messenger', ['tenantId' => 999999]), [
            'event' => 'messages.upsert',
            'data'  => ['messages' => []],
        ]);

        // Tenant inexistente (ou mothership inacessível) → 400, nada criado
        $response->assertStatus(400);
    }

    public function test_whatsapp_webhook_secret_is_enforced_when_configured(): void
    {
        $node = \SuiteZap\LawFirm\SaaS\Models\InfrastructureNode::create([
            'name' => 'Evo Teste', 'type' => 'evolution',
            'base_url' => 'https://evo.test', 'api_key' => 'k',
            'meta_data' => ['webhook_secret' => 'segredo-123'],
            'status' => 'active',
        ]);
        \SuiteZap\LawFirm\SaaS\Models\Tenant::create([
            'id' => '777001', 'name' => 'Tenant Secret',
            'evolution_node_id' => $node->id,
            'evolution_instance_name' => 'inst-777',
        ]);

        $route = route('webhooks.whatsapp_messenger', ['tenantId' => 777001]);

        // Sem token → 401
        $this->postJson($route, ['event' => 'ping'])->assertStatus(401);

        // Token errado → 401
        $this->postJson($route, ['event' => 'ping'], ['X-Webhook-Token' => 'errado'])
            ->assertStatus(401);

        // Token certo + instância do tenant → processa (200)
        $this->postJson($route, ['event' => 'ping', 'instance' => 'inst-777'],
            ['X-Webhook-Token' => 'segredo-123'])->assertStatus(200);
    }
}
