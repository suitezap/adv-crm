# 📊 Matriz de Cobertura e Rastreabilidade de Testes (COVERAGE_MATRIX.md)

> **Gerado automaticamente por `quality/scripts/generate_coverage_matrix.py`**  
> **Última geração:** 2026-09-09 21:03:00  
> **Fonte da verdade:** `quality/TEST_CATALOG.yaml`

---

## 1. Sumário Executivo de Cobertura

| Métrica | Quantidade | Percentual |
|:---|:---:|:---:|
| **Total de Testes Cadastrados** | **47** | 100% |
| 🟢 Ativos e Certificados (`active`) | 19 | 40.4% |
| 🟡 Implementados Não-Verificados (`implemented_unverified`) | 24 | 51.1% |
| ⚪ Planejados (`planned`) | 4 | 8.5% |
| 🟠 Em Quarentena (`quarantined`) | 0 | 0.0% |
| 🔴 Desativados (`disabled`) | 0 | 0.0% |
| 📦 Aposentados / Histórico (`retired`) | 0 | 0.0% |

---

## 2. Distribuição por Domínio e Prioridade

### Por Domínio
| Domínio | Quantidade de Testes |
|:---|:---:|
| **AI** | 16 |
| **Atendimento** | 2 |
| **Escavador** | 1 |
| **Financial** | 3 |
| **GED** | 1 |
| **Legal** | 10 |
| **Plataforma / Governança** | 3 |
| **SaaS** | 10 |
| **TenantFinance** | 1 |

### Por Prioridade
| Prioridade | Quantidade de Testes |
|:---|:---:|
| **P0** | 26 |
| **P1** | 16 |
| **P2** | 5 |

---

## 3. Catálogo Completo de Rastreabilidade

