# Gestão Financeira — Lançamentos (Financial)

**Última Revisão:** 2026-09-09 · **Versão:** v3.55.1 · **Change:** `financial-cobrancas-isolation-fix`

## Escopo
Lançamentos manuais do escritório (`law_financials`): receitas, despesas, baixa rápida (quick-pay), recibo PDF e aba Financeiro no Processo. Isolamento obrigatório por `tenant_id` (AGENTS.md §7, banco `mysql` compartilhado).

## Invariantes
- Toda query filtra `tenant_id` da sessão (`BelongsToTenant`/`TenantScope`); DataGrid adiciona `where law_financials.tenant_id` + `bouncer()->getAuthorizedUserIds()`.
- `quickPay`/`update`/`delete` via `findOrFail` escopado → ID forjado cross-tenant resulta 403/404.
- `billing_type` de escrita: `BOLETO|PIX|CREDIT_CARD`; `UNDEFINED` é legado leitura-only.
- Filtro de responsável usa `User::whereIn(authorized)` quando houver escopo.

## Testes
| ID | Nome | Status |
|---|---|---|
| `FIN-FEATURE-001` | CRUD de lançamento tenant-scoped (criar, baixar, recibo) | `implemented_unverified` |
| `FIN-FEATURE-002` | Quick-pay com permissão `lawfirm.financeiro.edit` e 401 sem permissão | `implemented_unverified` |
| `FIN-SEC-001` | Visibilidade por perfil: 401 sem permissão, leitura sem escrita, credenciais mascaradas, `individual` vs `global` | `implemented_unverified` |
| `TENANT-SEC-006` | Tenant A não acessa `law_financials`/`tenant_invoices` do Tenant B (403/404) | `implemented_unverified` |

## Permissões por usuário
- Dashboard exige `lawfirm.financeiro.view` (antes sem gate — qualquer autenticado via KPIs).
- Settings Asaas exige `lawfirm.financeiro.cobrancas.settings` (antes sem gate — leitura e Sobrescrita de `api_key`/`webhook_token`).
- Credenciais nunca renderizadas: inputs `password` vazios + placeholder de manutenção; save em branco preserva.
- `view_permission=individual` restringe DataGrids ao próprio `user_id` além do `tenant_id`.
