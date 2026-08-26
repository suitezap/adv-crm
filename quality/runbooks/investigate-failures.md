# 🔍 Runbook: Diagnóstico e Investigação de Falhas (investigate-failures.md)

Este guia orienta o isolamento de causa-raiz quando testes falharem nos pipelines ou no ambiente local.

---

## 1. Falhas na Validação Documental (`validate_test_docs.py`)
- Se o script falhar por **arquivo inexistente**: certifique-se de que testes novos estejam com `status: planned`. Apenas testes `implemented_unverified`, `active`, `quarantined` ou `disabled` exigem arquivo presente no disco.
- Se o script falhar por **matriz desatualizada**: execute `python quality/scripts/generate_coverage_matrix.py` e verifique o diff com `git diff quality/COVERAGE_MATRIX.md`.

## 2. Falhas no `DatabaseSafetyGuard`
- Se o teste abortar com `RuntimeException: SENTINEL TEST_ENVIRONMENT_ACK`: confirme que `.env.testing` contém `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`.
- Se o teste abortar por nome de banco: certifique-se de que as conexões estejam configuradas com sufixo `_test` (`tenant_a_test`, `tenant_b_test`, `mothership_test`).

## 3. Falhas nos Testes E2E (Playwright)
- Os artefatos de falha (screenshots, vídeos e traces zipados) são salvos em `reports/e2e/`.
- Para inspecionar um trace de falha:
  ```bash
  playwright show-trace reports/e2e/trace.zip
  ```
