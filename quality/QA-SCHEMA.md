# 📋 QA-SCHEMA.md — Lacuna de schema no repositório

> **Task:** `QA-DATA-001` · **Data:** 2026-10-01 · **Por:** Hermes (QA Architect)

## ⚠️ A tabela do domínio não tem migration no repositório

`database/migrations/` tem **12 arquivos**, e **todos** são alterações
(`add_*`, `make_*_nullable`) ou tabelas do Laravel. As migrations que **criam**
as tabelas do domínio não estão versionadas.

### Tabelas presentes via migration

| Tabela | Origem |
|---|---|
| `users`, `password_reset_tokens` | Laravel padrão |
| `jobs`, `job_batches`, `failed_jobs` | Laravel queue |
| `sessions` | Laravel session |

### Tabelas do domínio — usadas em código, ausentes das migrations

| Tabela | Evidência de uso |
|---|---|
| `processos` | `database/migrations/2026_01_02_214127_add_detailed_fields_to_processos_table.php` |
| `law_processo_whatsapp_messages` | `2026_09_07_150341_add_media_columns_to_law_processo_whatsapp_messages_table.php` |
| `leads` | modelos em `packages/SuiteZap/LawFirm/src/` |
| `subscriptions` | `suitecoin_balance` citado em `SyntheticDataFactory` e `SuiteCoinService` |
| `kanban_columns`, `kanban_cards` | `KAN-001` (task em andamento) |
| `chatwoot_conversations`, `ai_documents` | `SyntheticDataFactory` |

## Consequência prática

`php artisan migrate:fresh` a partir do repositório **não** cria o schema do
domínio. Só funciona contra um banco já provisionado (o `init_mothership_db`
do MotherShip ou um dump).

Consequência para QA: **fixtures não podem ser validadas por `migrate:fresh`**.
O `TenantTestSeeder` assume que os bancos `tenant_a_test` e `tenant_b_test`
já existem com o schema completo.

## Como fechar a lacuna

Duas opções, ambas do DSK7 (precisam de Docker):

**A — Versionar as migrations base**
Extrair o schema de um banco provisionado e gerar migrations
`create_*`. Garante `migrate:fresh` funcional em qualquer ambiente.

**B — Documentar o schema de referência**
Manter um `schema.sql` versionado, gerado de um banco provisionado, e
usá-lo no CI para criar os bancos `_test`.

**Recomendação:** A. Sem as migrations base, nenhuma verificação de schema é
reprodutível, e isso limita `QA-HARNESS-001` e `QA-JUR-001` tanto quanto
limitou `QA-DATA-001`.

## Pendência de verificação

O `SyntheticDataFactory` gera campos (`active_modules`, `numero_cnj`,
`sercreta`, `posicao`, `cor`) inferidos do código e das migrations de
alteração. **A forma exata de cada coluna não é verificável sem o schema.**
Precisa de confirmação contra um banco provisionado antes de o seeder rodar.
