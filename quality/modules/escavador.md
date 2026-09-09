# Escavador — Isolamento e Privilégios (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-09 · **Change:** `priv-audit-platform` (Onda 1a/1b)

## Escopo
Consultas, monitoramentos, histórico e webhook do Escavador. Isolamento por `tenant_id` + gates `lawfirm.escavador.view/create/certs`.

## Invariantes
- DataGrids filtram `tenant_id`; `EscavadorRequest`/`Monitoramento` usam `BelongsToTenant`.
- `History show` e `Monitoramento toggle` exigem permissão; toggle não reativa alertas (§8).
- Webhook resolve pelo registro e só muta dentro do tenant; `EscavadorProcesso` atualizado com `tenant_id` do request; disparo WhatsApp de monitoramento retido (§8).

## Testes
| ID | Nome | Status |
|---|---|---|
| `ESC-SEC-001` | Tenant-scoped + gates de perfil | `implemented_unverified` |
| `WEBHOOK-SEC-001` | Webhook nega forjados | `implemented_unverified` |