| ID | Nome / Intenção | Domínio | Camada | Tipo | Prio | Status | Arquivo de Teste | Documentação |
|:---|:---|:---:|:---:|:---:|:---:|:---:|:---|:---|
| **BASIC-UNIT-001** | Teste unitário básico de integridade do framework (Pest baseline) | - | platform | Unit | P2 | 🟢 active | `tests/Unit/BasicTest.php` | `quality/modules/governance.md` |
| **AUTH-FEATURE-001** | Fluxo básico de autenticação, dashboard e logout administrativo | - | platform | Feature | P1 | 🟢 active | `tests/Feature/AuthenticationTest.php` | `quality/modules/auth.md` |
| **CHATWOOT-FEATURE-001** | Preservação da invariante account_id / inbox_id do Chatwoot (GUARDRAILS 2026-07-01) | Atendimento | domain | Feature | P0 | 🟢 active | `tests/Feature/ChatwootConfigTest.php` | `quality/modules/chatwoot.md` |
| **LEGAL-FEATURE-001** | Conversão atômica de Lead ganho em Caso e Processo vinculados | Legal | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/Legal/LegalOrchestratorTest.php` | `quality/modules/legal-orchestrator.md` |
| **LEGAL-FEATURE-002** | Rollback completo de transação caso a criação do Caso falhe | Legal | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/Legal/LegalOrchestratorTest.php` | `quality/modules/legal-orchestrator.md` |
| **LEGAL-FEATURE-003** | Rollback completo de transação caso a criação do Processo falhe | Legal | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/Legal/LegalOrchestratorTest.php` | `quality/modules/legal-orchestrator.md` |
| **LEGAL-FEATURE-004** | Proteção contra conversão duplicada de Lead já convertido em Processo | Legal | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/Legal/LegalOrchestratorTest.php` | `quality/modules/legal-orchestrator.md` |
| **LEGAL-FEATURE-005** | Priorização de Tags canônicas do Lead para preenchimento de Área e Prioridade | Legal | domain | Feature | P2 | 🟡 implemented_unverified | `tests/Feature/Legal/LegalOrchestratorTest.php` | `quality/modules/legal-orchestrator.md` |
| **LEGAL-FEATURE-006** | Fallback para dados de IA (LeadTriagem) quando Tags do Lead estiverem ausentes | Legal | domain | Feature | P2 | 🟡 implemented_unverified | `tests/Feature/Legal/LegalOrchestratorTest.php` | `quality/modules/legal-orchestrator.md` |
| **LEAD-AI-001** | Execução assíncrona do assistente 'pre-triagem-lead' (Análise de Fatos) | AI | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-002** | Execução assíncrona do assistente 'pre-triagem-checklist' (Checklist Documental) | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-003** | Execução assíncrona do assistente 'gerador-proposta' (Proposta de Honorários) | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-004** | Execução assíncrona do assistente 'script-vendas' (Script de Vendas e Objeções) | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-005** | Transições canônicas de status do histórico: queued -> processing -> completed/failed | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-006** | Persistência íntegra do Markdown bruto em generated_content sem mutação backend | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-007** | Débito único de SuiteCoins no MotherShip e registro em saas_transactions no tenant | AI | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-008** | Falha HTTP do N8N marca histórico como failed sem derrubar o worker da fila | AI | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-009** | N8N ausente ou desconfigurado retorna contrato HTTP 503 no Controller | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-010** | Saldo insuficiente de SuiteCoins bloqueia execução antes do débito e do dispatch | AI | domain | Feature | P0 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-011** | Paginação e isolamento de consultas do histórico de execuções de IA | AI | domain | Feature | P2 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **LEAD-AI-012** | Idempotência e ausência de débito duplicado em retentativas | AI | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/AI/AiAssistantTest.php` | `quality/modules/ai-assistant.md` |
| **TENANT-SEC-001** | Tenant A não pode visualizar ou listar Casos e Processos do Tenant B | SaaS | platform | Security | P0 | 🟡 implemented_unverified | `tests/Security/MultiTenantIsolationTest.php` | `quality/modules/tenant-isolation.md` |
| **TENANT-SEC-002** | Tenant A não pode alterar, atualizar ou excluir registros do Tenant B via ID forjado | SaaS | platform | Security | P0 | 🟡 implemented_unverified | `tests/Security/MultiTenantIsolationTest.php` | `quality/modules/tenant-isolation.md` |
| **TENANT-SEC-003** | Tentativa de acesso a recurso de outro tenant resulta estritamente em HTTP 403 ou 404 | SaaS | platform | Security | P0 | 🟡 implemented_unverified | `tests/Security/MultiTenantIsolationTest.php` | `quality/modules/tenant-isolation.md` |
| **TENANT-SEC-004** | Histórico de execuções de IA não vaza entre instâncias de tenants distintos | SaaS | platform | Security | P0 | 🟡 implemented_unverified | `tests/Security/MultiTenantIsolationTest.php` | `quality/modules/tenant-isolation.md` |
| **TENANT-SEC-005** | Consumo de filas Redis isolado estritamente pelo prefixo REDIS_PREFIX por tenant | SaaS | platform | Security | P0 | 🟡 implemented_unverified | `tests/Security/MultiTenantIsolationTest.php` | `quality/modules/tenant-isolation.md` |
| **SEC-GUARD-001** | Trava anti-contaminação DatabaseSafetyGuard com sentinel, allowlist e sufixo _test | SaaS | platform | Unit | P0 | 🟢 active | `tests/Unit/DatabaseSafetyGuardTest.php` | `quality/modules/tenant-isolation.md` |
| **SAAS-FEATURE-001** | Integração e integridade de saldo de SuiteCoins entre Tenant e MotherShip | SaaS | domain | Feature | P1 | 🟡 implemented_unverified | `tests/Feature/SaaS/SuiteCoinIntegrationTest.php` | `quality/modules/tenant-isolation.md` |
| **DOCS-VAL-001** | Suíte de testes unitários do validador documental validate_test_docs.py | - | governance | Unit | P0 | 🟢 active | `quality/scripts/tests/test_validate_test_docs.py` | `quality/modules/governance.md` |
| **LEAD-E2E-001** | Fluxo E2E: Login -> Ficha do Lead -> Disparo das 4 ferramentas de IA -> Histórico e Telemetria | AI | e2e | E2E | P1 | ⚪ planned | `tests/e2e/workflows/test_lead_ai_workflow.py` | `quality/modules/ai-assistant.md` |
| **LEAD-E2E-002** | Fluxo E2E: Ganho de Lead na interface -> Confirmação visual da conversão em Caso e Processo | Legal | e2e | E2E | P1 | ⚪ planned | `tests/e2e/workflows/test_lead_conversion_workflow.py` | `quality/modules/legal-orchestrator.md` |
| **LEAD-E2E-003** | Validação E2E no DOM: Renderização de Markdown via marked.js e sanitização ativa contra XSS via DOMPurify | AI | e2e | E2E | P0 | ⚪ planned | `tests/e2e/workflows/test_lead_ai_workflow.py` | `quality/modules/ai-assistant.md` |
| **CHATWOOT-E2E-001** | Acesso isolado ao menu SAC, ACL, middleware de add-on e iframe do Chatwoot (sem requisições externas) | Atendimento | e2e | E2E | P1 | ⚪ planned | `tests/e2e/workflows/test_chatwoot_sac_workflow.py` | `quality/modules/chatwoot.md` |
| **E2E-LEAD-001** | Fluxo E2E de conversão de lead: criação, movimentação de funil e conversão em processo jurídico | Legal | e2e | E2E | P1 | 🟢 active | `tests/e2e/workflows/test_lead_conversion_workflow.py` | `quality/modules/lead.md` |
| **E2E-AI-001** | Fluxo E2E de triagem inteligente de lead via assistente de IA com mock WireMock | AI | e2e | E2E | P2 | 🟢 active | `tests/e2e/workflows/test_lead_ai_workflow.py` | `quality/modules/ai-assistant.md` |
| **FIN-FEATURE-001** | CRUD de lançamento financeiro com escopo tenant_id (criar, baixar, recibo) | Financial | domain | Feature | P0 | 🟢 active | `tests/Feature/Financial/FinancialTenantTest.php` | `quality/modules/financial.md` |
| **FIN-FEATURE-002** | Quick-pay exige lawfirm.financeiro.edit e respeita tenant (401 sem permissão) | Financial | domain | Feature | P1 | 🟢 active | `tests/Feature/Financial/FinancialTenantTest.php` | `quality/modules/financial.md` |
| **TENANT-FIN-001** | Cobrança Asaas do próprio tenant (criar, visualizar, cancelar, reenviar) | TenantFinance | domain | Feature | P0 | 🟢 active | `tests/Feature/TenantFinance/TenantInvoiceTest.php` | `quality/modules/tenant-finance.md` |
| **FIN-SEC-001** | Visibilidade por configuração do usuário (401 sem permissão, leitura sem escrita, credenciais mascaradas) | Financial | domain | Security | P0 | 🟢 active | `tests/Feature/Financial/FinancialPermissionsTest.php` | `quality/modules/financial.md` |
| **ESC-SEC-001** | Escavador tenant-scoped (DataGrids, show, toggle) + gates de perfil | Escavador | domain | Security | P0 | 🟢 active | `tests/Feature/Escavador/EscavadorTenantTest.php` | `quality/modules/escavador.md` |
| **LEGAL-SEC-001** | Casos e Processos exigem permissão de perfil (401 sem gate) | Legal | domain | Security | P0 | 🟢 active | `tests/Feature/Legal/LegalPermissionsTest.php` | `quality/modules/legal.md` |
| **GED-SEC-001** | GED exige permissão e propriedade do processo (fim do IDOR em downloads) | GED | domain | Security | P0 | 🟢 active | `tests/Feature/Legal/LegalPermissionsTest.php` | `quality/modules/ged.md` |
| **AI-SEC-001** | Histórico de IA escopado por tenant/usuário + SAC sem segredo hardcoded | AI | domain | Security | P0 | 🟢 active | `tests/Feature/AI/AssistantSecurityTest.php` | `quality/modules/ai-assistant.md` |
| **WEBHOOK-SEC-001** | Webhooks públicos negam eventos forjados (fail-closed) | SaaS | platform | Security | P0 | 🟢 active | `tests/Feature/Webhooks/WebhookAuthTest.php` | `quality/modules/webhooks.md` |
| **PLAT-SEC-002** | Gates de perfil em SaaS, AI, modelos e Whatsapp (401 sem permissão) | SaaS | platform | Security | P0 | 🟢 active | `tests/Feature/Platform/PlatformPermissionsTest.php` | `quality/modules/platform.md` |
| **PORTAL-SEC-001** | Portal público com token expirável, whitelist e upload restrito | Legal | domain | Security | P1 | 🟢 active | `tests/Feature/Platform/PlatformPermissionsTest.php` | `quality/modules/legal.md` |
| **TENANT-SEC-006** | Tenant A não acessa financeiro/cobranças do Tenant B; webhook com token alheio retorna 401 | SaaS | platform | Security | P0 | 🟢 active | `tests/Security/FinancialTenantIsolationTest.php` | `quality/modules/tenant-finance.md` |
