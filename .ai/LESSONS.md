# 💡 LESSONS.md — Lições Aprendidas e Guardrails Operacionais

> Registro cumulativo de lições aprendidas, incidentes prevenidos e boas práticas estabelecidas pelos agentes no LawFirm CRM.

---

## 1. Integridade e Sincronização
- **Lição 01 (Syncthing Concurrency):** O Syncthing sincroniza alterações em tempo real, mas não resolve conflitos de lógica. Arquivos `sync-conflict-*` ocorrem quando dois ambientes escrevem simultaneamente no mesmo arquivo. **Guardrail:** Sempre rodar verificação de conflitos antes de escrever e respeitar locks.
- **Lição 02 (Encoding e Line Endings):** Ambientes mistos (Windows IDE e Linux VPS) podem introduzir quebras `CRLF` em scripts bash ou corromper acentuação UTF-8. **Guardrail:** Arquivos Markdown e YAML devem ser mantidos estritamente em UTF-8 limpo sem BOM, e scripts `.sh` com `LF`.

---

## 2. Governança Multi-Tenant e Banco de Dados
- **Lição 03 (Isolamento de Tenant em Queries):** Qualquer query sem escopo explícito de tenant expõe dados cross-tenant e constitui falha crítica de segurança. **Guardrail:** Sempre auditar `tenant_id` em queries de domínios jurídicos, financeiros e transacionais.
- **Lição 04 (Conexão Mothership Explícita):** Migrations e models que acessam o banco Mothership devem declarar expressamente `connection('mothership')` para não criar tabelas no banco de tenant.

---

## 3. Gestão de Contexto e Ciclo de Vida de Tarefas
- **Lição 05 (Verificação Antes de Declarar DONE):** Declarar uma tarefa como `DONE` antes da validação comprovada gera falsos positivos e quebra a confiança do pipeline. **Guardrail:** O ciclo de status deve seguir obrigatoriamente `TODO` $\rightarrow$ `IN_PROGRESS` $\rightarrow$ `IMPLEMENTED_NOT_VERIFIED` $\rightarrow$ `VERIFIED` $\rightarrow$ `DONE`.
- **Lição 06 (Camada de Descoberta vs Leitura Completa):** Exigir a leitura de dezenas de logs a cada turno esgota a janela de contexto dos modelos. **Guardrail:** Utilizar `.ai/LOG_INDEX.md` e `.ai/CURRENT.md` como pontos de entrada indexados.

---

## 4. Integrações Externas e APIs (Chatwoot, WhatsApp, SaaS)
- **Lição 07 (Normalização de `base_url` em Infrastructure Nodes):** URLs cadastradas no banco Mothership para nós de infraestrutura podem conter sufixos de dashboard (ex: `/app/login` ou `/app`). Concatenar caminhos de API diretamente sobre essas strings causa falhas silenciosas de HTTP 406 (`"Please use API routes instead of dashboard routes for JSON requests"`). **Guardrail:** O método `MotherShipService::getChatwootConfig()` deve sempre extrair e normalizar estritamente `scheme + host (+ port)` via `parse_url()`, expurgando qualquer sub-path de UI antes de repassar a URL ao `ChatwootService`.

---

## 5. Frontend e Reatividade (Blade + Vue 3)
- **Lição 08 (Reatividade em Objetos no Vue 3 Options API):** Em componentes Vue 3 inicializados via Blade (ex: `v-leads-kanban`), objetos declarados como `{}` no `data()` perdem a reatividade para chaves adicionadas dinamicamente após chamadas assíncronas (`this.stageLeads[sortOrder] = data`). Isso faz com que cards arrastados no Kanban sumam ou não renderizem na coluna de destino. **Guardrail:** Sempre pré-semear todas as chaves esperadas (ex: `mapWithKeys` dos estágios do pipeline) no retorno inicial de `data()`, e utilizar `Object.assign(this.stageLeads[key], data)` para mutar o estado reativo preservando a referência, além de implementar rollback visual em caso de falha na API.

---

## 6. Integração Chatwoot — Labels e Pool de Limpeza
- **Lição 09 (Case-Sensitivity de Labels no Chatwoot):** A API do Chatwoot armazena e retorna labels **sempre em lowercase** (ex: `cas_prod`, `ld_ganho`). O PHP `array_diff` é case-sensitive; portanto, um pool contendo `CAS_PROD` nunca irá bater com `cas_prod` retornado pela API, impedindo a remoção de tags antigas. **Guardrail:** Em `ChatwootService::syncContactLabels()`, normalizar **todas** as comparações e envios para lowercase via `array_map('mb_strtolower', ...)` tanto no pool quanto nos labels atuais e nos novos.
- **Lição 10 (Pool de Limpeza é Só para Remoção — Não Misturar com Tags Permanentes):** O parâmetro `$stagePool` no `syncContactLabels` serve exclusivamente para indicar “quais tags podem ser removidas se estiverem presentes”. Tags de classificação permanente (ex: `cli_pf`, `previdenciário`) **nunca devem entrar no pool**, pois isso as removeria indevidamente em transições futuras. **Guardrail:** Pool do Caso contém apenas `CAS_*` + `ld_ganho` (condicional). `cli_pf` é adicionado como label desejado, não como candidato à remoção.
- **Lição 11 (Regra de Promoção Lead → Cliente):** A transição `ld_ganho` → `cli_pf` não acontece ao marcar o lead como Ganho (Kanban Leads), mas sim na **primeira movimentação do Caso para qualquer etapa além de "Novo Caso"** (Kanban Jurídico). Isso reflete o momento em que o cliente formalmente inicia atendimento jurídico ativo. Listener responsável: `SyncCasoStageToChatwootListener`. Ver ADR-ATEND-002 em `DECISIONS.md`.
