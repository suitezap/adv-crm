---
name: context-budget
description: Otimização e gestão eficiente do orçamento de contexto (tokens) para agentes multiagente.
---

# 🧠 Skill: Context Budget Optimization

## Quando Usar
Durante toda a execução de tarefas para evitar estouro de tokens e perda de precisão contextual.

## Regras de Gestão de Contexto

1. **Descoberta Indexada:**
   - **NÃO** ler todos os arquivos de log (`.ai/logs/*.md`) de ponta a ponta.
   - Usar `.ai/LOG_INDEX.md` para encontrar a seção exata necessária e ler apenas as linhas relevantes.

2. **Leitura Cirúrgica de Código:**
   - Usar `grep_search` e visualização com intervalos específicos (`StartLine` / `EndLine`) em arquivos grandes.
   - Não carregar arquivos binários, caches, `.git/` ou dumps de banco.

3. **Respostas Concisas:**
   - Manter atualizações objetivas no chat, direcionando detalhes extensos para artefatos ou arquivos em `.ai/logs/`.
