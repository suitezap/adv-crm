<?php

declare(strict_types=1);

namespace Tests\Feature\Escavador;

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
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
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
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($viewer, 'user');
        $this->postJson(route('lawfirm.escavador.monitoramentos.toggle_whatsapp', $mon->id))
            ->assertStatus(401);
    }
}
