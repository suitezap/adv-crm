<?php

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Webkul\User\Models\User;

/*
|--------------------------------------------------------------------------
| BootstrapSafetyGuard — Trava Global de Isolamento
|--------------------------------------------------------------------------
|
| EXECUTADO ANTES DE QUALQUER TESTE Feature, Security ou de integração.
|
| Esta trava bloqueia a suíte inteira quando:
|   1. TEST_ENVIRONMENT_ACK não for 'LAW_FIRM_ISOLATED_TEST'
|   2. DB_HOST da conexão padrão estiver fora da allowlist de hosts de teste
|   3. DB_DATABASE não terminar em '_test'
|
| O bloqueio é aplicado GLOBALMENTE no beforeAll() dos grupos Feature e
| Security — independe de herança manual de MultiDatabaseTestCase.
|
| Para testes Unit puros (sem container Laravel), o bloqueio não se aplica
| pois não há acesso a banco de dados.
|
| CRITÉRIOS DE DESBLOQUEIO:
|   - Copiar .env.testing.example → .env.testing
|   - php artisan key:generate --env=testing
|   - Executar dentro do contêiner php-tests via run-backend-tests.sh
|
 */

/**
 * Verifica as condições de isolamento.
 * Retorna null se seguro, string descritiva do problema se inseguro.
 */
function _bootstrapIsolationCheck(): ?string
{
    // 1. APP_ENV exato
    $appEnv = getenv('APP_ENV');
    if ($appEnv !== 'testing') {
        return "BootstrapSafetyGuard: APP_ENV inválido. Esperado 'testing', recebido '{$appEnv}'.";
    }

    // 2. Sentinel Exato e Contexto de Contêiner (garantido por docker-compose)
    $sentinelExpected = 'LAW_FIRM_ISOLATED_TEST';
    $sentinelActual   = getenv('TEST_ENVIRONMENT_ACK');
    if ($sentinelActual === false) $sentinelActual = null;

    if ($sentinelActual !== $sentinelExpected) {
        return "BootstrapSafetyGuard: TEST_ENVIRONMENT_ACK inválido ou ausente. " .
               "Esperado: '{$sentinelExpected}', recebido: '" . ($sentinelActual ?? 'null') . "'. " .
               "Execute SOMENTE via run-backend-tests.sh dentro do contêiner php-tests.";
    }

    // 3. Hosts permitidos (Allowlist)
    $allowedHosts = ['127.0.0.1', 'localhost', 'mysql-test', 'mothership-db-test'];
    
    // Verificar hosts ativos no ambiente
    $hostsToCheck = [
        getenv('DB_HOST') ?: null,
        getenv('DB_TEST_TENANT_A_HOST') ?: null,
        getenv('DB_TEST_TENANT_B_HOST') ?: null,
        getenv('DB_MOTHERSHIP_HOST') ?: null,
    ];

    foreach (array_filter($hostsToCheck) as $dbHost) {
        if (! in_array($dbHost, $allowedHosts, true)) {
            return "BootstrapSafetyGuard: HOST '{$dbHost}' não está na allowlist de hosts de teste. " .
                   "Hosts permitidos: " . implode(', ', $allowedHosts) . ". " .
                   "POSSÍVEL CONEXÃO COM PRODUÇÃO — SUÍTE BLOQUEADA.";
        }
    }

    // 4. Verificar sufixos dos bancos (_test)
    $dbsToCheck = [
        getenv('DB_DATABASE') ?: null,
        getenv('DB_TEST_TENANT_A_DATABASE') ?: null,
        getenv('DB_TEST_TENANT_B_DATABASE') ?: null,
        getenv('DB_MOTHERSHIP_DATABASE') ?: null,
    ];

    foreach (array_filter($dbsToCheck) as $dbName) {
        if (! str_ends_with($dbName, '_test')) {
            return "BootstrapSafetyGuard: DB_DATABASE '{$dbName}' não termina com '_test'. " .
                   "POSSÍVEL BANCO DE PRODUÇÃO — SUÍTE BLOQUEADA.";
        }
    }

    return null;
}

// ─── Aplicação Global: Feature ────────────────────────────────────────────────
// beforeAll() em Feature: bloqueia ANTES do primeiro teste do grupo
uses(\Tests\MultiDatabaseTestCase::class)
    ->beforeAll(function () {
        $error = _bootstrapIsolationCheck();
        if ($error !== null) {
            throw new \RuntimeException($error);
        }
    })
    ->beforeEach(function () {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    })
    ->in('Feature');

// ─── Aplicação Global: Security ───────────────────────────────────────────────
uses(\Tests\MultiDatabaseTestCase::class)
    ->beforeAll(function () {
        $error = _bootstrapIsolationCheck();
        if ($error !== null) {
            throw new \RuntimeException($error);
        }
    })
    ->beforeEach(function () {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    })
    ->in('Security');

// Unit tests run without the full Laravel application — pure Mockery
// A trava não é aplicada a Unit pois tests puros não acessam banco.
uses()->afterEach(fn () => \Mockery::close())->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
 */

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
 */

/**
 * Get default admin which is created on fresh instance.
 *
 * @return User
 */
function getDefaultAdmin()
{
    $admin = User::find(1);

    return $admin;
}

/**
 * Sanctum authenticated admin.
 *
 * @return User
 */
function actingAsSanctumAuthenticatedAdmin()
{
    return Sanctum::actingAs(
        getDefaultAdmin(),
        ['*']
    );
}

/**
 * Get first name.
 *
 * @param  string  $fullName
 * @return string
 */
function getFirstName($fullName)
{
    return explode(' ', $fullName)[0];
}
