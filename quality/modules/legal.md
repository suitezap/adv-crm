# Legal — Controle de Acesso (PRIV-AUDIT-001)

**Última Revisão:** 2026-09-09 · **Change:** `priv-audit-platform` (Onda 1e + pré-req)

## Escopo
Casos e Processos: gates de perfil + `tenant_id` em `processos` (`BelongsToTenant`).

## Invariantes
- Todo método sensível de `CasoController`/`ProcessoController` exige `lawfirm.casos|processos.view/create/edit/delete` (401 sem).
- `link/unlink`, `search*`, `requestRegistration/Documents`, `massDestroy` com gates.
- `show` de Processo mantém checagem `individual vs global` além do gate.

## Testes
| ID | Nome | Status |
|---|---|---|
| `LEGAL-SEC-001` | 401 sem permissão em casos/processos/GED | `active` |
| `PORTAL-SEC-001` | Portal com token expirável, whitelist e upload restrito | `active` |

## Portal público
- Tokens novos `exp.{ts}.{hmac}` (30 dias); legados aceitos com log de depreciação.
- `update` com whitelist validada; logs sem PII; `upload` restrito a pdf/doc/docx/jpg/png 20MB.

---

## Kanban Jurídico (KAN-001)

**Última Revisão:** 2026-10-03 · **Change:** KAN-001

### Componentes
- `LegalKanbanController` — skinny controller; delega a `LegalPipelineService`; ACL Bouncer.
- `LegalPipelineService::moveCaseToStage()` — atualiza `Caso` + cascade para `Processos`; dispatch de `CasoStageUpdated`.
- `SyncCasoStageToChatwootListener` — queued listener (tries=1); sincroniza label Chatwoot ao estágio do caso; degrada graciosamente.

### Invariantes
- Listener NUNCA lança exceção para o caller — qualquer erro é logado e absorvido.
- Listener usa `tries=1` para não inundar a API Chatwoot.
- `ld_ganho` é preservado no estágio `novo-caso`/`novo` (ADR-ATEND-002).
- `getDynamicCrmTagPool()` inclui sempre `CASO_STAGE_POOL` mesmo quando o DB falha (try/catch).
- `normalizePhone()` produz E.164 com prefixo `+55` para números brasileiros de 10+ dígitos sem o código de país.

### Testes
| ID | Nome | Arquivo | Status |
|---|---|---|---|
| `KAN-UNIT-001` | normalizePhone adiciona +55 a número 11-dígitos | `tests/Unit/SyncCasoStageToChatwootListenerTest.php` | `active` |
| `KAN-UNIT-002` | normalizePhone não duplica 55 quando já presente | idem | `active` |
| `KAN-UNIT-003` | normalizePhone remove caracteres não-numéricos | idem | `active` |
| `KAN-UNIT-004` | normalizePhone normaliza número já com código de país | idem | `active` |
| `KAN-UNIT-005` | STAGE_LABEL_MAP contém todos os 14 slugs canônicos | idem | `active` |
| `KAN-UNIT-006` | CASO_STAGE_POOL tem exatamente 12 labels | idem | `active` |
| `KAN-UNIT-007` | CASO_STAGE_POOL não contém ld_ganho | idem | `active` |
| `KAN-UNIT-008` | resolvePhone retorna null sem person | idem | `active` |
| `KAN-UNIT-009` | resolvePhone retorna null com contact_numbers vazio | idem | `active` |
| `KAN-UNIT-010` | resolvePhone retorna primeiro telefone válido em E.164 | idem | `active` |
| `KAN-UNIT-011` | resolvePhone pula entradas com value vazio | idem | `active` |
| `KAN-UNIT-012` | slugs novo-caso/novo são reconhecidos como novoCaso | idem | `active` |
| `KAN-UNIT-013` | slugs não-novo NÃO são novoCaso | idem | `active` |
| `KAN-UNIT-014` | getDynamicCrmTagPool inclui pool estático quando DB falha | idem | `active` |
| `KAN-UNIT-015` | getDynamicCrmTagPool retorna valores únicos | idem | `active` |
| `KAN-UNIT-016` | Listener implementa ShouldQueue | idem | `active` |
| `KAN-UNIT-017` | Listener usa queue default | idem | `active` |
| `KAN-UNIT-018` | Listener tem tries=1 | idem | `active` |

**Resultado local (2026-10-03):** 18/18 passed · 73 assertions · sem DB/Docker · 2.88s

