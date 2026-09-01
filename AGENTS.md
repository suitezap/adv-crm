# 🤖 AGENTS.md — Regras de Processo e Governança Multiagente (LawFirm SaaS)

## 1. Hierarquia de Fontes de Verdade

1. **SKILL.md** (`.agent/skills/krayin_lawfirm_dev/SKILL.md` e `.agents/skills/*`) é a **fonte primária** para QUALQUER regra de código, padrão de domínio, procedimento compartilhado ou convenção de frontend. Sempre consultar antes de escrever ou alterar código.

2. **Single Source of Truth (SSOT) em `.ai/`**: A pasta `.ai/` gerencia o estado operacional compartilhado entre todos os agentes (`BASELINE.md`, `CURRENT.md`, `TASKS.md`, `ROADMAP.md`, `LOG_INDEX.md`, `AGENTS_REGISTRY.md`, `DECISIONS.md`, `LESSONS.md`, `locks/`, `handoffs/`, `incidents/`, `logs/`).

3. **ARCHITECTURE.md**, **ARCHITECTURE_dir.md** e **ARCHITECTURE_mothership_orient.md** são a fonte para histórico de decisões (ADRs), mapas de diretório/rotas, e integrações cross-repo — **não** para regras de código do dia a dia.

4. **Infraestrutura de Qualidade e Memória Operacional (`quality/`)**: Antes de qualquer tarefa que crie ou modifique funcionalidades, testes, Docker, CI/CD ou releases, é **mandatório** ler:
   - `quality/README.md` (Índice central e arquitetura de testes)
   - `quality/TEST_CATALOG.yaml` (Catálogo e ciclo de vida dos testes)
   - `quality/KNOWN_GAPS.md` (Lacunas conhecidas e débitos mapeados)
   - O documento do módulo funcional afetado em `quality/modules/{module}.md`
   - ADRs de qualidade aplicáveis em `quality/adr/`

5. **Se um documento parecer contradizer o outro, PARE** e reporte o conflito explicitamente antes de agir — nunca escolher um lado silenciosamente.

6. **Este AGENTS.md nunca deve conter uma regra de código que já existe no SKILL.md.** Se uma regra nova de código for necessária, ela deve ser adicionada ao SKILL.md, não aqui.

---

## 2. Ecossistema Multiagente e Papéis Formais

| Agente | Role Primária | Ambiente | Atribuição Principal |
|---|---|---|---|
| **`ANTIGRAVITY`** | `ORCHESTRATOR` | Local (IDE) | Orquestração global, decomposição de tarefas, governança, mediação de locks e merge. |
| **`HERMES`** | `QA ARCHITECT / TEST ENGINEER / DIGITAL USER QA` | VPS Remota | Auditoria de infraestrutura, suíte de testes E2E/Playwright, validação como usuário digital. |
| **`OPENCODE`** | `IMPLEMENTER / DEBUGGER / REGRESSION TEST ENGINEER` | Dev Environment | Implementação de features de domínio, correção de débitos (ex: GAP-001) e hygiene de Docker. |

---

## 3. Protocolo de Concorrência e Locks (`.ai/locks/`)

> [!IMPORTANT]
> **Apenas um agente pode ser `WRITE OWNER` de uma tarefa por vez.**

1. **Aquisição Obrigatória:** Antes de editar qualquer arquivo, o agente deve criar o arquivo de lock em `.ai/locks/{TASK_ID}.lock.yaml` e transicionar a tarefa para `IN_PROGRESS` em `.ai/TASKS.md`.
2. **Permissão de Leitura:** Agentes sem lock têm permissão irrestrita de `READ`, `REVIEW` e `INVESTIGATE`, mas não podem commitar alterações simultâneas no mesmo escopo.
3. **Procedimento de Stale Lock:** Proibido executar remoção silenciosa (`rm lock`). Caso um lock pareça abandonado:
   `lock encontrado` $\rightarrow$ `consultar TASKS` $\rightarrow$ `consultar LOG_INDEX` $\rightarrow$ `consultar último checkpoint` $\rightarrow$ `se abandonado` $\rightarrow$ `STALE_LOCK_SUSPECTED` $\rightarrow$ `Orchestrator decide`.

