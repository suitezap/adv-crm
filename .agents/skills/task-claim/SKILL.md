---
name: task-claim
description: Procedimento formal para assumir a titularidade de escrita (Lock) de uma tarefa.
---

# 🏷️ Skill: Task Claim & Lock Acquisition

## Quando Usar
Antes de iniciar qualquer modificação de arquivos para uma tarefa específica.

## Procedimento

1. **Checagem de Autorização em `.ai/TASKS.md`:**
   - Confirmar se a tarefa está atribuída ao agente solicitante e possui status `TODO` ou `READY`.

2. **Checagem de Locks Concorrentes:**
   - Verificar se já existe `.ai/locks/{TASK_ID}.lock.yaml`.
   - Se existir com outro proprietário ativo $\rightarrow$ **NÃO EDITAR**. Reportar concorrência.
   - Se for um lock abandonado $\rightarrow$ seguir procedimento de `STALE_LOCK_SUSPECTED`.

3. **Criação do Arquivo de Lock:**
   - Criar `.ai/locks/{TASK_ID}.lock.yaml` especificando:
     - `task_id`
     - `owner`
     - `write_scope`
     - `started_at`
     - `last_checkpoint_at`
     - `base_commit`
     - `workspace`
     - `status: ACTIVE`

4. **Transição de Status:**
   - Atualizar status da tarefa em `.ai/TASKS.md` para `IN_PROGRESS`.
   - Atualizar `.ai/LOG_INDEX.md` e `.ai/CURRENT.md`.
