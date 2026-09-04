# 🗺️ ROADMAP.md — Roteiro de Implantação Multi-Agent QA & Digital User QA

> **Projeto:** LawFirm CRM Multi-Agent QA
> **Regra Fundamental:** `ROADMAP ≠ CURRENT TASK`. A presença de fases futuras no roadmap não autoriza sua execução antecipada.

---

## Fase 0: Governança e Baseline (CONCLUÍDA)
- [x] `GOV-001`: Registro do baseline canônico do projeto (`.ai/BASELINE.md`).
- [x] `GOV-002`: Implantação da governança multiagente compartilhada, locks, shared skills e handoff formal.

---

## Fase 1: Diagnóstico e Auditoria de Infraestrutura VPS (EM PREPARAÇÃO)
- [ ] `HERMES-001` (Status: `READY`): Auditoria técnica e diagnóstico da VPS pelo agente Hermes (`reports/hermes-001-audit.md`).
- [ ] `QA-ENV-001` (Status: `BLOCKED`): Provisionamento e configuração do ambiente de QA na VPS após aprovação da auditoria.

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

## Fase 4: Otimização de Imagens e Pipeline de Release
- [ ] `DOCKER-001` (Status: `TODO`): Higienização da imagem oficial de produção `suitezap/lawfirm` (remoção estrita de artefatos de teste/governança).
