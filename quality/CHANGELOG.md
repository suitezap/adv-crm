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

---

## [v3.55.1] - 2026-08-26 (Etapa 4 — E2E Playwright, Docker Compose e Estabilização Multi-Tenant)

### Adicionado
- Infraestrutura Docker completa para testes E2E com 9 serviços orquestrados (`docker-compose.test.yml` profile `e2e`):
  - `app-tenant-a` e `app-tenant-b`: instâncias da imagem candidata `suitezap/lawfirm:candidate-local`
  - `worker-tenant-a` e `worker-tenant-b`: workers de fila isolados por tenant
  - `playwright-test`: runner one-shot Python/Playwright
  - `mysql-test`, `mothership-db-test`, `redis-test`, `mock-server` (WireMock)
- Suíte Python Playwright em `tests/e2e/`:
  - Page Object Model: `LoginPage`, `LeadPage`, `LegalPage`, `BasePage`
  - `test_lead_conversion_workflow.py`: 2 testes de criação de lead, movimentação de funil e conversão em processo
  - `test_lead_ai_workflow.py`: 1 teste de triagem inteligente via mock WireMock (sem custo real de LLM)
- Cadastro dos novos testes ativos `E2E-LEAD-001` e `E2E-AI-001` no catálogo (`quality/TEST_CATALOG.yaml`)
- Adicionado `__pycache__/` e `*.pyc` ao `.gitignore` global
- `docker/testing/playwright-entrypoint.sh`: script que resolve IPs dos tenants no `/etc/hosts` antes de rodar o pytest

### Corrigido (Infraestrutura de Boot)
- **Race condition de migrações**: `app-tenant-b` agora depende de `app-tenant-a: service_healthy` via `depends_on`, evitando `Column already exists` na base `mothership_test` durante o boot paralelo
- **Permissão de logs do worker**: inserido `chown -R www-data:www-data storage bootstrap/cache` imediatamente antes do `exec` final no `docker/entrypoint.sh`, eliminando `Permission denied` no `laravel.log` para os processos `queue:work`
- **Timeout de healthcheck**: expandido `start_period` de 40s para 180s e `retries` de 15 para 30, acomodando o boot a frio com 150+ migrações (~161s de execução)

### Promovido para `active`
- `E2E-LEAD-001` e `E2E-AI-001`: 3 testes passaram em execução real (exit code 0) em 2026-08-26

### Atualizado
- `ARCHITECTURE.md`: versão promovida para `v3.55.1`, ADR §4.90 marcado como **Concluído** (Etapas 1–4)
- `LawFirmServiceProvider::VERSION`: `'3.55.0'` → `'3.55.1'`
- `docker/entrypoint.sh`: banner de boot atualizado para `v3.55.1`
- `quality/COVERAGE_MATRIX.md`: regenerada com 35 testes cadastrados (7 ativos, 24 implementados não verificados, 4 planejados)

