# 🧭 Hermes Log (Append-Only)

> **Agent:** Hermes  
> **Roles:** QA Architect / Test Engineer / Digital User QA  
> **Status:** ACTIVE  
> **Workspace:** VPS / Remote QA Environment

---

## [2026-08-26 23:15] SYSTEM-BOOTSTRAP

- **Agent:** Hermes
- **Role:** QA ARCHITECT / TEST ENGINEER / DIGITAL USER QA
- **Branch:** `law-firm-custom`
- **Base Commit:** `11f5b4e7e0812e53d5d0e70353326112386f18e6`
- **Objective:** Inicialização do log append-only do agente Hermes no ecossistema de governança multiagente LawFirm CRM.
- **Task Atribuída:** `HERMES-001` (Status: `READY`)
- **Próxima Ação:** Executar a tarefa `HERMES-001` diretamente na VPS após validação do snapshot de entrada.

---

## [2026-08-30] HERMES-001-AUDIT

- **Task:** `HERMES-001` — VPS, Workspace & QA Infrastructure Audit
- **Status:** `VERIFIED` (diagnostic complete, result delivered)
- **Gates:** Gate1 SNAPSHOT MATCH (HEAD=11f5b4e7...), Gate2 SYNC_CONFLICTS NONE, Gate3 lock acquired/released.
- **Result:** `.ai/handoffs/RESULT-HERMES-001.md` written.
- **Highlights:** Docker engine BLOCKED (daemon socket permission denied, user not in docker group); Docker Compose, Git, Python, Playwright + cached Chromium EXISTS; system pytest MISSING; full QA stack declared but not provisioned (no containers running); all 8 shared `.agents/skills` detected and loadable (YES); `validate_test_docs.py` -> 0 errors.
- **Risks flagged:** SECRET_SYNC_RISK (.env* in synced tree, no .stignore), WORKSPACE_ARCHITECTURE_GAP (operating in shared synced tree incl .git), RAM 3.7Gi tightness, Playwright artifacts in synced tree.
- **Outcome:** QA-ENV-001 marked READY upon review; resolve docker group access first.


---

## [2026-08-31] QA-ENV-001-PROVISION

- **Task:** `QA-ENV-001` — Provision & Harden Isolated LawFirm QA Environment on VPS
- **Status:** `DONE` (docker access remediated; minimal QA data-plane provisioned & verified)
- **Gates:** Gate1 SNAPSHOT MATCH (branch law-firm-custom, HEAD 11f5b4e7...). Gate2 SYNC_CONFLICTS NONE (0 *sync-conflict*). Gate3 pre-existing dirty worktree PRESERVED (no restore/reset/checkout/clean/stash; functional mods + untracked kept).
- **Lock:** `.ai/locks/QA-ENV-001.lock.yaml` acquired (base 11f5b4e7...), kept ACTIVE for after-action review.
- **Phase A — Docker access remediated:** rootz added to `docker` group (gid 988) via `usermod -aG docker rootz`; daemon enabled (`systemctl enable --now docker`, exit 0). Verified `docker version` (client+server 29.7.2), `docker info` (overlayfs, systemd cgroup v2), `docker compose version` v5.5.0 via `sg docker`. Socket remains 660 root:docker (NOT weakened). No TCP listener on :2375/:2376 (no insecure daemon exposure). Action taken with explicit user authorization (provided sudo password for the two documented admin commands).
- **Phase B — .stignore created:** none existed. Added SECRET_RUNTIME_EXCLUSIONS: `.env*` (case-insensitive), `*.pem/*.key/*.p12/*.pfx/*.id_rsa`, `*.log`, `storage/logs/`, `storage/framework/sessions/`, `reports/`, `test-results/`, `blob-report/`, `playwright-report/`, `playwright/.cache/`, `.pytest_cache/`, `__pycache__/`, `*.pyc`. No required QA source/config (tests/, quality/, docker/testing/, tests/e2e/) hidden. Existing env files NOT deleted.
- **Phase C — WORKSPACE_ARCHITECTURE_GAP documented:** Hermes continues operating on shared synced tree /home/rootz/LawFirm (incl .git). No second workspace/worktree created. Runtime artifacts directed outside repo via .stignore.
- **Phase D — Capacity:** 4 vCPU, 3.7GiB RAM (2.7Gi avail pre-stack), 3.7Gi swap, 39G disk. CLASSIFIED CAPACITY_LIMITATION: started only the data-plane (4 services) for validation; full 10-service stack (incl 2x app, 2x worker, playwright) deferred to avoid instability on 3.7GiB.
- **Phase E — QA deps:** validate_test_docs.py PASS (0 errors). pytest system MISSING (isolated env defined via tests/e2e/requirements-test.txt; canonical runner = playwright-test Docker image). Playwright 1.61.0 + cached Chromium/headless/ffmpeg EXISTS.
- **Phase F — Compose validated:** `docker compose config --quiet` exit 0. Created external `quality_internal` internal bridge network (enforces zero-egress E2E). ISOLATION guards confirmed: APP_ENV=testing + TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST on php-tests/app-tenant-a/app-tenant-b/worker-*; test-only DBs (tenant_a_test/tenant_b_test/mothership_test); no production targets. Mock-server + worker + playwright assets all present.
- **Phase G — Provisioned minimal QA data-plane:** mysql-test, mothership-db-test, redis-test, mock-server ALL healthy (wait verified MySQL healthy). App/worker/playwright NOT started: their referenced image `suitezap/lawfirm:candidate-local` is absent (produced by DOCKER-001/CI pipeline lawfirm-ci.yml:99) — out of QA-ENV-001 scope; no improvised build.
- **Phase H — Reachability verified:** Redis PONG; WireMock /__admin/health healthy (v3.13.2); all 4 containers attached ONLY to quality_internal; no host port publishing; test DBs present: tenant_a_test + tenant_b_test (mysql-test), mothership_test (mothership-db-test).
- **Highlights:** No CRM feature changes, no functional tests implemented, no production DB/Docker touched, no pre-existing functional code modified. RESULT: `.ai/handoffs/RESULT-QA-ENV-001.md`.
- **Risks:** (1) app/worker/playwright BLOCKED pending DOCKER-001 candidate image; (2) RAM 3.7GiB tight for full 10-service stack; (3) docker group membership confers container admin equivalent (standard Docker model) — documented.
- **Outcome:** QA-ENV-001 DONE; QA-DATA-001 readiness keyed to candidate image availability (see RESULT).

