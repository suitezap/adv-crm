<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MultiDatabaseTestCase;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * PlatformPermissionsTest — PRIV-AUDIT-001 Ondas 2-3 (PLAT-SEC-002)
 *
 * Gates de perfil em SaaS, AI, modelos, Whatsapp e portal público.
 */
class PlatformPermissionsTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private function makeUser(array $permissions): User
    {
        $role = Role::create([
            'name'            => 'Papel Plat '.uniqid(),
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

    public function test_saas_billing_and_checkout_require_manage(): void
    {
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        // PLAT-SEC-002: assinatura, cobrança e créditos sem manage → 401
        $this->get(route('admin.lawfirm.saas.index'))->assertStatus(401);
        $this->get(route('admin.lawfirm.saas.billing-info.index'))->assertStatus(401);
        $this->postJson(route('admin.lawfirm.saas.checkout.plan'), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.saas.checkout.credits'), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.saas.billing-info.store'), [])->assertStatus(401);
        $this->get(route('admin.lawfirm.saas.orders.index'))->assertStatus(401);
    }

    public function test_ai_execute_and_triagem_require_permissions(): void
    {
        $user = $this->makeUser(['lawfirm.assistants.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        $lead = \Webkul\Lead\Models\Lead::create([
            'title' => 'Lead Triagem Teste', 'user_id' => $user->id,
            'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1,
        ]);

        // Leitura ok; execução e triagem bloqueadas sem execute
        $this->get(route('lawfirm.assistants.index'))->assertOk();
        $this->postJson(route('lawfirm.assistants.execute', 'x'), [])->assertStatus(401);
        $this->postJson(route('lawfirm.assistants.generate', 'x'), [])->assertStatus(401);
        $this->get(route('lawfirm.assistants.triagem.get', $lead->id))->assertOk();
        $this->postJson(route('lawfirm.assistants.triagem.save', $lead->id), [])->assertStatus(401);
        $this->postJson(route('lawfirm.assistants.process'), [])->assertStatus(401);
    }

    public function test_modelos_and_whatsapp_require_permissions(): void
    {
        $user = $this->makeUser(['dashboard.view']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user, 'user');

        $this->get(route('admin.lawfirm.whatsapp.index'))->assertStatus(401);
        $this->postJson(route('admin.lawfirm.whatsapp.disconnect'), [])->assertStatus(401);
        $this->postJson(route('admin.lawfirm.whatsapp.test'), [])->assertStatus(401);
    }

    public function test_portal_rejects_invalid_and_expired_tokens(): void
    {
        // Token inválido → 403; expirado → 403
        $this->get(route('lawfirm.public.portal.index', ['id' => 1, 'token' => 'forjado']))
            ->assertStatus(403);

        $expired = 'exp.'.(time() - 10).'.'.hash_hmac('sha256', '1|'.(time() - 10), config('app.key'));
        $this->get(route('lawfirm.public.portal.index', ['id' => 1, 'token' => $expired]))
            ->assertStatus(403);
    }
}
