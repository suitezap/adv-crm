# 📍 CURRENT.md — Status Operacional Atual

> Ponto único de entrada para reconhecimento rápido do estado atual do projeto.

---

## 1. Onde estamos?
A **Fase 0 (Governança, Baseline e Hardening Documental)** foi concluída. Entregues e mergeadas na `2.1`: **`FIN-COBRANCAS-001`**, **`PRIV-AUDIT-001`**, **`DOC-001`/`GAP-001`/`CI-001`/`REPO-HYGIENE-001`/`REPO-HYGIENE-002`**, e **`SEC-HARD-002` Ondas 1-3**.
Em **2026-09-15**, entregue **`DOCKER-002`** (bump v3.56.0, migration idempotente de `chatwoot_conversation_id` em `leads`, build/push das imagens `suitezap/lawfirm:3.56.0` e `latest` no Docker Hub) e **`N8N-001`** (correção de query/expressão SQL no nó `Add Coluna Chatwoot` do workflow n8n de Triagem/Lead Tool para `$json.id`, reativação de nó de saldo SuiteCoins, e limpeza segura de dados de teste em `advdf2g` online).

---

## 2. Objetivo Atual
Follow-ups documentados em `.ai/TASKS.md`: `DOC-001`, `GAP-001`, `KAN-001` (restante), `CI-001`, cadeia QA (`QA-DATA-001` → `QA-HARNESS-001` → `QA-JUR-001`), `SEC-HARD-002` (cobertura `@can`), `OPS-WEBHOOK-001` (cadastrar segredos em produção), `REPO-HYGIENE-001` (gitignore `C*` + owners), `SKILLS-UPD-001` (IN_PROGRESS: AAS v13.5.0 → v17.3.0, estratégia A). Data-plane de QA replicável localmente via `quality/runbooks/local-qa-dataplane.md`; segredos de webhook em `quality/runbooks/webhook-secrets.md`.

---

## 3. O que está funcionando?
- **Governança Multiagente:** SSOT em `.ai/`, protocolo de locks com heartbeat (`last_checkpoint_at`), matriz de agentes formalizada (`AGENTS_REGISTRY.md`), camada de descoberta indexada (`LOG_INDEX.md`).
- **Shared Skills:** 8 SOPs padronizados em `.agents/skills/`.
- **Qualidade e Testes:** 47 testes catalogados em `quality/TEST_CATALOG.yaml` (19 `active` v3.55.1/v3.56.0), validador documental `validate_test_docs.py` passing com 0 erros.
- **Isolamento e Segurança:** `tenant_id` obrigatório nos domínios (ADR `ARCHITECTURE.md §4.91` e §4.92); webhooks fail-closed; migrations de tenant idempotentes.
- **Workflow n8n de Triagem:** Corrigido nó `Add Coluna Chatwoot` executando query dinâmica com `$json.id` sobre o schema do tenant (`ALTER TABLE \`{{ $json.id }}\`.\`leads\` ADD COLUMN IF NOT EXISTS \`chatwoot_conversation_id\` INT NULL DEFAULT NULL;`) e versão ativa publicada.

---

## 4. O que está bloqueado ou pendente?
- **`QA-DATA-001`** foi **DESBLOQUEADA**: com a conclusão de `DOCKER-001`/`DOCKER-002` e a publicação de `suitezap/lawfirm:3.56.0` e `latest` (com higiene estrita e Laravel operacional), o runner de QA pode prosseguir com fixtures e dados multi-tenant.
- **`DOCKER-002`** concluída (**DONE**). Imagem `suitezap/lawfirm:3.56.0` publicada no Docker Hub.
- **`DOCKER-003`** concluída (**DONE**). Imagem `suitezap/lawfirm:3.56.1`, `suitezap/lawfirm:v3.56.1` e `latest` (digest `sha256:0401da4e36bf9cc833304a088a13e733a355d3146fb473ac1dd83e7d7f75d7e0`) publicada no Docker Hub com higiene estrita.
- A tarefa `KAN-001` segue `BLOCKED` (aguardando manutenção de tags).

---

## 5. Quem está trabalhando?
- **Antigravity (Orchestrator):** Concluiu `DOCKER-003` (bump v3.56.1, build e push Docker Hub com higiene estrita), suporte ao workflow n8n (`N8N-001`), triggers EAV e Chatwoot Lead Chat modal.
- **Hermes (QA Architect):** VPS reconfigurada — aguarda revalidação de ambiente QA.
- **OpenCode (Implementer):** Entregou `DOCKER-002` (bump v3.56.0 + fix migration idempotente) e hygiene. `SKILLS-UPD-001` `VERIFIED`. `SKILLS-UPD-002` (`IMPLEMENTED_NOT_VERIFIED`: 3 skills UI v17.3.0 instaladas, 19/19 verificados; pendente smoke test na IDE).

---

## 6. Próximo Passo Seguro
Atualizar o serviço na VPS para a versão `suitezap/lawfirm:v3.56.1` e seguir com validações operacionais.
---
*2026-09-18: state refreshed after DOCKER-003 (v3.56.1).*