---

## 4. Protocolo de Transporte e Isolamento Syncthing

1. **Transporte vs Concorrência:** O Syncthing é estritamente um mecanismo de transporte/sincronização de arquivos entre ambientes, **nunca** um mecanismo de concorrência ou substituto de Git.
2. **Detecção de Conflitos (`STOP WRITE`):** Antes de qualquer escrita em workspace sincronizado, executar busca por `*sync-conflict*`. Caso detectado $\rightarrow$ `SYNC_CONFLICT_DETECTED` $\rightarrow$ parada mandatória de escrita (`STOP WRITE`) e registro imediato no log.
3. **Isolamento de `.git/` e Workspaces:** Cada ambiente (Antigravity local, Hermes VPS) deve manter seu próprio clone/workspace Git. O diretório `.git/` **não** deve ser sincronizado pelo Syncthing.
4. **Política de Exclusão de Segredos:** Bloquear no `.stignore` a sincronização de `.env`, chaves privadas (`*.pem`, `*.key`), logs voláteis e relatórios pesados. Preservar `.env.example` e `.env.testing.example`.

---

## 5. Proteção de Segredos

> [!CAUTION]
> Nenhuma credencial real (senhas, API keys, tokens de WhatsApp/Evolution, chaves SSH) pode ser registrada em arquivos de governança, logs, handoffs ou relatórios.
> Utilizar exclusivamente aliases: `QA_ADMIN_CREDENTIAL`, `MOTHERSHIP_TEST_CREDENTIAL`, `CHATWOOT_TEST_TOKEN`.

---

## 6. Requisito Futuro Mandatório — Higiene da Imagem Docker de Produção

A imagem oficial de produção `suitezap/lawfirm` (a ser consolidada na task `DOCKER-001`) **NÃO poderá conter**:
- `tests/`
- `quality/`
- `.ai/`
- `.agents/`
- `.github/`
- `docker/testing/`
- `reports/`
- `coverage/`
- `test-results/`
- `playwright-report/`

O ambiente de QA testa a imagem candidata externamente, promovendo-a sem necessidade de reconstrução após os testes.

---

## 7. Isolamento Multi-Tenant (Regra Crítica)

> [!CAUTION]
> **Qualquer código que quebre o isolamento entre tenants é uma falha de segurança — não apenas um bug de funcionalidade.**

### 1. Escopo de Tenant em Queries de Domínio
Toda query que acessa dados dos domínios **Legal, Financial, GED, Whatsapp, Atendimento, AI, Escavador, TenantFinance** ou tabelas tenant-scoped do domínio **SaaS** (ex: `saas_transactions`) deve estar escopada por `tenant_id` ou equivalente de isolamento.

**Exceção documentada — tabelas do banco `mothership` que permitem acesso cross-tenant por design:**

| Tabela | Motivo |
|:---|:---|
| `tenants` | Cadastro global de tenants |
| `subscriptions` | Assinaturas e saldo SuiteCoins |
| `infrastructure_nodes` | Nós de infraestrutura (Evolution, N8N, Asaas, Storage, Chatwoot) |
| `app_config` | Configurações globais da plataforma |
| `saas_orders` | Intenções de compra cross-tenant |
| `lawfirm_document_templates` | Templates globais de documentos (somente leitura pelo tenant) |
| `chatwoot_nodes` | Nós Chatwoot globais |
| `asaas_nodes` | Nós Asaas globais |
| `evolution_nodes` | Nós Evolution globais |
| `storage_nodes` | Nós de Storage globais |
| `n8n_nodes` | Nós N8N globais |
| `admin_users` | Usuários administrativos do MotherShip |

### 2. Alterações em Autenticação, Resolução de Tenant ou Queries Dinâmicas
Qualquer alteração nessas áreas deve justificar **explicitamente no plano** como o isolamento entre tenants é preservado.

### 3. Credenciais — Nunca Global Onde Deveria Ser Por Tenant
Nunca usar credenciais globais/master onde deveria haver credencial isolada por tenant.

