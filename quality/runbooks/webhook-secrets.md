# Runbook: Segredos de Webhooks (webhook-secrets.md)

Todos os webhooks públicos operam **fail-closed**: sem segredo válido, o evento
é negado e nada é alterado. Este guia diz onde cadastrar cada segredo.

| Webhook | Segredo esperado | Onde cadastrar (operador) |
|---|---|---|
| `POST api/webhooks/asaas` | Header `asaas-access-token` | Painel Asaas → Integrações → Mecanismos de segurança **+** `meta_data.webhook_token` do nó Asaas (MotherShip) |
| `POST api/webhooks/tenant-asaas` | Header `asaas-access-token` | Painel Asaas do escritório **+** campo `webhook_token` em Cobranças → Configurações |
| `POST api/webhooks/escavador` | Header `Authorization` (Bearer token) ou `X-Escavador-Token` | Painel Escavador (Token de callback) **+** `meta_data.webhook_token` do nó Escavador 'LawFimr V1 e V2' (MotherShip) |
| `POST api/webhooks/whatsapp-messenger/{tenant}` | Header `X-Webhook-Token` ou `?token=` | `meta_data.webhook_secret` do nó Evolution (MotherShip) **+** URL/headers do webhook na Evolution |
| `POST api/webhooks/chatwoot` | Header `X-Chatwoot-Signature` (HMAC-SHA1) | `chatwoot_webhook_token` do tenant (MotherShip) |
| `POST api/lawfirm/saas/webhook` | Header `X-SAAS-TOKEN` | `app_config.api_secret` (MotherShip) |

## Notas operacionais

- Sem `webhook_token` no nó Asaas, o webhook da plataforma **nega tudo**
  (comportamento intencional pós-PRIV-AUDIT-001) — se o financeiro parar de
  conciliar, confira o token antes de qualquer outra hipótese.
- SAC auto-login: `meta_data.sac_password` do nó Chatwoot; ausente = login manual.
- Nunca registrar valores de segredos em logs, handoffs ou `.ai/` — usar aliases.

- **Escavador (WEBHOOK-SEC-002)**: Sem webhook_token no nó Escavador ('LawFimr V1 e V2') do MotherShip, o middleware VerifyEscavadorWebhook opera **fail-closed** e rejeita com HTTP 401 qualquer chamada. Estornos de suitecoin_balance só acontecem se o webhook passar na autenticação, o xternal_id pertencer ao tenant e estiver em estado pending.
- **Asaas (WEBHOOK-SEC-003 / Decisão DSK7)**: O endpoint POST api/webhooks/asaas opera fail-closed (nega evento sem token), mas retorna HTTP 200 {"success": false, "message": "Unauthorized"} para evitar enumeração de rotas e retentativas excessivas do gateway. A decisão de migrar a resposta para HTTP 401 foi registrada para deliberação do DSK7.
