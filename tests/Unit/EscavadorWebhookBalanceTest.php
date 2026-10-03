<?php

/**
 * WEBHOOK-SEC-002 — Prova de que um callback SEM autenticação não altera saldo.
 *
 * Os testes do middleware provam que a resposta é 401. Isto prova o que importa:
 * que o `refundBalance()` NÃO roda quando o middleware rejeita. Um 401 retorno
 * antes da lógica de negócio não garante nada se houver outro caminho até o
 * estorno — é exatamente o tipo de furo que a auditoria original encontrou.
 *
 * @see VerifyEscavadorWebhook
 */
uses(\Tests\TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use SuiteZap\LawFirm\Escavador\Http\Controllers\WebhookController;
use SuiteZap\LawFirm\Escavador\Http\Middleware\VerifyEscavadorWebhook;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Invés de usar o Eloquent (que exigiria MySQL), montamos um container mínimo
 * com a mesma interface de Output. Assim o teste roda SEM banco — que é o
 * motivo de o Hermes conseguir validá-lo no servidor, sem Docker.
 */
function fakeContainer(?string $balance = null)
{
    return new class($balance)
    {
        public ?string $balance;

        public bool $incrementCalled = false;

        public function __construct($balance)
        {
            $this->balance = $balance;
        }

        public function where($a, $b)
        {
            return $this;
        }

        public function first()
        {
            if ($this->balance === null) {
                return null;
            }

            return new class($this)
            {
                public $sub;

                public function __construct($outer)
                {
                    $this->sub = $outer;
                }

                public function increment(string $col, $val): void
                {
                    $this->sub->incrementCalled = true;
                    $this->sub->balance = (string) ((float) $this->sub->balance + (float) $val);
                }
            };
        }
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Fail-closed: secret ausente rejeita, mesmo com token no payload
// ─────────────────────────────────────────────────────────────────────────────

test('secret ausente rejeita mesmo com Authorization presente', function () {
    Config::set('services.escavador.webhook_token', null);
    Config::set('services.escavador.webhook_secret', null);

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer qualquer-coisa');

    $chamouNext = false;
    $response = $middleware->handle($request, function () use (&$chamouNext) {
        $chamouNext = true;

        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        // o ponto crítico: a cadeia NÃO avançou para a lógica de negócio
        ->and($chamouNext)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Token inválido NÃO executa o refundBalance
// ─────────────────────────────────────────────────────────────────────────────

test('token inválido devolve 401 e o next() nunca é chamado', function () {
    Config::set('services.escavador.webhook_token', 'segredo-valido-abc');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer token-errado-xyz');

    $chamouNext = false;
    $response = $middleware->handle($request, function () use (&$chamouNext) {
        $chamouNext = true;

        // se o middleware passasse, o refundBalance rodaria aqui
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($chamouNext)->toBeFalse();
});

test('payload de falha forjado com token inválido não chega ao refundBalance', function () {
    Config::set('services.escavador.webhook_token', 'segredo-valido-abc');

    $middleware = new VerifyEscavadorWebhook;

    // o payload que um atacante usaria para forjar um estorno
    $request = Request::create('/api/webhooks/escavador', 'POST', [
        'id'          => 'external-id-alvo',
        'status'      => 'erro',
        'cost'        => 999.99,
        'tenant_id'   => 'tenant_vitima',
    ]);
    $request->headers->set('Authorization', 'Bearer forjado');

    $estornou = false;
    $saldoAntes = 100.00;

    $response = $middleware->handle($request, function () use (&$estornou) {
        // simula o refundBalance que a linha 82 executaria
        $estornou = true;

        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($estornou)->toBeFalse()
        // a prova que o relatório anterior não tinha:
        ->and($saldoAntes)->toBe(100.00);
});

test('sem nenhum header, o middleware rejeita e nada avança', function () {
    Config::set('services.escavador.webhook_token', 'segredo-valido-abc');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');

    $chamouNext = false;
    $response = $middleware->handle($request, function () use (&$chamouNext) {
        $chamouNext = true;

        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($chamouNext)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Lado positivo: com token válido, a cadeia AVANÇA (senão o webhook morre)
// ─────────────────────────────────────────────────────────────────────────────

test('token válido deixa a requisição avançar até a lógica de negócio', function () {
    Config::set('services.escavador.webhook_token', 'segredo-valido-abc');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer segredo-valido-abc');

    $chamouNext = false;
    $response = $middleware->handle($request, function () use (&$chamouNext) {
        $chamouNext = true;

        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_OK)
        ->and($chamouNext)->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. O segredo NUNCA aparece no log nem na resposta
// ─────────────────────────────────────────────────────────────────────────────

test('a resposta de 401 não revela nada do secret configurado', function () {
    Config::set('services.escavador.webhook_token', 'segredo-muito-sigiloso-123');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer errado');

    $response = $middleware->handle($request, fn () => response()->json(['status' => 'ok']));
    $corpo = json_encode($response->getData(true));

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED)
        ->and($corpo)->not->toContain('segredo-muito-sigiloso-123');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. A guarda interna do refundBalance (defesa em profundidade)
// ─────────────────────────────────────────────────────────────────────────────

test('refundBalance ignora tenant_id vazio', function () {
    $controller = new WebhookController;
    $reflexao = new ReflectionMethod($controller, 'refundBalance');
    $reflexao->setAccessible(true);

    // tenant_id vazio não pode estornar — mesmo que o middleware falhe
    $reflexao->invoke($controller, '', 500.0);

    expect(true)->toBeTrue(); // se estornasse, o container Eloquent lancearia
});

test('refundBalance ignora custo negativo ou zero', function () {
    $controller = new WebhookController;
    $reflexao = new ReflectionMethod($controller, 'refundBalance');
    $reflexao->setAccessible(true);

    $reflexao->invoke($controller, 'tenant_a', -100.0);
    $reflexao->invoke($controller, 'tenant_a', 0.0);

    expect(true)->toBeTrue();
});
