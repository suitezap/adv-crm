# 📍 CURRENT.md — Status Operacional Atual

> Ponto único de entrada para reconhecimento rápido do estado atual do projeto.

---

## 1. Onde estamos?
A **Fase 0 (Governança, Baseline e Hardening Documental)** foi concluída. Depois dela, foram entregues e mergeadas na `2.1`: **`FIN-COBRANCAS-001`** (isolamento `tenant_id` + fix Cobranças/Lançamentos, VERIFIED), **`PRIV-AUDIT-001`** (gates de perfil + webhooks fail-closed em Ondas 1–3, DONE), **`DOC-001`/`GAP-001`/`CI-001`/`REPO-HYGIENE-001`** (DONE) — PRs #1 e #2 no fork, suíte Pest **119/119** em data-plane local. **`SEC-HARD-002` Ondas 1-3** (28 blades + 21 gates em `EscavadorController` + `ESC-SEC-001` estendido, VERIFIED, Pest 121/121, 2026-09-12). Baseline em `.ai/BASELINE.md`, decisões em `.ai/DECISIONS.md`, incidente do 500 em `.ai/incidents/INC-2026-09-09-tenant-scope-500.md`.

---

## 2. Objetivo Atual
Follow-ups documentados em `.ai/TASKS.md`: `DOC-001`, `GAP-001`, `KAN-001` (restante), `CI-001`, cadeia QA (`QA-DATA-001` → `QA-HARNESS-001` → `QA-JUR-001`), `SEC-HARD-002` (cobertura `@can`), `OPS-WEBHOOK-001` (cadastrar segredos em produção), `REPO-HYGIENE-001 (gitignore `C*` + owners). Data-plane de QA replicável localmente via `quality/runbooks/local-qa-dataplane.md`; segredos de webhook em `quality/runbooks/webhook-secrets.md`.

---

## 3. O que está funcionando?
- **Governança Multiagente:** SSOT em `.ai/`, protocolo de locks com heartbeat (`last_checkpoint_at`), matriz de agentes formalizada (`AGENTS_REGISTRY.md`), camada de descoberta indexada (`LOG_INDEX.md`).
- **Shared Skills:** 8 SOPs padronizados em `.agents/skills/`.
- **Qualidade e Testes:** 47 testes catalogados em `quality/TEST_CATALOG.yaml` (19 `active` v3.55.1), validador documental `validate_test_docs.py` passing com 0 erros.
- **Isolamento e Segurança:** `tenant_id` obrigatório nos domínios (ADR `ARCHITECTURE.md §4.91`); webhooks fail-closed; 0 arquivos de conflito Syncthing detectados no workspace.

---

## 4. O que está bloqueado ou pendente?
- **`QA-DATA-001`** foi **DESBLOQUEADA**: com a conclusão de `DOCKER-001` e a publicação de `suitezap/lawfirm:3.55.1` e `latest` (com higiene estrita e Laravel operacional), o runner de QA pode prosseguir com fixtures e dados multi-tenant.
- **`DOCKER-001`** foi concluída com status **DONE** por Antigravity. Imagens `suitezap/lawfirm:3.55.1` e `suitezap/lawfirm:latest` publicadas no Docker Hub com 100% de conformidade com `AGENTS.md §6` (exclusão de `tests/`, `quality/`, `.ai/`, `.agents/`, `.github/`, `docker/testing/`, `reports/`, `coverage/`, `test-results/`, `playwright-report/`).
- A tarefa `DOC-001` (changelog raiz) segue `TODO`.

---

## 5. Quem está trabalhando?
- **Antigravity (Orchestrator):** Finalizou `GOV-001`, `GOV-002`, hardening documental e tracks Chatwoot/Kanban (`OS-001` DONE).
- **Hermes (QA Architect):** Concluiu `HERMES-001` e `QA-ENV-001` (resultado em `.ai/handoffs/RESULT-QA-ENV-001.md`).
- **OpenCode (Implementer):** Entregou `FIN-COBRANCAS-001` (VERIFIED), `PRIV-AUDIT-001` (DONE) e `SEC-HARD-002` Onda 1 (VERIFIED, 119/119) — aguardando aprovação de commit.

---

## 6. Próximo Passo Seguro
Atacar os follow-ups por prioridade operacional: `OPS-WEBHOOK-001` (sem tokens, conciliação nega), `DOC-001`/`GAP-001`, depois `SEC-HARD-002` e cadeia QA. MySQL do Laragon não sobe no boot — ver incidente.
---
*2026-09-11: state refreshed by OpenCode after FIN/PRIV merge (PRs #1 e #2) e suíte 117/117.*
