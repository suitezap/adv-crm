# Handoff — Pausa FIN-COBRANCAS-001 (restart equipamento)

**Data:** 2026-09-09 · **Owner:** OpenCode · **Lock:** `.ai/locks/FIN-COBRANCAS-001.lock.yaml` (ACTIVE, checkpoint 14:00Z)

## Onde paramos
- `FIN-COBRANCAS-001` em `IMPLEMENTED_NOT_VERIFIED`. Fases A–D + complemento FIN-SEC-001 implementados, **nada commitado** (branch `feature/ajustes-ui-e-melhorias-visualizacao-de-menus` tem sujeira pré-existente — commit deve ser atômico, só escopo FIN).
- Validação estática OK: `php -l` (22 arquivos), `validate_test_docs.py` 0 erros, matriz 40 testes, 11 testes FIN descobertos na coleta. Execução Pest bloqueada (sem `mysql-test` no dev).
- Novo TODO criado: `PRIV-AUDIT-001` — levar o padrão FIN-SEC-001 (gates por perfil, segredos fora de views, individual vs global) para a plataforma inteira, por decisão do usuário.

## Para retomar (diga "continue de onde paramos")
1. Verificar lock `FIN-COBRANCAS-001` + `git status` (conferir se arquivos do escopo seguem intactos).
2. Pendências FIN: backfill `tenant_id` + migration `NOT NULL`, execução foreground no data-plane QA, commit atômico.
3. Em seguida: planejar `PRIV-AUDIT-001` (auditoria domínio a domínio dos gates `bouncer()` e exposição de dados por perfil).

## Arquivos do escopo (não commitar mais nada junto)
`SaaS/Scopes/`, `SaaS/Concerns/`, `Financial/`, `TenantFinance/`, `Http/Routes/admin-tenant-finance.php`, `Config/acl.php`, `FinancialController.php`, views `TenantFinance/`, migrations `2026_09_09_00000*`, `quality/` (TEST_CATALOG, modules/financial.md, tenant-finance.md, CHANGELOG, COVERAGE_MATRIX), `tests/Feature/Financial/`, `tests/Feature/TenantFinance/`, `tests/Security/FinancialTenantIsolationTest.php`, `openspec/changes/financial-cobrancas-isolation-fix/`.
