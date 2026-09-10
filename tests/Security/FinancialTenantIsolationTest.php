<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasCustomer;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasSetting;
use SuiteZap\LawFirm\TenantFinance\Models\TenantInvoice;
use SuiteZap\LawFirm\TenantFinance\Services\TenantAsaasService;
use Tests\MultiDatabaseTestCase;

/**
 * FinancialTenantIsolationTest — FIN-COBRANCAS-001 (TENANT-SEC-006)
 *
 * Tenant A não acessa financeiro/cobranças do Tenant B; webhook com
 * token de outro tenant não autoriza mutação.
 */
class FinancialTenantIsolationTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    public function test_cross_tenant_invoice_access_is_blocked_at_model_level(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        $customer = TenantAsaasCustomer::create([
            'person_id' => 10, 'asaas_customer_id' => 'cus_SEC_A',
            'name'      => 'Sec A', 'cpf_cnpj' => '11144477735',
        ]);
        $invoice = TenantInvoice::create([
            'tenant_asaas_customer_id' => $customer->id,
            'asaas_payment_id'         => 'pay_SEC_A',
            'type'                     => 'single', 'description' => 'Sec',
            'value'                    => 100.00, 'billing_type' => 'BOLETO',
            'status'                   => 'PENDING', 'due_date' => now()->addDays(3)->toDateString(),
        ]);

        config(['lawfirm.tenant_id' => 'tenant-b']);

        // Equivale ao 403/404 da camada HTTP: invisível para B
        $this->assertNull(TenantInvoice::where('asaas_payment_id', 'pay_SEC_A')->first());
        $this->expectException(ModelNotFoundException::class);
        TenantInvoice::findOrFail($invoice->id);
    }

    public function test_webhook_settings_resolve_per_invoice_tenant(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        TenantAsaasSetting::create([
            'api_key'       => '$aact_hmlg_A', 'environment' => 'sandbox',
            'webhook_token' => 'secret-A', 'is_active' => true,
        ]);

        config(['lawfirm.tenant_id' => 'tenant-b']);
        TenantAsaasSetting::create([
            'api_key'       => '$aact_hmlg_B', 'environment' => 'sandbox',
            'webhook_token' => 'secret-B', 'is_active' => true,
        ]);

        $service = app(TenantAsaasService::class);

        // Cada tenant resolve apenas seu próprio settings (sem first() global)
        $this->assertEquals('secret-B', $service->getSettings()->webhook_token);
        $this->assertEquals('secret-B', $service->getSettingsForTenant('tenant-b')->webhook_token);
        $this->assertEquals('secret-A', $service->getSettingsForTenant('tenant-a')->webhook_token);
        $this->assertNotEquals(
            $service->getSettingsForTenant('tenant-a')->webhook_token,
            $service->getSettingsForTenant('tenant-b')->webhook_token
        );
    }
}
