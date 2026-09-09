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

## [Unreleased] - FIN-COBRANCAS-001 (Financial / TenantFinance — Fase 2)

### Adicionado
- `tenant_id` (string, nullable, indexed) a `law_financials`, `tenant_invoices`, `tenant_asaas_settings`, `tenant_asaas_customers` via 4 migrations idempotentes com `Schema::connection('mysql')` + `hasColumn` guard.
- `SaaS\Scopes\TenantScope` + `SaaS\Concerns\BelongsToTenant` (auto-fill no creating + escopo global por `config lawfirm.tenant_id`).
- Docs `quality/modules/financial.md` + `tenant-finance.md`; testes `FIN-FEATURE-001/002`, `TENANT-FIN-001`, `TENANT-SEC-006` em `implemented_unverified` (35 → 39 no catálogo).
- Testes Pest: `tests/Feature/Financial/FinancialTenantTest.php`, `tests/Feature/TenantFinance/TenantInvoiceTest.php`, `tests/Security/FinancialTenantIsolationTest.php`.

### Corrigido
- `InvoiceController@index` passa a usar `TenantInvoiceDataGrid` (antes `FinancialDataGrid` — tabela errada).
- `GET /cobrancas/api/customers/{person_id}` reordenada antes de `GET /cobrancas/{id}` + `whereNumber('id')` (fim do sombreamento `/api` → 404).
- `invoices/index.blade.php`: removido `fetch()` hardcoded `/financial/quick-pay|send-whatsapp`; ações Asaas via rotas nomeadas + `bouncer()` checks em todos os métodos; `api.customer` incluída no ACL `cobrancas.view`.
- `TenantAsaasService::getSettings()` escopado por sessão + `getSettingsForTenant()` para webhook; webhook valida `webhook_token` do tenant dono da invoice (401 em mismatch); DataGrids filtram `tenant_id`; filtro de responsável escopado por `getAuthorizedUserIds()`.

> Execução foreground pendente no data-plane Docker de QA (`mysql-test` indisponível no dev Windows — `getaddrinfo failed`); transição para `active` após `QA-DATA-001`/harness com saída comprovada.

## [Unreleased] - PRIV-AUDIT-001 Ondas 2-3 (SaaS, AI, modelos, Whatsapp, agenda, portal)

### Adicionado
- Gates `saas.manage` em assinatura, checkout (plano/créditos), billing, pedidos, extrato e dashboard; `SaasOrdersDataGrid` por usuário; rotas de checkout/billing no ACL.
- `MothershipTemplate::upsert` valida `tenant_id` existente; `SaasWebhook` com `hash_equals` fail-closed.
- IA: `findAccessibleTemplate` (tenant + módulo) em show/generate/execute/processForLead; gates `view/execute`; ownership de lead na triagem; log sem `form_data`/webhook.
- Modelos: gates `modelos.view/create/edit/delete` + `documentos.create` no `saveGenerated` com propriedade do processo.
- Whatsapp: gates `whatsapp.manage` em index/QR/status/disconnect/teste.
- Agenda com escopo de prazos por usuário + gates; Kanban com ownership; Checklist com contexto + gates; Prazos notify/toggle com gates + propriedade; `DeadlineService` com `assertPrazoTenant` (inclui `syncDeadlines`).
- Portal: tokens `exp.*` 30 dias (legado depreciado), whitelist validada, logs sem PII, upload tipado 20MB.
- Blades GED com `@if(bouncer()->hasPermission('documentos.delete'))` nos botões de exclusão.
- Testes `PLAT-SEC-002`, `PORTAL-SEC-001` (`implemented_unverified`); doc `platform.md`; catálogo 45 → 47.

## [v3.55.1] - 2026-09-09 (Verificação foreground local — FIN + PRIV Onda 1-3)

### Verificado
- Data-plane de teste replicado localmente (`tenant_a_test`, `tenant_b_test`, `mothership_test` + migrate total, incluindo base `tenants`/`subscriptions`/`infrastructure_nodes` mínima) e suíte Pest executada com `DatabaseSafetyGuard` ativo: **26/26 passaram** (FIN 11, PRIV Onda 1: 11, Ondas 2-3: 4).
- 12 testes transitados `implemented_unverified` → `active` (`last_verified_version v3.55.1`, `2026-09-09`): `FIN-FEATURE-001/002`, `TENANT-FIN-001`, `TENANT-SEC-006`, `FIN-SEC-001`, `ESC-SEC-001`, `LEGAL-SEC-001`, `GED-SEC-001`, `AI-SEC-001`, `WEBHOOK-SEC-001`, `PLAT-SEC-002`, `PORTAL-SEC-001`.
- Correção de teste (não de produto): `PlatformPermissionsTest` passou a criar o Lead da triagem (404 era dado inexistente, não falta de gate).

## [Unreleased] - PRIV-AUDIT-001 Onda 1 (pré-req + Escavador + webhooks + AI + Legal/GED + SAC)

### Adicionado
- `tenant_id` em `processos` (migration + backfill + NOT NULL condicional) + `BelongsToTenant` em `Processo`, `EscavadorRequest`, `EscavadorMonitoramento`, `AssistantHistory` (+ migration `lawfirm_assistant_history`).
- Gates `bouncer()` em `Caso/Processo` (index/show/store/update/massDestroy/search/link), `GED` (todas + `assertTenantProcesso`), `Escavador History/Monitoramento`, `AssistantHistory`, `chatwoot()` (+ permissão `assistants.chatwoot`); novas chaves ACL `lawfirm.documentos.view/delete`.
- Webhooks fail-closed: Asaas sem token = nega + fim do mint ROTA 4; Escavador com tenant-scoped lookups + disparo WhatsApp de monitoramento retido (§8); Whatsapp com vínculo instância↔tenant + ACK/upsert escopados; `BlockSuspendedImport` (410) isolando rotas de importação sem tocar suspensos.
- Senha do SAC fora do código: `meta_data.sac_password` do nó Chatwoot (ver `ARCHITECTURE_mothership_orient.md §16.1`); ausente = login manual.
- Testes `ESC-SEC-001`, `LEGAL-SEC-001`, `GED-SEC-001`, `AI-SEC-001`, `WEBHOOK-SEC-001` (`implemented_unverified`); docs `escavador.md`, `ged.md`, `legal.md`, `webhooks.md`; specs `platform/*`.

### Complemento 2 — Backfill e enforcement (retomada pós-restart)
- Migrations `2026_09_09_000005` (backfill NULLs com TENANT_ID do env + log por tabela, irreversível por segurança, precedente `2026_04_01`) e `2026_09_09_000006` (NOT NULL condicional: só aplica sem NULLs restantes, senão warning + conciliação manual; isolamento segue via app).
- Aviso multi-tenant compartilhado: em base com >1 tenant, conferir o log antes de rodar o backfill (atribuição manual nesse caso).

### Complemento — Visibilidade por configuração do usuário (FIN-SEC-001)
- Gates adicionados onde faltavam: dashboard financeiro (`lawfirm.financeiro.view`) e settings Asaas (`cobrancas.settings`) — antes sem `bouncer()`, qualquer autenticado via/alterava.
- Credenciais fora do HTML: inputs `password` vazios + keep-old no `store()` (em branco preserva).
- Teste `tests/Feature/Financial/FinancialPermissionsTest.php` (401 sem permissão, leitura sem escrita, máscara de segredos, `individual` vs `global`) + spec `specs/financial/permissions/spec.md`; catálogo 39 → 40.

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

