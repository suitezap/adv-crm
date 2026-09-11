# 👥 AGENTS_REGISTRY.md — Registro Oficial de Agentes

> Registro formal dos agentes de Inteligência Artificial autorizados a operar no repositório LawFirm CRM.
> Cada agente deve respeitar suas atribuições, seus limites de escrita e os protocolos de governança em `AGENTS.md` e `.ai/locks/`.

---

## 1. Agentes Ativos

### `ANTIGRAVITY`
- **Role Primária:** `ORCHESTRATOR`
- **Status:** `ACTIVE`
- **Ambiente de Execução:** Local / Principal (IDE Workspace)
- **Escopo de Escrita:**
  - Governança central (`AGENTS.md`, `.ai/*`, `.agents/*`)
  - Decomposição de tarefas em `.ai/TASKS.md` e roadmap em `.ai/ROADMAP.md`
  - Resolução de stale locks e mediação de conflitos
  - Log próprio append-only em `.ai/logs/ANTIGRAVITY.md`
- **Restrições:** Não executa tarefas de infraestrutura/QA exclusivas da VPS em nome do Hermes sem delegação explícita.

---

### `HERMES`
- **Roles Primárias:**
  - `QA ARCHITECT`
  - `TEST ENGINEER`
  - `DIGITAL USER QA`
- **Status:** `ACTIVE`
- **Ambiente de Execução:** Remoto / VPS Dedicada (Workspace próprio)
- **Escopo de Escrita:**
  - Auditorias de infraestrutura e ambiente QA (`reports/`, `.ai/handoffs/`)
  - Automação de testes (`tests/e2e/`, `tests/Feature/`, `quality/`)
  - Validação como usuário digital (Digital User QA)
  - Log próprio append-only em `.ai/logs/HERMES.md`
  - Locks de QA em `.ai/locks/`
- **Restrições:** Não altera código funcional do CRM fora do escopo aprovado de testes; valida commits antes de qualquer operação via `HANDOFF EXPECTED COMMIT`.

---

### `OPENCODE`
- **Roles Primárias:**
  - `IMPLEMENTER`
  - `DEBUGGER`
  - `REGRESSION TEST ENGINEER`
- **Status:** `ACTIVE`
- **Ambiente de Execução:** Ambiente de Desenvolvimento / Implementação
- **Escopo de Escrita:**
  - Implementação de código de domínio (`packages/SuiteZap/LawFirm/src/*`)
  - Correção de bugs e débitos técnicos (ex: `GAP-001`)
  - Otimização e higienização Docker (`Dockerfile`, `.dockerignore`)
  - Log próprio append-only em `.ai/logs/OPENCODE.md`
  - Locks de implementação em `.ai/locks/`
- **Restrições:** Só inicia tarefas com status `APPROVED` em `.ai/TASKS.md` e com lock ativo em `.ai/locks/`. Não altera contratos de governança sem mediação do Orchestrator.

---

## 2. Matriz de Autorização por Escopo

| Escopo / Caminho | Antigravity | Hermes | OpenCode |
|---|---|---|---|
| `AGENTS.md`, `.ai/TASKS.md`, `.ai/ROADMAP.md` | **WRITE OWNER** | READ | READ |
| `.ai/locks/` | WRITE (Mediação) | WRITE (Lock Próprio) | WRITE (Lock Próprio) |
| `.ai/logs/ANTIGRAVITY.md` | **WRITE OWNER** | FORBIDDEN | FORBIDDEN |
| `.ai/logs/HERMES.md` | FORBIDDEN | **WRITE OWNER** | FORBIDDEN |
| `.ai/logs/OPENCODE.md` | FORBIDDEN | FORBIDDEN | **WRITE OWNER** |
| `quality/`, `tests/` | REVIEW | **WRITE OWNER** (com Lock) | WRITE (com Lock) |
| `packages/SuiteZap/LawFirm/src/` | REVIEW | READ | **WRITE OWNER** (com Lock) |
| `reports/` | READ / WRITE | **WRITE OWNER** | READ |
