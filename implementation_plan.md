# 🛡️ Plano de Implementação — Infraestrutura Permanente de Qualidade, Governança e Testes (Versão 2.8)
**LawFirm CRM / SuiteZap** — SaaS Jurídico Multi-Tenant  
**Status Atual**: Etapa 1 Concluída e Validada com Sucesso (0 Erros) | Planejamento Atualizado para Versão 2.8 | Aguardando Autorização da Etapa 2

---

## 1. Visão Geral e Objetivos do Sistema

O objetivo deste projeto é estabelecer uma **infraestrutura permanente de qualidade, memória técnica e testes automatizados** dentro do repositório do LawFirm CRM. O sistema garante:
1. **Documentação Viva e Sincronizada**: Nenhum teste existe sem estar no catálogo formal (`quality/TEST_CATALOG.yaml`) e vinculado ao documento funcional do respectivo módulo em `quality/modules/`.
2. **Ciclo de Vida em 6 Estados**: `planned` $\rightarrow$ `implemented_unverified` $\rightarrow$ `active` (além de `quarantined`, `disabled` e `retired`), com proibição estrita de transição direta de `planned` para `active`.
3. **Isolamento Multi-Tenant e Multi-Database Rigoroso**: Testes executados em contêineres MySQL dedicados com topologia segregada:
   - `mysql-test`: hospeda exclusivamente `tenant_a_test` e `tenant_b_test`.
   - `mothership-db-test`: hospeda exclusivamente `mothership_test`.
   - Travas `DatabaseSafetyGuard` com sentinel obrigatório `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`, allowlist de hosts/URLs/tenants e sufixo `_test`.
   - Isolamento de filas Redis com `REDIS_PREFIX` (`tenant_a_test_` e `tenant_b_test_`).
4. **Isolamento de Requisições Externas**: Bloqueio padrão com `Http::preventStrayRequests()` nos testes Pest e simulação de serviços via `Http::fake()` ou `mock-server` (incluindo `mothership`, `n8n`, `chatwoot`, `asaas`, `evolution`).
5. **Estratégia de Execução Docker Desacoplada e Paridade PHP 8.3**:
   - Testes PHP/Pest executados via contêiner one-shot `php-tests` baseado em imagem dedicada `docker/testing/Dockerfile.php-tests` (PHP 8.3 CLI com extensões `pdo_mysql`, `redis`, `bcmath`, `mbstring`, `curl`, `gd`, `zip`), mantendo 100% de paridade com o PHP 8.3 de produção.
   - Testes E2E no navegador executados em contêiner dedicado one-shot `playwright-test` baseado em `docker/testing/Dockerfile.playwright`.
   - Imagem de produção `suitezap/lawfirm` permanece 100% limpa (sem navegadores ou PCOV).
6. **Espera Real Bloqueante de Healthchecks e Script com Preservação de Exit Code**:
   - Inicialização dos serviços com espera determinística: `docker compose up -d --wait --wait-timeout 120`.
   - Script seguro `quality/scripts/run-backend-tests.sh` com `set -Eeuo pipefail`, trap de saída que garante `docker compose down -v`, captura e retorno fiel do exit code do Pest (`$?`) e coleta de logs em `reports/backend/` em caso de erro.
7. **Descomissionamento Definitivo do Whaticket e Centralização no Chatwoot**:
   - O Whaticket foi completamente descontinuado e removido do projeto, sendo substituído pelo acesso centralizado ao Chatwoot através do menu SAC da plataforma.
8. **Build Único e Promoção por SHA256 Digest**: A mesma imagem candidata testada no E2E é promovida e implantada através do seu Digest imutável via GitHub Environment protegido, sem reconstrução de imagens.

---

## 2. Progresso Consolidado e Status das Etapas

