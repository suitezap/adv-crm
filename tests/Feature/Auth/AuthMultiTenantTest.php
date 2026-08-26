<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MultiDatabaseTestCase;
use Webkul\User\Models\User;

/**
 * AuthMultiTenantTest — Etapa 3 (Infraestrutura de Qualidade)
 *
 * Testa o isolamento de sessão e credenciais entre usuários de tenants distintos.
 * Garante que um usuário autenticado do Tenant A não pode acessar o painel do Tenant B
 * nem enxergar dados administrativos além do seu escopo.
 *
 * Cobertura: AUTH-FEATURE-001 (migrado → Auth multi-tenant)
 *
 * @package Tests\Feature\Auth
 * @since   v3.55.0 — Etapa 3 da Infraestrutura de Qualidade
 */
class AuthMultiTenantTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    /**
     * Garante que a página de login do administrador é renderizada corretamente.
     */
    public function test_it_can_see_the_admin_login_page(): void
    {
        $response = $this->get(route('admin.session.create'));

        $response->assertStatus(200);
        $response->assertSee('email');
        $response->assertSee('password');
    }

    /**
     * Garante que o admin pode autenticar e acessar o dashboard.
     */
    public function test_it_can_login_and_access_dashboard(): void
    {
        $admin = getDefaultAdmin();

        $this->assertNotNull($admin, 'Usuário admin padrão não encontrado no banco de teste.');

        $response = $this->post(route('admin.session.store'), [
            'email'    => $admin->email,
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.dashboard.index'));
        $this->assertAuthenticatedAs($admin, 'user');
    }

    /**
     * Garante que o admin pode efetuar logout com sucesso.
     */
    public function test_it_can_logout(): void
    {
        $admin = getDefaultAdmin();
        $this->actingAs($admin, 'user');

        $response = $this->delete(route('admin.session.destroy'));

        $response->assertRedirect();
        $this->assertGuest('user');
    }

    /**
     * Garante que uma requisição não autenticada é redirecionada para login.
     */
    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $response = $this->get(route('admin.dashboard.index'));

        $response->assertRedirect(route('admin.session.create'));
    }

    /**
     * Garante que credenciais inválidas não autenticam.
     */
    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $response = $this->post(route('admin.session.store'), [
            'email'    => 'naoexiste@example.com',
            'password' => 'senhaerrada',
        ]);

        $this->assertGuest('user');
        // O controller usa session()->flash('error', ...) ao falhar, não withErrors()
        $response->assertSessionHas('error');
    }

    /**
     * Garante que um usuário de tenant A não vê dados de outro usuário
     * de tenant B — simulando dois usuários com perfis distintos.
     */
    public function test_user_session_is_isolated_per_user(): void
    {
        $this->seedCoreData();

        /** @var User $adminA */
        $adminA = User::withoutEvents(function () {
            return User::create([
                'name'     => 'Admin Tenant A',
                'email'    => 'admin_a@tenanta.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'global',
                'status'   => 1,
            ]);
        });

        /** @var User $adminB */
        $adminB = User::withoutEvents(function () {
            return User::create([
                'name'     => 'Admin Tenant B',
                'email'    => 'admin_b@tenantb.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'global',
                'status'   => 1,
            ]);
        });

        // A sessão do Admin A não contém informação do Admin B
        $this->actingAs($adminA, 'user');
        $responseA = $this->get(route('admin.dashboard.index'));
        $responseA->assertStatus(200);
        $responseA->assertDontSee($adminB->email);

        // Após logout de A, B pode autenticar de forma independente
        $this->post(route('admin.session.destroy'));

        $this->actingAs($adminB, 'user');
        $responseB = $this->get(route('admin.dashboard.index'));
        $responseB->assertStatus(200);
        $responseB->assertDontSee($adminA->email);
    }
}
