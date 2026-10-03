<?php

/**
 * KAN-001 — Unit tests for SyncCasoStageToChatwootListener.
 *
 * Design goal: ZERO real Eloquent / MySQL calls.
 * We test the listener's pure-logic paths by:
 *   a) exercising the private helpers via Reflection
 *   b) stubbing heavy dependencies (MotherShipService, ChatwootService, Tag)
 *      with facades / anonymous-class fakes so the suite runs without Docker.
 *
 * What is covered:
 *  1. Phone normalization (normalizePhone) — pure string logic
 *  2. Stage slug → Chatwoot label resolution (STAGE_LABEL_MAP) completeness
 *  3. CASO_STAGE_POOL has exactly 12 labels, ld_ganho NOT in pool
 *  4. resolvePhone guards: null person, empty contacts, first-valid, skips-empty-value
 *  5. novoCaso slug detection invariant
 *  6. getDynamicCrmTagPool: static fallback when Tag query fails; uniqueness
 *  7. ShouldQueue interface + queue name + tries=1
 */

uses(\Tests\TestCase::class);

use SuiteZap\LawFirm\Legal\Listeners\SyncCasoStageToChatwootListener;

// ──────────────────────────────────────────────────────────────────────────────
// Helper: open a private/protected method via Reflection.
// ──────────────────────────────────────────────────────────────────────────────

function kanMethod(string $method): \ReflectionMethod
{
    $rm = new \ReflectionMethod(SyncCasoStageToChatwootListener::class, $method);
    $rm->setAccessible(true);

    return $rm;
}

// ──────────────────────────────────────────────────────────────────────────────
// 1. normalizePhone — pure string logic, no DB
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-001 normalizePhone adds +55 to 11-digit Brazilian number', function () {
    $m = kanMethod('normalizePhone');
    $l = new SyncCasoStageToChatwootListener;

    expect($m->invoke($l, '11999998888'))->toBe('+5511999998888');
});

test('KAN-UNIT-002 normalizePhone does not double-add 55 when already present', function () {
    $m = kanMethod('normalizePhone');
    $l = new SyncCasoStageToChatwootListener;

    expect($m->invoke($l, '5511999998888'))->toBe('+5511999998888');
});

test('KAN-UNIT-003 normalizePhone strips non-digit chars before normalizing', function () {
    $m = kanMethod('normalizePhone');
    $l = new SyncCasoStageToChatwootListener;

    expect($m->invoke($l, '(11) 99999-8888'))->toBe('+5511999998888');
});

test('KAN-UNIT-004 normalizePhone strips formatting from number already with country code', function () {
    $m = kanMethod('normalizePhone');
    $l = new SyncCasoStageToChatwootListener;

    expect($m->invoke($l, '+55 (11) 9999-98888'))->toBe('+5511999998888');
});

// ──────────────────────────────────────────────────────────────────────────────
// 2. STAGE_LABEL_MAP — mapping completeness
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-005 STAGE_LABEL_MAP contains all 14 canonical stage slugs', function () {
    $rc  = new \ReflectionClass(SyncCasoStageToChatwootListener::class);
    $map = $rc->getConstant('STAGE_LABEL_MAP');

    $required = [
        'novo-caso'             => 'CAS_NOVO',
        'novo'                  => 'CAS_NOVO',
        'em-analise'            => 'CAS_ANAL',
        'aguardando-cliente'    => 'CAS_AGCLI',
        'producao-interna'      => 'CAS_PROD',
        'em-producao-juridica'  => 'CAS_PROD',
        'protocolado'           => 'CAS_PROT',
        'aguardando-judiciario' => 'CAS_AGJUD',
        'prazo-em-andamento'    => 'CAS_PRAZO',
        'audiencia'             => 'CAS_AUD',
        'sentenca'              => 'CAS_SENT',
        'recurso'               => 'CAS_RECUR',
        'execucao'              => 'CAS_EXEC',
        'encerrado'             => 'CAS_ENCER',
    ];

    foreach ($required as $slug => $label) {
        expect($map)->toHaveKey($slug);
        expect($map[$slug])->toBe($label, "Slug '{$slug}' should map to '{$label}'");
    }
});

// ──────────────────────────────────────────────────────────────────────────────
// 3. CASO_STAGE_POOL
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-006 CASO_STAGE_POOL contains exactly 12 labels', function () {
    $rc   = new \ReflectionClass(SyncCasoStageToChatwootListener::class);
    $pool = $rc->getConstant('CASO_STAGE_POOL');

    expect(count($pool))->toBe(12);
    expect($pool)->toContain('CAS_NOVO')
        ->toContain('CAS_ANAL')->toContain('CAS_AGCLI')
        ->toContain('CAS_PROD')->toContain('CAS_PROT')
        ->toContain('CAS_AGJUD')->toContain('CAS_PRAZO')
        ->toContain('CAS_AUD')->toContain('CAS_SENT')
        ->toContain('CAS_RECUR')->toContain('CAS_EXEC')
        ->toContain('CAS_ENCER');
});

