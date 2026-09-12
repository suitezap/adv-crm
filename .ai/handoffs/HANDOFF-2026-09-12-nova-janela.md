# Handoff — Continuação em nova janela de contexto (2026-09-12)

## Por que este documento
A janela anterior atingiu ~90% da cota. Todo o estado operacional está aqui para
retomar sem perda. Branch atual: **`2.1`** (tudo commitado e pushado).

## Estado final da janela anterior
- `FIN-COBRANCAS-001` VERIFIED · `PRIV-AUDIT-001` DONE · `DOC-001`/`GAP-001` DONE
- `CI-001` DONE (pipeline verde: validate + Pest + E2E) · `REPO-HYGIENE-001` DONE
- PRs #1 e #2 mergeadas e branches deletadas. Suite local: **119/119**.
- Pendentes: `KAN-001` (Antigravity), `SEC-HARD-002`, `OPS-WEBHOOK-001` (operador),
  cadeia QA (Hermes/VPS), owners `unassigned` (decisão humana).

## Instruções exatas para retomar (cole na nova janela)
```
Estou no repo D:\Z.Hermes\www\adv-crm, branch 2.1. Leia .ai/handoffs/HANDOFF-2026-09-12-nova-janela.md,
.ai/TASKS.md e .ai/CURRENT.md, verifique git status e continue pelos TODOs em aberto.
Regras: AGENTS.md (locks em .ai/locks/, nunca git add -A, commits só com aprovação,
nada para o upstream krayin — push bloqueado via `no_push`; gh sempre com --repo suitezap/adv-crm).
```

## Fatos operacionais críticos (não redescobrir)
- **MySQL**: via Laragon (`C:\laragon\bin\mysql\mysql-8.4.3-winx64`), NÃO sobe no boot.
  Subir com: `Start-Process mysqld --defaults-file=my.ini` (datadir `C:/laragon/data/mysql-8.4`).
  Credenciais dev: usuário `krayin_admin` (senha no `.env` local, nunca commitar).
- **Data-plane de teste local**: `tenant_a_test`, `tenant_b_test`, `mothership_test`
  (ver `quality/runbooks/local-qa-dataplane.md`). Rodar Pest exige os envs do runbook.
- **Outro agente ativo no workspace** (Antigravity): já sobrescreveu `TASKS.md` uma vez.
  Releia arquivos antes de editar; locks em `.ai/locks/` valem.
- **Upstream krayin**: PR #2664 foi aberto por engano e fechado sem merge. Push para
  upstream está bloqueado. PRs #1 e #2 vivem em `suitezap/adv-crm`.
- **CI**: `lawfirm-ci.yml` verde na `2.1`. Peculiaridades já corrigidas: rede
  `quality_internal`, stub Vite (`quality/scripts/stub_admin_vite_manifest.py`),
  mock `tenants` completo, pytest com cwd `/e2e`, mounts ro de stubs.
- **Nunca commitar**: `public/**/build`, segredos, `*.bak`. `openspec/changes/` é ignorado.

## Próximo trabalho sugerido (ordem)
1. `SEC-HARD-002` — cobertura `@can`/`bouncer()` total das blades (P0s prontos).
2. `KAN-001` — com o dono (escopo restante com Antigravity).
3. `OPS-WEBHOOK-001` — ação do operador em produção (runbook pronto).
