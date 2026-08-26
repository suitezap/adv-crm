<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Config;

/**
 * MultiTenantTestBootstrapper
 *
 * Injeta dinamicamente as conexões de banco de dados de teste
 * (tenant_a, tenant_b, mothership) no config do Laravel em tempo
 * de execução de teste, sem alterar config/database.php de produção.
 *
 * As conexões são lidas das variáveis de ambiente DB_TEST_TENANT_A_*,
 * DB_TEST_TENANT_B_* e DB_TEST_MOTHERSHIP_* definidas em .env.testing.
 *
 * @since   v3.55.0 — Etapa 2 da Infraestrutura de Qualidade
 */
class MultiTenantTestBootstrapper
{
    /**
     * Injeta todas as conexões de teste na configuração do banco de dados.
     * Deve ser chamado antes de DatabaseSafetyGuard::assertSafe().
     */
    public static function boot(): void
    {
        static::injectTenantAConnection();
        static::injectTenantBConnection();
        static::injectMothershipConnection();
    }

    /**
     * Injeta a conexão tenant_a no config('database.connections').
     */
    private static function injectTenantAConnection(): void
    {
        Config::set('database.connections.tenant_a', [
            'driver'    => 'mysql',
            'host'      => env('DB_TEST_TENANT_A_HOST', 'mysql-test'),
            'port'      => env('DB_TEST_TENANT_A_PORT', '3306'),
            'database'  => env('DB_TEST_TENANT_A_DATABASE', 'tenant_a_test'),
            'username'  => env('DB_TEST_TENANT_A_USERNAME', 'test_user'),
            'password'  => env('DB_TEST_TENANT_A_PASSWORD', 'test_password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ]);
    }

    /**
     * Injeta a conexão tenant_b no config('database.connections').
     */
    private static function injectTenantBConnection(): void
    {
        Config::set('database.connections.tenant_b', [
            'driver'    => 'mysql',
            'host'      => env('DB_TEST_TENANT_B_HOST', 'mysql-test'),
            'port'      => env('DB_TEST_TENANT_B_PORT', '3306'),
            'database'  => env('DB_TEST_TENANT_B_DATABASE', 'tenant_b_test'),
            'username'  => env('DB_TEST_TENANT_B_USERNAME', 'test_user'),
            'password'  => env('DB_TEST_TENANT_B_PASSWORD', 'test_password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ]);
    }

    /**
     * Injeta a conexão mothership no config('database.connections').
     */
    private static function injectMothershipConnection(): void
    {
        Config::set('database.connections.mothership', [
            'driver'    => 'mysql',
            'host'      => env('DB_TEST_MOTHERSHIP_HOST', 'mothership-db-test'),
            'port'      => env('DB_TEST_MOTHERSHIP_PORT', '3306'),
            'database'  => env('DB_TEST_MOTHERSHIP_DATABASE', 'mothership_test'),
            'username'  => env('DB_TEST_MOTHERSHIP_USERNAME', 'test_user'),
            'password'  => env('DB_TEST_MOTHERSHIP_PASSWORD', 'test_password'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ]);
    }

    /**
     * Retorna a lista de nomes de conexões injetadas.
     *
     * @return string[]
     */
    public static function getConnectionNames(): array
    {
        return ['tenant_a', 'tenant_b', 'mothership'];
    }
}
