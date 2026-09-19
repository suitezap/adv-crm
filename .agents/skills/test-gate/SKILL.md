---
name: test-gate
description: Critérios rigorosos de verificação (Verification Gate) antes de transição para VERIFIED e DONE.
---

# 🚦 Skill: Verification Gate

## Quando Usar
Antes de considerar qualquer tarefa como `VERIFIED` ou propor um commit final como `DONE`.

## Checklist Mandatório

1. **Validação Documental de Qualidade:**
   ```bash
   python quality/scripts/validate_test_docs.py
   ```
   *Critério:* 0 erros de validação.

2. **Integridade de Git Diff:**
   ```bash
   git status --short
   git diff --check
   git diff --stat
   git diff --name-only
   ```
   *Critério:* `git diff --check` sem erros; arquivos modificados 100% dentro da allowlist da tarefa.

3. **Verificação de Sincronismo:**
   ```bash
   find . -name "*sync-conflict*"
   ```
   *Critério:* 0 arquivos encontrados.

4. **Preservação de Código Funcional:**
   *Critério:* Nenhum arquivo de domínio funcional alterado fora do escopo autorizado.
