# 📋 QA-SCHEMA.md — Schema de Teste e Domínio LawFirm

> **Tasks:** `QA-DATA-001` e `QA-SCHEMA-001` · **Data:** 2026-10-02 · **Por:** Hermes (QA Architect) & Antigravity (Security/Infra)

---

## 1. Resolução da Lacuna de Schema (`QA-SCHEMA-001`)

A aparente "ausência de migrations que criam o domínio" em `database/migrations/` ocorria porque o LawFirm CRM utiliza a arquitetura modular de pacotes do Krayin CRM:
- As migrations do CRM base residem em `packages/Webkul/*/src/Database/Migrations/`
- As migrations do domínio jurídico residem em `packages/SuiteZap/LawFirm/src/Database/Migrations/` (70+ migrations)
- O total de migrations consolidadas do domínio é de **201 migrations**.

Para viabilizar execução limpa, reprodutível e independente de ambiente (inclusive em CI e no Hermes VPS sem provisionamento prévio), ambas as opções propostas foram implementadas:

### Opção A — Schema Dump Base (Padrão Laravel)
- Arquivo: [`database/schema/mysql-schema.sql`](../database/schema/mysql-schema.sql)
- Contém a DDL completa das 85 tabelas do domínio e o registro das 201 migrations na tabela `migrations`.
- Permite que `php artisan migrate` funcione de forma instantânea sem precisar rodar migrations do zero.

### Opção B — Inicialização Automática no Docker
- Arquivo: [`docker/testing/mysql-init/03-lawfirm-tables.sql`](../docker/testing/mysql-init/03-lawfirm-tables.sql)
- Montado no `mysql-test` em `docker-compose.test.yml`:
  ```yaml
  - ./docker/testing/mysql-init/03-lawfirm-tables.sql:/docker-entrypoint-initdb.d/03-lawfirm-tables.sql:ro
  ```
- Ao subir o container MySQL com volume limpo, o script popula automaticamente tanto `tenant_a_test` quanto `tenant_b_test` com todas as 85 tabelas e 201 migrations aplicadas.

---

## 2. Mapa do Schema Real do Domínio

Auditoria realizada diretamente contra o banco de dados provisionado (`tenant_a_test` e `mothership_test`):

| Entidade / Tabela | Banco de Dados | PK Real | Colunas Críticas & Tipos Reais | Observação para Fixtures / Seeder |
|---|---|---|---|---|
| `users` | `tenant_*_test` | `id` (int unsigned) | `name`, `email` (unique), `password`, `whatsapp`, `status` (tinyint 1), `role_id` | **Sem `uuid`** e **sem `is_admin`** (roles são gerenciadas via tabela `roles`). |
| `leads` | `tenant_*_test` | `id` (int unsigned) | `title`, `description`, `lead_value`, `status` (tinyint), `chatwoot_conversation_id` (int) | **Sem `uuid`**. Conversa Chatwoot é uma coluna em `leads`, não uma tabela separada. |
| `processos` | `tenant_*_test` | `id` (bigint unsigned) | `tenant_id`, `titulo`, `numero_cnj`, `tribunal`, `vara`, `valor_causa`, `data_distribuicao`, `status`, `sercreta` | **Sem `uuid`**. Não possui `orgao_judicial`, `classe` ou `responsavel_uuid` (usa `user_id`). |
| `subscriptions` | `mothership_test` | `id` (int) | `tenant_id`, `plan_name`, `suitecoin_balance` (dec 20,4), `active_modules` (json), `status` | **Fica no `mothership_test`**, não nos bancos tenant. PK é `id` auto_increment; plano é `plan_name`. |
| `kanban_*` | N/A | - | - | Tabelas `kanban_columns` / `kanban_cards` dependem da implementação de `KAN-001`. No CRM padrão, estágios vivem em `lead_pipeline_stages` e `law_legal_pipeline_stages`. |
| `chatwoot_conversations` | N/A | - | - | Não é tabela do CRM. O CRM armazena `chatwoot_conversation_id` na tabela `leads`. |
| `ai_documents` | N/A | - | - | Execuções de IA ficam em `lawfirm_ai_executions` e templates em `law_document_templates`. |

---

## 3. Estado das Tasks da Cadeia

- `QA-SCHEMA-001`: **IMPLEMENTED_NOT_VERIFIED** (ambas opções A e B entregues).
- `QA-DATA-001`: Desbloqueada para execução no Docker.
- `QA-HARNESS-001`: Desbloqueada após validação do seeder.
- `QA-JUR-001`: Desbloqueada após harness.
