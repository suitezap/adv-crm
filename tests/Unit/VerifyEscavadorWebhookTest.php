<?php

uses(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use SuiteZap\LawFirm\Escavador\Http\Middleware\VerifyEscavadorWebhook;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

beforeEach(function () {
    // Limpa cache da chave do webhook antes de cada teste
    cache()->forget('escavador_webhook_token_'.md5('LawFimr V1 e V2'));
    try {
        InfrastructureNode::on('mothership')
            ->where('type', 'escavador')
            ->delete();
    } catch (Throwable $e) {
    }
});

test('VerifyEscavadorWebhook rejects request when secret is not configured (fail-closed)', function () {
    Config::set('services.escavador.webhook_token', null);
    Config::set('services.escavador.webhook_secret', null);

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer some-token');

    $response = $middleware->handle($request, function () {
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
    expect($response->getData(true))->toMatchArray([
        'status'  => 'unauthorized',
        'message' => 'Webhook secret not configured',
    ]);
});

test('VerifyEscavadorWebhook rejects request without authorization header', function () {
    Config::set('services.escavador.webhook_token', 'valid-secret-123');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');

    $response = $middleware->handle($request, function () {
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
    expect($response->getData(true))->toMatchArray([
        'status'  => 'unauthorized',
        'message' => 'Invalid webhook token',
    ]);
});

test('VerifyEscavadorWebhook rejects request with invalid token', function () {
    Config::set('services.escavador.webhook_token', 'valid-secret-123');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer wrong-token');

    $response = $middleware->handle($request, function () {
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
});

test('VerifyEscavadorWebhook accepts request with valid Bearer token', function () {
    Config::set('services.escavador.webhook_token', 'valid-secret-123');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'Bearer valid-secret-123');

    $nextCalled = false;
    $response = $middleware->handle($request, function () use (&$nextCalled) {
        $nextCalled = true;

        return response()->json(['status' => 'ok']);
    });

    expect($nextCalled)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
});

test('VerifyEscavadorWebhook accepts request with valid token without Bearer prefix', function () {
    Config::set('services.escavador.webhook_token', 'valid-secret-123');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('Authorization', 'valid-secret-123');

    $nextCalled = false;
    $response = $middleware->handle($request, function () use (&$nextCalled) {
        $nextCalled = true;

        return response()->json(['status' => 'ok']);
    });

    expect($nextCalled)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
});

test('VerifyEscavadorWebhook accepts request with valid X-Escavador-Token header', function () {
    Config::set('services.escavador.webhook_token', 'valid-secret-123');

    $middleware = new VerifyEscavadorWebhook;
    $request = Request::create('/api/webhooks/escavador', 'POST');
    $request->headers->set('X-Escavador-Token', 'valid-secret-123');

    $nextCalled = false;
    $response = $middleware->handle($request, function () use (&$nextCalled) {
        $nextCalled = true;

        return response()->json(['status' => 'ok']);
    });

    expect($nextCalled)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
});
