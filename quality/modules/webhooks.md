# Webhooks Públicos — Autenticação Fail-Closed (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-09 · **Change:** `priv-audit-platform` (Onda 1b)

## Escopo
`api/webhooks/{asaas,tenant-asaas,escavador,whatsapp-messenger,chatwoot}`.

## Invariantes
- Ausência de token = negação (nunca bypass). Resposta 200 obscura no Asaas plataforma; 400/401 nos demais.
- Asaas plataforma: fim do mint ROTA 4 (sem `externalReference`/pedido, sem crédito).
- TenantAsaas: settings do tenant da invoice; Escavador: mutação só no tenant do registro; Whatsapp: tenant existe + instância do payload pertence ao tenant; ACK e upsert escopados.
- Chatwoot: HMAC + inbox (inalterado, já correto).

## Testes
| ID | Nome | Status |
|---|---|---|
| `WEBHOOK-SEC-001` | Forjados negados sem mutação | `active` |
