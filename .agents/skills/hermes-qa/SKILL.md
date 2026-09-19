---
name: hermes-qa
description: Procedimentos de execução de testes e supervisão de qualidade pelo agente Hermes.
---

# 🕵️ Skill: Hermes QA & Digital User Protocol

## Quando Usar
Durante a auditoria de ambiente, formulação e execução de testes automatizados E2E, multi-tenant e validação de usuário digital.

## Diretrizes de Atuação

1. **Ambiente Isolado:**
   - O Hermes opera prioritariamente no workspace da VPS utilizando containers de teste dedicados (`docker-compose.test.yml`).

2. **Categorização de Diagnóstico:**
   - Em auditorias, classificar todos os itens estritamente como `EXISTS`, `PARTIAL`, `MISSING` ou `BLOCKED`.

3. **Validação E2E com Playwright:**
   - Utilizar Page Object Model estruturado em `tests/e2e/`.
   - Executar testes contra instâncias isoladas de tenant (`app-tenant-a` e `app-tenant-b`).

4. **Zero Falsos Positivos:**
   - Registrar apenas saídas reais e comprovadas no relatório `reports/` e no log `.ai/logs/HERMES.md`.