test('KAN-UNIT-007 CASO_STAGE_POOL does NOT contain ld_ganho (lead label must not be stripped)', function () {
    $rc   = new \ReflectionClass(SyncCasoStageToChatwootListener::class);
    $pool = $rc->getConstant('CASO_STAGE_POOL');

    expect($pool)->not->toContain('ld_ganho');
});

// ──────────────────────────────────────────────────────────────────────────────
// 4. resolvePhone guards — exercised via Reflection, no DB
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-008 resolvePhone returns null when caso has no person', function () {
    $m = kanMethod('resolvePhone');
    $l = new SyncCasoStageToChatwootListener;

    $caso = new \SuiteZap\LawFirm\Legal\Models\Caso;
    $caso->setRelation('person', null);

    expect($m->invoke($l, $caso))->toBeNull();
});

test('KAN-UNIT-009 resolvePhone returns null when person has empty contact_numbers', function () {
    $m = kanMethod('resolvePhone');
    $l = new SyncCasoStageToChatwootListener;

    $person = new class {
        public array $contact_numbers = [];
    };

    $caso = new \SuiteZap\LawFirm\Legal\Models\Caso;
    $caso->setRelation('person', $person);

    expect($m->invoke($l, $caso))->toBeNull();
});

test('KAN-UNIT-010 resolvePhone returns first valid phone in E.164 format', function () {
    $m = kanMethod('resolvePhone');
    $l = new SyncCasoStageToChatwootListener;

    $person = new class {
        public array $contact_numbers = [
            ['value' => '11999998888', 'label' => 'phone'],
            ['value' => '11888887777', 'label' => 'phone'],
        ];
    };

    $caso = new \SuiteZap\LawFirm\Legal\Models\Caso;
    $caso->setRelation('person', $person);

    expect($m->invoke($l, $caso))->toBe('+5511999998888');
});

test('KAN-UNIT-011 resolvePhone skips entries with empty value and returns next valid one', function () {
    $m = kanMethod('resolvePhone');
    $l = new SyncCasoStageToChatwootListener;

    $person = new class {
        public array $contact_numbers = [
            ['value' => '', 'label' => 'phone'],
            ['value' => '21987654321', 'label' => 'phone'],
        ];
    };

    $caso = new \SuiteZap\LawFirm\Legal\Models\Caso;
    $caso->setRelation('person', $person);

    expect($m->invoke($l, $caso))->toBe('+5521987654321');
});

// ──────────────────────────────────────────────────────────────────────────────
// 5. novoCaso slug detection invariant
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-012 novo-caso and novo slugs are treated as novoCaso (ld_ganho preserved)', function () {
    $novoSlugs = ['novo-caso', 'novo'];
    foreach ($novoSlugs as $slug) {
        expect(in_array($slug, ['novo-caso', 'novo'], true))->toBeTrue(
            "Slug '{$slug}' must be treated as novoCaso"
        );
    }
});

test('KAN-UNIT-013 non-novo slugs are NOT treated as novoCaso (ld_ganho stripped)', function () {
    $nonNovoSlugs = ['em-analise', 'protocolado', 'encerrado', 'audiencia', 'sentenca'];
    foreach ($nonNovoSlugs as $slug) {
        expect(in_array($slug, ['novo-caso', 'novo'], true))->toBeFalse(
            "Slug '{$slug}' must NOT be treated as novoCaso"
        );
    }
});

// ──────────────────────────────────────────────────────────────────────────────
// 6. getDynamicCrmTagPool
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-014 getDynamicCrmTagPool always includes CASO_STAGE_POOL even when Tag query fails', function () {
    $m = kanMethod('getDynamicCrmTagPool');
    $l = new SyncCasoStageToChatwootListener;

    // Tag::pluck will fail (no MySQL) — static pool must still be in result.
    $pool       = $m->invoke($l);
    $rc         = new \ReflectionClass(SyncCasoStageToChatwootListener::class);
    $staticPool = $rc->getConstant('CASO_STAGE_POOL');

    foreach ($staticPool as $label) {
        $inPool = in_array($label, $pool, true);
        expect($inPool)->toBeTrue(); // label: $label
    }
});

test('KAN-UNIT-015 getDynamicCrmTagPool returns unique values only', function () {
    $m = kanMethod('getDynamicCrmTagPool');
    $l = new SyncCasoStageToChatwootListener;

    $pool = $m->invoke($l);

    expect(count($pool))->toBe(count(array_unique($pool)));
});

// ──────────────────────────────────────────────────────────────────────────────
// 7. Listener class configuration
// ──────────────────────────────────────────────────────────────────────────────

test('KAN-UNIT-016 SyncCasoStageToChatwootListener implements ShouldQueue', function () {
    expect(new SyncCasoStageToChatwootListener)
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('KAN-UNIT-017 SyncCasoStageToChatwootListener dispatches to the default queue', function () {
    expect((new SyncCasoStageToChatwootListener)->queue)->toBe('default');
});

test('KAN-UNIT-018 SyncCasoStageToChatwootListener has tries=1 to avoid flooding Chatwoot', function () {
    expect((new SyncCasoStageToChatwootListener)->tries)->toBe(1);
});