| Etapa | Descrição | Status | Entregáveis e Validações |
|:---|:---|:---:|:---|
| **Etapa 1** | **Governança Funcional, Sincronia de Versão e Validador** | 🟢 **CONCLUÍDA** | • Ponteiro no `AGENTS.md`<br>• `ARCHITECTURE.md` (ADR §4.90) e `LawFirmServiceProvider.php` em `v3.55.0`<br>• `quality/` criado com README, CHANGELOG, KNOWN_GAPS, RELEASE_CHECKLIST e 4 ADRs<br>• 7 Stubs de módulos em `quality/modules/`<br>• Catálogo `TEST_CATALOG.yaml` canônico com 33 testes recalculados<br>• `validate_test_docs.py` (13 regras) e `generate_coverage_matrix.py`<br>• **6/6 testes unitários aprovados no Pytest e Validador com 0 erros** |
| **Etapa 2** | **Ambiente MySQL, Sentinel, Docker Compose Inicial e Baseline dos Testes Existentes** | ⏳ **Aguardando Autorização** | • Configuração `.env.testing.example` (com instrução `php artisan key:generate --env=testing`) e sentinel `TEST_ENVIRONMENT_ACK`<br>• Criação de `docker/testing/Dockerfile.php-tests` (imagem exclusiva de teste PHP 8.3 CLI, sem PCOV nesta fase)<br>• Criação inicial de `docker-compose.test.yml` com infraestrutura de dados (`mysql-test`, `mothership-db-test`, `redis-test`) e runner `php-tests`<br>• Criação de `quality/scripts/run-backend-tests.sh` com espera bloqueante `--wait --wait-timeout 120` e preservação de exit code<br>• Criação de `tests/MultiDatabaseTestCase.php`, `DatabaseSafetyGuard.php`, `MultiTenantTestBootstrapper.php`<br>• Criação e execução dos testes negativos em `tests/Unit/DatabaseSafetyGuardTest.php` (`SEC-GUARD-001`)<br>• Injeção dinâmica de conexões `tenant_a`, `tenant_b` e `mothership`<br>• **Execução e baseline ativo condicional de `BasicTest.php`, `AuthenticationTest.php` e `ChatwootConfigTest.php`** |
| **Etapa 3** | **Testes Pest Críticos e SuiteCoins** | ⚪ Planejada | • Migração de `AuthenticationTest` $\rightarrow$ `AuthMultiTenantTest`<br>• Criação de `LegalOrchestratorTest.php` (`LEGAL-FEATURE-001` a `006`)<br>• Criação de `AiAssistantTest.php` (`LEAD-AI-001` a `012`)<br>• Criação de `SuiteCoinIntegrationTest.php` (`SAAS-FEATURE-001`)<br>• Criação de `tests/Security/MultiTenantIsolationTest.php` (`TENANT-SEC-001` a `005`)<br>• Configuração padrão de `Http::preventStrayRequests()` |
| **Etapa 4** | **Build Único da Imagem Candidata, Expansão do Compose (9 Serviços Ativos) e E2E Playwright** | ⚪ Planejada | • Build da imagem candidata `suitezap/lawfirm:candidate-{COMMIT_SHA}`<br>• Expansão de `docker-compose.test.yml` com `mock-server`, `app-tenant-a`, `app-tenant-b`, `worker-tenant-a`, `worker-tenant-b` e `playwright-test` one-shot (total de 9 serviços ativos no perfil E2E)<br>• Execução dos testes E2E (`LEAD-E2E-001` a `003`), telemetria e sanitização DOMPurify<br>• Teste negativo de egress (bloqueio de saída para internet)<br>• Coleta de screenshots, logs, traces e limpeza de volumes (`down -v`) |
| **Etapa 5** | **Consolidação Documental e Baseline de Cobertura com PCOV** | ⚪ Planejada | • Adição da extensão **PCOV** no `docker/testing/Dockerfile.php-tests`<br>• Geração do relatório `reports/coverage.xml`<br>• Atualização da matriz de cobertura definitiva e baseline real no `KNOWN_GAPS.md` |
| **Etapa 6** | **CI/CD Quality Gate e Pipeline de Release por Digest** | ⚪ Planejada | • `.github/workflows/quality-gate.yml` para PRs<br>• `.github/workflows/release.yml` com GitHub Environment protegido (`production-release`), `permissions: contents: read`, secrets do Docker Hub e promoção por SHA256 Digest imutável (sem deploy automático Swarm)<br>• ADR §4.90 marcado como concluído |

---

## 3. Schema Canônico do Catálogo (`TEST_CATALOG.yaml`) e Governança de Status

### 3.1 Schema de 19 Campos
Todo teste automatizado segue rigorosamente o schema:

```yaml
tests:
  - id: STRING                                      # Formato: {DOMÍNIO}-{CAMADA}-{NÚMERO}
    name: STRING                                    # Título descritivo em português
    domain: "Legal" | "Financial" | "GED" | "SaaS" | "AI" | "Escavador" | "DataJud" | "Whatsapp" | "TenantFinance" | "Atendimento" | null
    layer: "domain" | "platform" | "governance" | "e2e"
    module: STRING                                  # Slug do módulo (ex: "legal-orchestrator", "ai-assistant", "tenant-isolation", "governance")
    type: "Unit" | "Feature" | "Security" | "E2E"
    priority: "P0" | "P1" | "P2"
    status: "planned" | "implemented_unverified" | "active" | "quarantined" | "disabled" | "retired"
    automated: BOOLEAN                              # true se há código de teste implementado
    test_file: STRING | null                        # Caminho do arquivo a partir da raiz (obrigatório se automated: true; previsto se planned; null se retired)
    documentation: STRING                           # Caminho do arquivo em quality/modules/
    source_references:                              # Rastreabilidade de código em produção
      - path: STRING
        symbol: STRING
        purpose: STRING
    dependencies: ["mysql", "redis", "mock-server"] # Dependências de infraestrutura
    external_services:                              # Declaração de integrações externas
      - name: "n8n" | "mothership" | "chatwoot" | "asaas" | "evolution" | "escavador" | "datajud" | "storage"
        mode: "none" | "mock" | "real_controlled"
    tenant_scope: "single_tenant" | "multi_tenant_matrix" | "cross_tenant_validation" | "static_analysis"
    destructive: BOOLEAN                            # true se altera/apaga dados durante execução
    owner: STRING                                   # Responsável técnico (padrão: "unassigned")
    last_verified_version: STRING | null            # Versão validada (null se planned ou implemented_unverified)
    last_verified_date: STRING | null               # Data ISO8601 da execução real (null se planned ou implemented_unverified)
    notes: STRING | null                            # Justificativa obrigatória se quarantined ou disabled
```

