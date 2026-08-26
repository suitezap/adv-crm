<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * DatabaseSafetyGuard
 *
 * Trava de segurança multi-tenant para o ambiente de testes.
 *
 * OBJETIVO: Garantir que testes NUNCA acessem bancos de produção.
 *
 * Executa ANTES de qualquer conexão ou operação de banco.
 * Aborta com RuntimeException imediata caso qualquer regra seja violada.
 *
 * Regras aplicadas:
 *  1. app()->environment('testing') === true
 *  2. TEST_ENVIRONMENT_ACK === 'LAW_FIRM_ISOLATED_TEST'
 *  3. Conexões tenant_a, tenant_b e mothership devem estar configuradas
 *  4. Todos os nomes de banco devem terminar estritamente com '_test'
 *  5. Hosts permitidos apenas via allowlist (127.0.0.1, localhost,
 *     mysql-test, mothership-db-test)
 *  6. Tenant IDs permitidos: tenant_a, tenant_b, 1, 2
 *
 * API pública:
 *  - assertSafe(): ponto de entrada principal (usa config() do Laravel)
 *  - assertSentinelToken(): valida TEST_ENVIRONMENT_ACK via getenv()
 *  - assertTenantIdAllowed(string $tenantId): valida tenant ID
 *  - assertDatabaseSuffixForConnections(array $connections): valida sufixo _test
 *  - assertHostsForConnections(array $connections): valida hosts via allowlist
 *
 * @package Tests\Support
 * @since   v3.55.0 — Etapa 2 da Infraestrutura de Qualidade
 */
class DatabaseSafetyGuard
{
    /**
     * Allowlist de hosts de banco de dados permitidos em testes.
     * Qualquer host fora desta lista é considerado potencialmente produção.
     */
    public const ALLOWED_HOSTS = [
        '127.0.0.1',
        'localhost',
        'mysql-test',
        'mothership-db-test',
    ];

    /**
     * Allowlist de tenant IDs válidos em testes.
     */
    public const ALLOWED_TENANT_IDS = [
        'tenant_a',
        'tenant_b',
        '1',
        '2',
    ];

    /**
     * Conexões de banco que devem estar presentes e configuradas.
     */
    public const REQUIRED_CONNECTIONS = [
        'tenant_a',
        'tenant_b',
        'mothership',
    ];

    /**
     * Sufixo obrigatório para todos os nomes de banco de dados.
     */
    public const REQUIRED_DB_SUFFIX = '_test';

    /**
     * Ponto de entrada principal. Chame este método antes de qualquer
     * operação de banco em testes com o container Laravel ativo.
     *
     * @throws \RuntimeException se qualquer regra de isolamento for violada
     */
    public static function assertSafe(): void
    {
        static::assertTestEnvironment();
        static::assertSentinelToken();
        static::assertConnectionsConfigured();

        // Lê as conexões via config() do Laravel
        $connections = config('database.connections', []);
        $mapped = [];
        foreach (static::REQUIRED_CONNECTIONS as $conn) {
            if (isset($connections[$conn])) {
                $mapped[$conn] = $connections[$conn];
            }
        }

        static::assertDatabaseSuffixForConnections($mapped);
        static::assertHostsForConnections($mapped);
    }

    /**
     * Garante que APP_ENV=testing (requer container Laravel ativo).
     *
     * @throws \RuntimeException
     */
    public static function assertTestEnvironment(): void
    {
        $env = app()->environment();

        if ($env !== 'testing') {
            throw new \RuntimeException(
                "DatabaseSafetyGuard: APP_ENV deve ser 'testing', mas é '{$env}'. " .
                'Abortando para proteger dados de produção.'
            );
        }
    }

    /**
     * Garante que TEST_ENVIRONMENT_ACK está definido e correto.
     * Usa getenv() diretamente para funcionar tanto com quanto sem
     * o container Laravel bootstrapped.
     *
     * @throws \RuntimeException
     */
    public static function assertSentinelToken(): void
    {
        $ack = getenv('TEST_ENVIRONMENT_ACK');
        if ($ack === false) {
            $ack = null;
        }
        $expected = 'LAW_FIRM_ISOLATED_TEST';

        if ($ack !== $expected) {
            throw new \RuntimeException(
                "DatabaseSafetyGuard: TEST_ENVIRONMENT_ACK inválido ou ausente. " .
                "Esperado: '{$expected}', recebido: '" . ($ack ?? 'null') . "'. " .
                'Copie .env.testing.example para .env.testing e execute ' .
                'php artisan key:generate --env=testing'
            );
        }
    }

    /**
     * Garante que todas as conexões obrigatórias estão configuradas.
     * Requer container Laravel ativo (usa config()).
     *
     * @throws \RuntimeException
     */
    public static function assertConnectionsConfigured(): void
    {
        $connections = config('database.connections', []);

        foreach (static::REQUIRED_CONNECTIONS as $connection) {
            if (empty($connections[$connection])) {
                throw new \RuntimeException(
                    "DatabaseSafetyGuard: Conexão de banco '{$connection}' não está configurada. " .
                    'O MultiTenantTestBootstrapper deve ser chamado antes.'
                );
            }
        }
    }

    /**
     * Valida que todos os nomes de banco nas conexões fornecidas
     * terminam com '_test'.
     *
     * Aceita um array de conexões no formato:
     *   ['connection_name' => ['database' => '...', 'host' => '...'], ...]
     *
     * @param  array<string, array<string, mixed>>  $connections
     * @throws \RuntimeException
     */
    public static function assertDatabaseSuffixForConnections(array $connections): void
    {
        foreach ($connections as $connectionName => $config) {
            $dbName = $config['database'] ?? null;

            if ($dbName === null) {
                continue;
            }

            if (! str_ends_with((string) $dbName, static::REQUIRED_DB_SUFFIX)) {
                throw new \RuntimeException(
                    "DatabaseSafetyGuard: O banco '{$dbName}' da conexão '{$connectionName}' " .
                    "não termina em '_test'. " .
                    'ACESSO A BANCO DE PRODUÇÃO BLOQUEADO.'
                );
            }
        }
    }

    /**
     * Valida que todos os hosts nas conexões fornecidas estão na allowlist.
     *
     * @param  array<string, array<string, mixed>>  $connections
     * @throws \RuntimeException
     */
    public static function assertHostsForConnections(array $connections): void
    {
        foreach ($connections as $connectionName => $config) {
            $host = $config['host'] ?? null;

            if ($host === null) {
                continue;
            }

            if (! in_array((string) $host, static::ALLOWED_HOSTS, true)) {
                throw new \RuntimeException(
                    "DatabaseSafetyGuard: Host '{$host}' da conexão '{$connectionName}' " .
                    'não está na allowlist de hosts de teste. ' .
                    'Hosts permitidos: ' . implode(', ', static::ALLOWED_HOSTS) . '. ' .
                    'POSSÍVEL CONEXÃO COM PRODUÇÃO — ABORTANDO.'
                );
            }
        }
    }

    /**
     * Garante que o tenant ID passado está na allowlist de IDs de teste.
     *
     * @throws \RuntimeException
     */
    public static function assertTenantIdAllowed(string $tenantId): void
    {
        if (! in_array($tenantId, static::ALLOWED_TENANT_IDS, true)) {
            throw new \RuntimeException(
                "DatabaseSafetyGuard: Tenant ID '{$tenantId}' não está na allowlist " .
                'de tenants de teste. ' .
                'IDs permitidos: ' . implode(', ', static::ALLOWED_TENANT_IDS)
            );
        }
    }
}
