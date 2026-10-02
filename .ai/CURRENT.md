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
- **Qualidade e Testes:** 48 testes catalogados em `quality/TEST_CATALOG.yaml` (20 `active`, 24 `implemented_unverified`, 4 `planned`); validador documental `validate_test_docs.py` passing com 0 erros, agora **incluindo a Regra 14 (Consistência de Versão)**.
- **Isolamento e Segurança:** `tenant_id` obrigatório nos domínios (ADR `ARCHITECTURE.md §4.91` e §4.92); webhooks fail-closed; migrations de tenant idempotentes.
- **Workflow n8n de Triagem:** Corrigido nó `Add Coluna Chatwoot` executando query dinâmica com `$json.id` sobre o schema do tenant (`ALTER TABLE \`{{ $json.id }}\`.\`leads\` ADD COLUMN IF NOT EXISTS \`chatwoot_conversation_id\` INT NULL DEFAULT NULL;`) e versão ativa publicada.

---

## 4. O que está bloqueado ou pendente?
- **`QA-DATA-001` permanece `BLOCKED`.** *(Correção DOC-002, 2026-09-30.)* Uma versão anterior deste arquivo afirmava que a task havia sido desbloqueada pela publicação da imagem. **Isso não se confirmou:** sem ambiente de execução containerizado no servidor (Portainer desativado, Docker ausente), a cadeia `QA-DATA-001 → QA-HARNESS-001 → QA-JUR-001` não avança. Publicar a imagem não é o mesmo que prover o ambiente que a executa. Estado oficial: `TASKS.md`.
- **Pre-requisito para desbloqueio:** reprovisionar um ambiente de execução (Docker na VPS ou host alternativo com o `docker-compose.test.yml` de 9 serviços). Decisão de infraestrutura pendente do DSK7.
- **`DOCKER-002`/`003`/`004`** concluídas. Imagens `suitezap/lawfirm:3.56.0`, `:3.56.1` e `:3.56.2` publicadas.
- **DOC-006 (2026-09-30)** — (a) `CHATWOOT-E2E-001` implementado (Page Object + 4 testes) e movido para `implemented_unverified`; **Regra 4 estendida para `planned`**, fechando o furo de guardrail (teste planejado citando arquivo inexistente passava). (b) `INC-2026-09-15` resolvido: **criado `.stignore` versionado** excluindo `.git/` (a causa do sync-conflict), segredos e artefatos — nenhuma exclusão existia antes. O `.stignore` só tem efeito quando o serviço Syncthing voltar a rodar (atualmente `inactive`) e precisa existir também na DSK7. (c) Criado `.ai/INSTRUCOES-OPENCODE-OPS-WEBHOOK.md` com o roteiro da `OPS-WEBHOOK-ENV-001`.
- **Auditoria de documentação concluída (`DOC-005`, 2026-09-30)** — varredura de todas as referências de `.ai/` e `quality/` contra o disco. **5 furos encontrados com o validador em verde (15 regras)**: `CHATWOOT-E2E-001` cita `test_chatwoot_sac_workflow.py`, que não existe; a Regra 4 não checa arquivo em status `planned`, então esse furo passa; `.ai/TASKS.md` referencia `INC-2026-09-15-sync-conflict-git-index.md`, ausente do repo (só na árvore local); os 20 testes `active` declaram verificação em v3.55.0/v3.55.1 com o código em v3.56.3 — **nenhum verificado na versão corrente**; `INC-2026-09-27` cita `packages/Webkul/LawFirm/src/Routes/web.php`, inexistente. Verificado como correto: matriz × catálogo batem, 14 módulos existem e todos citados. Correção: `.ai/REGRAS-OPENCODE-OPERACAO.md` e divisão do `OPS-WEBHOOK-001` em 3 tasks com donos.
- **Regras obrigatórias instituídas (`DOC-004`, 2026-09-30)** — `.ai/REGRAS-DE-RELEASE.md` e `.ai/REGRAS-DE-CONCORRENCIA.md`, ancoradas no `AGENTS.md` §1.7/§1.8 e no `quality/RELEASE_CHECKLIST.md` seção 0 (gate que bloqueia as demais seções). Fecham a causa raiz de processo: a detecção já era automática (Regras 14/15), mas a **obrigação** não estava declarada em lugar nenhum. Confirmação do DSK7: o OpenCode atuava no MotherShip e **não há distinção de autoria no git** — a atribuição vem só do campo *Owner* do `TASKS.md`.
- **Documentação das releases regularizada** — `DOC-002` (2026-09-30) adicionou as entradas v3.56.0/3.56.1/3.56.2 aos CHANGELOGs e criou a **Regra 14 (Consistência de Versão)**. O `DOC-002` foi merged na `2.1` pelo Antigravity (`62c61153`, `8f563040`) e a v3.56.3 (`DOCKER-005`, ADR §4.95) foi corretamente documentada nos dois arquivos — primeiro release a passar pelo guardrail. `DOC-003` (2026-09-30) acrescenta a **Regra 15 (Cobertura da Série)**, que fecha o furo da Regra 14: ela só comparava a entrada mais recente e aprovava lacunas intermediárias (v3.56.0 e v3.56.1 faltavam no `quality/CHANGELOG.md`). Ver `INC-2026-09-30-doc-drift-v356` e `INC-2026-09-30-changelog-cobertura`.
- **`OPS-WEBHOOK-001`** é a única `TODO` e segue **Unassigned** — segredos de webhook (Asaas, tenant-Asaas, Evolution) são pré-requisito de cobrança real. Provável bloqueador do piloto.
- **`DOCKER-003`** concluída (**DONE**). Imagem `suitezap/lawfirm:3.56.1`, `suitezap/lawfirm:v3.56.1` e `latest` (digest `sha256:0401da4e36bf9cc833304a088a13e733a355d3146fb473ac1dd83e7d7f75d7e0`) publicada no Docker Hub com higiene estrita.
- **`DOCKER-004`** concluída (**VERIFIED**, OpenCode): bump v3.56.2 + ADR 4.94; `suitezap/lawfirm:3.56.2`, `:v3.56.2`, `:latest` (digest `sha256:02b7b37e`) publicadas com higiene estrita.
- **`DOCKER-005`** concluída (**VERIFIED**, OpenCode): bump v3.56.3 + ADR 4.95; `suitezap/lawfirm:3.56.3`, `:v3.56.3`, `:latest` (digest `sha256:a4887dd4`) publicadas com higiene estrita.
- A tarefa `KAN-001` segue `BLOCKED` (aguardando manutenção de tags).

