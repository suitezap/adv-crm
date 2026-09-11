# INC-2026-09-09 — 500 no dashboard local após escopos tenant_id

**Severidade:** Alta (indisponibilidade local) · **Status:** Resolvido · **Owner:** OpenCode

## Sintoma
`GET /admin/dashboard` 500. Log: `SQLSTATE[42S22] Unknown column 'processos.tenant_id'` (e `tenant_asaas_settings.tenant_id` às 11:22).

## Causa raiz
Escopos globais (`TenantScope`/`BelongsToTenant`) e filtros `tenant_id` entraram no código (FIN-COBRANCAS-001 + PRIV-AUDIT-001) enquanto as migrations `2026_09_09_000001–000009` seguiam **Pending** no banco local. Código à frente do schema = toda query escopada quebrava.

## Correção
`php artisan migrate --force` local: 9/9 DONE. Verificado via tinker (6/6 colunas OK + queries escopadas sem erro).

## Recorrência 2026-09-10 — MySQL fora do ar pós-restart
Sintoma idêntico (500 + `SQLSTATE[HY000] [2002] refused`), causa diferente: Laragon/MySQL não subiu no boot (21:36). Dados intactos em `C:\laragon\data\mysql-8.4`. Correção: `mysqld --defaults-file=my.ini` manual. Suíte completa revalidada: **117/117**. Prevenção: colocar MySQL do Laragon para iniciar com o Windows ou documentar partida manual pós-restart.

## Lição / prevenção
- Deploy deve rodar migrations **antes** de ativar código com escopo (ordMA obrigatória no runbook/CI).
- Nunca assumir que o banco local acompanhou o branch — `migrate:status` entra no checklist de retomada pós-restart/branch.
- Tasks afetadas: FIN-COBRANCAS-001, PRIV-AUDIT-001.
