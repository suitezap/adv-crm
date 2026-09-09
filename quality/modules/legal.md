# Legal — Controle de Acesso (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-09 · **Change:** `priv-audit-platform` (Onda 1e + pré-req)

## Escopo
Casos e Processos: gates de perfil + `tenant_id` em `processos` (`BelongsToTenant`).

## Invariantes
- Todo método sensível de `CasoController`/`ProcessoController` exige `lawfirm.casos|processos.view/create/edit/delete` (401 sem).
- `link/unlink`, `search*`, `requestRegistration/Documents`, `massDestroy` com gates.
- `show` de Processo mantém checagem `individual vs global` além do gate.

## Testes
| ID | Nome | Status |
|---|---|---|
| `LEGAL-SEC-001` | 401 sem permissão em casos/processos/GED | `implemented_unverified` |
