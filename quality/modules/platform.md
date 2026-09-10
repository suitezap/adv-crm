# Plataforma — Gates de Perfil SaaS/AI/Whatsapp (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-09 · **Change:** `priv-audit-platform` (Ondas 2-3)

## Escopo
Assinatura/checkout/faturamento (`saas.manage`), assistentes IA (`assistants.view/execute` + módulo + ownership de lead), modelos de documento (`modelos.*`), conexão Whatsapp (`whatsapp.manage`), `SaasOrdersDataGrid` por usuário.

## Invariantes
- Checkout/billing/pedidos/extrato exigem `saas.manage` (401 sem).
- Templates IA resolvidos por tenant + módulo (`findAccessibleTemplate`); `processForLead`/triagem com ownership do lead; histórico com ownership.
- `MothershipTemplate::upsert` valida `tenant_id` existente; `SaasWebhook` com `hash_equals` fail-closed.
- SAC sem senha no código (`sac_password` no nó); portal com token expirável + whitelist + upload tipado.

## Testes
| ID | Nome | Status |
|---|---|---|
| `PLAT-SEC-002` | Gates SaaS/AI/modelos/Whatsapp | `active` |
| `PORTAL-SEC-001` | Portal token/whitelist/upload | `active` |
