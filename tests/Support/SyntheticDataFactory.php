<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Str;

/**
 * SyntheticDataFactory
 *
 * Gerador de entidades sintéticas determinísticas para testes.
 *
 * Dados gerados são sempre fictícios, consistentes entre execuções
 * (com seed fixo quando necessário) e nunca provêm de produção.
 *
 * @package Tests\Support
 * @since   v3.55.0 — Etapa 2 da Infraestrutura de Qualidade
 */
class SyntheticDataFactory
{
    /**
     * Gera um tenant ID sintético de teste.
     *
     * @param  string  $variant  'a' ou 'b'
     */
    public static function tenantId(string $variant = 'a'): string
    {
        return "tenant_{$variant}";
    }

    /**
     * Gera dados sintéticos de usuário admin para testes.
     *
     * @param  string  $tenantVariant  'a' ou 'b'
     * @return array<string, mixed>
     */
    public static function adminUser(string $tenantVariant = 'a'): array
    {
        return [
            'name'     => "Admin Teste Tenant " . strtoupper($tenantVariant),
            'email'    => "admin.test.{$tenantVariant}@lawfirm-test.invalid",
            'password' => bcrypt('Test@Password123!'),
        ];
    }

    /**
     * Gera dados sintéticos de lead para testes.
     *
     * @param  string  $variant  sufixo para diferenciação
     * @return array<string, mixed>
     */
    public static function lead(string $variant = '001'): array
    {
        return [
            'title'       => "Lead de Teste #{$variant}",
            'description' => "Descrição sintética de lead para testes automatizados. Variante: {$variant}.",
            'status'      => 'new',
        ];
    }

    /**
     * Gera um UUID determinístico baseado em um namespace de teste.
     */
    public static function deterministicUuid(string $name): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Gera dados sintéticos de subscription do MotherShip para testes.
     *
     * @param  string  $tenantId
     * @param  array<string>  $activeModules
     * @return array<string, mixed>
     */
    public static function subscription(
        string $tenantId = 'tenant_a',
        array $activeModules = ['CHATWOOT', 'AI']
    ): array {
        return [
            'tenant_id'        => $tenantId,
            'plan'             => 'professional_test',
            'active_modules'   => $activeModules,
            'suitecoin_balance' => 1000.00,
            'status'           => 'active',
        ];
    }
}
