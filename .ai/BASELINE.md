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
| `CODE_VERSION` | `3.56.3` | `packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php:47` |
| `ARCHITECTURE_VERSION` | `v3.56.3` (ADR §4.95) | `ARCHITECTURE.md` (raiz) |
| `DOCUMENTED_VERSION` | `v3.56.3` | `CHANGELOG.md` (raiz), `quality/CHANGELOG.md` |
| `IMAGE_VERSION` | `3.56.3` / `v3.56.3` / `latest` | Docker Hub `suitezap/lawfirm` |

### `BASELINE_VERSION_MISMATCH` — histórico e fechamento (DOC-002, 2026-09-30)

| # | Data | Ocorrência | Fechada por |
|---|---|---|---|
| 1 | 2026-08-26 | `CHANGELOG.md` raiz parava em v3.54.1; código em v3.55.1 | `DOC-001` |
| 2 | 2026-09-15 | v3.56.0 (`DOCKER-002`) publicada sem entrada em CHANGELOG | `DOC-002` |
| 3 | 2026-09-28 | v3.56.1 (`DOCKER-003`) e v3.56.2 (`DOCKER-004`) publicadas sem entrada em CHANGELOG | `DOC-002` |

- **Padrão:** três ocorrências em dois meses, sempre com o mesmo formato — a release é publicada no Docker Hub e registrada em ADR no `ARCHITECTURE.md`, mas os `CHANGELOG.md` (raiz e `quality/`) não acompanham.
- **Por que não foi detectado:** o `quality/scripts/validate_test_docs.py` validava o catálogo contra `tests/` e `quality/modules/`, mas **nenhuma das 13 regras comparava a versão do código com a documentação**. O validador executava com **0 erros** durante todo o drift.
- **Causa raiz de processo:** os bumps foram executados por agentes distintos (OpenCode em `DOCKER-002`/`004`, Antigravity em `DOCKER-003`). O procedimento de bump inclui, por convenção, atualizar os CHANGELOGs — mas esse procedimento não é automatizado nem verificado, e os CHANGELOGs não são gerados a partir do ADR.
- **Correção aplicada (DOC-002):** entradas v3.56.0, v3.56.1 e v3.56.2 adicionadas ao `CHANGELOG.md` raiz e ao `quality/CHANGELOG.md`; este `BASELINE.md` reconciliado; **Regra 14 (Consistência de Versão)** criada no validador — compara `LawFirmServiceProvider::VERSION` com a entrada mais recente de cada CHANGELOG e **falha o CI** na divergência. Testada nos dois sentidos.
- **Prevenção:** qualquer bump futuro sem entrada de changelog passa a quebrar o `lawfirm-ci.yml`.

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