### 3.2 Harmonização do Campo `test_file`
- **Em `planned`**: O campo `test_file` pode conter o caminho previsto do arquivo que será criado no futuro, com `automated: false`.
- **Quando `automated: true`**: O campo `test_file` torna-se **estritamente obrigatório** e o arquivo deve existir no disco.
- **Em `retired`**: O campo `test_file` pode ser `null` quando o código foi descontinuado e não preservado no disco.

### 3.3 Governança Rigorosa de Transição de Status
1. **Transição em 3 Passos Obrigatória**:
   - Todo teste novo nasce como `planned` (`automated: false`, `last_verified_*: null`).
   - Após o arquivo de código ser criado no disco, o teste transita para `implemented_unverified` (`automated: true`, `last_verified_*: null`).
   - Somente após **execução real em primeiro plano aprovada com saída comprovada**, transita para `active` com versão e data real de execução preenchidas.
   - **É terminantemente proibido** transitar diretamente de `planned` para `active`.
   - **Promoção Condicional**: Testes com falha ou erro **jamais** podem ser promovidos para `active`.
   - Toda transição atualiza simultaneamente o `TEST_CATALOG.yaml`, o respectivo documento em `quality/modules/`, o `quality/CHANGELOG.md` e a matriz de cobertura (`COVERAGE_MATRIX.md`).
2. **Regras de Responsabilidade de Testes P0**:
   - `owner: unassigned` é permitido em testes `planned`, `implemented_unverified` e `active` durante a fase de desenvolvimento.
   - Testes P0 `quarantined` ou `disabled` devem possuir responsável definido **imediatamente**.
   - Antes de qualquer release de homologação ou produção, **nenhum teste P0 `active`, `quarantined` ou `disabled` pode permanecer com `owner: unassigned`**. O release gate falhará se essa condição não for atendida.

---

## 4. Detalhamento Técnico das Próximas Etapas

### Etapa 2 — Ambiente MySQL, Sentinel, Docker Compose Inicial e Baseline dos Testes Existentes

#### 1. Topologia de Dados, Runner Pest e Paridade PHP 8.3
- **Estratégia Escolhida**: Execução dos testes PHP/Pest dentro do contêiner isolado one-shot `php-tests`, conectado à mesma rede Docker dos bancos (`quality_internal`).
- **Paridade PHP**: Criar `docker/testing/Dockerfile.php-tests`, preferencialmente derivando do mesmo estágio-base PHP utilizado pela imagem de produção, para garantir paridade real de versão, extensões e bibliotecas do sistema (sem PCOV nesta fase).
  - O `composer install` será executado **dentro** do contêiner Linux `php-tests`, utilizando as dependências de desenvolvimento (dev dependencies) necessárias ao Pest.
  - O uso do `composer.lock` será obrigatório.
  - Cache ou volume Docker poderá ser utilizado para acelerar os downloads, mas **não poderá reutilizar o diretório `vendor` produzido no Windows**.
  - O comando `composer check-platform-reqs` será executado dentro do contêiner.
  - Nota: Não declararemos “100% de paridade” antes dessa comprovação ser demonstrada.
- **Criação Inicial do `docker-compose.test.yml`**:
  - `mysql-test` (MySQL 8.0): hospeda exclusivamente `tenant_a_test` e `tenant_b_test`.
  - `mothership-db-test` (MySQL 8.0): hospeda exclusivamente `mothership_test`.
  - `redis-test` (Redis 7.0): suporte para filas com prefixos `tenant_a_test_` e `tenant_b_test_`.
  - `php-tests`: serviço one-shot configurado com volumes montados para o código da aplicação e rede `quality_internal`.
- **Script de Execução Segura com Preservação de Exit Code (`quality/scripts/run-backend-tests.sh`)**:
  ```bash
  #!/usr/bin/env bash
  set -Eeuo pipefail

  cleanup() {
      local status=$?
      echo "🧹 Finalizando contêineres e limpando volumes de teste..."
      if [ "$status" -ne 0 ]; then
          echo "❌ Falha detectada (Exit Code: $status). Coletando logs de diagnóstico..."
          mkdir -p reports/backend
          docker compose -f docker-compose.test.yml logs --no-color > reports/backend/docker-compose-failure.log || true
      fi
      docker compose -f docker-compose.test.yml down -v || true
      exit $status
  }
  trap cleanup EXIT

  echo "🚀 Subindo serviços de infraestrutura com espera bloqueante por healthchecks..."
  docker compose -f docker-compose.test.yml up -d --wait --wait-timeout 120 mysql-test mothership-db-test redis-test

  echo "🧪 Executando suíte Pest no contêiner php-tests..."
  docker compose -f docker-compose.test.yml run --rm php-tests vendor/bin/pest "$@"
  ```
