# 🤝 HANDOFF — HERMES-001

- **From:** ANTIGRAVITY (Orchestrator)
- **To:** HERMES (QA Architect / Test Engineer / Digital User QA)
- **Task ID:** `HERMES-001`
- **Task Title:** VPS, Workspace & QA Infrastructure Audit
- **Status:** `READY`
- **Expected Commit:** `11f5b4e7e0812e53d5d0e70353326112386f18e6`
- **Reference Base:** `660f4f10b8c9b856805d1ea9da23a4ad8c0797b5`
- **Branch:** `law-firm-custom`
- **Formal Output Target:** `.ai/handoffs/RESULT-HERMES-001.md`

---

## 1. Objetivo da Tarefa
Realizar uma auditoria técnica e diagnóstica diretamente no ambiente da **VPS** para mapear os recursos disponíveis, a saúde do workspace sincronizado, o carregamento de shared skills e a prontidão da infraestrutura necessária para a execução da suíte de QA.

Esta tarefa é **estritamente diagnóstica (predominantemente READ-ONLY)**. O provisionamento e implementação do ambiente de testes serão executados na tarefa subsequente `QA-ENV-001` após revisão do resultado formal.

---

## 2. Gates de Entrada Obrigatórios na VPS

Antes de qualquer operação de escrita na VPS, o Hermes deve:

### Gate 1: Validação de Snapshot Git
- Obter o hash local na VPS (`git rev-parse HEAD`).
- Comparar com o `Expected Commit` (`11f5b4e7e0812e53d5d0e70353326112386f18e6`).
- Se houver divergência $\rightarrow$ registrar `SNAPSHOT_MISMATCH` e **parar imediatamente** (`STOP WRITE`). Não realizar `pull`, `merge`, `reset` ou `checkout` automaticamente.

### Gate 2: Verificação de Conflitos Syncthing
- Executar busca por arquivos de conflito:
  ```bash
  find . -name "*sync-conflict*"
  ```
- Se houver qualquer resultado $\rightarrow$ registrar `SYNC_CONFLICT_DETECTED` no log e **parar imediatamente** (`STOP WRITE`).

### Gate 3: Aquisição de Lock Operacional
- Criar `.ai/locks/HERMES-001.lock.yaml` contendo:
  ```yaml
  task_id: HERMES-001
  owner: HERMES
  write_scope:
    - .ai/logs/HERMES.md
    - .ai/LOG_INDEX.md
    - .ai/TASKS.md
    - .ai/CURRENT.md
    - .ai/handoffs/RESULT-HERMES-001.md
  started_at: "2026-08-26T23:30:00Z"
  last_checkpoint_at: "2026-08-26T23:30:00Z"
  base_commit: "11f5b4e7e0812e53d5d0e70353326112386f18e6"
  workspace: "vps-hermes"
  status: ACTIVE
  ```

---

## 3. Matriz de Auditoria e Critérios de Aceite

Para cada item abaixo, classificar rigorosamente em:
- `EXISTS` — Componente presente, funcional e em conformidade.
- `PARTIAL` — Componente presente com limitações ou configuração incompleta.
- `MISSING` — Componente ausente que precisará ser provisionado na `QA-ENV-001`.
- `BLOCKED` — Impedimento técnico ou restrição de ambiente que impede o uso.

### 3.1 Infraestrutura da VPS
- [ ] **OS:** Distribuição Linux e versão de kernel.
- [ ] **Hardware Capacity:** Contagem de vCPUs, RAM total/livre e espaço em disco disponível. Avaliar se há capacidade razoável para a stack futura (LawFirm candidate, Mothership DB, Tenant DBs, Redis, Playwright/browser). Não subir os serviços ainda.
- [ ] **Docker Engine:** Versão e status (`EXISTS | PARTIAL | MISSING | BLOCKED`).
- [ ] **Docker Compose:** Versão do plugin/binário (`EXISTS | PARTIAL | MISSING | BLOCKED`).
- [ ] **Git:** Versão e configuração (`EXISTS | PARTIAL | MISSING | BLOCKED`).
- [ ] **Python:** Versão ($\ge$ 3.10) (`EXISTS | PARTIAL | MISSING | BLOCKED`).
- [ ] **Pytest:** Framework instalado (`EXISTS | PARTIAL | MISSING | BLOCKED`).
- [ ] **Playwright:** Pacotes e drivers (`EXISTS | PARTIAL | MISSING | BLOCKED`).
- [ ] **Browser Capability:** Suporte a Chromium headless (`EXISTS | PARTIAL | MISSING | BLOCKED`).

