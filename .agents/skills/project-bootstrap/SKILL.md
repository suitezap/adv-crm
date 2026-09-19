---
name: project-bootstrap
description: Procedimento canônico de inicialização de sessão para agentes no LawFirm CRM.
---

# 🚀 Skill: Project Bootstrap

## Quando Usar
Toda vez que um agente iniciar uma nova sessão ou receber uma nova tarefa no repositório LawFirm CRM.

## Procedimento Passo a Passo

1. **Leitura dos Arquivos de Governança Raiz:**
   - Ler `AGENTS.md` para carregar regras de processo e limites de segurança.
   - Ler `.ai/CURRENT.md` para entender a fase ativa e o estado do sistema.

2. **Identificação da Tarefa Ativa:**
   - Consultar `.ai/TASKS.md` para identificar o ID da tarefa atribuída ao agente.
   - Consultar `.ai/LOG_INDEX.md` para localizar o último checkpoint do log correspondente.

3. **Verificação de Ambiente e Integridade:**
   - Verificar branch atual (`git branch --show-current`).
   - Verificar commit HEAD (`git rev-parse HEAD`).
   - Executar busca de conflitos de sincronização (`find . -name "*sync-conflict*"`). Se houver $\rightarrow$ `STOP WRITE`.

4. **Verificação de Locks:**
   - Consultar `.ai/locks/` para validar se há lock ativo para a tarefa ou se é necessário adquiri-lo.
