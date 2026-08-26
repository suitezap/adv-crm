# ADR-003: Estratégia de Testes de IA — Simulação Determinística vs Execução Real

## Status
Aprovado

## Contexto
O LawFirm CRM integra assistentes jurídicos de IA via webhooks N8N com débito de SuiteCoins no MotherShip. Executar chamadas reais de LLMs em suítes contínuas de CI/CD gera custos financeiros elevados, latência variável e risco de quebras por instabilidade de rede externa.

## Decisão
1. **Padrão Simulado (Default Custo R$ 0,00)**: Todos os testes de Feature e E2E executam com `AI_REAL_TESTS=false` e `Http::preventStrayRequests()` ativo. As respostas do N8N e MotherShip são simuladas com payloads determinísticos contendo Markdown e metadados.
2. **Suíte Real Controlada (Manual)**: Testes com LLMs reais são isolados sob flag explícita `AI_REAL_TESTS=true`, executados apenas manualmente em ambiente de homologação com limites rígidos de orçamento e dados sintéticos.
3. **Auditoria de Falhas e Degradação Graciosa**: A suíte cobre formalmente cenários de falha HTTP do N8N, validação de transições de status (`queued` -> `processing` -> `completed`/`failed`) e ausência de travamento no worker da fila.

## Consequências
- Execução rápida, reprodutível e com custo financeiro zero nos pipelines diários e PRs.
- Cobertura robusta de contratos de erro e resiliência da fila assíncrona.
