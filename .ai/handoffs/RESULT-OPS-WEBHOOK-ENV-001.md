# RESULT — OPS-WEBHOOK-ENV-001

TASK:
OPS-WEBHOOK-ENV-001

AGENT:
ANTIGRAVITY

ROLE:
Security QA / Infrastructure

WORKSPACE:
DSK7 — d:\Z.Hermes\www\adv-crm — branch 2.1 @ b7e2e087 (revalidado em 2026-10-03)

---

ENTRY GATES:

LOCK:
`.ai/locks/OPS-WEBHOOK-ENV-001.lock.yaml` acquired (base commit b7e2e087), status IN_PROGRESS -> IMPLEMENTED_NOT_VERIFIED.

---

## 1. DOCKER COMPOSE PS (todos os serviços)

| NOME                          | IMAGE                               | STATUS                    |
|-------------------------------|-------------------------------------|---------------------------|
| adv-crm-app-tenant-a-1        | suitezap/lawfirm:candidate-local    | Up (healthy) — 80/tcp     |
| adv-crm-app-tenant-b-1        | suitezap/lawfirm:candidate-local    | Up (healthy) — 80/tcp     |
| adv-crm-mock-server-1         | wiremock/wiremock:latest            | Up (healthy) — 8080/tcp   |
| adv-crm-mothership-db-test-1  | mysql:8.0                           | Up (healthy) — 3306/tcp   |
| adv-crm-mysql-test-1          | mysql:8.0                           | Up (healthy) — 3306/tcp   |
| adv-crm-redis-test-1          | redis:7.0-alpine                    | Up (healthy) — 6379/tcp   |
| adv-crm-worker-tenant-a-1     | suitezap/lawfirm:candidate-local    | Up — 80/tcp               |
| adv-crm-worker-tenant-b-1     | suitezap/lawfirm:candidate-local    | Up — 80/tcp               |

Nota: playwright-test runner (Exited 0) — executou normalmente e encerrou.
php-tests image construída a partir do Dockerfile.php-tests (cache parcial).

---

## 2. GUARD DE ISOLAMENTO

Executado em app-tenant-a:

```
TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST
DB_DATABASE=tenant_a_test
DB_TEST_TENANT_A_DATABASE=tenant_a_test
APP_ENV=testing
```

Executado em app-tenant-b:

```
TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST
DB_DATABASE=tenant_b_test
DB_TEST_TENANT_B_DATABASE=tenant_b_test
APP_ENV=testing
```

✅ ISOLAMENTO CONFIRMADO — todos os bancos com sufixo `_test`. Nenhum banco sem `_test` foi usado.

---

## 3. TABELA FAIL-CLOSED DOS 5 ENDPOINTS

| Endpoint                       | Sem token | Token errado | Token correto |
|-------------------------------|-----------|--------------|---------------|
| `POST api/webhooks/chatwoot`  | **200*** | **200***      | **200***       |
| `POST api/webhooks/asaas`     | **200*** | **200***      | 200           |
| `POST api/webhooks/tenant-asaas` | 401    | 401           | 200           |
| `POST api/webhooks/whatsapp-messenger/{id}` | 401 | 401     | 200           |
| `POST api/webhooks/escavador` | **401** | **401**       | **200**        |

*Chatwoot: retorna 200 "ignored" quando tenant não tem configuração Chatwoot no mothership_test
(sem nó ativo com chatwoot_node_id configurado para o tenant). Comportamento por design.
*Asaas SaaS: retorna 200 com {"success":false} por design documentado (WEBHOOK-SEC-003 — decisão DSK7 pendente).

### Detalhes dos testes Escavador (endpoint corrigido por WEBHOOK-SEC-002):

- **Sem header** → `401` ✅ fail-closed
- **Header errado** (`Bearer WRONG`) → `401` ✅ fail-closed
- **Header correto** (`Bearer escavador-secret-token-xyz` via mothership_test node) → `200` ✅

---

## 4. VERIFICAÇÃO DE SALDO — nenhum estorno não-autorizado

- `SELECT asaas_payment_id, status FROM tenant_invoices WHERE id=999;`
  - **Resultado**: `pay_test_999 | RECEIVED` — (atualizado somente pela requisição autenticada)
- Banco `tenant_a_test` não foi modificado pelas requisições com 401 no tenant-asaas
- `EscavadorRequest.suitecoin_balance` não foi incrementado sem autenticação

---

## 5. TESTES PEST — WEBHOOK-SEC-002

- `WebhookAuthTest` (Feature): **5/5 PASS** (10 assertions)
- `VerifyEscavadorWebhookTest` (Unit): **6/6 PASS** (17 assertions)
- Total: **11/11 tests PASS**

---

## 6. AJUSTES DE TESTE REALIZADOS

- `tests/Feature/Webhooks/WebhookAuthTest.php`: adicionado `updateOrCreate` do InfrastructureNode Escavador
  no teste `escavador_webhook_with_unknown_external_id_changes_nothing` — necessário porque
  `EscavadorService::getWebhookToken()` consulta o mothership_test (não há `config()` fallback confiável
  no ambiente Docker sem DB).
- `tests/Unit/VerifyEscavadorWebhookTest.php`: `beforeEach` agora deleta nós Escavador do mothership_test
  antes de cada teste unitário para garantir isolamento.

---

## 7. OBSERVAÇÃO SOBRE .dockerignore

O build do `playwright-test` requer os diretórios `tests/e2e/` e `docker/testing/` no contexto de build.
A CI (`.github/workflows/lawfirm-ci.yml:132`) já faz `sed` temporário no `.dockerignore` antes de buildar
a imagem de teste. Localmente, seguimos o mesmo padrão: removemos as linhas temporariamente, buildamos
e restauramos com `git checkout .dockerignore`.

---

## 8. CONFIRMAÇÃO EXPLÍCITA

Nenhum banco sem sufixo `_test` foi usado durante toda a sessão.
Bancos verificados: `tenant_a_test`, `tenant_b_test`, `mothership_test`.
Nenhuma operação de escrita em dados de produção.

---

*Entregue pelo ANTIGRAVITY em 2026-10-02 e revalidado contra stack ao vivo em 2026-10-03 — base 2.1 @ b7e2e087. Task OPS-WEBHOOK-ENV-001 (IMPLEMENTED_NOT_VERIFIED).*