- **Diferenciação e Contagem Rigorosa de Serviços**:
  - **10 Serviços Definidos no Arquivo Compose (Total: 10)**: `mysql-test`, `mothership-db-test`, `redis-test`, `mock-server`, `app-tenant-a`, `app-tenant-b`, `worker-tenant-a`, `worker-tenant-b`, `php-tests`, `playwright-test`.
  - **9 Serviços Ativos Simultaneamente no Perfil E2E (Etapa 4: 9 Serviços)**: os 8 serviços de aplicação/infraestrutura (`mysql-test`, `mothership-db-test`, `redis-test`, `mock-server`, `app-tenant-a`, `app-tenant-b`, `worker-tenant-a`, `worker-tenant-b`) + o runner one-shot `playwright-test`.
  - **Serviços Ativos na Etapa 2 (4 Serviços)**: `mysql-test`, `mothership-db-test`, `redis-test` + runner one-shot `php-tests`.

#### 2. Configuração de Variáveis de Ambiente e Sentinel
- Criar `.env.testing.example` contendo:
  ```env
  APP_ENV=testing
  # Gerar chave válida via: php artisan key:generate --env=testing
  APP_KEY=
  TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST
  
  # Topologia de Conexões de Teste
  DB_CONNECTION=tenant_a
  DB_TEST_TENANT_A_HOST=mysql-test
  DB_TEST_TENANT_A_PORT=3306
  DB_TEST_TENANT_A_DATABASE=tenant_a_test
  
  DB_TEST_TENANT_B_HOST=mysql-test
  DB_TEST_TENANT_B_PORT=3306
  DB_TEST_TENANT_B_DATABASE=tenant_b_test
  
  DB_TEST_MOTHERSHIP_HOST=mothership-db-test
  DB_TEST_MOTHERSHIP_PORT=3306
  DB_TEST_MOTHERSHIP_DATABASE=mothership_test
  
  REDIS_HOST=redis-test
  REDIS_PORT=6379
  REDIS_PREFIX=tenant_a_test_
  
  AI_REAL_TESTS=false
  ```
- Instrução mandatória: após copiar `.env.testing.example` para `.env.testing`, executar `php artisan key:generate --env=testing`.

#### 3. Fortalecimento do `DatabaseSafetyGuard.php` e Testes Negativos
- A classe `tests/Support/DatabaseSafetyGuard.php` executa antes de qualquer conexão ou operação de banco:
  1. Valida `app()->environment('testing') === true`.
  2. Valida `env('TEST_ENVIRONMENT_ACK') === 'LAW_FIRM_ISOLATED_TEST'`.
  3. Valida as 3 conexões (`tenant_a`, `tenant_b`, `mothership`).
  4. Exige que o nome de todos os bancos termine estritamente com `_test`.
  5. Aplica allowlist estrita de hosts (`127.0.0.1`, `localhost`, `mysql-test`, `mothership-db-test`), URLs e tenant IDs de teste (`tenant_a`, `tenant_b`, `1`, `2`).
  6. Aborta com `RuntimeException` imediata caso qualquer regra seja violada.
- Criar `tests/Unit/DatabaseSafetyGuardTest.php` (`SEC-GUARD-001`) contendo testes negativos:
  - Aborto se `TEST_ENVIRONMENT_ACK` ausente ou incorreto.
  - Aborto se o nome do banco não terminar com `_test`.
  - Aborto se o host estiver fora da allowlist (ex: host de produção).

#### 4. Utilitários Base e Injeção Dinâmica de Conexões
- **`MultiTenantTestBootstrapper.php`**: Injeta dinamicamente as conexões `tenant_a`, `tenant_b` e `mothership` no `config(['database.connections...'])` em tempo de execução de teste sem alterar `config/database.php` de produção.
- **`MultiDatabaseTestCase.php`**: Base abstrata herdada por testes Feature e Security que garante acionamento do `DatabaseSafetyGuard` e bootstrapper.
- **`SyntheticDataFactory.php`**: Gerador de entidades sintéticas determinísticas.

#### 5. Arquitetura Comprovada do menu Chatwoot (`CHATWOOT-E2E-001`)
O Whaticket foi integralmente substituído pelo Chatwoot, com a seguinte arquitetura validada e inspecionada no repositório:
- **Definição do Menu SAC**: Configurado em `packages/SuiteZap/LawFirm/src/Config/menu.php` (key: `sac`, name: `SAC`, route: `lawfirm.assistants.chatwoot`).
- **Rota**: A rota `Route::get('sac')` nomeada `lawfirm.assistants.chatwoot` está registrada em `packages/SuiteZap/LawFirm/src/Http/Routes/admin-saas.php`.
- **Controller e Método**: Resolvido por `SuiteZap\LawFirm\AI\Http\Controllers\Admin\AssistantController@chatwoot`.
- **View, iframe e Origem**: O controller renderiza a view `packages/SuiteZap/LawFirm/src/Resources/views/admin/assistants/chatwoot.blade.php`, que exibe o iframe apontando para a URL configurada do Chatwoot (`chatwootUrl` injetada, ex: `https://whats.suitezap.com.br`).
- **Configuração ACL**: Protegido pela permissão `lawfirm.assistants.chatwoot`, conforme definido em `packages/SuiteZap/LawFirm/src/Config/acl.php`.
- **Middleware**: Acesso mediado por `SuiteZap\LawFirm\Atendimento\Http\Middleware\CheckChatwootModule`.
- **Validação de Assinatura**: O middleware valida a permissão utilizando `SuiteZap\LawFirm\SaaS\Services\MotherShipService::getCurrentSubscription()`.

