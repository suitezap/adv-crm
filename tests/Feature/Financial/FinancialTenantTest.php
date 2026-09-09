<?php

declare(strict_types=1);

namespace Tests\Feature\Financial;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\Legal\Models\Processo;
use Tests\MultiDatabaseTestCase;
use Webkul\Contact\Models\Person;
use Webkul\User\Models\User;

/**
 * FinancialTenantTest — FIN-COBRANCAS-001 (FIN-FEATURE-001/002)
 *
 * Prova de isolamento tenant_id em law_financials (mysql compartilhado).
 */
class FinancialTenantTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private function makeProcesso(User $user, Person $person): Processo
    {
        return Processo::withoutEvents(function () use ($user, $person) {
            return Processo::create([
                'titulo'    => 'Processo Financeiro Teste',
                'status'    => 'Ativo',
                'user_id'   => $user->id,
                'person_id' => $person->id,
            ]);
        });
    }

    public function test_financial_applies_tenant_scope_and_autofills_tenant_id(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);

        $user = User::withoutEvents(fn () => User::create([
            'name' => 'Fin A', 'email' => 'fin_a@tenant.test',
            'password' => bcrypt('password'), 'role_id' => 1, 'status' => 1,
        ]));
        $person = Person::create(['name' => 'Person Fin A', 'emails' => [['value' => 'fina@test.com', 'label' => 'work']]]);
        $processo = $this->makeProcesso($user, $person);

        // FIN-FEATURE-001: criação preenche tenant_id automaticamente
        $financial = Financial::create([
            'processo_id'     => $processo->id,
            'tipo'            => 'receita',
            'nome'            => 'Honorários',
            'valor'           => 1500.00,
            'data_vencimento' => now()->addDays(10)->toDateString(),
            'status'          => 'pendente',
        ]);

        $this->assertEquals('tenant-a', $financial->tenant_id);

        // Escopo global presente na query
        $this->assertStringContainsString('tenant_id', Financial::query()->toSql());

        // FIN-FEATURE-002: quick-pay simulado respeita escopo (update via model escopado)
        $financial->update(['status' => 'pago', 'payment_date' => now()->toDateString(), 'payment_method' => 'pix']);
        $this->assertEquals('pago', $financial->fresh()->status);
    }

    public function test_tenant_b_cannot_see_financial_of_tenant_a(): void
    {
        config(['lawfirm.tenant_id' => 'tenant-a']);

        $user = User::withoutEvents(fn () => User::create([
            'name' => 'Fin A2', 'email' => 'fin_a2@tenant.test',
            'password' => bcrypt('password'), 'role_id' => 1, 'status' => 1,
        ]));
        $person = Person::create(['name' => 'Person Fin A2', 'emails' => [['value' => 'fina2@test.com', 'label' => 'work']]]);
        $processo = $this->makeProcesso($user, $person);

        $financial = Financial::create([
            'processo_id'     => $processo->id,
            'tipo'            => 'receita',
            'nome'            => 'Honorários Sigilosos',
            'valor'           => 999.00,
            'data_vencimento' => now()->addDays(5)->toDateString(),
            'status'          => 'pendente',
        ]);

        // Troca para tenant B: registro de A deve ser invisível (TENANT-SEC-006 / FIN-FEATURE-001)
        config(['lawfirm.tenant_id' => 'tenant-b']);

        $this->assertNull(Financial::where('id', $financial->id)->first());
        $this->assertEquals(0, Financial::where('id', $financial->id)->count());

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Financial::findOrFail($financial->id);
    }
}
