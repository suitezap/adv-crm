<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * TenantTestSeeder (QA-DATA-001)
 *
 * Persiste os fixtures do `SyntheticDataFactory` nos bancos isolados
 * `tenant_a_test` e `tenant_b_test`.
 *
 * ─── Segurança ────────────────────────────────────────────────────────────
 * Este seeder é **fail-closed**: chama `DatabaseSafetyGuard::assertSafe()`
 * ANTES de qualquer escrita. Ele aborta com RuntimeException se o ambiente
 * não for `testing`, se o sentinel `TEST_ENVIRONMENT_ACK` faltar, se alguma
 * conexão não terminar em `_test` ou se o host estiver fora da allowlist.
 *
 * Não inventamos um guard próprio — reusamos o `DatabaseSafetyGuard`
 * (SEC-GUARD-001), que já é a trava oficial do projeto e tem testes negativos.
 *
 * ─── Isolamento ───────────────────────────────────────────────────────────
 * Cada tenant vive no SEU banco (`tenant_a_test` / `tenant_b_test`). Nada é
 * compartilhado, então não há como um registro de `tenant_a` vazar para
 * `tenant_b` por engano de filtro.
 *
 * ─── Idempotência ─────────────────────────────────────────────────────────
 * Rodar duas vezes produz o mesmo estado: os UUIDs são determinísticos
 * (sha256 do nome) e cada escrita faz upsert por chave natural.
 *
 * @since   QA-DATA-001
 */
class TenantTestSeeder
{
    /** @var array<string, int> contagem de registros escritos por tabela */
    public static array $written = [];

    /**
     * @return array<int, string>
     */
    public static function tenants(): array
    {
        return ['a', 'b'];
    }

    /**
     * Nome do banco isolado de um tenant.
     */
    public static function databaseFor(string $tenantVariant): string
    {
        return 'tenant_'.$tenantVariant.'_test';
    }

    /**
     * Nome da conexão — segue a convenção do docker-compose.test.yml.
     */
    public static function connectionFor(string $tenantVariant): string
    {
        return 'tenant_'.$tenantVariant;
    }

    /**
     * Popula um tenant com o conjunto completo de fixtures.
     *
     * @return array<string, int> contagem por tabela
     */
    public static function seedTenant(string $tenantVariant): array
    {
        // ── Guard fail-closed: ANTES de qualquer escrita ──
        DatabaseSafetyGuard::assertSafe();
        DatabaseSafetyGuard::assertTenantIdAllowed(SyntheticDataFactory::tenantId($tenantVariant));

        $conn = self::connectionFor($tenantVariant);
        $counts = [];

        // ── Assinatura (base de tudo que cobra saldo) ──
        $counts['subscription'] = self::upsert(
            $conn,
            'subscriptions',
            SyntheticDataFactory::subscription(SyntheticDataFactory::tenantId($tenantVariant)),
            ['tenant_id']
        );

        // ── Usuários ──
        $counts['users'] = self::upsert($conn, 'users', SyntheticDataFactory::adminUser($tenantVariant), ['uuid']);
        $counts['users'] += self::upsert($conn, 'users', SyntheticDataFactory::lawyer($tenantVariant), ['uuid']);

        // ── Leads ──
        foreach (['001', '002'] as $v) {
            $counts['leads'] = ($counts['leads'] ?? 0)
                + self::upsert($conn, 'leads', SyntheticDataFactory::lead($tenantVariant, $v), ['uuid']);
        }

        // ── Domínio jurídico ──
        $counts['processos'] = self::upsert($conn, 'processos', SyntheticDataFactory::processo($tenantVariant), ['uuid']);

        // ── Kanban (KAN-001) ──
        $counts['kanban_columns'] = self::upsert($conn, 'kanban_columns', SyntheticDataFactory::kanbanColumn($tenantVariant), ['uuid']);
        $counts['kanban_cards'] = self::upsert($conn, 'kanban_cards', SyntheticDataFactory::kanbanCard($tenantVariant), ['uuid']);

        // ── Integrações ──
        $counts['chatwoot_conversations'] = self::upsert(
            $conn,
            'chatwoot_conversations',
            SyntheticDataFactory::chatwootConversation($tenantVariant),
            ['uuid']
        );
        $counts['ai_documents'] = self::upsert($conn, 'ai_documents', SyntheticDataFactory::aiDocument($tenantVariant), ['uuid']);

        // Senha: resolvida AQUI, nunca no factory nem no repositório.
        self::resolveAliases($conn);

        self::$written[$tenantVariant] = array_filter($counts);

        return self::$written[$tenantVariant];
    }

