<?php

declare(strict_types=1);

namespace Tests\Feature\Escavador;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\Escavador\Models\EscavadorMonitoramento;
use SuiteZap\LawFirm\Escavador\Models\EscavadorRequest;
use Tests\MultiDatabaseTestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * EscavadorTenantTest — PRIV-AUDIT-001 Onda 1a (ESC-SEC-001)
 *
 * Tenant isolation em escavador_requests/monitoramentos + gates de perfil.
 */
class EscavadorTenantTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private function makeUser(array $permissions): User
    {
        $role = Role::create([
            'name'            => 'Papel Esc '.uniqid(),
            'permission_type' => 'custom',
            'permissions'     => $permissions,
        ]);

        return User::withoutEvents(fn () => User::create([
            'name'            => 'User '.uniqid(),
            'email'           => uniqid().'@tenant.test',
            'password'        => bcrypt('password'),
            'role_id'         => $role->id,
            'view_permission' => 'individual',
            'status'          => 1,
        ]));
    }

    public function test_requests_and_monitoramentos_are_tenant_scoped(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);

        $req = EscavadorRequest::create([
            'endpoint_type' => 'CAPA_PROCESSO', 'status' => 'completed', 'cost' => 1.5,
        ]);
        $mon = EscavadorMonitoramento::create([
            'type' => 'TRIBUNAL', 'query_value' => '123', 'frequency' => 'daily', 'status' => 'ativo',
        ]);

        $this->assertEquals('tenant-a', $req->tenant_id);
        $this->assertEquals('tenant-a', $mon->tenant_id);

        config(['lawfirm.tenant_id' => 'tenant-b']);
        $this->assertNull(EscavadorRequest::where('id', $req->id)->first());
        $this->assertNull(EscavadorMonitoramento::where('id', $mon->id)->first());
    }

    public function test_history_and_monitoramentos_require_view_permission(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // ESC-SEC-001: sem lawfirm.escavador.view → 401, sem vazar listagem
        $this->get(route('lawfirm.escavador.history'))->assertStatus(401);
        $this->get(route('lawfirm.escavador.monitoramentos.index'))->assertStatus(401);
        $this->get(route('lawfirm.escavador.monitoramentos.create'))->assertStatus(401);
    }

    public function test_toggle_whatsapp_requires_create_permission(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        $mon = EscavadorMonitoramento::create([
            'type' => 'TRIBUNAL', 'query_value' => '456', 'frequency' => 'daily', 'status' => 'ativo',
        ]);

        // Só leitura → toggle bloqueado
        $viewer = $this->makeUser(['lawfirm.escavador.view']);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs($viewer, 'user');
        $this->postJson(route('lawfirm.escavador.monitoramentos.toggle_whatsapp', $mon->id))
            ->assertStatus(401);
    }

    /**
     * SEC-HARD-002 Onda 3 — EscavadorController: dashboard e leituras exigem view.
     */
    public function test_dashboard_and_reads_require_view_permission(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // Sem lawfirm.escavador.view → 401 antes de qualquer chamada de serviço
        // (lawfirm.escavador.monitoramentos fora: rota V1 sombreada pela .index — método morto)
        $this->get(route('lawfirm.escavador.index'))->assertStatus(401);
        $this->get(route('lawfirm.escavador.saldo'))->assertStatus(401);
        $this->get(route('lawfirm.escavador.saldo_cliente'))->assertStatus(401);
        $this->get(route('lawfirm.escavador.processo_details', 9999))->assertStatus(401);
        $this->get(route('lawfirm.escavador.certificados.view'))->assertStatus(401);
    }

    /**
     * SEC-HARD-002 Onda 3 — serviços pagos e mutações exigem create/certs.manage.
     *
     * Gates executam antes de validate/serviço: 401 sem efeitos colaterais.
     */
    public function test_paid_services_require_elevated_permission(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);
        $viewer = $this->makeUser(['lawfirm.escavador.view']);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs($viewer, 'user');

        // Leitura não autoriza execução paga nem upload de certificado
        $this->postJson(route('lawfirm.escavador.servico'), [])->assertStatus(401);
        $this->postJson(route('lawfirm.escavador.download_autos'), [])->assertStatus(401);
        $this->postJson(route('lawfirm.escavador.sync_processo'), [])->assertStatus(401);
        $this->postJson(route('lawfirm.escavador.busca'), [])->assertStatus(401);
        $this->postJson(route('lawfirm.escavador.certificados.store'), [])->assertStatus(401);
        $this->deleteJson(route('lawfirm.escavador.certificados.destroy', 9999))->assertStatus(401);
        $this->get(route('lawfirm.escavador.certificados.index'))->assertStatus(401);
    }
}