## 2026-09-30 — DOC-002 (Documentação das releases v3.56.0/3.56.1/3.56.2 + guardrail de versão)

- **Lock:** `.ai/locks/DOC-002.lock.yaml` (base `e8afb269`, worktree isolado `/home/rootz/lawfirm-doc002`).
- **Autorização:** DSK7 em 2026-09-30, após relatório de avaliação. Exceção explícita à regra de não-edição de docs do LawFirm, motivada por releases saírem sem documentação e o OpenCode ser problema conhecido nesse quesito (relato do DSK7).
- **Escopo revisto durante a execução:** a primeira versão desta task documentava apenas a v3.56.0 sobre a branch `feature/ajustes-no-leads-view`. Ao buscar o remoto, verificou-se que a `2.1` já avançara 30 commits e que o código estava em **v3.56.2** — faltando **três** releases nos CHANGELOGs, não uma. A task foi reaplicada sobre `origin/2.1` em worktree isolado, para não tocar o WIP de outro agente na branch de feature (`lead-tools-panel.blade.php`, `tests/e2e/`), que colidiria com o commit remoto `cef01638` (modal Chatwoot no LeadToolsPanel).
- **Achado:** v3.56.0 (DOCKER-002, 2026-09-15), v3.56.1 (DOCKER-003) e v3.56.2 (DOCKER-004, 2026-09-28) publicadas no Docker Hub e registradas nos ADRs §4.92-4.94 do ARCHITECTURE.md, ausentes de ambos os CHANGELOGs. Terceira ocorrência do BASELINE_VERSION_MISMATCH (a primeira, v3.55.1, foi fechada por DOC-001 em 2026-08-26).
- **Causa raiz (dupla):** (1) processo — o procedimento de release **não é verificado por nenhum agente**. O `.ai/TASKS.md` atribui `DOCKER-002`/`004` ao OpenCode e `DOCKER-003` ao **Antigravity**; a v3.56.1 (Antigravity) também ficou sem changelog, o que prova que o defeito **não é exclusivo do OpenCode** (já conhecido pelo DSK7 como problema de documentação) e sim a ausência de um procedimento verificável por qualquer agente. (2) guardrail — as 13 regras do validador não comparavam versão de código com documentação, então passava com 0 erros durante o drift.
- **Atribuição:** commits assinados `SuiteZap <suitezap@gmail.com>` **não distinguem agente**; a fonte da atribuição é exclusivamente o campo *Owner* do `.ai/TASKS.md`. Se o registro divergir da realidade, o git não revela.
- **Correção:** entradas v3.56.0/3.56.1/3.56.2 no CHANGELOG raiz e em quality/CHANGELOG (reconstituídas dos ADRs §4.92-4.94, ordem decrescente preservada); BASELINE.md reconciliado para 3.56.2 com histórico das três ocorrências; ROADMAP.md fases 1 e 4 concluídas + bloqueadores de ambiente; LOG_INDEX.md com CI-001/DOC-001/GAP-001 corrigidos e DOCKER-003/004/DOC-002/OPS-WEBHOOK-001 acrescentados; CURRENT.md com QA-DATA-001 mantida BLOCKED e contagem de testes corrigida (48: 20 active / 24 implemented_unverified / 4 planned).
- **Guardrail novo — Regra 14 (Consistência de Versão):** extrai LawFirmServiceProvider::VERSION e compara com a entrada mais recente de cada CHANGELOG; falha o CI na divergência. Testada nos dois sentidos na v3.56.2: exit 0 alinhado, exit 1 com CHANGELOG regredido para v3.56.1. Import `re` adicionado.
- **Incidente:** `.ai/incidents/INC-2026-09-30-doc-drift-v356.md`.
- **Isolamento:** aplicação em worktree dedicado; a árvore principal (`/home/rootz/Sync/Lawfirm`, branch `feature/ajustes-no-leads-view`) não foi modificada — WIP de outro agente preservado.
- **Pendências não resolvidas:** (a) procedimento de release não compartilhado com o OpenCode — causa raiz real; (b) ADR §4.94 registra conteúdo não commitado no build da v3.56.2; (c) `docs/ARCHITECTURE.md` (cópia) desatualizada; (d) `OPS-WEBHOOK-001` Unassigned; (e) `.git/` segue sincronizado pelo Syncthing (INC-2026-09-15).