#### 6. Baseline Condicional dos Testes Existentes
- Executar contra o ambiente MySQL isolado:
  - `tests/Unit/BasicTest.php` (`BASIC-UNIT-001`)
  - `tests/Feature/AuthenticationTest.php` (`AUTH-FEATURE-001`)
  - `tests/Feature/ChatwootConfigTest.php` (`CHATWOOT-FEATURE-001`): Este teste **não testa** o menu SAC. Ele testa puramente invariantes da API subjacente (mapeamento de `inbox_id`/`account_id` e uso correto de HMAC vs API tokens). A validação visual e de permissão do menu SAC (middleware/rota) requer cobertura separada (vide `CHATWOOT-E2E-001`).

**Promoção Condicional**: somente se cada teste passar com 100% de sucesso e sua saída real for registrada, alterar o status de `implemented_unverified` para `active`, informando versão, data, ambiente e referência da evidência. Em caso de falha ou ausência de evidência, manter `implemented_unverified` e registrar o erro.

---

### Etapa 3 — Testes Pest Críticos e SuiteCoins

#### 1. Bloqueio Padrão de Requisições Externas
- Adicionar `Http::preventStrayRequests()` no boot do Pest (`tests/Pest.php` ou base `TestCase`).
- Toda requisição externa autorizada utiliza `Http::fake()` ou aponta para o `mock-server`.
- O serviço `mothership` é explicitamente simulado entre os serviços externos (junto a `n8n`, `chatwoot`, `asaas`, `evolution`).

#### 2. Migração de Autenticação
- Migrar `tests/Feature/AuthenticationTest.php` $\rightarrow$ `tests/Feature/Auth/AuthMultiTenantTest.php` (`planned` $\rightarrow$ `implemented_unverified` $\rightarrow$ `active`), validando isolamento de login, credenciais e sessão entre múltiplos usuários de tenants distintos.

#### 3. Suíte do LegalOrchestrator (`tests/Feature/Legal/LegalOrchestratorTest.php`)
- `LEGAL-FEATURE-001`: Conversão atômica de Lead ganho em Caso e Processo vinculados (`DB::transaction`).
- `LEGAL-FEATURE-002`: Rollback completo se a criação do Caso falhar.
- `LEGAL-FEATURE-003`: Rollback completo se a criação do Processo falhar.
- `LEGAL-FEATURE-004`: Proteção contra conversão duplicada de Lead já convertido em Processo.
- `LEGAL-FEATURE-005`: Priorização de Tags canônicas do Lead para preenchimento de Área e Prioridade.
- `LEGAL-FEATURE-006`: Fallback para dados de IA (`LeadTriagem`) quando Tags do Lead estiverem ausentes.

#### 4. Suíte de IA e SuiteCoins (`tests/Feature/AI/AiAssistantTest.php` e `SuiteCoinIntegrationTest.php`)
- `LEAD-AI-001` a `004`: Disparo assíncrono dos 4 assistentes (`pre-triagem-lead`, `pre-triagem-checklist`, `gerador-proposta`, `script-vendas`).
- `LEAD-AI-005`: Transições de status `queued` $\rightarrow$ `processing` $\rightarrow$ `completed`/`failed`.
- `LEAD-AI-006`: Persistência de Markdown bruto sem mutação no backend.
- `LEAD-AI-007`: Débito antecipado de SuiteCoins e registro em `saas_transactions`.
- `LEAD-AI-008`: Falha HTTP do N8N marca `failed` sem derrubar o worker.
- `LEAD-AI-009`: N8N ausente retorna contrato HTTP 503 no Controller.
- `LEAD-AI-010`: Saldo insuficiente bloqueia execução antes do débito (HTTP 402).
- `LEAD-AI-011`: Listagem paginada e isolada do histórico de execuções.
- `LEAD-AI-012`: Idempotência e proteção contra duplo débito em retentativas.
- `SAAS-FEATURE-001` (`tests/Feature/SaaS/SuiteCoinIntegrationTest.php`): Validação da transação de saldo de SuiteCoins entre o tenant local (`saas_transactions`) e a assinatura no MotherShip (`subscriptions`).

