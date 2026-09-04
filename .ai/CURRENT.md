# 📍 CURRENT.md — Status Operacional Atual

> Ponto único de entrada para reconhecimento rápido do estado atual do projeto.

---

## 1. Onde estamos?
A **Fase 0 (Governança, Baseline e Hardening Documental)** foi concluída com sucesso. O baseline canônico com auditoria de versões está registrado em `.ai/BASELINE.md`, as diretrizes de imagem Docker e locks com checkpoint em `.ai/DECISIONS.md` e `.ai/locks/README.md`, e o contrato de handoff para a tarefa `HERMES-001` está formalizado em `.ai/handoffs/HANDOFF-HERMES-001.md`.

---

## 2. Objetivo Atual
A tarefa **`QA-ENV-001`** (provisionamento e hardening do ambiente isolado de QA na VPS) foi concluída. O acesso Docker foi remediado (grupo `docker`), `.stignore` aplicado para exclusão de segredos/artefatos, e o data-plane de QA (mysql-test, mothership-db-test, redis-test, mock-server) foi provisionado e verificado saudável com os bancos de teste `tenant_a_test`, `tenant_b_test` e `mothership_test`. Resultado homologável em `.ai/handoffs/RESULT-QA-ENV-001.md`. Próximo elo: **`QA-DATA-001`**, ainda **BLOCKED** aguardando a imagem `candidate-local` (DOCKER-001).

---

## 3. O que está funcionando?
- **Governança Multiagente:** SSOT em `.ai/`, protocolo de locks com heartbeat (`last_checkpoint_at`), matriz de agentes formalizada (`AGENTS_REGISTRY.md`), camada de descoberta indexada (`LOG_INDEX.md`).
- **Shared Skills:** 8 SOPs padronizados em `.agents/skills/`.
- **Qualidade e Testes:** 35 testes catalogados em `quality/TEST_CATALOG.yaml`, validador documental `validate_test_docs.py` passing com 0 erros.
- **Isolamento e Segurança:** 0 arquivos de conflito Syncthing detectados no workspace.

---

## 4. O que está bloqueado ou pendente?
- **`QA-ENV-001`** (provisionamento QA) está **DONE** — resultado em `.ai/handoffs/RESULT-QA-ENV-001.md`. Data-plane de QA provisionado (mysql-test, mothership-db-test, redis-test, mock-server — todos healthy) com os bancos `tenant_a_test`, `tenant_b_test`, `mothership_test` presentes.
- **`QA-DATA-001`** permanece **BLOCKED** (`QA-ENV-001`): os bancos e o ambiente Docker estão utilizáveis e os guards de teste (`APP_ENV=testing`, `TEST_ENVIRONMENT_ACK`) são efetivos, mas a camada de aplicação E2E (`app-tenant-a/b`, workers, playwright) depende da imagem `suitezap/lawfirm:candidate-local`, que é produzida por `DOCKER-001` — ainda pendente. Liberar `QA-DATA-001` quando a imagem estiver disponível ou quando o Orchestrator escopar a tarefa apenas à camada de dados.
- As tarefas `DOCKER-001` (higiene da imagem de produção) e `DOC-001` (changelog raiz) seguem `TODO`.

---

## 5. Quem está trabalhando?
- **Antigravity (Orchestrator):** Finalizou `GOV-001`, `GOV-002` e o hardening documental.
- **Hermes (QA Architect):** Concluiu a auditoria `HERMES-001` (DONE) e provisionou `QA-ENV-001` (DONE) — acesso Docker remediado, `.stignore` aplicado, data-plane de QA provisionado. Resultado em `.ai/handoffs/RESULT-QA-ENV-001.md`.

---

## 6. Próximo Passo Seguro
O Orchestrator homologar o resultado de `QA-ENV-001` (`.ai/handoffs/RESULT-QA-ENV-001.md`) e liberar **`QA-DATA-001`** quando a imagem `suitezap/lawfirm:candidate-local` estiver disponível (produzida por `DOCKER-001`) — ou escopado corretamente apenas à camada de dados.
---
*AUDIT-2026-08-31: state refreshed by Hermes after QA-ENV-001 completion.*
