<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use SuiteZap\LawFirm\SaaS\Models\Tenant;
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

    public function test_escavador_webhook_without_valid_token_is_rejected_401(): void
    {
        // WEBHOOK-SEC-002: Sem token → negado fail-closed (401)
        $response = $this->postJson(route('webhooks.escavador'), ['id' => 'req_FORGED']);
        $response->assertStatus(401);
    }

    public function test_escavador_webhook_with_unknown_external_id_changes_nothing(): void
    {
        // WEBHOOK-SEC-002: Com token válido mas external_id desconhecido → 200 not_found sem estorno
        config(['services.escavador.webhook_token' => 'test-escavador-token']);
        InfrastructureNode::on('mothership')->updateOrCreate(
            ['type' => 'escavador'],
            ['name' => 'LawFimr V1 e V2', 'status' => 'active', 'meta_data' => ['webhook_token' => 'test-escavador-token']]
        );
        cache()->forget('escavador_webhook_token_'.md5('LawFimr V1 e V2'));

        $response = $this->postJson(
            route('webhooks.escavador'),
            ['id' => 'req_UNKNOWN'],
            ['Authorization' => 'Bearer test-escavador-token']
        );

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
        // Idempotente: conexão mothership não é limpa pelo RefreshDatabase.
        $node = InfrastructureNode::firstOrCreate(
            ['name' => 'Evo Teste WebhookAuth'],
            ['type'         => 'evolution',
                'base_url'  => 'https://evo.test', 'api_key' => 'k',
                'meta_data' => ['webhook_secret' => 'segredo-123'],
                'status'    => 'active']
        );
        $node->update(['meta_data' => ['webhook_secret' => 'segredo-123']]);
        Tenant::updateOrCreate(
            ['id' => '777001'],
            ['name'                       => 'Tenant Secret',
                'evolution_node_id'       => $node->id,
                'evolution_instance_name' => 'inst-777']
        );

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
