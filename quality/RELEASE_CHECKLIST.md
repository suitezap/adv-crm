# ✅ Checklist de Homologação e Release (RELEASE_CHECKLIST.md)

Este checklist deve ser executado obrigatoriamente antes de qualquer publicação de versão do LawFirm CRM para Staging ou Produção.

> ## ⚠️ LEIA ANTES: [`.ai/REGRAS-DE-RELEASE.md`](../.ai/REGRAS-DE-RELEASE.md)
>
> **Regra obrigatória para todos os agentes (Antigravity, OpenCode, Hermes e humano).** A seção 0 é **gate**: se qualquer item dela falhar, **não prossiga** para nenhuma outra seção. As releases `v3.56.0`, `v3.56.1` e `v3.56.2` (15–23/09/2026) foram publicadas sem entrada em changelog porque este gate não existia.

## 0. 🚦 GATE DE RELEASE — bloqueia todas as demais seções

- [ ] `LawFirmServiceProvider::VERSION` alterado para a nova versão e **coincidente** com `docker/entrypoint.sh` e `docker-stack-template.yml`.
- [ ] ADR criado no `ARCHITECTURE.md` na seção `4.x` subsequente.
- [ ] **Entrada no `CHANGELOG.md` (raiz), no TOPO**, em ordem decrescente de versão.
- [ ] **Entrada no `quality/CHANGELOG.md`, no TOPO**, com a mesma versão e o efeito sobre testes/qualidade.
- [ ] Toda versão da série corrente presente no `CHANGELOG.md` raiz **também** está no `quality/CHANGELOG.md` (paridade de série — Regra 15).
- [ ] `.ai/BASELINE.md` atualizado (`CODE_VERSION` e `DOCUMENTED_VERSION`).
- [ ] **`python quality/scripts/validate_test_docs.py` retorna `exit 0`.** Gate automático (Regras 14 e 15); falha o `lawfirm-ci.yml`.
- [ ] **Todas as alterações COMMITADAS antes do build** — a imagem reflete o disco, e disco sujo publica código sem histórico (ADR §4.94).
- [ ] Lock `.ai/locks/{TASK}.lock.yaml` criado **antes** de começar, com `write_scope` cobrindo todos os arquivos tocados, agora em `RELEASED`.

```bash
python quality/scripts/validate_test_docs.py; echo "validador exit=$?"
grep -m1 "const VERSION" packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php
grep -E "^## " CHANGELOG.md | head -2
grep -E "^## " quality/CHANGELOG.md | head -2
git status --porcelain        # tem que estar vazio: sem WIP no momento do build
```

---

## 1. Validação Estática e Documental
- [x] `python quality/scripts/validate_test_docs.py` retorna código de saída `0` com 0 erros. *(Verificado em v3.55.1 — 2026-08-26)*
- [ ] `git diff --exit-code quality/COVERAGE_MATRIX.md` retorna 0 (matriz 100% sincronizada com o código).
- [ ] Nenhum teste P0 ativo, quarentenado ou desativado possui `owner: unassigned`.
- [ ] Todos os testes quarentenados (`quarantined`) possuem issue válida e prazo de resolução não expirado.

## 2. Validação Backend e Testes Automatizados
- [ ] `php vendor/bin/pest --testsuite=Unit,Feature,Security` passa com 100% de sucesso.
- [x] Trava de segurança `DatabaseSafetyGuard` testada e aprovada com sentinela `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST` (`SEC-GUARD-001`). *(Verificado em v3.55.0)*
- [ ] Provas de isolamento multi-tenant (`TENANT-SEC-001` a `005`) aprovadas sem vazamento entre `tenant_a` e `tenant_b`.

## 3. Validação E2E no Navegador
- [x] Build único da imagem candidata `suitezap/lawfirm:candidate-local` executado com sucesso. *(Verificado em v3.55.1 — 2026-08-26)*
- [x] Ambiente de testes sobe via `docker-compose.test.yml` em rede `quality_internal` com os 9 serviços orquestrados. *(Verificado em v3.55.1 — 2026-08-26)*
- [x] Readiness de todos os 9 serviços confirmada via `service_healthy`. *(Verificado em v3.55.1 — 2026-08-26)*
- [x] Testes Playwright (`E2E-LEAD-001`, `E2E-AI-001`) executam com 100% de sucesso (3/3 passed). *(Verificado em v3.55.1 — 2026-08-26)*
- [ ] Teste negativo de egress confirma bloqueio de saída para a internet nos contêineres de app e worker.

## 4. Promoção e Release
- [ ] Tag semântica aplicada sobre a mesma imagem candidata testada (sem rebuild).
- [ ] Imagem enviada ao Docker Hub e **SHA256 Digest imutável** capturado e registrado no changelog.
- [ ] Staging atualizado com o SHA256 Digest registrado.
- [ ] Smoke tests executados com sucesso em Staging.
- [ ] Aprovação humana formal registrada antes da promoção para Produção no Docker Swarm.