**Casos aprovados e documentados de herança de token:**
| Caso | Mecanismo | Referência |
|:---|:---|:---|
| `MotherShipService::getEvolutionConfig()` | Fallback para token master do nó quando tenant não tem token próprio | `ARCHITECTURE.md §4.35` |

### 4. Migrations no Banco `mothership`
Toda migration que roda no banco `mothership` deve declarar a conexão **explicitamente**:
```php
Schema::connection('mothership')->create('tabela', function (Blueprint $table) { ... });
```

### 5. Isolamento de Filas Redis
Em deployments Docker Swarm com Redis compartilhado, toda configuração de fila **deve** declarar `REDIS_PREFIX: ${TENANT_ID}_` no `docker-compose.yml`.

---

## 8. Módulos Suspensos — Não Reativar Sem Aprovação Explícita

> [!CAUTION]
> **Reativar qualquer item desta lista sem aprovação explícita é uma alteração proibida, independentemente do contexto da tarefa.**

| Funcionalidade ativa | Localização |
|:---|:---|
| Faturas WhatsApp | `Whatsapp/` — notificações de cobrança |
| Alertas de Prazo | `Whatsapp/` — alertas jurídicos automáticos |
| Importação de Histórico | `Whatsapp/` — `WhatsappImport` |
| Agendador de Prazos | `Whatsapp/` — `SendScheduledPrazoNotifications` |

---

## 9. Isolamento de Domínio — Não Cruzar Sem Justificativa

Qualquer tarefa em um domínio (ex: Legal) não deve alterar arquivos de outro domínio (ex: Financial, Whatsapp) a menos que a tarefa exija integração explícita entre eles formalizada no plano.

---

## 10. Sincronização Obrigatória de Arquitetura MotherShip

Toda alteração no LawFirm que crie ou altere tabelas/colunas na conexão `mothership`, crie novos endpoints consumidos pelo MotherShip, ou mude a semântica de campos documentados em `ARCHITECTURE_mothership_orient.md`, deve **atualizar esse arquivo na mesma tarefa**.

---

## 11. Fluxo Obrigatório para Qualquer Alteração

1. **EXPLORAÇÃO**: explicar como o comportamento atual resolve o problema referenciando a arquitetura relevante. Não gerar código nesta fase.
2. **PLANO**: apresentar plano explícito com arquivos tocados, confirmação de isolamento multi-tenant e verificação de módulos suspensos.
3. **EXECUÇÃO**: aplicar apenas o aprovado com diff. Migrations devem ser idempotentes e declarar conexão explicitamente.
4. **VALIDAÇÃO**: rodar testes de forma síncrona. O ciclo de status de tarefa deve seguir: `TODO` $\rightarrow$ `IN_PROGRESS` $\rightarrow$ `IMPLEMENTED_NOT_VERIFIED` $\rightarrow$ `VERIFIED` $\rightarrow$ `DONE`.
5. **SINCRONIZAÇÃO DE VERSÃO**: mudanças estruturais exigem sincronização entre `ARCHITECTURE.md` e `LawFirmServiceProvider::VERSION`.
6. **REGISTRO**: registrar incidentes em `GUARDRAILS.md` ou `.ai/incidents/`.
7. **COMMIT**: sugerir mensagem atômica e commitar só após aprovação.

---

## 12. Higiene de Commits

- **Nunca usar `git add -A` ou `git add .`** sem antes rodar `git status` e revisar a lista completa de arquivos.
- **Atomicidade Estrita:** Commits devem conter apenas arquivos da tarefa em execução.
- **Artefatos Proibidos:** Nunca commitar arquivos `*.bak`, `*.bak2`, `*.ffs_db`, coleções soltas ou builds compilados em `public/**/build/`.

---

## 13. Verificação de Integridade em Arquivos de Governança

> [!IMPORTANT]
> **Após qualquer escrita em `GUARDRAILS.md`, `AGENTS.md` ou `SKILL.md`**, reabrir o arquivo e confirmar visualmente que o conteúdo está íntegro antes de considerar a tarefa concluída.
