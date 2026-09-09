<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MultiDatabaseTestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * AssistantSecurityTest — PRIV-AUDIT-001 Onda 1c/1d (AI-SEC-001)
 *
 * Histórico de IA escopado + SAC sem segredo hardcoded.
 */
class AssistantSecurityTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private function makeUser(array $permissions): User
    {
        $role = Role::create([
            'name'            => 'Papel AI '.uniqid(),
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

    public function test_history_requires_view_permission(): void
    {
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // AI-SEC-001: sem lawfirm.assistants.view → 401 (antes: IDOR total)
        $this->get(route('lawfirm.assistants.history.index'))->assertStatus(401);
        $this->get(route('lawfirm.assistants.history.show', 1))->assertStatus(401);
    }

    public function test_chatwoot_requires_permission_and_has_no_hardcoded_secret(): void
    {
        // AI-SEC-002: senha fora do código — regressão proibida
        $controller = file_get_contents(
            base_path('packages/SuiteZap/LawFirm/src/AI/Http/Controllers/Admin/AssistantController.php')
        );
        $this->assertStringNotContainsString('Eu&m2k2x', $controller);

        $blade = file_get_contents(
            base_path('packages/SuiteZap/LawFirm/src/Resources/views/admin/assistants/chatwoot.blade.php')
        );
        $this->assertStringNotContainsString('Eu&m2k2x', $blade);

        // Sem permissão do SAC → 401 antes de qualquer render
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');
        $this->get(route('lawfirm.assistants.chatwoot'))->assertStatus(401);
    }
}
