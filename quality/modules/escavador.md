# Escavador — Isolamento e Privilégios (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-12 · **Change:** `sec-hard-002-onda-3`

## Escopo
Consultas, monitoramentos, histórico, certificados e webhook do Escavador. Isolamento por `tenant_id` + gates `lawfirm.escavador.view/create/certs/certs.manage`.

## Invariantes
- DataGrids filtram `tenant_id`; `EscavadorRequest`/`Monitoramento` usam `BelongsToTenant`.
- `History show` e `Monitoramento toggle` exigem permissão; toggle não reativa alertas (§8).
- `EscavadorController` (21 métodos): leituras exigem `view`, consultas/serviços pagos e sync exigem `create`, certificados exigem `certs` (leitura) / `certs.manage` (upload/remoção).
- Webhook resolve pelo registro e só muta dentro do tenant; `EscavadorProcesso` atualizado com `tenant_id` do request; disparo WhatsApp de monitoramento retido (§8).
- Rota `lawfirm.escavador.monitoramentos` (lista V1) sombreada pela `.index` — método morto, sem rota alcançável.

## Testes
| ID | Nome | Status |
|---|---|---|
| `ESC-SEC-001` | Tenant-scoped + gates de perfil | `active` |
| `WEBHOOK-SEC-001` | Webhook nega forjados | `active` |