---

## 5. Quem está trabalhando?
- **Antigravity (Orchestrator):** Concluiu `DOCKER-003` (bump v3.56.1, build e push Docker Hub com higiene estrita), suporte ao workflow n8n (`N8N-001`), triggers EAV e Chatwoot Lead Chat modal.
- **Hermes (QA Architect):** Entregou `DOC-002` (documentação v3.56.x nos CHANGELOGs, Regra 14 no validador e incidente doc-drift). VPS reconfigurada — aguarda revalidação de ambiente QA.
- **OpenCode (Implementer):** Entregou `DOCKER-002` (v3.56.0), `DOCKER-004` (v3.56.2), `DOCKER-005` (v3.56.3). `SKILLS-UPD-001` e `SKILLS-UPD-002` concluídas e integradas com sucesso (**DONE**).

---

## 6. Próximo Passo Seguro

**Bloqueado por 2 furos de segurança** (`WEBHOOK-SEC-002` e `WEBHOOK-SEC-003`): o webhook do Escavador é rota
pública sem autenticação e altera saldo financeiro. **Corrigir antes de qualquer teste de webhook** — o
`ENV-001` vai exercitar esses endpoints.

Ordem: Furo A → Furo B → `ENV-001` no DSK7 (Antigravity) → `SEC-001` (DSK7 popula segredos) → `VER-001` (Hermes).

Atualizar o serviço na VPS para `suitezap/lawfirm:v3.56.3` fica para depois dos furos.
---
*2026-09-30: state refreshed after DOC-002 e DOCKER-005 (v3.56.3) — CHANGELOGs e governança reconciliados; Regra 14 ativa.*
