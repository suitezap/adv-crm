# 🚨 Registro de Incidentes Operacionais (.ai/incidents/)

> Este diretório registra interrupções graves, falhas de sincronização, corrupção de arquivos, regressões de build ou incidentes de isolamento detectados durante a operação multiagente.

---

## 1. Quando Registrar um Incidente

Um arquivo de incidente deve ser criado imediatamente quando ocorrer:
1. `SYNC_CONFLICT_DETECTED` não resolvido que paralisa o trabalho.
2. `SNAPSHOT_MISMATCH` entre o commit esperado no handoff e o workspace do agente.
3. `UNEXPECTED_FUNCTIONAL_CHANGE` (código de domínio alterado sem autorização de escopo).
4. `TENANT_ISOLATION_BREACH` (falha comprovada de vazamento de dados entre tenants).
5. Falha crítica de pipeline CI/CD após commit.

---

## 2. Formato do Relatório (`INCIDENT-{YYYYMMDD}-{ID}.md`)

```markdown
# INCIDENT REPORT — {YYYYMMDD}-{ID}

- **Data:** {TIMESTAMP}
- **Agente Notificador:** {ANTIGRAVITY | HERMES | OPENCODE}
- **Severidade:** CRITICAL | HIGH | MEDIUM | LOW
- **Status:** OPEN | INVESTIGATING | RESOLVED

## 1. Descrição do Ocorrido
{O_QUE_ACONTECEU}

## 2. Causa Raiz Identificada
{ROOT_CAUSE}

## 3. Impacto Operacional
{IMPACTO}

## 4. Ações de Mitigação Imediatas
{ACOES_TOMADAS}

## 5. Lição Aprendida e Guardrail Adicionado
{LICAO_REGISTRADA_EM_LESSONS_MD}
```