### 3.2 Workspace e Isolamento Syncthing
- [ ] **Distinção Arquitetural:** Identificar se `SYNCED_ROOT` e `HERMES_WORKSPACE` estão separados. Se a VPS opera no mesmo working tree sincronizado sem clone Git próprio, registrar `WORKSPACE_ARCHITECTURE_GAP` e recomendar solução sem improvisar alterações estruturais.
- [ ] **Branch & Commit:** `law-firm-custom` @ `11f5b4e7...`.
- [ ] **Repo Health:** Permissões de diretório (`storage/`, `bootstrap/cache/`).
- [ ] **Segredos e Proteção:** Verificar se o Syncthing está sincronizando indevidamente `.env`, `.env.*`, `*.pem`, `*.key`, logs ou dumps. Se houver risco, registrar `SECRET_SYNC_RISK`. Nunca registrar valores de credenciais.
- [ ] **Artefatos Pesados:** Avaliar onde serão armazenados traces, screenshots, vídeos e relatórios de Playwright para não trafegarem pelo Syncthing ou Git (`EXISTS | PARTIAL | MISSING | BLOCKED`).

### 3.3 Validação de Shared Skills na VPS
Hermes deve comprovar que consegue localizar, ler e carregar as 8 shared skills:
- `.agents/skills/project-bootstrap/`
- `.agents/skills/context-budget/`
- `.agents/skills/task-claim/`
- `.agents/skills/handoff/`
- `.agents/skills/sync-safety/`
- `.agents/skills/safe-code-change/`
- `.agents/skills/hermes-qa/`
- `.agents/skills/test-gate/`
Classificar: `Project skills detected: YES | PARTIAL | NO` e `Hermes can load .agents/skills: YES | NO | BLOCKED`. Caso seja necessária configuração específica do Hermes para reconhecer a pasta, documentar: `EXISTING CONFIG`, `CHANGE REQUIRED`, `CHANGE PERFORMED`, `VERIFICATION`.

### 3.4 Infraestrutura de QA Existente
- [ ] `quality/` e `TEST_CATALOG.yaml`
- [ ] `tests/e2e/`, `docker/testing/`, `Dockerfile.playwright`, `docker-compose.test.yml`
- [ ] Configurações de pytest, fixtures, reports e execução de `python quality/scripts/validate_test_docs.py`.

### 3.5 Conectividade e Redes
- [ ] **QA Application Reachability:** Bind nas portas locais para containers `app-tenant-a` e `app-tenant-b`.
- [ ] **MotherShip Test:** Acesso ao banco `mothership-db-test`.
- [ ] **Tenant Test Environment:** Acesso ao banco multi-tenant `mysql-test`.
- [ ] **External Integrations:** Validação de estratégia com `mock-server` (WireMock).

---

## 4. O que o Hermes NÃO Deve Fazer (Restrições Estritas)

> [!CAUTION]
> 1. **NÃO** implementar testes funcionais do CRM (Kanban, Processos, Financeiro).
> 2. **NÃO** alterar código-fonte funcional em `packages/SuiteZap/LawFirm/src/` ou `app/`.
> 3. **NÃO** refatorar Controllers, Services, Models, Blade views ou Migrations.
> 4. **NÃO** alterar Docker ou banco de dados de produção.
> 5. **NÃO** executar `QA-ENV-001` simultaneamente.
> 6. **NÃO** registrar valores reais de credenciais em relatórios ou logs.

