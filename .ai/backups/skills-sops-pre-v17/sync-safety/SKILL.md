---
name: sync-safety
description: Diretrizes e procedimentos para prevenção e resolução de conflitos de sincronização Syncthing.
---

# 🔄 Skill: Syncthing Sync Safety

## Quando Usar
Antes de qualquer operação de escrita em arquivos do repositório em ambientes que utilizam sincronização contínua (Syncthing/SyncTrayzor).

## Procedimento de Segurança

1. **Scan de Arquivos de Conflito:**
   ```bash
   find . -name "*sync-conflict*"
   ```
2. **Tratamento de Resultados:**
   - **0 arquivos encontrados:** Seguro para prosseguir.
   - **$\ge$ 1 arquivo encontrado:**
     - Emitir evento `SYNC_CONFLICT_DETECTED`.
     - Executar parada imediata de escrita (`STOP WRITE`).
     - Não deletar nem sobrescrever o arquivo em conflito automaticamente.
     - Registrar ocorrência no log próprio e em `.ai/incidents/` se necessário.
     - Aguardar resolução pelo desenvolvedor humano ou Orchestrator.

3. **Política de Arquivos Ignorados:**
   - Garantir que `.env`, chaves privadas, dumps e logs voláteis estejam isolados localmente.
