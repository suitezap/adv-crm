# ADR-001: Isolamento de Bancos de Teste MySQL e Eliminação do SQLite na Certificação

## Status
Aprovado

## Contexto
O LawFirm CRM é uma aplicação SaaS jurídica multi-tenant complexa que opera sobre múltiplos bancos MySQL (`mothership` para controle global e bancos tenant-scoped para dados dos escritórios). Testar essa arquitetura utilizando SQLite em memória mascara incompatibilidades críticas de dialeto SQL, transações cross-database e isolamento multi-tenant real.

## Decisão
1. **Eliminação do SQLite para Certificação**: Nenhuma release ou pipeline de homologação utilizará SQLite como banco de testes.
2. **Ambiente MySQL Multi-Database**: A suíte de testes utilizará contêineres MySQL dedicados com três bancos isolados:
   - `mothership_test`
   - `tenant_a_test`
   - `tenant_b_test`
3. **DatabaseSafetyGuard Obrigatório**: Toda execução de teste é protegida por trava estrita que aborta se `APP_ENV !== 'testing'`, se o sentinel `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST` estiver ausente, ou se qualquer banco não terminar com `_test`.

## Consequências
- Testes reproduzem com 100% de fidelidade o comportamento de produção.
- Zero risco de contaminação ou execução acidental contra bancos reais.
