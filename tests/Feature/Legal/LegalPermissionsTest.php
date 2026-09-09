<?php

declare(strict_types=1);

namespace Tests\Feature\Legal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MultiDatabaseTestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * LegalPermissionsTest — PRIV-AUDIT-001 Onda 1e (LEGAL-SEC-001)
 *
 * Gates de perfil em Casos, Processos e GED: sem a permissão, 401.
 */
class LegalPermissionsTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private function makeUser(array $permissions): User
    {
        $role = Role::create([
            'name'            => 'Papel Legal '.uniqid(),
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

    public function test_casos_require_profile_permissions(): void
    {
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // LEGAL-SEC-001: leitura e escrita de casos sem permissão → 401
        $this->get(route('admin.lawfirm.casos.index'))->assertStatus(401);
        $this->get(route('admin.lawfirm.casos.show', 1))->assertStatus(401);
        $this->get(route('admin.lawfirm.casos.search'))->assertStatus(401);
        $this->postJson(route('admin.lawfirm.casos.store'), [])->assertStatus(401);
        $this->putJson(route('admin.lawfirm.casos.update', 1), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.casos.mass_delete'), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.casos.link_processo', 1), [])->assertStatus(401);
    }

    public function test_processos_require_profile_permissions(): void
    {
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        $this->get(route('admin.processos.index'))->assertStatus(401);
        $this->get(route('admin.processos.show', 1))->assertStatus(401);
        $this->get(route('admin.processos.search_person'))->assertStatus(401);
        $this->postJson(route('admin.processos.store'), [])->assertStatus(401);
        $this->putJson(route('admin.processos.update', 1), [])->assertStatus(401);
        $this->postJson(route('admin.processos.mass_delete'), [])->assertStatus(401);
    }

    public function test_ged_downloads_and_deletes_require_permissions(): void
    {
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // GED-SEC-001: downloads e exclusões sem permissão → 401 (antes: IDOR direto)
        $this->get(route('admin.lawfirm.ged.download', 1))->assertStatus(401);
        $this->get(route('admin.processos.download_attachment', 1))->assertStatus(401);
        $this->deleteJson(route('admin.lawfirm.ged.destroy', 1))->assertStatus(401);
        $this->deleteJson(route('admin.processos.delete_attachment', 1))->assertStatus(401);
        $this->get(route('lawfirm.documents.procuration', 1))->assertStatus(401);
        $this->get(route('lawfirm.documents.contract', 1))->assertStatus(401);
    }
}
