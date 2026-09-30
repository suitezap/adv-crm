# 🗺️ ROADMAP.md — Roteiro de Implantação Multi-Agent QA & Digital User QA

> **Projeto:** LawFirm CRM Multi-Agent QA
> **Regra Fundamental:** `ROADMAP ≠ CURRENT TASK`. A presença de fases futuras no roadmap não autoriza sua execução antecipada.
>
> **⚠️ Sincronizado em 2026-09-30 (DOC-002).** Este arquivo estava defasado: listava `HERMES-001`, `QA-ENV-001` e `DOCKER-001` como pendentes quando todas estão `DONE` no `TASKS.md`. Statusos reconciliados.

---

## Fase 0: Governança e Baseline (CONCLUÍDA)
- [x] `GOV-001`: Registro do baseline canônico do projeto (`.ai/BASELINE.md`).
- [x] `GOV-002`: Implantação da governança multiagente compartilhada, locks, shared skills e handoff formal.

---

## Fase 1: Diagnóstico e Auditoria de Infraestrutura VPS (CONCLUÍDA)
- [x] `HERMES-001`: Auditoria técnica e diagnóstico da VPS pelo agente Hermes. Resultado: `.ai/handoffs/RESULT-HERMES-001.md`.
- [x] `QA-ENV-001`: Provisionamento e configuração do ambiente de QA na VPS. Resultado: `.ai/handoffs/RESULT-QA-ENV-001.md`.

> **Nota de ambiente (2026-09-30):** a infraestrutura self-hosted foi **desativada** (Portainer removido, Docker ausente no servidor). As Fases 2 e 3 dependem de um ambiente de execução containerizado que **precisa ser reprovisionado**.

---

## Fase 2: Estruturação de Dados e Harness de Testes
- [ ] `QA-DATA-001` (Status: `BLOCKED`): Estruturação de seeds, fixtures determinísticas e isolamento multi-tenant de dados.
- [ ] `QA-HARNESS-001` (Status: `BLOCKED`): Harness de orquestração de testes E2E e relatórios automatizados.

---

## Fase 3: Validação Funcional e Digital User QA por Domínio
- [ ] `QA-JUR-001` (Status: `BLOCKED`): Testes funcionais do domínio Jurídico (Kanban, Casos, Processos, Prazos).
- [ ] `QA-FIN-001` (Status: `BLOCKED`): Testes funcionais do domínio Financeiro e faturamento.
- [ ] `QA-AI-001` (Status: `BLOCKED`): Testes de assistentes e triagem de IA com validação de estornos e custos.

---

## Fase 4: Otimização de Imagens e Pipeline de Release (CONCLUÍDA)
- [x] `DOCKER-001`: Higienização da imagem oficial `suitezap/lawfirm`.
- [x] `DOCKER-002`: Bump v3.56.0 (`chatwoot_conversation_id` em leads) + migration idempotente.
- [x] `DOCKER-003`: Bump v3.56.1 (modal de chat Chatwoot no Lead, proxy controller, triggers EAV).
- [x] `DOCKER-004`: Bump v3.56.2 (consolidação Atendimento/Chatwoot sobre 3.56.1).

---

## Pendências Ativas Fora do Roteiro de QA

| Task | Status | Observação |
|---|---|---|
| `OPS-WEBHOOK-001` | `TODO` / Unassigned | Segredos de webhook de produção (Asaas, tenant-Asaas, Evolution). Runbook: `quality/runbooks/webhook-secrets.md`. **Provável bloqueador do piloto.** |
| `KAN-001` | `BLOCKED` | Kanban jurídico + Chatwoot; aguardando manutenção de tags pelo operador. |

## Bloqueadores de Ambiente (2026-09-30)

1. **Sem Docker no servidor** — Portainer desativado, `docker` ausente. As imagens v3.56.x existem no Docker Hub mas nada as executa.
2. **`.git/` sincronizado pelo Syncthing** — incidente `INC-2026-09-15` documenta sync-conflict no índice; a ação corretiva (excluir `.git/` da sincronização) **não foi executada** e viola o `ADR-GOV-003`.
3. **Testes não verificados** — 24 `implemented_unverified` + 4 `planned` no catálogo (20 `active` de 48), 17 em `ai-assistant`.
