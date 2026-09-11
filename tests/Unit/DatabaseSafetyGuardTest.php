<?php

declare(strict_types=1);

use Tests\Support\DatabaseSafetyGuard;

/**
 * DatabaseSafetyGuardTest (SEC-GUARD-001)
 *
 * Testes negativos da trava de segurança multi-tenant.
 *
 * OBJETIVO: Provar que o DatabaseSafetyGuard BLOQUEIA corretamente
 * acessos em condições inseguras. Cada teste deve lançar RuntimeException.
 *
 * Estes testes exercitam diretamente os métodos que recebem arrays de config
 * como parâmetro (para compatibilidade com contexto puro Unit sem container).
 *
 * @catalog  SEC-GUARD-001 — tenant-isolation — P0
 *
 * @since    v3.55.0 — Etapa 2 da Infraestrutura de Qualidade
 */
describe('DatabaseSafetyGuard — Testes Negativos (SEC-GUARD-001)', function () {

    // ─────────────────────────────────────────────────────────
    // 1. Validação do Sentinel TEST_ENVIRONMENT_ACK
    // ─────────────────────────────────────────────────────────

    it('aborta se TEST_ENVIRONMENT_ACK estiver ausente', function () {
        $originalAck = getenv('TEST_ENVIRONMENT_ACK');
        putenv('TEST_ENVIRONMENT_ACK=');

        try {
            DatabaseSafetyGuard::assertSentinelToken();
        } finally {
            if ($originalAck !== false) {
                putenv("TEST_ENVIRONMENT_ACK={$originalAck}");
            } else {
                putenv('TEST_ENVIRONMENT_ACK');
            }
        }

    })->throws(RuntimeException::class, 'TEST_ENVIRONMENT_ACK inválido ou ausente');

    it('aborta se TEST_ENVIRONMENT_ACK tiver valor incorreto', function () {
        $originalAck = getenv('TEST_ENVIRONMENT_ACK');
        putenv('TEST_ENVIRONMENT_ACK=WRONG_VALUE_THAT_WILL_FAIL');

        try {
            DatabaseSafetyGuard::assertSentinelToken();
        } finally {
            if ($originalAck !== false) {
                putenv("TEST_ENVIRONMENT_ACK={$originalAck}");
            } else {
                putenv('TEST_ENVIRONMENT_ACK');
            }
        }

    })->throws(RuntimeException::class, 'TEST_ENVIRONMENT_ACK inválido ou ausente');

    // ─────────────────────────────────────────────────────────
    // 2. Validação de sufixo _test no nome do banco
    // ─────────────────────────────────────────────────────────

    it('aborta se o banco tenant_a não terminar com _test', function () {
        DatabaseSafetyGuard::assertDatabaseSuffixForConnections([
            'tenant_a'   => ['database' => 'advdf2g', 'host' => 'mysql-test'],
            'tenant_b'   => ['database' => 'tenant_b_test', 'host' => 'mysql-test'],
            'mothership' => ['database' => 'mothership_test', 'host' => 'mothership-db-test'],
        ]);

    })->throws(RuntimeException::class, "não termina em '_test'");

    it('aborta se o banco mothership não terminar com _test', function () {
        DatabaseSafetyGuard::assertDatabaseSuffixForConnections([
            'tenant_a'   => ['database' => 'tenant_a_test', 'host' => 'mysql-test'],
            'tenant_b'   => ['database' => 'tenant_b_test', 'host' => 'mysql-test'],
            'mothership' => ['database' => 'mothership_db', 'host' => 'mothership-db-test'],
        ]);

    })->throws(RuntimeException::class, "não termina em '_test'");

    // ─────────────────────────────────────────────────────────
    // 3. Validação de host fora da allowlist
    // ─────────────────────────────────────────────────────────

    it('aborta se o host for um IP de produção remoto', function () {
        DatabaseSafetyGuard::assertHostsForConnections([
            'tenant_a'   => ['host' => '75.119.128.13', 'database' => 'tenant_a_test'],
            'tenant_b'   => ['host' => 'mysql-test', 'database' => 'tenant_b_test'],
            'mothership' => ['host' => 'mothership-db-test', 'database' => 'mothership_test'],
        ]);

    })->throws(RuntimeException::class, 'POSSÍVEL CONEXÃO COM PRODUÇÃO — ABORTANDO');

    it('aborta se o host for um domínio de produção externo', function () {
        DatabaseSafetyGuard::assertHostsForConnections([
            'tenant_a'   => ['host' => 'db.suitezap.com.br', 'database' => 'tenant_a_test'],
            'tenant_b'   => ['host' => 'mysql-test', 'database' => 'tenant_b_test'],
            'mothership' => ['host' => 'mothership-db-test', 'database' => 'mothership_test'],
        ]);

    })->throws(RuntimeException::class, 'POSSÍVEL CONEXÃO COM PRODUÇÃO — ABORTANDO');

    // ─────────────────────────────────────────────────────────
    // 4. Validação de Tenant ID
    // ─────────────────────────────────────────────────────────

    it('aborta se o tenant ID de produção não estiver na allowlist', function () {
        DatabaseSafetyGuard::assertTenantIdAllowed('advdf2g');

    })->throws(RuntimeException::class, 'não está na allowlist de tenants de teste');

    it('aborta se o tenant ID vazio não estiver na allowlist', function () {
        DatabaseSafetyGuard::assertTenantIdAllowed('');

    })->throws(RuntimeException::class, 'não está na allowlist de tenants de teste');

    // ─────────────────────────────────────────────────────────
    // 5. Positivos: cenários válidos não lançam exceção
    // ─────────────────────────────────────────────────────────

    it('passa se todas as conexões tiverem bancos com sufixo _test', function () {
        // Não deve lançar exceção
        DatabaseSafetyGuard::assertDatabaseSuffixForConnections([
            'tenant_a'   => ['database' => 'tenant_a_test', 'host' => 'mysql-test'],
            'tenant_b'   => ['database' => 'tenant_b_test', 'host' => 'mysql-test'],
            'mothership' => ['database' => 'mothership_test', 'host' => 'mothership-db-test'],
        ]);

        expect(true)->toBeTrue();
    });

    it('passa se todos os hosts estiverem na allowlist', function () {
        DatabaseSafetyGuard::assertHostsForConnections([
            'tenant_a'   => ['host' => 'mysql-test', 'database' => 'tenant_a_test'],
            'tenant_b'   => ['host' => '127.0.0.1', 'database' => 'tenant_b_test'],
            'mothership' => ['host' => 'mothership-db-test', 'database' => 'mothership_test'],
        ]);

        expect(true)->toBeTrue();
    });

    it('valida tenant IDs permitidos com sucesso', function () {
        DatabaseSafetyGuard::assertTenantIdAllowed('tenant_a');
        DatabaseSafetyGuard::assertTenantIdAllowed('tenant_b');
        DatabaseSafetyGuard::assertTenantIdAllowed('1');
        DatabaseSafetyGuard::assertTenantIdAllowed('2');

        expect(true)->toBeTrue();
    });

    it('valida que sentinel correto passa sem exceção', function () {
        $originalAck = getenv('TEST_ENVIRONMENT_ACK');
        putenv('TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST');

        try {
            DatabaseSafetyGuard::assertSentinelToken();
            expect(true)->toBeTrue();
        } finally {
            if ($originalAck !== false) {
                putenv("TEST_ENVIRONMENT_ACK={$originalAck}");
            } else {
                putenv('TEST_ENVIRONMENT_ACK');
            }
        }
    });

})->afterEach(fn () => Mockery::close());
