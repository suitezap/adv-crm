# INC-2026-09-30 — Drift documental das releases v3.56.0 / v3.56.1 / v3.56.2 (3º ciclo do BASELINE_VERSION_MISMATCH)

- **Detectado em:** 2026-09-30 por auditoria do Hermes (QA Architect)
- **Severidade:** média (documental, sem impacto em runtime) · **Task:** `DOC-002` (lock `.ai/locks/DOC-002.lock.yaml`)
- **Branch de aplicação:** `doc-002-v3562`, sobre `origin/2.1` em `e8afb269` (worktree isolado `/home/rootz/lawfirm-doc002`)

## Evento

Três releases consecutivas foram publicadas no Docker Hub e registradas em ADR no `ARCHITECTURE.md`, **sem entrada em nenhum CHANGELOG do repositório**:

| Release | Task | Data da imagem | ADR |
|---|---|---|---|
| v3.56.0 | `DOCKER-002` (OpenCode) | 2026-09-15 | §4.92 |
| v3.56.1 | `DOCKER-003` (Antigravity) | 2026-09-28 | §4.93 |
| v3.56.2 | `DOCKER-004` (OpenCode) | 2026-09-28 | §4.94 |

`LawFirmServiceProvider::VERSION` = `3.56.2`, mas `CHANGELOG.md` (raiz) e `quality/CHANGELOG.md` tinham como entrada mais recente `v3.55.1` — **três versões de distância**.

## Escopo

| Arquivo | Estado | Correto? |
|---|---|---|
| `LawFirmServiceProvider.php` | `3.56.2` | ✅ canônico |
| `ARCHITECTURE.md` (raiz) | v3.56.0/3.56.1/3.56.2, ADRs §4.92–4.94 | ✅ |
| `CHANGELOG.md` (raiz) | mais recente `v3.55.1` | ❌ 3 releases faltando |
| `quality/CHANGELOG.md` | mais recente `v3.55.1` | ❌ 3 releases faltando |
| `.ai/BASELINE.md` | `CODE_VERSION = 3.55.1` | ❌ |
| `.ai/ROADMAP.md` | `HERMES-001 READY`, `QA-ENV-001 BLOCKED`, `DOCKER-001 TODO` — todas `DONE` | ❌ |
| `.ai/LOG_INDEX.md` | `DOC-001`/`GAP-001` `TODO`, `CI-001` `IMPLEMENTED_NOT_VERIFIED`; `DOCKER-003/004` ausentes | ❌ |
| `.ai/CURRENT.md` | afirmava `QA-DATA-001` desbloqueada; `TASKS.md` dizia `BLOCKED`; contagem de testes desatualizada (47/19 vs 48/20) | ❌ |

**Padrão relevante:** o `ARCHITECTURE.md` **estava** correto nas três. O que se perde é justamente o changelog e a governança em `.ai/`.

## Causa raiz

**Confirmação do DSK7 (2026-09-30):** o OpenCode é um problema conhecido de documentação — confirmado pelo operador, que relata ter reportado a necessidade de documentação e mesmo assim visto releases serem publicadas sem changelog.

1. **De processo — o procedimento de release não é verificado por nenhum agente.** Os três bumps foram executados por agentes distintos: `DOCKER-002` (OpenCode), `DOCKER-003` (**Antigravity**) e `DOCKER-004` (OpenCode), conforme `.ai/TASKS.md`. A release v3.56.1, atribuída ao **Antigravity**, também ficou sem entrada nos CHANGELOGs — o que demonstra que **o defeito não é exclusivo do OpenCode**: nenhum dos agentes tem o procedimento de bump como rotina verificada, e nada no fluxo impede uma release publicada sem rastro documental. O bump inclui, por convenção, atualizar os CHANGELOGs, mas essa convenção não é automatizada, não é exigida por nenhum checklist, e os CHANGELOGs não são gerados a partir do ADR (que, nas três releases, **foi** atualizado corretamente).
2. **De guardrail — nenhuma das 13 regras do `validate_test_docs.py` comparava a versão do código com a documentação.** O validador executava com **0 erros** durante todo o drift, inclusive sob o Antigravity.

É a **terceira** ocorrência do mesmo defeito: `DOC-001` fechou a de v3.55.1 (2026-08-26) com o mesmo formato de falha.

**Sobre atribuição:** os commits são assinados `SuiteZap <suitezap@gmail.com>` — a identidade do git **não distingue agente**. A atribuição vem exclusivamente do campo *Owner* no `.ai/TASKS.md`, então divergências de atribuição entre o registro e a realidade são invisíveis para o git. Se o `TASKS.md` estiver errado, o registro está errado.

## Impacto

- Quem consultasse changelog encontraria v3.55.1 como release atual, com duas versões e três imagens já em produção.
- `ROADMAP.md` mandava um agente novo reexecutar `HERMES-001`, `QA-ENV-001` e `DOCKER-001` (todas concluídas) — retrabalho e risco de lock desnecessário.
- `CURRENT.md` afirmava cadeia QA desbloqueada sem ambiente de execução — Gasto de effort numa task irrealizável.
- Nenhum impacto em runtime: as imagens publicadas estão íntegras.

## Correção aplicada (`DOC-002`)

1. Entradas v3.56.0, v3.56.1 e v3.56.2 no `CHANGELOG.md` raiz e no `quality/CHANGELOG.md` (reconstituídas a partir dos ADRs §4.92–4.94 e do estado do repositório, preservando ordem decrescente).
2. `.ai/BASELINE.md`: tabela de versões reconciliada para 3.56.2; histórico das três ocorrências registrado.
3. `.ai/ROADMAP.md`: fases 1 e 4 concluídas; bloqueadores de ambiente documentados.
4. `.ai/LOG_INDEX.md`: `DOC-001`/`GAP-001`/`CI-001` corrigidos; `DOCKER-003`/`DOCKER-004`/`DOC-002`/`OPS-WEBHOOK-001` acrescentados; regra "índice é derivado do TASKS.md" adicionada.
5. `.ai/CURRENT.md`: `QA-DATA-001` mantida `BLOCKED` com pré-requisito explícito; contagem de testes corrigida (48: 20 active / 24 implemented_unverified / 4 planned).
6. **Regra 14 (Consistência de Versão)** no `validate_test_docs.py`: extrai `LawFirmServiceProvider::VERSION` e compara com a entrada mais recente de cada CHANGELOG; falha o CI na divergência. Testada nos dois sentidos (exit 0 alinhado; exit 1 com changelog regredido para v3.56.1).

## Prevenção

- Bump futuro de `LawFirmServiceProvider::VERSION` sem entrada de changelog **quebra o `lawfirm-ci.yml`**.
- **Pendências não resolvidas por esta task:**
  - **Procedimento de release ainda não é compartilhado** com o OpenCode — a causa raiz de processo permanece. O guardrail mitiga o sintoma, não a origem.
  - ADR §4.94 registra que parte do conteúdo da v3.56.2 estava **não commitada no momento do build** — a imagem reflete o disco, não um commit. Reconciliação imagem↔histórico pendente.
  - `packages/SuiteZap/LawFirm/docs/ARCHITECTURE.md` continua sem menção às releases (cópia desatualizada, anterior a este incidente).
  - `OPS-WEBHOOK-001` segue `Unassigned` — bloqueador provável do piloto.
  - `.git/` continua sincronizado pelo Syncthing (`INC-2026-09-15`), violando o `ADR-GOV-003`.
