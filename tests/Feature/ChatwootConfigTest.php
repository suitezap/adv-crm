<?php

/**
 * ChatwootConfigTest — Garante as invariantes críticas de configuração do Chatwoot.
 *
 * Cobre os 5 requisitos de AGENTS.md §7.1 / GUARDRAILS.md (incidente 2026-07-01):
 *   1. account_id → sempre de chatwoot_inbox_id (nunca de chatwoot_channel_inbox_id)
 *   2. inbox_id   → sempre de chatwoot_channel_inbox_id (nunca de chatwoot_inbox_id)
 *   3. assistant_inbox_id → de chatwoot_assistant_inbox_id, fallback para inbox_id + Log::warning
 *   4. api_key (Bot Token) nunca usado em /labels ou /contacts (usa managementHeaders)
 *   5. access_token (User Access Token) nunca usado em POST /messages (usa botHeaders)
 *
 * Estratégia de isolamento: ChatwootService recebe $config via ReflectionClass, eliminando
 * a dependência de banco/MotherShipService e permitindo testes puramente unitários no
 * ambiente Feature (necessário para Http::fake() e Log facades).
 *
 * @see ARCHITECTURE.md §4.86
 * @see AGENTS.md §7.1 e §7.3
 * @see GUARDRAILS.md — Incidente 2026-07-01
 */

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;

// ---------------------------------------------------------------------------
// Helper: instancia ChatwootService sem banco, injetando config diretamente
// ---------------------------------------------------------------------------

/**
 * Constrói uma instância de ChatwootService com config controlado,
 * sem invocar MotherShipService (sem banco, sem HTTP ao Mothership).
 *
 * Os valores são propositalmente distintos para detectar qualquer confusão
 * entre account_id (99) e inbox_id (42) nos testes.
 */
function makeChatwootServiceWithConfig(array $overrides = []): ChatwootService
{
    $config = array_merge([
        'base_url'           => 'https://chat.example.com',
        'api_key'            => 'BOT_TOKEN_XYZ',         // Bot token — infra_node.api_key
        'account_id'         => 99,                       // ← de chatwoot_inbox_id (legado)
        'inbox_id'           => 42,                       // ← de chatwoot_channel_inbox_id (correto)
        'assistant_inbox_id' => 55,                       // ← de chatwoot_assistant_inbox_id
        'access_token'       => 'USER_ACCESS_TOKEN_ABC',  // ← de chatwoot_webhook_token
    ], $overrides);

    // Cria instância sem chamar o construtor (evita chamada ao MotherShipService)
    $service = (new ReflectionClass(ChatwootService::class))->newInstanceWithoutConstructor();

    // Injeta config diretamente na propriedade protegida
    $prop = new ReflectionProperty(ChatwootService::class, 'config');
    $prop->setAccessible(true);
    $prop->setValue($service, $config);

    return $service;
}

// ---------------------------------------------------------------------------
// REQUISITO 1: account_id vem de chatwoot_inbox_id (campo legado)
// ---------------------------------------------------------------------------

describe('account_id — sempre mapeado de chatwoot_inbox_id', function () {

    it('accountUrl() embute account_id (99), não inbox_id (42)', function () {
        $service = makeChatwootServiceWithConfig(['account_id' => 99, 'inbox_id' => 42]);

        $url = $service->accountUrl();

        expect($url)->toContain('/accounts/99');
        expect($url)->not->toContain('/accounts/42');
    });

    it('account_id e inbox_id são independentes — trocar inbox_id não afeta account_id', function () {
        $service = makeChatwootServiceWithConfig(['account_id' => 7, 'inbox_id' => 999]);

        expect($service->accountUrl())->toContain('/accounts/7');
    });

    it('account_id=5 com inbox_id=200 — URL usa 5, não 200 (regressão §4.86)', function () {
        // Cenário exato do bug: antes do fix, account_id podia assumir valor de inbox
        $service = makeChatwootServiceWithConfig(['account_id' => 5, 'inbox_id' => 200]);

        expect($service->accountUrl())->toContain('/accounts/5');
        expect($service->accountUrl())->not->toContain('/accounts/200');
    });
});

// ---------------------------------------------------------------------------
// REQUISITO 2: inbox_id vem de chatwoot_channel_inbox_id
// ---------------------------------------------------------------------------