#### 5. Suíte de Isolamento Multi-Tenant (`tests/Security/MultiTenantIsolationTest.php`)
- `TENANT-SEC-001` a `005` (`tests/Security/MultiTenantIsolationTest.php`): Provas de isolamento de consultas, bloqueio de ID forjado (403/404), isolamento de histórico de IA e isolamento de filas Redis por `REDIS_PREFIX`.

---

### Etapa 4 — Build Único da Imagem Candidata, Expansão do Compose (9 Serviços Ativos) e E2E Playwright

#### 1. Build Único da Imagem Candidata
- Construir a imagem candidata uma única vez:
  ```bash
  docker build -t suitezap/lawfirm:candidate-{COMMIT_SHA} .
  ```
- Registrar o Image ID imutável para execução de todos os testes sem reconstruções posteriores.

#### 2. Orquestração Completa de 9 Serviços Ativos (`docker-compose.test.yml`)
- Rede isolada `quality_internal` (`internal: true`, zero egress para internet).
- Serviços ativos simultaneamente no perfil E2E:
  1. `mysql-test` (Bancos: `tenant_a_test`, `tenant_b_test`)
  2. `mothership-db-test` (Banco: `mothership_test`)
  3. `redis-test`
  4. `mock-server` (Mocks de N8N, MotherShip, Chatwoot, Asaas, Evolution)
  5. `app-tenant-a` (Imagem candidata)
  6. `app-tenant-b` (Imagem candidata)
  7. `worker-tenant-a` (`REDIS_PREFIX=tenant_a_test_`)
  8. `worker-tenant-b` (`REDIS_PREFIX=tenant_b_test_`)
  9. `playwright-test` (**Serviço one-shot** baseado em `docker/testing/Dockerfile.playwright`)
- Configuração de `depends_on` com `condition: service_healthy` em todas as dependências antes da inicialização do `playwright-test`.
- Timeouts explícitos, captura de logs de contêineres, screenshots, vídeos, traces e relatórios HTML salvos em `reports/e2e/`.
- Limpeza de volumes anônimos e dados entre execuções (`docker compose -f docker-compose.test.yml down -v`).

#### 3. Testes Playwright Python (`tests/e2e/`)
- `LEAD-E2E-001`: Fluxo E2E: Login $\rightarrow$ Ficha do Lead $\rightarrow$ Disparo das 4 IAs $\rightarrow$ Histórico e Telemetria.
- `LEAD-E2E-002`: Fluxo E2E: Ganho de Lead na interface $\rightarrow$ Criação visual de Caso e Processo.
- `LEAD-E2E-003`: Validação visual no DOM: Renderização Markdown via `marked.js` e sanitização ativa contra XSS via `DOMPurify`.
- `CHATWOOT-E2E-001`: Acesso isolado ao menu SAC, ACL, middleware de add-on e iframe do Chatwoot (sem requisições externas).
- **Teste Negativo de Egress**: Confirmação programática de que requisições externas a partir dos contêineres de app e worker são bloqueadas pela rede interna.

---

### Etapa 5 — Consolidação Documental e Baseline de Cobertura com PCOV

1. Adicionar a extensão **PCOV** no `docker/testing/Dockerfile.php-tests` (sem contaminar a imagem de produção).
2. Executar suíte completa via `docker compose -f docker-compose.test.yml run --rm php-tests vendor/bin/pest --coverage --coverage-clover=reports/coverage.xml --coverage-html=reports/coverage-html`.
3. Executar `python quality/scripts/generate_coverage_matrix.py` e registrar o baseline real em `quality/KNOWN_GAPS.md`.
4. Validar integridade documental (`python quality/scripts/validate_test_docs.py`).

---

### Etapa 6 — CI/CD Quality Gate e Pipeline de Release por Digest

#### 1. Portão Automático de PR (`.github/workflows/quality-gate.yml`)
- Disparado em pull requests para branches principais.
- Executa validação estática de documentação (`validate_test_docs.py`), verificação de matriz (`COVERAGE_MATRIX.md`), linting e testes automatizados.
- Permissões mínimas de token: `permissions: contents: read`.

#### 2. Pipeline de Release Protegido (`.github/workflows/release.yml`)
- Acionado manualmente com tag semântica de release (ex: `v3.55.0`).
- **GitHub Environment Protegido**: Executa sob o environment `production-release`, exigindo aprovação humana formal no GitHub antes do início do deploy.
- **Secrets Isolados**: Utiliza `DOCKERHUB_USERNAME` e `DOCKERHUB_TOKEN` configurados exclusivamente no environment protegido.
- **Permissões Mínimas**:
  ```yaml
  permissions:
    contents: read
  ```
  *(Removida permissão `packages: write` pois a publicação ocorre estritamente no Docker Hub).*
- **Build Único e Promoção por Digest**:
  1. Constrói a imagem candidata única.
  2. Executa a suíte de testes e E2E Playwright sobre ela.
  3. Se aprovada, aplica a tag oficial sobre a mesma imagem candidata testada (sem rebuild).
  4. Realiza o push para o Docker Hub e captura o **SHA256 Digest imutável**.
  5. Gera instruções e comandos oficiais de implantação em Staging utilizando o digest capturado.