    /**
     * Popula todos os tenants de teste.
     *
     * @return array<string, array<string, int>>
     */
    public static function seedAll(): array
    {
        $out = [];
        foreach (self::tenants() as $v) {
            $out[$v] = self::seedTenant($v);
        }

        return $out;
    }

    /**
     * Upsert por chave natural — idempotente por construção.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys  colunas que identificam o registro
     * @return int 1 se escreveu, 0 se o registro já era idêntico
     */
    private static function upsert(string $conn, string $table, array $row, array $keys): int
    {
        $query = DB::connection($conn)->table($table);

        $where = [];
        foreach ($keys as $k) {
            $where[$k] = $row[$k] ?? null;
        }

        $existing = (clone $query)->where($where)->first();

        if ($existing === null) {
            $query->insert($row);

            return 1;
        }

        // Só atualiza se algo mudou — mantém a escrita idempotente observável.
        $changes = array_diff_assoc((array) $existing, $row);
        if ($changes !== []) {
            $query->where($where)->update($changes);

            return 1;
        }

        return 0;
    }

    /**
     * Troca os aliases pelos valores resolvidos no momento do seed.
     *
     * `__TEST_PASSWORD__` vira bcrypt da senha de teste — aqui e só aqui, nunca
     * no repositório. `__TEST_TOKEN__` vira um token aleatório descartável.
     */
    private static function resolveAliases(string $conn): void
    {
        $password = SyntheticDataFactory::hashTestPassword();

        $users = DB::connection($conn)->table('users')->where('password', SyntheticDataFactory::TEST_PASSWORD);

        foreach ($users->get() as $user) {
            DB::connection($conn)->table('users')->where('uuid', $user->uuid)->update(['password' => $password]);
        }

        // Tokens: aleatórios por tenant, descartáveis. Nunca reaproveitados.
        $conversations = DB::connection($conn)->table('chatwoot_conversations')
            ->where('contact_token', SyntheticDataFactory::TEST_TOKEN);

        foreach ($conversations->get() as $conv) {
            DB::connection($conn)->table('chatwoot_conversations')
                ->where('uuid', $conv->uuid)
                ->update(['contact_token' => bin2hex(random_bytes(16))]);
        }
    }

    /**
     * Limpa todos os registros de fixtures de um tenant.
     *
     * Só remove linhas cujos UUID batem com os determinísticos do factory —
     * um dado criado ad-hoc durante o teste **não** é apagado.
     */
    public static function clear(string $tenantVariant): int
    {
        DatabaseSafetyGuard::assertSafe();

        $conn = self::connectionFor($tenantVariant);
        $removed = 0;

        $spec = [
            'ai_documents'           => ['ai-doc:001:'.SyntheticDataFactory::tenantId($tenantVariant)],
            'chatwoot_conversations' => ['chatwoot-conv:001:'.SyntheticDataFactory::tenantId($tenantVariant)],
            'kanban_cards'           => ['kanban-card:001:'.SyntheticDataFactory::tenantId($tenantVariant)],
            'kanban_columns'         => ['kanban-column:Triagem:'.SyntheticDataFactory::tenantId($tenantVariant)],
            'processos'              => ['processo:001:'.SyntheticDataFactory::tenantId($tenantVariant)],
            'leads'                  => ['lead:001:'.SyntheticDataFactory::tenantId($tenantVariant), 'lead:002:'.SyntheticDataFactory::tenantId($tenantVariant)],
            'users'                  => [
                'user:admin:'.SyntheticDataFactory::tenantId($tenantVariant),
                'user:lawyer:'.SyntheticDataFactory::tenantId($tenantVariant).':001',
            ],
        ];

        foreach ($spec as $table => $names) {
            $uuids = array_map(fn (string $n) => SyntheticDataFactory::deterministicUuid($n), $names);
            $removed += DB::connection($conn)->table($table)->whereIn('uuid', $uuids)->delete();
        }

        return $removed;
    }
}
