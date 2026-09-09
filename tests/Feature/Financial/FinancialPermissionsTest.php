<?php

declare(strict_types=1);

namespace Tests\Feature\Financial;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasCustomer;
use SuiteZap\LawFirm\TenantFinance\Models\TenantAsaasSetting;
use SuiteZap\LawFirm\TenantFinance\Models\TenantInvoice;
use Tests\MultiDatabaseTestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * FinancialPermissionsTest — FIN-COBRANCAS-001 (FIN-SEC-001)
 *
 * Visibilidade dependente da configuração do usuário (role/permissions):
 * - Sem permissão financeira → 401 em dashboard, cobranças, settings e ações.
 * - Com `cobrancas.view` apenas → lê lista/detalhe, mas não cria, cancela,
 *   reenvia nem acessa settings.
 * - Credenciais Asaas (api_key/webhook_token) nunca expostas em texto plano
 *   na tela de settings (inputs password) e nunca fora do gate `settings`.
 */
class FinancialPermissionsTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private function makeRole(array $permissions): Role
    {
        return Role::create([
            'name'            => 'Papel Teste '.uniqid(),
            'permission_type' => 'custom',
            'permissions'     => $permissions,
        ]);
    }

    private function makeUser(Role $role, string $viewPermission = 'individual'): User
    {
        return User::withoutEvents(fn () => User::create([
            'name'            => 'User '.uniqid(),
            'email'           => uniqid().'@tenant.test',
            'password'        => bcrypt('password'),
            'role_id'         => $role->id,
            'view_permission' => $viewPermission,
            'status'          => 1,
        ]));
    }

    private function seedSubscription(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        Cache::flush();

        // Gate de módulo (CheckTenantFinanceModule): assinatura com TENANT_FINANCE no mothership
        Subscription::create([
            'tenant_id'      => 'tenant-a',
            'status'         => 'active',
            'active_modules' => ['TENANT_FINANCE'],
        ]);
    }

    private function seedTenantData(): TenantInvoice
    {
        $this->seedSubscription();

        $customer = TenantAsaasCustomer::create([
            'person_id' => 1, 'asaas_customer_id' => 'cus_PERM_A',
            'name' => 'Cliente Perm', 'cpf_cnpj' => '12345678909',
        ]);

        return TenantInvoice::create([
            'tenant_asaas_customer_id' => $customer->id,
            'asaas_payment_id'         => 'pay_PERM_A',
            'type'                     => 'single',
            'description'              => 'Cobrança perm',
            'value'                    => 250.00,
            'billing_type'             => 'PIX',
            'status'                   => 'PENDING',
            'due_date'                 => now()->addDays(7)->toDateString(),
        ]);
    }

    public function test_user_without_financial_permissions_gets_401_everywhere(): void
    {
        $invoice = $this->seedTenantData();
        $user = $this->makeUser($this->makeRole(['dashboard.view']));

        // CSRF fora (foco em auth/ACL/tenancy); demais middlewares ativos
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // FIN-SEC-001: dashboard financeiro oculto sem lawfirm.financeiro.view
        $this->get(route('admin.lawfirm.financial.index'))->assertStatus(401);

        // Cobranças ocultas sem cobrancas.view
        $this->get(route('admin.lawfirm.tenant_finance.index'))->assertStatus(401);
        $this->get(route('admin.lawfirm.tenant_finance.show', $invoice->id))->assertStatus(401);
        $this->get(route('admin.lawfirm.tenant_finance.api.customer', 1))->assertStatus(401);

        // Ações de escrita bloqueadas sem create/edit/delete
        $this->postJson(route('admin.lawfirm.tenant_finance.store'), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.tenant_finance.cancel', $invoice->id))->assertStatus(401);
        $this->postJson(route('admin.lawfirm.tenant_finance.resend', $invoice->id))->assertStatus(401);

        // Settings (credenciais) ocultas sem cobrancas.settings
        $this->get(route('admin.lawfirm.tenant_finance.settings'))->assertStatus(401);
        $this->post(route('admin.lawfirm.tenant_finance.settings.store'), [])->assertStatus(401);
    }

    public function test_view_only_user_reads_but_cannot_write_or_see_settings(): void
    {
        $invoice = $this->seedTenantData();
        $user = $this->makeUser($this->makeRole(['lawfirm.financeiro.cobrancas.view']));

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // Leitura liberada...
        $this->get(route('admin.lawfirm.tenant_finance.index'))->assertOk();
        $this->get(route('admin.lawfirm.tenant_finance.show', $invoice->id))->assertOk();

        // ...escrita e settings bloqueadas
        $this->postJson(route('admin.lawfirm.tenant_finance.store'), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.tenant_finance.cancel', $invoice->id))->assertStatus(401);
        $this->postJson(route('admin.lawfirm.tenant_finance.resend', $invoice->id))->assertStatus(401);
        $this->get(route('admin.lawfirm.tenant_finance.settings'))->assertStatus(401);
    }

    public function test_settings_page_masks_credentials_and_never_leaks_plaintext_key(): void
    {
        $this->seedSubscription();
        TenantAsaasSetting::create([
            'api_key' => '$aact_hmlg_SECRETKEY',
            'environment' => 'sandbox',
            'webhook_token' => 'SECRETTOKEN',
            'is_active' => true,
        ]);

        $user = $this->makeUser($this->makeRole(['lawfirm.financeiro.cobrancas.settings']));
        $this->actingAs($user, 'user');

        $response = $this->get(route('admin.lawfirm.tenant_finance.settings'));
        $response->assertOk();

        // FIN-SEC-001: segredos nunca no HTML (nem em value, nem em texto) + inputs mascarados
        $response->assertDontSee('$aact_hmlg_SECRETKEY', false);
        $response->assertDontSee('SECRETTOKEN', false);
        $response->assertSee('type="password"', false);
        $response->assertSee('mantém a atual', false);
    }

    public function test_settings_store_keeps_existing_key_when_blank(): void
    {
        $this->seedSubscription();
        TenantAsaasSetting::create([
            'api_key' => '$aact_hmlg_KEEPME',
            'environment' => 'sandbox',
            'webhook_token' => 'KEEPTOKEN',
            'is_active' => true,
        ]);

        $user = $this->makeUser($this->makeRole(['lawfirm.financeiro.cobrancas.settings']));
        $this->actingAs($user, 'user');

        // Salva sem tocar nas credenciais → chaves preservadas (CSRF fora; auth/tenancy ativos)
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('admin.lawfirm.tenant_finance.settings.store'), [
            'api_key' => '',
            'webhook_token' => '',
            'environment' => 'sandbox',
        ])->assertRedirect(route('admin.lawfirm.tenant_finance.settings'));

        $settings = TenantAsaasSetting::first();
        $this->assertEquals('$aact_hmlg_KEEPME', $settings->api_key);
        $this->assertEquals('KEEPTOKEN', $settings->webhook_token);
    }

    public function test_individual_view_permission_sees_only_own_user_id(): void
    {
        $user = $this->makeUser($this->makeRole([]), 'individual');

        // Bouncer: individual → apenas o próprio id; global → null (tudo)
        $this->actingAs($user, 'user');
        $this->assertEquals([$user->id], bouncer()->getAuthorizedUserIds());

        $global = $this->makeUser($this->makeRole([]), 'global');
        $this->actingAs($global, 'user');
        $this->assertNull(bouncer()->getAuthorizedUserIds());
    }
}
