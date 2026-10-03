<?php

declare(strict_types=1);

use Tests\Support\SyntheticDataFactory;

/**
 * SyntheticDataFactoryTest (QA-DATA-001)
 *
 * Testes do gerador de fixtures. NÃO tocam banco — o factory só devolve
 * arrays — então rodam em qualquer ambiente e provam os invariantes de
 * determinismo e de ausência de segredo.
 *
 * @since   QA-DATA-001
 */

// ─────────────────────────────────────────────────────────────────────────────
// 1. Determinismo do UUID — a correção que esta task fez
// ─────────────────────────────────────────────────────────────────────────────

describe('deterministicUuid — é realmente determinístico', function () {

    it('retorna o MESMO valor para o mesmo nome', function () {
        $a = SyntheticDataFactory::deterministicUuid('lead:001');
        $b = SyntheticDataFactory::deterministicUuid('lead:001');

        expect($a)->toBe($b);
    });

    it('retorna valores DIFERENTES para nomes diferentes', function () {
        $a = SyntheticDataFactory::deterministicUuid('lead:001');
        $b = SyntheticDataFactory::deterministicUuid('lead:002');

        expect($a)->not->toBe($b);
    });

    it('mantém o formato de UUID (8-4-4-4-12)', function () {
        $uuid = SyntheticDataFactory::deterministicUuid('qualquer:nome');

        expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    it('gera um conjunto reprodutível de N itens', function () {
        $primeiro = SyntheticDataFactory::deterministicUuidSet('processo', 5);
        $segundo  = SyntheticDataFactory::deterministicUuidSet('processo', 5);

        expect($primeiro)->toHaveCount(5)
            ->and($primeiro)->toBe($segundo)
            ->and(array_unique($primeiro))->toHaveCount(5);
    });

    it('NÃO colide entre tenants diferentes', function () {
        $a = SyntheticDataFactory::lead('a', '001');
        $b = SyntheticDataFactory::lead('b', '001');

        expect($a['uuid'])->not->toBe($b['uuid']);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Ausência de segredo — ADR-GOV-005
// ─────────────────────────────────────────────────────────────────────────────

describe('SyntheticDataFactory — nenhum segredo real', function () {

    it('usa alias na senha, não um valor real', function () {
        $user = SyntheticDataFactory::adminUser('a');

        expect($user['password'])->toBe(SyntheticDataFactory::TEST_PASSWORD)
            ->and(SyntheticDataFactory::TEST_PASSWORD)->toBe('__TEST_PASSWORD__');
    });

    it('usa alias no token do Chatwoot', function () {
        $conv = SyntheticDataFactory::chatwootConversation('a');

        expect($conv['contact_token'])->toBe(SyntheticDataFactory::TEST_TOKEN)
            ->and(SyntheticDataFactory::TEST_TOKEN)->toBe('__TEST_TOKEN__');
    });

    it('nenhum método devolve string que pareça segredo real', function () {
        $todos = array_merge(
            [SyntheticDataFactory::adminUser('a'), SyntheticDataFactory::lawyer('a')],
            [SyntheticDataFactory::lead('a'), SyntheticDataFactory::processo('a')],
            [SyntheticDataFactory::subscription()],
            [SyntheticDataFactory::kanbanColumn('a'), SyntheticDataFactory::kanbanCard('a')],
            [SyntheticDataFactory::chatwootConversation('a'), SyntheticDataFactory::aiDocument('a')],
        );

        $serializado = json_encode($todos);

        // Nenhum segredo real: nem sk-, nem ghp_, nem JWT, nem senha embutida
        expect($serializado)->not->toMatch('/sk-[A-Za-z0-9]{8,}/')
            ->and($serializado)->not->toMatch('/ghp_[A-Za-z0-9]{8,}/')
            ->and($serializado)->not->toMatch('/eyJ[A-Za-z0-9_-]{10,}/')
            ->and($serializado)->not->toMatch('/Test@Password/');
    });

    it('o e-mail de teste usa domínio .invalid (RFC 2606)', function () {
        $user = SyntheticDataFactory::adminUser('a');

        expect($user['email'])->toEndWith('.invalid');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Completeness e coerência dos fixtures
// ─────────────────────────────────────────────────────────────────────────────

describe('SyntheticDataFactory — fixtures coerentes', function () {

    it('todo fixture carrega tenant_id', function () {
        $fixtures = [
            SyntheticDataFactory::adminUser('a'),
            SyntheticDataFactory::lawyer('a'),
            SyntheticDataFactory::lead('a'),
            SyntheticDataFactory::processo('a'),
            SyntheticDataFactory::kanbanColumn('a'),
            SyntheticDataFactory::kanbanCard('a'),
            SyntheticDataFactory::chatwootConversation('a'),
            SyntheticDataFactory::aiDocument('a'),
            SyntheticDataFactory::subscription(),
        ];

        foreach ($fixtures as $i => $f) {
            // toContain() nao aceita mensagem custom como 2o argumento —
            // ele trataria a string como valor procurado.
            expect($f)->toHaveKeys(['uuid', 'tenant_id']);
            expect($f['tenant_id'])->toBe('tenant_a');
        }
    });

    it('o processo aponta para um lawyer do mesmo tenant', function () {
        $lawyer  = SyntheticDataFactory::lawyer('a', '001');
        $processo = SyntheticDataFactory::processo('a');

        expect($processo['responsavel_uuid'])->toBe($lawyer['uuid']);
    });

    it('o card aponta para a coluna do mesmo tenant', function () {
        $coluna = SyntheticDataFactory::kanbanColumn('a', 'Triagem');
        $card   = SyntheticDataFactory::kanbanCard('a', 'Triagem');

        expect($card['column_uuid'])->toBe($coluna['uuid']);
    });

    it('o documento de IA aponta para o processo do mesmo tenant', function () {
        $processo = SyntheticDataFactory::processo('a');
        $doc      = SyntheticDataFactory::aiDocument('a');

        expect($doc['processo_uuid'])->toBe($processo['uuid']);
    });

    it('o admin é admin e o lawyer não', function () {
        expect(SyntheticDataFactory::adminUser('a')['is_admin'])->toBeTrue()
            ->and(SyntheticDataFactory::lawyer('a')['is_admin'])->toBeFalse();
    });

    it('os tenants a e b geram e-mails distintos', function () {
        expect(SyntheticDataFactory::adminUser('a')['email'])
            ->not->toBe(SyntheticDataFactory::adminUser('b')['email']);
    });

    it('o saldo da subscription dá folga para teste de estorno', function () {
        $sub = SyntheticDataFactory::subscription();

        // os testes de estorno precisam de decremento E incremento sem negativo
        expect($sub['suitecoin_balance'])->toBeGreaterThan(500.0);
    });
});