---

## 5. Formato Exigido para o Resultado Formal (`.ai/handoffs/RESULT-HERMES-001.md`)

O Hermes deve salvar o resultado formal no arquivo `.ai/handoffs/RESULT-HERMES-001.md` com a seguinte estrutura:

```markdown
# RESULT — HERMES-001

TASK:
HERMES-001

AGENT:
HERMES

WORKSPACE:
VPS

EXPECTED_COMMIT:
11f5b4e7e0812e53d5d0e70353326112386f18e6

LOCAL_COMMIT:
{HASH_LOCAL}

SNAPSHOT:
MATCH | MISMATCH

SYNC_CONFLICTS:
NONE | DETECTED

VPS:

OS:
{VERSAO_OS_KERNEL}

CPU:
{CONTAGEM_VCPU}

RAM:
{RAM_TOTAL_LIVRE}

DISK:
{ESPACO_DISCO_DISPONIVEL}

Docker:
EXISTS | PARTIAL | MISSING | BLOCKED

Docker Compose:
EXISTS | PARTIAL | MISSING | BLOCKED

Git:
EXISTS | PARTIAL | MISSING | BLOCKED

Python:
EXISTS | PARTIAL | MISSING | BLOCKED

Pytest:
EXISTS | PARTIAL | MISSING | BLOCKED

Playwright:
EXISTS | PARTIAL | MISSING | BLOCKED

Browser Capability:
EXISTS | PARTIAL | MISSING | BLOCKED

PROJECT:

Synced Root:
{CAMINHO_SYNCED_ROOT}

Own Workspace:
{CAMINHO_WORKSPACE_HERMES}

Branch:
{BRANCH_LOCAL}

Commit:
{COMMIT_LOCAL}

Repo Health:
{STATUS_PERMISSOES}

Sync Health:
{STATUS_SYNC}

QA:

quality/:
EXISTS | PARTIAL | MISSING | BLOCKED

TEST_CATALOG.yaml:
EXISTS | PARTIAL | MISSING | BLOCKED

tests/e2e/:
EXISTS | PARTIAL | MISSING | BLOCKED

docker/testing/:
EXISTS | PARTIAL | MISSING | BLOCKED

Dockerfile.playwright:
EXISTS | PARTIAL | MISSING | BLOCKED

docker-compose.test.yml:
EXISTS | PARTIAL | MISSING | BLOCKED

pytest config:
EXISTS | PARTIAL | MISSING | BLOCKED

fixtures:
EXISTS | PARTIAL | MISSING | BLOCKED

reports:
EXISTS | PARTIAL | MISSING | BLOCKED

scripts:
EXISTS | PARTIAL | MISSING | BLOCKED

NETWORK:

QA Application:
EXISTS | PARTIAL | MISSING | BLOCKED

MotherShip Test:
EXISTS | PARTIAL | MISSING | BLOCKED

Tenant Test Environment:
EXISTS | PARTIAL | MISSING | BLOCKED

External Integrations:
EXISTS | PARTIAL | MISSING | BLOCKED

SHARED SKILLS:

Project skills detected:
YES | PARTIAL | NO

Hermes can load .agents/skills:
YES | NO | BLOCKED

BLOCKERS:
- {LISTA_DE_BLOQUEIOS_OU_NONE}

RISKS:
- {LISTA_DE_RISCOS_OU_NONE}

RECOMMENDED NEXT ACTION:
{RECOMENDACAO}

QA-ENV-001:
READY | NOT_READY
```

---

## 6. Próximo Passo Após Conclusão
Ao concluir a escrita de `.ai/handoffs/RESULT-HERMES-001.md`, atualizar `.ai/logs/HERMES.md`, `.ai/LOG_INDEX.md`, liberar o lock em `.ai/locks/HERMES-001.lock.yaml` e notificar o Orchestrator para homologação e desbloqueio de `QA-ENV-001`.
