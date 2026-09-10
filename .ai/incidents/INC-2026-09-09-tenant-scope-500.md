# INC-2026-09-09 — 500 no dashboard local após escopos tenant_id

**Severidade:** Alta (indisponibilidade local) · **Status:** Resolvido · **Owner:** OpenCode

## Sintoma
`GET /admin/dashboard` 500. Log: `SQLSTATE[42S22] Unknown column 'processos.tenant_id'` (e `tenant_asaas_settings.tenant_id` às 11:22).

## Causa raiz
Escopos globais (`TenantScope`/`BelongsToTenant`) e filtros `tenant_id` entraram no código (FIN-COBRANCAS-001 + PRIV-AUDIT-001) enquanto as migrations `2026_09_09_000001–000009` seguiam **Pending** no banco local. Código à frente do schema = toda query escopada quebrava.

## Correção
`php artisan migrate --force` local: 9/9 DONE. Verificado via tinker (6/6 colunas OK + queries escopadas sem erro).

## Lição / prevenção
- Deploy deve rodar migrations **antes** de ativar código com escopo (ordMA obrigatória no runbook/CI).
- Nunca assumir que o banco local acompanhou o branch — `migrate:status` entra no checklist de retomada pós-restart/branch.
- Tasks afetadas: FIN-COBRANCAS-001, PRIV-AUDIT-001.