- **Deploy Swarm/Portainer**: Permanece **sem automação direta nesta fase**, garantindo controle humano na aplicação do digest.
- Marcar o ADR §4.90 no `ARCHITECTURE.md` como "concluído".

---

## 5. Mapeamento Completo do Catálogo Atualizado (33 Testes Totais)

*Legenda de Transição: `planned` $\rightarrow$ `implemented_unverified` $\rightarrow$ `active` (promoção estritamente condicional à aprovação com saída comprovada em primeiro plano).*

```text
# PLATAFORMA & GOVERNANÇA (3 testes)
1. BASIC-UNIT-001        (governance)            [implemented_unverified -> active condicional na Etapa 2]
2. AUTH-FEATURE-001      (auth)                  [implemented_unverified -> active condicional na Etapa 2 / migrado na Etapa 3]
3. DOCS-VAL-001          (governance)            [active] (Validado com sucesso via pytest com 6/6 testes unitários passando)

# ATENDIMENTO / CHATWOOT (2 testes)
4. CHATWOOT-FEATURE-001  (chatwoot)              [implemented_unverified -> active condicional na Etapa 2]
5. CHATWOOT-E2E-001      (chatwoot)              [planned -> implemented_unverified -> active na Etapa 4]

# SEGURANÇA & ISOLAMENTO MULTI-TENANT (7 testes)
6. SEC-GUARD-001         (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 2]
7. SAAS-FEATURE-001      (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 3]
8. TENANT-SEC-001        (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 3]
9. TENANT-SEC-002        (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 3]
10. TENANT-SEC-003       (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 3]
11. TENANT-SEC-004       (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 3]
12. TENANT-SEC-005       (tenant-isolation)      [planned -> implemented_unverified -> active na Etapa 3]

# DOMÍNIO LEGAL / LEGAL ORCHESTRATOR (7 testes)
13. LEGAL-FEATURE-001    (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 3]
14. LEGAL-FEATURE-002    (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 3]
15. LEGAL-FEATURE-003    (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 3]
16. LEGAL-FEATURE-004    (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 3]
17. LEGAL-FEATURE-005    (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 3]
18. LEGAL-FEATURE-006    (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 3]
19. LEAD-E2E-002         (legal-orchestrator)    [planned -> implemented_unverified -> active na Etapa 4]

# DOMÍNIO AI / ASSISTENTES DE IA (14 testes)
20. LEAD-AI-001          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
21. LEAD-AI-002          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
22. LEAD-AI-003          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
23. LEAD-AI-004          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
24. LEAD-AI-005          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
25. LEAD-AI-006          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
26. LEAD-AI-007          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
27. LEAD-AI-008          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
28. LEAD-AI-009          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
29. LEAD-AI-010          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
30. LEAD-AI-011          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
31. LEAD-AI-012          (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 3]
32. LEAD-E2E-001         (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 4]
33. LEAD-E2E-003         (ai-assistant)          [planned -> implemented_unverified -> active na Etapa 4]
```

### Sumário da Matriz de Cobertura
- 🟢 **Ativos e Certificados (`active`)**: 1 teste (`DOCS-VAL-001`) — 3.0%
- 🟡 **Implementados Não-Verificados (`implemented_unverified`)**: 3 testes (`BASIC-UNIT-001`, `AUTH-FEATURE-001`, `CHATWOOT-FEATURE-001`) — 9.1%
- ⚪ **Planejados (`planned`)**: 29 testes — 87.9%
- 📦 **Aposentados / Histórico (`retired`)**: 0 testes — 0.0%
- **Total**: **33 testes cadastrados**.

---

## 6. Matriz de Arquivos Afetados por Etapa

