<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseSafetyGuard;
use Tests\Support\MultiTenantTestBootstrapper;

/**
 * MultiDatabaseTestCase
 *
 * Classe base abstrata para testes Feature e Security que precisam
 * de conexões multi-banco (tenant_a, tenant_b, mothership).
 *
 * Garante na ordem correta:
 *  1. Injeção dinâmica das conexões de teste via MultiTenantTestBootstrapper
 *  2. Validação de isolamento via DatabaseSafetyGuard::assertSafe()
 *  3. Preservação do ambiente para cada teste
 *
 * @since   v3.55.0 — Etapa 2 da Infraestrutura de Qualidade
 */
abstract class MultiDatabaseTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Executado antes de cada teste da classe.
     * Injeta conexões e verifica isolamento.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Injeta as conexões de teste no config do Laravel
        MultiTenantTestBootstrapper::boot();

        // 2. Valida isolamento — aborta imediatamente se qualquer
        //    regra de segurança for violada
        DatabaseSafetyGuard::assertSafe();

        $this->seedCoreData();
    }

    /**
     * Seed minimal core data required by FK constraints.
     *
     * Deve ser chamado em setUp() dos testes que precisam criar Users ou Leads.
     * O RefreshDatabase limpa roles e lead_pipelines — este método os recria
     * diretamente via DB::table() sem acionar seeders que destroem dados existentes.
     */
    protected function seedCoreData(): void
    {
        // Role id=1 (Administrator) — FK de users.role_id
        DB::table('roles')->insertOrIgnore([
            'id'              => 1,
            'name'            => 'Administrator',
            'description'     => 'Administrator role',
            'permission_type' => 'all',
            'permissions'     => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Lead Pipeline id=1 (Default) — FK de leads.lead_pipeline_id
        DB::table('lead_pipelines')->insertOrIgnore([
            'id'         => 1,
            'name'       => 'Default',
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lead Pipeline Stage id=1 (New) — FK de leads.lead_pipeline_stage_id
        DB::table('lead_pipeline_stages')->insertOrIgnore([
            'id'               => 1,
            'code'             => 'new',
            'name'             => 'New',
            'probability'      => 100,
            'sort_order'       => 1,
            'lead_pipeline_id' => 1,
        ]);

        // Default Admin (id=1)
        DB::table('users')->insertOrIgnore([
            'id'              => 1,
            'name'            => 'Example Admin',
            'email'           => 'admin@example.com',
            'password'        => bcrypt('admin123'),
            'role_id'         => 1,
            'view_permission' => 'global',
            'status'          => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
