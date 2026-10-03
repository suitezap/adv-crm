<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * SyntheticDataFactory
 *
 * Gerador de entidades sintéticas determinísticas para testes.
 *
 * ⚠️ NENHUM valor de segredo real. Senhas e tokens usam os aliases
 * `__TEST_PASSWORD__` / `__TEST_TOKEN__`, resolvidos pelo seeder no momento do
 * seed — respeita o ADR-GOV-005 (segredo nunca passa por agente, fixture ou
 * repositório).
 *
 * Esta classe **gera** dados. Persistência é do `TenantTestSeeder`.
 *
 * @since   v3.55.0 — Etapa 2 da Infraestrutura de Qualidade
 * @since   QA-DATA-001 — deterministicUuid corrigido, domínio jurídico adicionado
 */
class SyntheticDataFactory
{
    /** Alias de senha — nunca um segredo real (ADR-GOV-005). */
    public const TEST_PASSWORD = '__TEST_PASSWORD__';

    /** Alias de token — nunca um segredo real (ADR-GOV-005). */
    public const TEST_TOKEN = '__TEST_TOKEN__';

    /** `.invalid` é reservado pela RFC 2606 — nunca resolve. */
    private const TEST_DOMAIN = 'lawfirm-test.invalid';

    // ─────────────────────────────────────────────────────────────────────
    // Identidade
    // ─────────────────────────────────────────────────────────────────────

    /**
     * UUID **determinístico** derivado de um nome.
     *
     * Antes retornava `Str::uuid()` ignorando `$name` — dois testes com o mesmo
     * nome recebiam UUIDs diferentes, o que quebrava a idempotência de qualquer
     * fixture. Agora deriva de `sha256('lawfirm-test::' . $name)`.
     */
    public static function deterministicUuid(string $name): string
    {
        // \hash e \dechex explícitos: dentro de namespace, chamadas a função
        // nativa sem prefixo caem no resolver de classes do Laravel.
        $hash = substr(\hash('sha256', 'lawfirm-test::' . $name), 0, 32);

        return \sprintf(
            '%s-%s-4%s-%s%s-%s',
            \substr($hash, 0, 8),
            \substr($hash, 8, 4),
            \substr($hash, 13, 3),
            \substr($hash, 16, 3),
            \dechex((\hexdec($hash[16]) & 0x3) | 0x8),
            \substr($hash, 20, 12)
        );
    }

    /**
     * Conjunto de UUIDs determinísticos — coleção reprodutível.
     *
     * @return array<int, string>
     */
    public static function deterministicUuidSet(string $prefix, int $count): array
    {
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            $out[] = self::deterministicUuid("{$prefix}:{$i}");
        }

        return $out;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Tenants
    // ─────────────────────────────────────────────────────────────────────

    public static function tenantId(string $variant = 'a'): string
    {
        return "tenant_{$variant}";
    }