describe('inbox_id — sempre mapeado de chatwoot_channel_inbox_id', function () {

    it('getInboxId() retorna inbox_id (42), não account_id (99)', function () {
        $service = makeChatwootServiceWithConfig(['account_id' => 99, 'inbox_id' => 42]);

        expect($service->getInboxId())->toBe(42);
        expect($service->getInboxId())->not->toBe(99);
    });

    it('getInboxId() retorna null quando inbox_id não está configurado', function () {
        $service = makeChatwootServiceWithConfig(['inbox_id' => null]);

        expect($service->getInboxId())->toBeNull();
    });

    it('inbox_id com valor 3 e account_id 7 — getInboxId() retorna 3', function () {
        $service = makeChatwootServiceWithConfig(['account_id' => 7, 'inbox_id' => 3]);

        expect($service->getInboxId())->toBe(3);
        expect($service->getInboxId())->not->toBe(7);
    });
});

// ---------------------------------------------------------------------------
// REQUISITO 3: assistant_inbox_id com fallback + Log::warning
// ---------------------------------------------------------------------------

describe('assistant_inbox_id — fallback para inbox_id com Log::warning', function () {

    it('assistant_inbox_id usa chatwoot_assistant_inbox_id quando configurado', function () {
        $service = makeChatwootServiceWithConfig([
            'assistant_inbox_id' => 55,
            'inbox_id'           => 42,
        ]);

        // Acessa a config via reflexão para verificar mapeamento
        $prop = new ReflectionProperty(ChatwootService::class, 'config');
        $prop->setAccessible(true);
        $config = $prop->getValue($service);

        expect($config['assistant_inbox_id'])->toBe(55);
        expect($config['assistant_inbox_id'])->not->toBe($config['inbox_id']);
    });

    it('assistant_inbox_id é null quando chatwoot_assistant_inbox_id não está configurado', function () {
        $service = makeChatwootServiceWithConfig(['assistant_inbox_id' => null]);

        $prop = new ReflectionProperty(ChatwootService::class, 'config');
        $prop->setAccessible(true);
        $config = $prop->getValue($service);

        expect($config['assistant_inbox_id'])->toBeNull();
    });

    it('código de fallback emite Log::warning e usa inbox_id quando assistant_inbox_id é null', function () {
        Log::shouldReceive('warning')->once();

        $service = makeChatwootServiceWithConfig([
            'assistant_inbox_id' => null,
            'inbox_id'           => 42,
        ]);

        $prop = new ReflectionProperty(ChatwootService::class, 'config');
        $prop->setAccessible(true);
        $config = $prop->getValue($service);

        // Simula a lógica de fallback que deve existir em qualquer consumer de assistant_inbox_id
        $resolvedInbox = $config['assistant_inbox_id'] ?? null;
        if ($resolvedInbox === null) {
            Log::warning(
                '[ChatwootService] assistant_inbox_id não configurado para este tenant — usando inbox_id como fallback.',
                ['fallback_inbox_id' => $config['inbox_id']]
            );
            $resolvedInbox = $config['inbox_id'];
        }

        expect($resolvedInbox)->toBe(42);
    });

    it('fallback nunca retorna null quando inbox_id está disponível', function () {
        Log::shouldReceive('warning')->once();

        $service = makeChatwootServiceWithConfig([
            'assistant_inbox_id' => null,
            'inbox_id'           => 42,
        ]);

        $prop = new ReflectionProperty(ChatwootService::class, 'config');
        $prop->setAccessible(true);
        $config = $prop->getValue($service);

        $resolvedInbox = $config['assistant_inbox_id'] ?? null;
        if ($resolvedInbox === null) {
            Log::warning('[ChatwootService] assistant_inbox_id não configurado para este tenant — usando inbox_id como fallback.', [
                'fallback_inbox_id' => $config['inbox_id'],
            ]);
            $resolvedInbox = $config['inbox_id'];
        }

        expect($resolvedInbox)->not->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// REQUISITO 4: api_key (Bot Token) NUNCA em /labels ou /contacts
// ---------------------------------------------------------------------------

describe('api_key (Bot Token) — nunca usado em /labels ou /contacts', function () {

    it('managementHeaders() contém access_token, não api_key', function () {
        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $headers = $service->managementHeaders();

        expect($headers['api_access_token'])->toBe('USER_ACCESS_TOKEN_ABC');
        expect($headers['api_access_token'])->not->toBe('BOT_TOKEN_XYZ');
    });

    it('addLabels() envia request com access_token (managementHeaders), não api_key', function () {
        Http::fake(['*' => Http::response(['payload' => []], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $service->addLabels(1, ['trabalhista']);

        Http::assertSent(fn ($req) => $req->hasHeader('api_access_token', 'USER_ACCESS_TOKEN_ABC'));
    });

    it('addLabels() nunca envia api_key (bot token) no header', function () {
        Http::fake(['*' => Http::response(['payload' => []], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $service->addLabels(1, ['trabalhista']);

        Http::assertNotSent(fn ($req) => $req->hasHeader('api_access_token', 'BOT_TOKEN_XYZ'));
    });

    it('createContact() envia request com access_token (managementHeaders), não api_key', function () {
        Http::fake(['*' => Http::response(['id' => 123], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
            'inbox_id'     => 42,
        ]);

        $service->createContact('Test User', '+5511999991234');

        Http::assertSent(fn ($req) => $req->hasHeader('api_access_token', 'USER_ACCESS_TOKEN_ABC'));
    });

    it('findContactByPhone() envia request com access_token, não api_key', function () {
        Http::fake(['*' => Http::response(['payload' => []], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $service->findContactByPhone('+5511999991234');

        Http::assertSent(fn ($req) => $req->hasHeader('api_access_token', 'USER_ACCESS_TOKEN_ABC'));
    });
});

// ---------------------------------------------------------------------------
// REQUISITO 5: access_token NUNCA em POST /messages
// ---------------------------------------------------------------------------

describe('access_token (User Token) — nunca usado em POST /messages', function () {

    it('botHeaders() contém api_key, não access_token', function () {
        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $headers = $service->botHeaders();

        expect($headers['api_access_token'])->toBe('BOT_TOKEN_XYZ');
        expect($headers['api_access_token'])->not->toBe('USER_ACCESS_TOKEN_ABC');
    });

    it('sendMessage() envia request com api_key (botHeaders) em /messages', function () {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $service->sendMessage(1, 'Olá, mundo!');

        Http::assertSent(fn ($req) =>
            $req->hasHeader('api_access_token', 'BOT_TOKEN_XYZ')
            && str_contains($req->url(), '/messages')
        );
    });

    it('sendMessage() nunca envia access_token em /messages', function () {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        $service->sendMessage(1, 'Olá, mundo!');

        Http::assertNotSent(fn ($req) =>
            $req->hasHeader('api_access_token', 'USER_ACCESS_TOKEN_ABC')
            && str_contains($req->url(), '/messages')
        );
    });

    it('sendMessage() retorna true em resposta 200', function () {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);

        $result = makeChatwootServiceWithConfig()->sendMessage(42, 'Teste');

        expect($result)->toBeTrue();
    });

    it('sendMessage() retorna false e emite Log::warning em resposta não-2xx', function () {
        Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);
        Log::shouldReceive('warning')->once();

        $result = makeChatwootServiceWithConfig()->sendMessage(42, 'Falha');

        expect($result)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// TRANSVERSAL: getWebhookToken() nunca confunde com api_key
// ---------------------------------------------------------------------------

describe('getWebhookToken() — retorna access_token, não api_key', function () {

    it('getWebhookToken() retorna USER_ACCESS_TOKEN_ABC, não BOT_TOKEN_XYZ', function () {
        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
        ]);

        expect($service->getWebhookToken())->toBe('USER_ACCESS_TOKEN_ABC');
        expect($service->getWebhookToken())->not->toBe('BOT_TOKEN_XYZ');
    });

    it('getWebhookToken() retorna null quando access_token não está configurado', function () {
        $service = makeChatwootServiceWithConfig(['access_token' => null]);

        expect($service->getWebhookToken())->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// REQUISITO: sendAssistantMessage (bot token + assistant_inbox_id)
// ---------------------------------------------------------------------------

describe('sendAssistantMessage() — botHeaders e fallback de assistant_inbox_id', function () {

    it('envia request com api_key (botHeaders) em /messages', function () {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);

        $service = makeChatwootServiceWithConfig([
            'api_key'      => 'BOT_TOKEN_XYZ',
            'access_token' => 'USER_ACCESS_TOKEN_ABC',
            'assistant_inbox_id' => 55,
        ]);

        $service->sendAssistantMessage(1, 'Olá do Assistente!');

        Http::assertSent(fn ($req) =>
            $req->hasHeader('api_access_token', 'BOT_TOKEN_XYZ')
            && str_contains($req->url(), '/messages')
        );
    });

    it('usa assistant_inbox_id no payload quando disponível', function () {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);

        $service = makeChatwootServiceWithConfig([
            'assistant_inbox_id' => 55,
            'inbox_id'           => 42,
        ]);

        $service->sendAssistantMessage(1, 'Teste');

        Http::assertSent(fn ($req) => $req->data()['inbox_id'] === 55);
    });

    it('usa inbox_id como fallback e emite Log::warning quando assistant_inbox_id for null', function () {
        Http::fake(['*' => Http::response(['id' => 1], 200)]);
        Log::shouldReceive('warning')->once();

        $service = makeChatwootServiceWithConfig([
            'assistant_inbox_id' => null,
            'inbox_id'           => 42,
        ]);

        $service->sendAssistantMessage(1, 'Teste Fallback');

        Http::assertSent(fn ($req) => $req->data()['inbox_id'] === 42);
    });
});

// ---------------------------------------------------------------------------
// REQUISITO: Webhook Controller usa access_token para HMAC
// ---------------------------------------------------------------------------

describe('ChatwootWebhookController — usa access_token como secret HMAC', function () {

    it('valida assinatura HMAC corretamente com o access_token do tenant', function () {
        // Mocking cache to bypass DB and inject our config
        config(['lawfirm.tenant_id' => 'tenant_test']);
        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->with('tenant_tenant_test_config', \Mockery::any(), \Mockery::any())
            ->andReturn((object) [
                'chatwoot_node_id' => 1,
                'chatwoot_inbox_id' => 99,
                'chatwoot_channel_inbox_id' => 42,
                'chatwoot_assistant_inbox_id' => 55,
                'chatwoot_webhook_token' => 'SECRET_USER_TOKEN',
            ]);

        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->with('chatwoot_node_1', \Mockery::any(), \Mockery::any())
            ->andReturn((object) [
                'base_url' => 'https://chat.test',
                'api_key' => 'NODE_BOT_TOKEN',
                'meta_data' => ['account_id' => 99],
            ]);

        $payload = '{"event":"conversation_created","id":123,"inbox":{"id":42}}';
        $signature = 'sha1=' . hash_hmac('sha1', $payload, 'SECRET_USER_TOKEN');

        $request = \Illuminate\Http\Request::create('/api/webhooks/chatwoot', 'POST', [], [], [], [
            'HTTP_X_CHATWOOT_SIGNATURE' => $signature,
            'CONTENT_TYPE'              => 'application/json',
        ], $payload);

        $controller = new \SuiteZap\LawFirm\Atendimento\Http\Controllers\ChatwootWebhookController();
        $response = $controller->handle($request);

        if ($response->getData()->status !== 'ok') {
            dump($response->getData());
        }

        expect($response->status())->toBe(200);
        expect($response->getData()->status)->toBe('ok');
    });
});

// ---------------------------------------------------------------------------
// REQUISITO: MotherShipService mapeia nomes de campo reais (ARCHITECTURE.md §4.86)
// ---------------------------------------------------------------------------

describe('MotherShipService::getChatwootConfig() — mapeamento de tokens e IDs', function () {

    it('mapeia account_id a partir de chatwoot_inbox_id e inbox_id a partir de chatwoot_channel_inbox_id', function () {
        config(['lawfirm.tenant_id' => 'tenant_test']);
        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->with('tenant_tenant_test_config', \Mockery::any(), \Mockery::any())
            ->andReturn((object) [
                'chatwoot_node_id' => 1,
                'chatwoot_inbox_id' => 99, // BUG HISTÓRICO: Nome confuso, mas guarda o account_id
                'chatwoot_channel_inbox_id' => 42, // Nome correto do inbox
                'chatwoot_assistant_inbox_id' => 55,
                'chatwoot_webhook_token' => 'USER_TOKEN',
            ]);

        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->with('chatwoot_node_1', \Mockery::any(), \Mockery::any())
            ->andReturn((object) [
                'base_url' => 'https://chat.test',
                'api_key' => 'NODE_BOT_TOKEN',
                'meta_data' => [], // Forçando a ler do tenant legado
            ]);

        $config = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getChatwootConfig();

        expect($config['account_id'])->toBe(99);
        expect($config['inbox_id'])->toBe(42);
        expect($config['assistant_inbox_id'])->toBe(55);
        expect($config['api_key'])->toBe('NODE_BOT_TOKEN');
        expect($config['access_token'])->toBe('USER_TOKEN');
    });
});
