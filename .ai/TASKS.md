# 📋 TASKS.md — Quadro Oficial de Tarefas Multiagente

> Status permitidos: `TODO` | `READY` | `IN_PROGRESS` | `IMPLEMENTED_NOT_VERIFIED` | `VERIFIED` | `DONE` | `BLOCKED`

---

| Task ID | Categoria | Status | Owner | Bloqueada Por | Objetivo |
|---|---|---|---|---|---|
| `GOV-001` | Governança | DONE | Antigravity | - | Registrar baseline canônico completo do projeto LawFirm CRM. |
| `GOV-002` | Governança | DONE | Antigravity | - | Implantar SSOT multiagente, shared skills, locks, regras Syncthing e handoff HERMES-001. |
| `HERMES-001` | QA / Infra | DONE | Hermes | - | Auditoria técnica e diagnóstico da VPS, workspace e infraestrutura de QA. Resultado: `.ai/handoffs/RESULT-HERMES-001.md`. |
| `QA-ENV-001` | QA / Setup | DONE | Hermes | - | Provisionamento e configuração do ambiente local de QA na VPS. Resultado: `.ai/handoffs/RESULT-QA-ENV-001.md`. |
| `QA-DATA-001` | QA / Fixtures | BLOCKED | Hermes | `QA-ENV-001` | Estruturação de dados e fixtures de teste multi-tenant. |
| `QA-HARNESS-001` | QA / Harness | BLOCKED | Hermes | `QA-DATA-001` | Framework e runners para execução contínua de testes E2E/Playwright. |
| `QA-JUR-001` | QA / Domínio | BLOCKED | Hermes | `QA-HARNESS-001` | Implementação de testes funcionais do domínio Jurídico (Kanban, Casos, Processos). |
| `DOCKER-001` | Infra / Build | DONE | Antigravity | - | Higienização da imagem de produção `suitezap/lawfirm` (remoção de `tests/`, `quality/`, `.ai/`, etc.) e publicação no Docker Hub. |
| `DOC-001` | Documentação | TODO | OpenCode | - | Consolidação da versão no `CHANGELOG.md` raiz para resolver `BASELINE_VERSION_MISMATCH`. |
| `GAP-001` | AI / Débito | TODO | OpenCode | - | Implementação de estorno automático de SuiteCoins em caso de falha de Job de IA. |
| `CI-001` | CI/CD | IMPLEMENTED_NOT_VERIFIED | Antigravity | - | Workflow completo de CI/CD para LawFirm no GitHub Actions (`lawfirm-ci.yml`). |
