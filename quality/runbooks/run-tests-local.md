# 💻 Runbook: Execução Local da Suíte de Qualidade (run-tests-local.md)

Este guia orienta a execução dos testes e validações em ambiente de desenvolvimento local.

---

## 1. Validação Documental e Estática (Sem Banco)

```bash
# 1. Instalar dependências Python dos scripts de qualidade
pip install -r quality/scripts/requirements-quality.txt

# 2. Executar testes unitários do validador
pytest quality/scripts/tests/test_validate_test_docs.py -v

# 3. Executar o validador estático (12 regras)
python quality/scripts/validate_test_docs.py

# 4. Gerar e verificar a matriz de cobertura
python quality/scripts/generate_coverage_matrix.py
git diff --exit-code quality/COVERAGE_MATRIX.md
```

---

## 2. Execução de Testes PHP (Pest) com Ambiente Local

*(Aplicável a partir da Etapa 2)*

```bash
# 1. Configurar .env.testing local
cp .env.testing.example .env.testing

# 2. Executar suítes de teste (Unit, Feature, Security)
php vendor/bin/pest --testsuite=Unit,Feature,Security

# 3. Executar com cobertura de código (PCOV habilitado)
php vendor/bin/pest --coverage --coverage-clover=reports/coverage.xml --coverage-html=reports/coverage-html
```
