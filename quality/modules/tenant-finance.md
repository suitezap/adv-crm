# Cobranças do Escritório — Asaas (TenantFinance)

**Última Revisão:** 2026-09-09 · **Versão:** v3.55.1 · **Change:** `financial-cobrancas-isolation-fix`

## Escopo
Cobranças emitidas pelo escritório aos clientes via Asaas (`tenant_invoices`, `tenant_asaas_customers`, `tenant_asaas_settings`). Gate de módulo `TENANT_FINANCE` (`CheckTenantFinanceModule`) + permissões `lawfirm.financeiro.cobrancas.*`.

## Invariantes
- `index` processa via `TenantInvoiceDataGrid` (query `tenant_invoices` + `tenant_id`); nunca `FinancialDataGrid`.
- `GET /cobrancas/api/customers/{person_id}` declarada antes de `GET /cobrancas/{id}` + `whereNumber('id')`.
- Frontend usa rotas nomeadas (`cancel`/`resend` REPLACE_ID); nenhum `fetch()` hardcoded para `/financial/*`.
- `TenantAsaasService::getSettings()` escopado por sessão; webhook público resolve settings pelo `tenant_id` da invoice e valida `asaas-access-token` desse tenant.
- Controllers exigem `bouncer()`: `index/show/api → cobrancas.view`, `store → create`, `resend → edit`, `cancel → delete`.

## Testes
| ID | Nome | Status |
|---|---|---|
| `TENANT-FIN-001` | Criar/visualizar/cancelar/reenviar cobrança Asaas do próprio tenant | `active` |
| `TENANT-SEC-006` | Cross-tenant em cobranças bloqueado (403/404) + webhook com token de outro tenant → 401 | `active` |
