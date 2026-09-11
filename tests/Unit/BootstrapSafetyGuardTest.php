<?php

declare(strict_types=1);

/**
 * BootstrapSafetyGuardTest (SEC-GUARD-002)
 *
 * Teste negativo comprovando que a trava global no Pest.php bloqueia
 * execuções de testes Feature quando o ambiente aponta para produção,
 * antes mesmo de estabelecer qualquer conexão com o banco ou
 * executar o framework.
 *
 * @catalog  SEC-GUARD-002 — tenant-isolation
 */
describe('BootstrapSafetyGuard — Trava Global de Pest', function () {

    it('bloquearia AuthenticationTest se DB_HOST fosse de produção', function () {
        // Simulamos o estado das variáveis de ambiente de produção que
        // foram usadas inadvertidamente na Etapa 2

        $originalAck = getenv('TEST_ENVIRONMENT_ACK');
        $originalHost = getenv('DB_HOST');
        $originalDb = getenv('DB_DATABASE');

        putenv('TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST'); // Assumimos que o sentinel estivesse correto
        putenv('DB_HOST=75.119.128.13'); // O host remoto da produção
        putenv('DB_DATABASE=advdf2g'); // O banco da produção

        try {
            // Executamos a função definida no tests/Pest.php para avaliar
            // as variáveis do sistema operacional antes do bootstrap do Laravel
            $error = _bootstrapIsolationCheck();

            expect($error)->not->toBeNull()
                ->toContain('POSSÍVEL CONEXÃO COM PRODUÇÃO — SUÍTE BLOQUEADA')
                ->toContain('75.119.128.13')
                ->toContain('não está na allowlist');

        } finally {
            // Restaurar ambiente
            $originalAck !== false ? putenv("TEST_ENVIRONMENT_ACK={$originalAck}") : putenv('TEST_ENVIRONMENT_ACK');
            $originalHost !== false ? putenv("DB_HOST={$originalHost}") : putenv('DB_HOST');
            $originalDb !== false ? putenv("DB_DATABASE={$originalDb}") : putenv('DB_DATABASE');
        }
    });

    it('bloquearia AuthenticationTest se DB_DATABASE não tiver sufixo _test', function () {
        $originalAck = getenv('TEST_ENVIRONMENT_ACK');
        $originalHost = getenv('DB_HOST');
        $originalDb = getenv('DB_DATABASE');

        putenv('TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST');
        putenv('DB_HOST=mysql-test'); // Host correto
        putenv('DB_DATABASE=advdf2g'); // Mas banco sem o sufixo _test

        try {
            $error = _bootstrapIsolationCheck();

            expect($error)->not->toBeNull()
                ->toContain("DB_DATABASE 'advdf2g' não termina com '_test'")
                ->toContain('POSSÍVEL BANCO DE PRODUÇÃO — SUÍTE BLOQUEADA');

        } finally {
            $originalAck !== false ? putenv("TEST_ENVIRONMENT_ACK={$originalAck}") : putenv('TEST_ENVIRONMENT_ACK');
            $originalHost !== false ? putenv("DB_HOST={$originalHost}") : putenv('DB_HOST');
            $originalDb !== false ? putenv("DB_DATABASE={$originalDb}") : putenv('DB_DATABASE');
        }
    });

});
