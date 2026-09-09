<?php

declare(strict_types=1);

namespace Tests\Feature\TenantFinance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasCustomer;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasSetting;
use SuiteZap\LawFirm\TenantFinance\Models\TenantInvoice;
use Tests\MultiDatabaseTestCase;

/**
 * TenantInvoiceTest — FIN-COBRANCAS-001 (TENANT-FIN-001)
 *
 * Cobranças Asaas isoladas por tenant_id + settings por tenant.
 */
class TenantInvoiceTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    public function test_invoice_and_customer_autofill_tenant_and_settings_are_per_tenant(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);

        TenantAsaasSetting::create([
            'api_key' => '$aact_hmlg_testkeyA', 'environment' => 'sandbox',
            'webhook_token' => 'token-a', 'is_active' => true,
        ]);

        // TENANT-FIN-001: customer criado já carrega tenant_id
        $customer = TenantAsaasCustomer::create([
            'person_id' => 1, 'asaas_customer_id' => 'cus_A',
            'name' => 'Cliente A', 'cpf_cnpj' => '12345678909',
        ]);
        $this->assertEquals('tenant-a', $customer->tenant_id);

        $invoice = TenantInvoice::create([
            'tenant_asaas_customer_id' => $customer->id,
            'type' => 'single', 'description' => 'Cobrança teste',
            'value' => 500.00, 'billing_type' => 'PIX',
            'status' => 'PENDING', 'due_date' => now()->addDays(7)->toDateString(),
        ]);
        $this->assertEquals('tenant-a', $invoice->tenant_id);
        $this->assertTrue($invoice->isPending());

        // Settings resolvidas para o tenant da sessão
        $this->assertEquals('token-a', app(\SuiteZap\LawFirm\TenantFinance\Services\TenantAsaasService::class)->getSettings()->webhook_token);

        // Tenant B não enxerga nada de A
        config(['lawfirm.tenant_id' => 'tenant-b']);
        $this->assertNull(TenantInvoice::where('id', $invoice->id)->first());
        $this->assertNull(TenantAsaasCustomer::where('id', $customer->id)->first());
        $this->assertNull(
            app(\SuiteZap\LawFirm\TenantFinance\Services\TenantAsaasService::class)->getSettings()
        );
    }

    public function test_invoice_grid_query_is_tenant_scoped(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        $this->assertStringContainsString('tenant_id', TenantInvoice::query()->toSql());
    }
}