```text
adv-crm/
├── quality/                                     # [CONCLUÍDO NA ETAPA 1]
│   ├── AGENTS.md
│   ├── README.md
│   ├── TEST_CATALOG.yaml                        # Atualizado com 33 testes canônicos
│   ├── COVERAGE_MATRIX.md                       # Sincronizado e gerado automaticamente
│   ├── CHANGELOG.md
│   ├── KNOWN_GAPS.md
│   ├── RELEASE_CHECKLIST.md
│   ├── adr/
│   │   ├── ADR-001-multi-database-isolation.md
│   │   ├── ADR-002-playwright-python-stack.md
│   │   ├── ADR-003-ai-testing-strategy.md
│   │   └── ADR-004-document-validation-gate.md
│   ├── modules/
│   │   ├── auth.md
│   │   ├── chatwoot.md                          # Atualizado com arquitetura real do menu SAC
│   │   ├── lead.md
│   │   ├── legal-orchestrator.md
│   │   ├── ai-assistant.md
│   │   ├── tenant-isolation.md
│   │   └── governance.md                        # Atualizado (sem Whaticket)
│   ├── runbooks/
│   │   ├── run-tests-local.md
│   │   ├── run-tests-docker.md
│   │   └── investigate-failures.md
│   └── scripts/
│       ├── validate_test_docs.py                # Atualizado com Regra 13 (anti-componentes obsoletos)
│       ├── generate_coverage_matrix.py
│       ├── requirements-quality.txt
│       ├── run-backend-tests.sh                 # [Etapa 2 - Script seguro com trap, exit code e wait determinístico]
│       └── tests/
│           └── test_validate_test_docs.py       # [6/6 testes aprovados no Pytest]
│
├── tests/
│   ├── TestCase.php
│   ├── Pest.php                                 # [Http::preventStrayRequests na Etapa 3]
│   ├── MultiDatabaseTestCase.php                # [Etapa 2]
│   ├── Support/                                 # [Etapa 2]
│   │   ├── DatabaseSafetyGuard.php              # [Etapa 2]
│   │   ├── MultiTenantTestBootstrapper.php      # [Etapa 2]
│   │   └── SyntheticDataFactory.php             # [Etapa 2]
│   ├── Unit/
│   │   ├── BasicTest.php                        # [BASIC-UNIT-001 - Baseline Etapa 2]
│   │   └── DatabaseSafetyGuardTest.php          # [SEC-GUARD-001 - Etapa 2]
│   ├── Feature/
│   │   ├── AuthenticationTest.php               # [AUTH-FEATURE-001 - Baseline Etapa 2 / Migrado Etapa 3]
│   │   ├── ChatwootConfigTest.php               # [CHATWOOT-FEATURE-001 - Baseline Etapa 2]
│   │   ├── Auth/AuthMultiTenantTest.php         # [Etapa 3]
│   │   ├── Legal/LegalOrchestratorTest.php      # [Etapa 3]
│   │   ├── AI/AiAssistantTest.php               # [Etapa 3]
│   │   └── SaaS/SuiteCoinIntegrationTest.php    # [SAAS-FEATURE-001 - Etapa 3]
│   ├── Security/                                # [Etapa 3]
│   │   └── MultiTenantIsolationTest.php         # [TENANT-SEC-001 a 005 - Etapa 3]
│   └── e2e/                                     # [Etapa 4]
│       ├── conftest.py
│       ├── pyproject.toml
│       ├── requirements-test.txt
│       ├── pages/
│       │   ├── base_page.py
│       │   ├── login_page.py
│       │   ├── lead_page.py
│       │   └── legal_page.py
│       ├── workflows/
│       │   ├── test_lead_ai_workflow.py
│       │   └── test_lead_conversion_workflow.py
│       └── fixtures/
│           ├── tenant_a_context.json
│           └── tenant_b_context.json
│
├── docker/
│   └── testing/
│       ├── Dockerfile.php-tests                 # [Criado na Etapa 2 (PHP 8.3 CLI) / PCOV adicionado na Etapa 5]
│       ├── Dockerfile.playwright                # [Etapa 4]
│       └── mock-server/                         # [Etapa 4]
│           └── default.conf
│
├── .github/workflows/
│   ├── quality-gate.yml                         # [Etapa 6]
│   └── release.yml                              # [Etapa 6 - permissions: contents: read]
│
├── .env.testing.example                         # [Etapa 2]
├── docker-compose.test.yml                      # [Criado na Etapa 2 / Expandido na Etapa 4 para perfil E2E com 9 serviços ativos]
└── reports/.gitkeep                             # [CONCLUÍDO NA ETAPA 1]
```

---

## 7. Critérios Objetivos para Autorização da Etapa 2

A **Etapa 2** será iniciada somente após aprovação formal deste plano e contemplará:
1. Criação do `.env.testing.example` (com instrução `php artisan key:generate --env=testing`) e sentinel `TEST_ENVIRONMENT_ACK=LAW_FIRM_ISOLATED_TEST`.
2. Criação do `docker/testing/Dockerfile.php-tests` (imagem exclusiva de teste PHP 8.3 CLI) e versão inicial do `docker-compose.test.yml` (`mysql-test`, `mothership-db-test`, `redis-test`, `php-tests`).
3. Criação de `quality/scripts/run-backend-tests.sh` com espera determinística `--wait --wait-timeout 120`, preservação de exit code e exit trap de limpeza.
4. Criação das classes de segurança e suporte em `tests/Support/` (`DatabaseSafetyGuard.php`, `MultiTenantTestBootstrapper.php`, `SyntheticDataFactory.php`) e classe base `MultiDatabaseTestCase.php`.
5. Criação e execução dos testes negativos unitários da trava em `tests/Unit/DatabaseSafetyGuardTest.php` (`SEC-GUARD-001`).
6. Execução dos testes existentes (`BasicTest.php`, `AuthenticationTest.php` e `ChatwootConfigTest.php`) contra a infraestrutura isolada via contêiner `php-tests`, captura da saída real em foreground e promoção condicional para status `active` no catálogo.
7. Validação documental via `validate_test_docs.py` e regeneração da matriz `COVERAGE_MATRIX.md`. *(Nota: Após criar `tests/Support/DatabaseSafetyGuard.php`, o aviso esperado atual sobre `SEC-GUARD-001` no validador não deve mais aparecer).*
