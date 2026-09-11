# 🏛️ BASELINE.md — Baseline Canônico do Projeto (LawFirm CRM)

> **Documento Gerado pela Task:** `GOV-001 — Project Baseline`  
> **Data de Registro:** 2026-08-26T23:15:00-03:00  
> **Orquestrador Responsável:** Antigravity (ORCHESTRATOR)

---

## 1. Identificação Git e Ponto de Referência

| Parâmetro | Valor | Descrição |
|---|---|---|
| `CURRENT_HEAD` | `11f5b4e7e0812e53d5d0e70353326112386f18e6` | Hash do commit HEAD ativo na branch de trabalho |
| `REFERENCE_BASE` | `660f4f10b8c9b856805d1ea9da23a4ad8c0797b5` | Hash do commit base estável de referência |
| `REFERENCE_BASE_TYPE` | `previous-baseline` | Commit de conclusão da Etapa 4 de Qualidade (E2E Docker / Documentação Viva v3.55.1) |
| `BASELINE_CREATED_AT` | `2026-08-26T23:15:00-03:00` | Timestamp ISO-8601 da auditoria do baseline |
| `BRANCH` | `law-firm-custom` | Branch ativa de customização e expansão de testes |

---

## 2. Auditoria Canônica de Versões

| Origem do Dado | Versão Encontrada | Arquivo Fonte |
|---|---|---|
| `DOCUMENTED_VERSION` | `v3.55.1` | `ARCHITECTURE.md`, `quality/CHANGELOG.md` |
| `CODE_VERSION` | `3.55.1` | `packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php:47` |
| `CHANGELOG_VERSION` | `v3.54.1` (Raiz) / `v3.55.1` (`quality/`) | `CHANGELOG.md`, `quality/CHANGELOG.md` |
| `IMAGE_VERSION` | `candidate-local` / `latest` (Observado) | `docker-compose.test.yml`, `Dockerfile` |

### `BASELINE_VERSION_MISMATCH`
- **Diagnóstico:** O arquivo `CHANGELOG.md` raiz encerra suas entradas na versão `v3.54.1 (Julho 2026)`, enquanto o código-fonte (`LawFirmServiceProvider::VERSION`), a documentação de arquitetura (`ARCHITECTURE.md`) e a memória de qualidade (`quality/CHANGELOG.md`) já operam canonicamente em `v3.55.1`.
- **Decisão Governança:** Registrada a divergência documental existente no baseline. Criada a task documental futura `DOC-001` para consolidação do changelog. Nenhuma alteração silenciosa de código ou changelog é executada durante `GOV-001`.

### Diretriz Arquitetural sobre Imagens Docker (`suitezap/lawfirm:latest`)
> [!IMPORTANT]
> A referência `suitezap/lawfirm:latest` é **estritamente um ESTADO OBSERVADO** nos arquivos de desenvolvimento no momento do baseline.
> Isso **NÃO representa autorização** para utilização de `:latest` em ambiente de produção.
> A política arquitetural definitiva é:
> 1. **Produção:** Utilizar sempre versão ou digest imutável formalmente auditado e aprovado.
> 2. **Release:** Nunca depender da tag flutuante `:latest` para releases ou deploy Swarm.
> 3. **Higiene:** A consolidação e higienização da imagem oficial de produção será tratada na task `DOCKER-001` (Owner: OpenCode).

---

## 3. Estrutura Física e Arquitetura do Software

- **Padrão Arquitetural:** Domain-Driven Design (DDD) adaptado para SaaS Multi-Tenant.
- **Namespace Raiz do Domínio:** `SuiteZap\LawFirm` (`packages/SuiteZap/LawFirm/src/`).
- **Bounded Contexts (Domínios Ativos):**
  1. `Legal`: Casos, Processos, Prazos, Checklists, LegalOrchestrator.
  2. `Financial`: Honorários, Custas e Faturamento.
  3. `GED`: Gestão Eletrônica de Documentos, Anexos e Integração S3/MinIO via `SaasFileService`.
  4. `SaaS`: Multi-tenancy, Assinaturas, Nós de Infraestrutura, Transações SuiteCoins.
  5. `AI`: Assistentes de IA, Triagem de Leads, Histórico e Auditoria de Prompts.
  6. `Escavador`: Integrações v1/v2 para monitoramento e busca processual.
  7. `Atendimento`: Integração com Chatwoot e Dual Inbox.
- **Domínios com Partes Suspensas:** `Whatsapp/` (conforme regras em `AGENTS.md`).

---

## 4. Estado da Infraestrutura de Qualidade e Testes

- **Validador Documental:** `quality/scripts/validate_test_docs.py` (12 regras estáticas de integridade, 0 violações).
- **Catálogo de Testes (`quality/TEST_CATALOG.yaml`):** 35 testes estruturados em 7 suítes formais com ciclo de vida rigoroso.
- **Suíte de Testes Automatizados:**
  - `Pest / PHPUnit`: Suíte backend multi-database com isolamento `tenant` + `mothership` (`tests/Feature/`, `tests/Security/`, `tests/Support/`).
  - `Playwright Python`: Suíte E2E (`tests/e2e/`) com Page Object Model (`LoginPage`, `LeadPage`, `LegalPage`).
  - `Docker Testing Stack`: 9 containers orquestrados (`docker-compose.test.yml` profile `e2e`):
    - `app-tenant-a`, `app-tenant-b`
    - `worker-tenant-a`, `worker-tenant-b`
    - `playwright-test`
    - `mysql-test`, `mothership-db-test`, `redis-test`, `mock-server` (WireMock)
- **Débitos e Lacunas Mapeados (`quality/KNOWN_GAPS.md`):**
  - `GAP-001`: Ausência de estorno automático em falha de Job de IA (débito prévio de SuiteCoins).
  - `GAP-002`: Domínios fora da cobertura inicial da Fase 1 (`Financial`, `GED`, `Escavador`, `DataJud`, `TenantFinance`, `Whatsapp`).
  - `GAP-003`: Testes de IA operam com mocks determinísticos (`AI_REAL_TESTS=false`).

---

## 5. Regras de Integridade e Isolamento Multi-Tenant

1. Toda query de domínio deve ser estritamente escopada por tenant.
2. Todas as operações de fila Redis devem utilizar prefixo `REDIS_PREFIX: ${TENANT_ID}_`.
3. Todos os uploads de arquivo devem utilizar exclusivamente `SaasFileService`.
4. Todas as conexões ao banco global devem declarar explicitamente `connection('mothership')`.