    /**
     * @return array<int, string>
     */
    public static function tenantIds(): array
    {
        return [self::tenantId('a'), self::tenantId('b')];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Assinatura / billing
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Subscription sintética.
     *
     * `suitecoin_balance` alto de propósito: os testes de estorno precisam de
     * folga para validar incremento E decremento sem saldo negativo.
     *
     * @param  array<int, string>  $activeModules
     * @return array<string, mixed>
     */
    public static function subscription(
        string $tenantId = 'tenant_a',
        array $activeModules = ['CHATWOOT', 'AI'],
        float $balance = 1000.00
    ): array {
        return [
            'uuid'              => self::deterministicUuid("subscription:{$tenantId}"),
            'tenant_id'         => $tenantId,
            'plan'              => 'professional_test',
            'active_modules'    => array_values($activeModules),
            'suitecoin_balance' => $balance,
            'status'            => 'active',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Usuários
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Admin sintético.
     *
     * A senha é o **alias**, não um hash — `bcrypt()` é caro e roda uma vez por
     * chamada. O seeder resolve.
     *
     * @return array<string, mixed>
     */
    public static function adminUser(string $tenantVariant = 'a'): array
    {
        return [
            'uuid'      => self::deterministicUuid("user:admin:tenant_{$tenantVariant}"),
            'name'      => 'Admin Teste Tenant ' . strtoupper($tenantVariant),
            'email'     => "admin.test.{$tenantVariant}@" . self::TEST_DOMAIN,
            'password'  => self::TEST_PASSWORD,
            'tenant_id' => self::tenantId($tenantVariant),
            'is_admin'  => true,
            'status'    => 'active',
        ];
    }

    /**
     * Lawyer (não-admin) sintético.
     *
     * @return array<string, mixed>
     */
    public static function lawyer(string $tenantVariant = 'a', string $variant = '001'): array
    {
        return [
            'uuid'      => self::deterministicUuid("user:lawyer:tenant_{$tenantVariant}:{$variant}"),
            'name'      => "Advogado Teste {$variant}",
            'email'     => "lawyer.test.{$variant}@" . self::TEST_DOMAIN,
            'password'  => self::TEST_PASSWORD,
            'tenant_id' => self::tenantId($tenantVariant),
            'is_admin'  => false,
            'status'    => 'active',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Leads
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public static function lead(string $tenantVariant = 'a', string $variant = '001'): array
    {
        return [
            'uuid'        => self::deterministicUuid("lead:{$variant}:tenant_{$tenantVariant}"),
            'title'       => "Lead de Teste #{$variant}",
            'description' => "Descrição sintética de lead para testes automatizados. Variante: {$variant}.",
            'status'      => 'new',
            'tenant_id'   => self::tenantId($tenantVariant),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Domínio jurídico
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Processo jurídico sintético.
     *
     * Campos alinhados ao que as migrations versionadas tocam (`numero_cnj`
     * nullable, `sercreta`). A migration que **cria** a tabela não está no
     * repositório — ver `quality/QA-SCHEMA.md`.
     *
     * @return array<string, mixed>
     */
    public static function processo(string $tenantVariant = 'a', string $variant = '001'): array
    {
        return [
            'uuid'              => self::deterministicUuid("processo:{$variant}:tenant_{$tenantVariant}"),
            'numero_cnj'        => sprintf('%07d-2026.8.6000.0.00.0000', 1000000 + (int) $variant),
            'orgao_judicial'    => 'TJSP — Comarca de São Paulo',
            'classe'            => 'Ação de Cobrança',
            'valor_causa'       => 15000.00,
            'data_distribuicao' => '2026-01-15',
            'status'            => 'ativo',
            'sercreta'          => false,
            'tenant_id'         => self::tenantId($tenantVariant),
            'responsavel_uuid'  => self::deterministicUuid("user:lawyer:tenant_{$tenantVariant}:001"),
        ];
    }

    /**
     * Coluna de Kanban sintética (KAN-001).
     *
     * @return array<string, mixed>
     */
    public static function kanbanColumn(string $tenantVariant = 'a', string $titulo = 'Triagem'): array
    {
        return [
            'uuid'      => self::deterministicUuid("kanban-column:{$titulo}:tenant_{$tenantVariant}"),
            'titulo'    => $titulo,
            'posicao'   => 1,
            'cor'       => '#6B7280',
            'tenant_id' => self::tenantId($tenantVariant),
        ];
    }

    /**
     * Card de Kanban sintético.
     *
     * @return array<string, mixed>
     */
    public static function kanbanCard(
        string $tenantVariant = 'a',
        string $columnTitulo = 'Triagem',
        string $variant = '001'
    ): array {
        return [
            'uuid'        => self::deterministicUuid("kanban-card:{$variant}:tenant_{$tenantVariant}"),
            'titulo'      => "Card de Teste #{$variant}",
            'descricao'   => 'Card sintético para validação de fluxo jurídico.',
            'prioridade'  => 'media',
            'posicao'     => 1,
            'column_uuid' => self::deterministicUuid("kanban-column:{$columnTitulo}:tenant_{$tenantVariant}"),
            'tenant_id'   => self::tenantId($tenantVariant),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Integrações
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Conversa do Chatwoot sintética.
     *
     * @return array<string, mixed>
     */
    public static function chatwootConversation(string $tenantVariant = 'a', string $variant = '001'): array
    {
        return [
            'uuid'          => self::deterministicUuid("chatwoot-conv:{$variant}:tenant_{$tenantVariant}"),
            'chatwoot_id'   => 900000 + (int) $variant,
            'status'        => 'open',
            'channel_type'  => 'WhatsApp',
            'contact_token' => self::TEST_TOKEN,
            'tenant_id'     => self::tenantId($tenantVariant),
        ];
    }

    /**
     * Documento processado por IA, sintético.
     *
     * @return array<string, mixed>
     */
    public static function aiDocument(string $tenantVariant = 'a', string $variant = '001'): array
    {
        return [
            'uuid'          => self::deterministicUuid("ai-doc:{$variant}:tenant_{$tenantVariant}"),
            'processo_uuid' => self::deterministicUuid("processo:{$variant}:tenant_{$tenantVariant}"),
            'status'        => 'concluido',
            'custo'         => 2.50,
            'resumo'        => 'Resumo sintético para validação de teste.',
            'tenant_id'     => self::tenantId($tenantVariant),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Auxiliares
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Hash da senha de teste — usado pelo seeder, fora da geração.
     */
    public static function hashTestPassword(): string
    {
        return bcrypt(self::TEST_PASSWORD);
    }
}