## 2026-09-30 — DOC-003 (Regra 15: Cobertura da Série no changelog de qualidade)

- **Lock:** `.ai/locks/DOC-003.lock.yaml` (base `8f563040`, worktree `/home/rootz/lawfirm-doc002`).
- **Autorização:** DSK7 em 2026-09-30, após validar o merge do DOC-002 feito pelo Antigravity.
- **Gatilho:** validação pós-merge. O Antigravity mergeou o DOC-002 (`62c61153`, `8f563040`) e publicou a v3.56.3 (`DOCKER-005`, ADR §4.95) corretamente documentada nos DOIS changelogs — primeiro release a passar pelo guardrail da Regra 14, quecumpreu seu papel.
- **Furo encontrado na própria Regra 14:** ela compara `LawFirmServiceProvider::VERSION` apenas com a entrada MAIS RECENTE de cada changelog. No estado real, `CHANGELOG.md` raiz tinha 3.56.0-3.56.3 mas o `quality/CHANGELOG.md` só tinha 3.56.2 e 3.56.3 — v3.56.0 e v3.56.1 ausentes — e o validador retornava exit 0 (verde). A Regra 14 detecta deriva de PONTA (release sem doc), nao deriva de INTERVALO (release na raiz, ausente no derivado).
- **Correcao:** nova **Regra 15 (Cobertura da Serie)** exige que toda versao da serie corrente (major.minor do codigo) presente no CHANGELOG raiz esteja tambem no quality/CHANGELOG. Escopo restrito a serie corrente: series antigas (3.55.x e anteriores) ficam fora para nao gerar divida historica infinita. A Regra 14 foi refatorada para deduplicar a serie preservando a ordem de aparicao.
- **Entradas retroativas:** v3.56.0 e v3.56.1 adicionadas ao quality/CHANGELOG.md, em ordem decrescente (3.56.3, 3.56.2, 3.56.1, 3.56.0).
- **Verificacao (4 cenarios):** (1) estado real pre-correcao -> Regra 15 falha citando v3.56.1 e v3.56.0; (2) apos as entradas -> exit 0; (3) removendo v3.56.1 -> Regra 15 falha citando a versao; (4) codigo em v3.57.0 (serie diferente) -> so a Regra 14 dispara, a Regra 15 NAO exige as 3.56.x, confirmando que e cirugica.
- **Incidente:** `.ai/incidents/INC-2026-09-30-changelog-cobertura.md`.
- **Erro próprio:** a primeira tentativa de reordenar os blocos do changelog duplicou secoes; detectada pelo proprio diff, revertida com `git checkout --` e refeita com patch ancorado.
