# GUARDRAILS.md — Registro de Incidentes e Regras Derivadas

> [!IMPORTANT]
> **Protocolo obrigatório:** Toda vez que uma correção quebrar algo que funcionava,
> uma entrada deve ser registrada aqui **antes de considerar a tarefa concluída**,
> e a regra correspondente adicionada ao AGENTS.md (se for regra de processo)
> ou ao SKILL.md (se for regra de código).

Formato de cada entrada:
```markdown
## [DATA] — [Componente afetado]
**O que quebrou:**
**Causa raiz:**
**Regra criada para evitar repetição:**
```

---

## 2026-08-18 — Processo de Geração de Documentação (Escape Sequences em GUARDRAILS.md)

**O que quebrou:** O arquivo `GUARDRAILS.md` continha caracteres de controle ASCII não imprimíveis (`0x07` BEL, `0x08` BS, `0x0B` VT, `0x0C` FF, tabulações espúrias) e perda de trechos de código (ex: `{{ $value }}` em Blade e blocos de código com crase simples), tornando palavras truncadas como `ccount_id`, `ield-type.blade.php`, `tenants`.

**Causa raiz:** O arquivo foi gerado/editado via script/ferramenta com interpolação de strings em aspas duplas ou parsing de JSON/Shell sem o devido escape de barras invertidas (`\`). Sequências como `\account_id`, `\field-type`, `\tenants`, `\v3.54.1`, `\blade` foram convertidas em bytes de controle ASCII. A ausência de uma etapa de reabertura e validação byte-a-byte permitiu que o arquivo fosse commitado corrompido.

**Regra criada para evitar repetição:** AGENTS.md §Verificação de Integridade em Arquivos de Governança — Após qualquer escrita em `GUARDRAILS.md`, `AGENTS.md` ou `SKILL.md`, reabrir o arquivo e confirmar visualmente e programmaticamente que o conteúdo está íntegro (sem caracteres de controle, sem perda de palavras ou variáveis) antes de considerar a tarefa concluída.

**Referência:** Commit `3dbb4575` (introdução) | **Versão corrigida:** 2026-08-18

---

## 2026-07-01 — ChatwootService / MotherShipService (account_id / inbox_id)

**O que quebrou:** Guard de inbox_id no ChatwootWebhookController comparava valor errado, criando risco de **cross-tenant event leakage**. ChatwootService::createContact() passava inbox_id incorreto ao Chatwoot, resultando em erro 422 ou criação de contato no inbox errado.

**Causa raiz:** A coluna chatwoot_inbox_id na tabela tenants (banco mothership) estava sendo usada de forma ambígua — ora guardava o account_id (ID da conta Chatwoot), ora o inbox_id (ID da caixa de entrada). A ausência de coluna dedicada para cada semântica tornou o mapeamento implícito e propenso a erros a cada refatoração.

**Regra criada para evitar repetição:** SKILL.md §3.3 e AGENTS.md §Isolamento Multi-Tenant §1 — account_id SEMPRE mapeado de chatwoot_inbox_id; inbox_id SEMPRE de chatwoot_channel_inbox_id (coluna nova, adicionada em v3.54.1). Nunca inverter. Testes automatizados em tests/Feature/ChatwootConfigTest.php cobrem este mapeamento.

**Referência:** ARCHITECTURE.md §4.85 | **Versão corrigida:** v3.54.1

---

## 2026-01-25 — Config UI (field-type.blade.php — "Array to String conversion")

**O que quebrou:** A tela **Geral → Configurações** do admin parou de salvar, exibindo erro fatal Array to String conversion. A página ficou inacessível para todos os tenants afetados.

**Causa raiz:** O Blade template field-type.blade.php recebe como parâmetro alguns campos de configuração cujo valor é um array (opções múltiplas, checkboxes, selects dinâmicos). Quando um valor array era passado para uma diretiva que esperava string (ex: {{ $value }}), o PHP lançava a exceção. A causa se repete após qualquer modificação em tipos de campo de configuração sem adicionar o guard is_array().

**Regra criada para evitar repetição:** Sempre que modificar ou adicionar campos em field-type.blade.php ou qualquer Blade de configuração, verificar se o valor pode ser array e adicionar guard explícito:
```blade
@if(is_array($value)) {{ implode(', ', $value) }} @else {{ $value }} @endif
```

**Referência:** AUDIT_REPORT.md §7 (Known Regression Risks — High Risk #1) | **Versão do audit:** v1.6 (2026-01-25)

---

## 2026-01-25 — Sistema de Traduções (pt_BR/app.php — Chave de tradução crua exibida)

**O que quebrou:** Usuários viram a chave literal lawfirm::app.deadlines.status exibida na interface em vez do texto traduzido. Afetou o módulo de Prazos.

**Causa raiz:** Estrutura incorreta de array de tradução em packages/SuiteZap/LawFirm/src/Resources/lang/pt_BR/app.php. A chave deadlines estava definida como array plano sem o sub-array status, quebrando a resolução hierárquica do Laravel Translation.

**Regra criada para evitar repetição:** Ao adicionar ou mover chaves de tradução em pt_BR/app.php, sempre replicar a hierarquia exata do código chamador. Verificar com php artisan lang:check (ou equivalente) se todas as chaves usadas no código existem no arquivo de tradução. Nunca mover uma chave sem verificar todos os pontos de uso.

**Referência:** AUDIT_REPORT.md §7 (Known Regression Risks — High Risk #2) | **Versão do audit:** v1.6 (2026-01-25)

---

## 2025 (v2.4) — ProcessoController (método inexistente uploadProcessAttachment)

**O que quebrou:** Upload de arquivos em Processos falhava silenciosamente ou com erro 500. Os métodos store() e update() de ProcessoController chamavam uploadProcessAttachment() que nunca existiu no DocumentService.

**Causa raiz:** Renomeação ou refatoração do método no DocumentService (de uploadProcessAttachment para processUploads) sem atualizar os chamadores. A ausência de testes de integração para o fluxo de upload permitiu que o erro passasse despercebido até uso em produção.

**Regra criada para evitar repetição:** Ao renomear qualquer método público de Service, executar busca global (grep -r) por todas as ocorrências antes de confirmar a tarefa. O método correto é DocumentService::processUploads() — qualquer referência a uploadProcessAttachment em código novo é um bug. Cobrir o fluxo de upload com ao menos um teste de feature.

**Referência:** ARCHITECTURE.md §4.5 (Correção de Bug Crítico v2.4) | **Versão corrigida:** v2.4
