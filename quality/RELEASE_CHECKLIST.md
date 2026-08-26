# ✅ Checklist de Homologação e Release (RELEASE_CHECKLIST.md)

Este checklist deve ser executado obrigatoriamente antes de qualquer publicação de versão do LawFirm CRM para Staging ou Produção.

---

## 1. Validação Estática e Documental
- [ ] `python quality/scripts/validate_test_docs.py` retorna código de saída `0` com 0 erros.
- [ ] `git diff --exit-code quality/COVERAGE_MATRIX.md` retorna 0 (matriz 100% sincronizada com o código).
- [ ] Nenhum teste P0 ativo, quarentenado ou desativado possui `owner: unassigned`.
- [ ] Todos os testes quarentenados (`quarantined`) possuem issue válida e prazo de resolução não expirado.

## 2. Validação Backend e Testes Automatizados
- [ ] `php vendor/bin/pest --testsuite=Unit,Feature,Security` passa com 100% de sucesso.
- [ ] Trava de segurança `DatabaseSafetyGuard` testada e aprovada com sentinela `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST` (`SEC-GUARD-001`).
- [ ] Provas de isolamento multi-tenant (`TENANT-SEC-001` a `005`) aprovadas sem vazamento entre `tenant_a` e `tenant_b`.

## 3. Validação E2E no Navegador
- [ ] Build único da imagem candidata `suitezap/lawfirm:candidate-{COMMIT_SHA}` executado com sucesso.
- [ ] Ambiente de testes sobe via `docker-compose.test.yml` em rede `quality_internal` (`internal: true`).
- [ ] Readiness de todos os 8 serviços (`mysql-test`, `mothership-db-test`, `redis-test`, `mock-server`, `app-tenant-a`, `app-tenant-b`, `worker-tenant-a`, `worker-tenant-b`) confirmada.
- [ ] Testes Playwright (`LEAD-E2E-001` a `003`) executam com 100% de sucesso.
- [ ] Teste negativo de egress confirma bloqueio de saída para a internet nos contêineres de app e worker.

## 4. Promoção e Release
- [ ] Tag semântica aplicada sobre a mesma imagem candidata testada (sem rebuild).
- [ ] Imagem enviada ao Docker Hub e **SHA256 Digest imutável** capturado e registrado no changelog.
- [ ] Staging atualizado com o SHA256 Digest registrado.
- [ ] Smoke tests executados com sucesso em Staging.
- [ ] Aprovação humana formal registrada antes da promoção para Produção no Docker Swarm.
