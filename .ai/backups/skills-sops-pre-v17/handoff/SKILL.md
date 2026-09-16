---
name: handoff
description: Procedimento de elaboração e validação de contratos de handoff entre agentes.
---

# 🤝 Skill: Handoff Protocol

## Quando Usar
Sempre que uma tarefa depender da atuação de outro agente ou for concluída e passar a responsabilidade para o próximo elo.

## Procedimento de Emissão (Sender Agent)

1. **Preenchimento do Modelo:**
   - Criar `.ai/handoffs/HANDOFF-{TASK_ID}.md` contendo:
     - `From`, `To`, `Task ID`, `Status: READY`
     - `Expected Commit` (hash HEAD exato)
     - `Objective`, `Contexto`, `Instruções`, `Restrições`, `Definition of Done`
2. **Atualização de SSOT:**
   - Definir status da tarefa em `.ai/TASKS.md` como `READY`.
   - Notificar no log próprio (`.ai/logs/{AGENT}.md`).

## Procedimento de Recepção (Receiver Agent)

1. **Validação de Commit:**
   - Comparar `Expected Commit` do documento com `git rev-parse HEAD` local.
   - Se divergente $\rightarrow$ `SNAPSHOT_MISMATCH` (STOP).
2. **Validação de Sincronismo:**
   - Checar ausência de `*sync-conflict*`.
3. **Início da Execução:**
   - Adquirir lock e transicionar tarefa para `IN_PROGRESS`.
