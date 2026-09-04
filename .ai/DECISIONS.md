# 📋 DECISIONS.md — Registro de Decisões Arquiteturais e Operacionais de Governança

> Histórico formal de Architecture Decision Records (ADRs) operacionais para o ecossistema multiagente LawFirm CRM.

---

## ADR-GOV-001: Modelo de Governança Multiagente com SSOT em `.ai/`
- **Data:** 2026-08-26
- **Status:** APPROVED
- **Contexto:** Três agentes (`Antigravity`, `Hermes`, `OpenCode`) colaboram no ciclo de vida do CRM em diferentes ambientes (local, VPS, desenvolvimento). É mandatório evitar retrabalho, perda de contexto e desvios de padrão.
- **Decisão:** A pasta `.ai/` é o Single Source of Truth (SSOT) operacional e rastreável via Git. Toda documentação utiliza estritamente caminhos relativos à raiz do repositório para garantir 100% de portabilidade entre diferentes sistemas operacionais e máquinas.
- **Consequências:** Agentes consultam `AGENTS.md` e `.ai/CURRENT.md` no bootstrap da sessão, minimizando o consumo de tokens e preservando contexto compartilhado.

---

## ADR-GOV-002: Protocolo de Concorrência e Locks Baseados em Arquivo (`.ai/locks/`)
- **Data:** 2026-08-26
- **Status:** APPROVED
- **Contexto:** Múltiplos agentes podem atuar em momentos próximos. A edição simultânea do mesmo domínio ou arquivo causa divergência e sobreposição de código.
- **Decisão:** Todo agente que atuar como `WRITE OWNER` de uma tarefa deve criar um arquivo de lock YAML em `.ai/locks/{TASK_ID}.lock.yaml` especificando escopo, commit base e `last_checkpoint_at`. Proibida a deleção silenciosa de locks existentes; suspeitas de abandono (`STALE_LOCK_SUSPECTED`) são mediadas pelo Orchestrator.
- **Consequências:** Elimina concorrência de escrita e garante rastreabilidade estrita de titularidade de cada alteração.

---

## ADR-GOV-003: Transporte via Syncthing e Proteção de Concorrência
- **Data:** 2026-08-26
- **Status:** APPROVED
- **Contexto:** O Syncthing/SyncTrayzor é utilizado para transporte e sincronização contínua de arquivos entre ambientes físicos (ex: IDE $\leftrightarrow$ VPS).
- **Decisão:**
  1. O Syncthing é tratado estritamente como mecanismo de transporte, **nunca** como controle de concorrência ou substituto de Git.
  2. Qualquer arquivo `*sync-conflict*` detectado no workspace dispara o evento `SYNC_CONFLICT_DETECTED` com parada mandatória de escrita (`STOP WRITE`).
  3. Cada ambiente mantém seu próprio repositório/workspace Git (`.git/` não é compartilhado via Syncthing).
  4. Arquivos sensíveis (`.env`, certificados, logs pesados, dumps) são excluídos da sincronização via `.stignore` ou isolamento operacional.
- **Consequências:** Sincronização segura sem risco de corrupção silenciosa de arquivos ou sobrescrita acidental.

---

## ADR-GOV-004: Requisito Mandatório de Higiene e Versionamento da Imagem Docker
- **Data:** 2026-08-26
- **Status:** APPROVED (Requisito Formalizado para `DOCKER-001`)
- **Contexto:** A tag `suitezap/lawfirm:latest` é apenas um estado observado em arquivos locais e não representa política de release. Além disso, artefatos de teste, governança de IA e relatórios transitórios não devem vazar para as imagens de produção.
- **Decisão:**
  1. Em produção, utilizar estritamente tags imutáveis com release formal ou digest imutável aprovado; **nunca** depender de `:latest`.
  2. A imagem final de produção `suitezap/lawfirm` NÃO poderá conter os diretórios e arquivos:
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
- **Consequências:** A imagem candidata de produção é testada externamente pela infraestrutura de QA e promovida por digest/tag sem reconstrução pós-teste.

---

## ADR-GOV-005: Proteção de Segredos e Uso de Aliases Canônicos
- **Data:** 2026-08-26
- **Status:** APPROVED
- **Contexto:** Logs e documentos compartilhados entre agentes são versionados no Git e replicados via Syncthing.
- **Decisão:** Nenhuma credencial real (senhas, API keys, tokens JWT/Evolution, SSH keys) pode ser escrita em arquivos `.ai/`, `.agents/`, `logs/` ou `reports/`. Devem ser utilizados exclusivamente aliases formais (`QA_ADMIN_CREDENTIAL`, `MOTHERSHIP_TEST_CREDENTIAL`, `CHATWOOT_TEST_TOKEN`).
- **Consequências:** Segurança absoluta de credenciais contra vazamento acidental em histórico versionado.

---

## ADR-ATEND-001: Normalização de Endpoints Chatwoot e Reatividade do Kanban
- **Data:** 2026-08-28
- **Status:** APPROVED
- **Contexto:** 
  1. O nó de infraestrutura do Chatwoot no Mothership continha uma URL com caminho de dashboard (`https://host/app/login`). Ao concatenar endpoints de API (`/api/v1/accounts/1/labels`), o Chatwoot retornava erro HTTP 406 (`"Please use API routes instead of dashboard routes for JSON requests"`), impedindo a sincronização automática de tags nas conversas.
  2. No Kanban de Leads (`v-leads-kanban`), o objeto `stageLeads` era iniciado como `{}` e populado por chave dinâmica no `data()`, fazendo com que o Vue 3 Options API perdesse a reatividade de cards ao movimentá-los entre etapas.
- **Decisão:**
  1. **MotherShipService (`getChatwootConfig`):** Passa a sanitizar obrigatoriamente a `base_url` de qualquer nó Chatwoot utilizando `parse_url()` para extrair exclusivamente `scheme + host (+ port)`, descartando qualquer fragmento de path web.
  2. **Kanban Leads (`kanban.blade.php`):** Pré-semeia a estrutura completa de chaves dos estágios (`stageLeads`) diretamente no `data()` via `@json($pipeline->stages->mapWithKeys(...))` e utiliza `Object.assign()` nas atualizações assíncronas, garantindo reatividade total em tempo real e rollback visual em caso de erro na API.
- **Consequências:** Sincronização 100% resiliente de etiquetas no Chatwoot (`ld_novo`, `ld_acomp`, `ld_qual`, `ld_neg`, `ld_ganho`, `ld_perd`) e estabilidade visual definitiva na movimentação de leads no funil de vendas.

