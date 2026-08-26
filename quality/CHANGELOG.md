# 📜 CHANGELOG da Suíte de Testes e Governança de Qualidade

Todas as alterações, adições, quarentenas e aposentadorias de testes automatizados do LawFirm CRM são registradas neste documento.

---

## [v3.55.0] - 2026-08-21 (Etapa 1 — Governança e Validador)

### Adicionado
- Criação da infraestrutura de governança viva em `quality/`.
- Criação do catálogo formal `quality/TEST_CATALOG.yaml` com schema em 6 estados (`planned`, `implemented_unverified`, `active`, `quarantined`, `disabled`, `retired`).
- Criação dos stubs funcionais dos 7 módulos em `quality/modules/` (`auth.md`, `chatwoot.md`, `lead.md`, `legal-orchestrator.md`, `ai-assistant.md`, `tenant-isolation.md`, `governance.md`).
- Criação dos ADRs de qualidade `ADR-001` a `ADR-004` em `quality/adr/`.
- Criação dos scripts de automação `quality/scripts/validate_test_docs.py` e `quality/scripts/generate_coverage_matrix.py`.
- Cadastro inicial dos testes existentes `AUTH-FEATURE-001` e `CHATWOOT-FEATURE-001` em estado `implemented_unverified` (aguardando baseline da Etapa 2).
- Cadastro dos testes planejados `LEGAL-FEATURE-001` a `006`, `LEAD-AI-001` a `012`, `TENANT-SEC-001` a `005`, `STATIC-INTEGRITY-001` e `LEAD-E2E-001` a `003` em estado `planned`.
- Sincronização da versão para `v3.55.0` no `ARCHITECTURE.md` (ADR §4.90 em andamento), `ARCHITECTURE_dir.md` e `LawFirmServiceProvider.php`.
