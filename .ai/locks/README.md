# 🔒 Protocolo de Locks de Tarefas (.ai/locks/)

> Mecanismo de controle de concorrência operacional para garantir que apenas **um único agente** atue como `WRITE OWNER` de uma tarefa e escopo por vez.

---

## 1. Princípios Fundamentais

1. **Exclusividade de Escrita:** Somente o agente detentor do lock ativo pode realizar commits e alterações nos arquivos dentro do `write_scope`.
2. **Permissão de Leitura para Outros Agentes:** Agentes sem lock podem ler (`READ`), auditar (`REVIEW`) e investigar (`INVESTIGATE`), mas não editar simultaneamente.
3. **Imutabilidade e Rastreabilidade:** Locks são registrados via arquivo YAML versionado ou visível na estrutura `.ai/locks/`.
4. **Heartbeat e Checkpoints:** O campo `last_checkpoint_at` registra o último momento de atividade do agente e é fundamental para avaliar se uma tarefa foi abandonada.

---

## 2. Formato Canônico do Lock (`.ai/locks/{TASK_ID}.lock.yaml`)

```yaml
task_id: HERMES-001
owner: HERMES # ANTIGRAVITY | HERMES | OPENCODE
write_scope:
  - .ai/logs/HERMES.md
  - .ai/LOG_INDEX.md
  - .ai/TASKS.md
  - .ai/CURRENT.md
  - .ai/handoffs/RESULT-HERMES-001.md
started_at: "2026-08-26T23:30:00Z"
last_checkpoint_at: "2026-08-26T23:45:00Z"
base_commit: "11f5b4e7e0812e53d5d0e70353326112386f18e6"
workspace: "vps-hermes"
status: ACTIVE # ACTIVE | RELEASED
```

---

## 3. Ciclo de Vida do Lock

1. **Aquisição:** O agente cria o arquivo `.ai/locks/{TASK_ID}.lock.yaml` com `status: ACTIVE` ao iniciar a execução de uma tarefa aprovada em `.ai/TASKS.md`.
2. **Atualização de Checkpoint:** Em tarefas longas, o agente atualiza `last_checkpoint_at` a cada etapa relevante concluída.
3. **Liberação Normal:** Ao concluir a tarefa e validar o Verification Gate, o agente altera o arquivo para `status: RELEASED` ou remove o arquivo de lock e atualiza `.ai/TASKS.md` para `DONE`.

---

## 4. Procedimento de Stale Lock (Bloqueio Abandonado)

> [!CAUTION]
> **Nenhum lock poderá ser removido automaticamente apenas por idade.**
> É estritamente proibido que um agente delete silenciosamente o arquivo de lock de outro agente (`rm lock`).

Caso um lock ativo seja encontrado e o agente responsável pareça inativo:
1. Consultar `.ai/TASKS.md` para verificar o status oficial da tarefa.
2. Consultar `.ai/LOG_INDEX.md` e o log do agente responsável para verificar o último registro.
3. Avaliar o timestamp de `last_checkpoint_at` no arquivo de lock.
4. Se não houver atividade recente e a tarefa parecer interrompida, sinalizar formalmente `STALE_LOCK_SUSPECTED`.
5. Apenas o **Orchestrator (Antigravity)** ou o **Desenvolvedor Humano** pode deliberar, liberar o lock e reatribuir a tarefa.
