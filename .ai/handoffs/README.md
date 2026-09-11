# 🤝 Protocolo de Handoffs entre Agentes (.ai/handoffs/)

> Este diretório armazena os contratos formais de transição de tarefas e contexto operacional entre os agentes do ecossistema LawFirm CRM.

---

## 1. Princípios de Handoff

1. **Autocontido e Inequívoco:** O documento de handoff deve conter todas as informações necessárias para que o agente destinatário execute a tarefa sem necessitar adivinhar intenções ou refazer análises prévias.
2. **Validação de Snapshot Obrigatória:** O agente destinatário deve validar se o `HANDOFF EXPECTED COMMIT` coincide com o commit do seu workspace local antes de iniciar qualquer trabalho. Se houver divergência $\rightarrow$ `SNAPSHOT_MISMATCH` $\rightarrow$ STOP.
3. **Escopo Delimitado:** O handoff deve listar explicitamente o que está autorizado a ser feito e o que é proibido de ser feito.

---

## 2. Estrutura Padrão de Handoff (`HANDOFF-{TASK_ID}.md`)

```markdown
# HANDOFF — {TASK_ID}

- **From:** {AGENT_ORIGEM}
- **To:** {AGENT_DESTINO}
- **Task ID:** {TASK_ID}
- **Status:** READY
- **Expected Commit:** {GIT_COMMIT_HASH}
- **Objective:** {OBJETIVO_CLARO}

## 1. Contexto e Estado Atual
{RESUMO_DO_ESTADO_ATUAL}

## 2. Instruções de Execução
{PASSOS_DETALHADOS}

## 3. Restrições e Não-Objetivos
{O_QUE_NAO_FAZER}

## 4. Critérios de Aceite (Definition of Done)
{CHECKLIST_DE_VALIDACAO}

## 5. Próximo Passo Após Conclusão
{HANDOFF_SEGUINTE_OU_RETORNO_AO_ORCHESTRATOR}
```
