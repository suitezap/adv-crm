# Plano de Governança — LawFirm CRM (Antigravity)

**Contexto:** projeto Laravel/DDD multi-tenant, já com documentação madura
(`ARCHITECTURE.md`, `ARCHITECTURE_dir.md`, `ARCHITECTURE_mothership_orient.md`,
`AUDIT_REPORT.md`). O objetivo aqui não é criar governança do zero — é
**consolidar o que já existe em regras que o Antigravity respeita
automaticamente**, e fechar lacunas concretas que os próprios documentos
revelam.

Siga a sequência um prompt por vez, revisando o resultado antes do próximo.

---

## Etapa 0 — Achados que precisam de atenção antes de começar

Levantei 4 problemas concretos nos documentos que valem correção manual ou
via prompt dedicado, antes de formalizar qualquer regra em cima deles:

1. **Encoding corrompido em `ARCHITECTURE_mothership_orient.md`** — o arquivo
   tem trechos em mojibake (`Ã§Ã£o`, `Ã¡rea`, `nÃºmero`, etc.), sinal de que foi
   salvo/reaberto com encoding errado em algum momento. Isso é frágil: se o
   Antigravity ler esse arquivo como contexto, pode interpretar mal trechos
   corrompidos. Vale corrigir para UTF-8 antes de tratá-lo como fonte de
   verdade.
2. **Numeração de seção duplicada em `ARCHITECTURE.md`** — existem duas
   seções `### 4.85` ("Correção de Bug Crítico — Separação de account_id e
   inbox_id" e "Consolidação do Docker Hub"). Não quebra nada tecnicamente,
   mas dificulta referenciar seções de forma inequívoca em prompts futuros.
3. **Pacote fantasma `packages/SuiteZap/Whaticket/`** — migrations nunca
   executadas, mantido só para não quebrar o `composer.json`/`entrypoint.sh`.
   Isso é exatamente o tipo de coisa que um agente de IA, sem contexto, pode
   "limpar" achando que é lixo — e derrubar o deploy.
4. **Histórico de regressão já documentado mas não formalizado como regra
   viva** — o `AUDIT_REPORT.md` já lista riscos conhecidos (config UI,
   traduções, upload de arquivo, PDF) e o `ARCHITECTURE.md` já documentou o
   bug do `account_id`/`inbox_id` do Chatwoot. Isso é ouro para um
   `GUARDRAILS.md`, mas hoje está espalhado em prosa dentro de docs longos —
   o agente pode não conectar os pontos na hora de tocar nesse código de novo.

---

## PROMPT 1 — Auditoria de consistência entre os documentos existentes

> Objetivo: antes de formalizar regras, garantir que a base documental não
> tem contradições que o agente possa herdar.

```
Leia ARCHITECTURE.md, ARCHITECTURE_dir.md, ARCHITECTURE_mothership_orient.md
e AUDIT_REPORT.md por completo. Não altere nenhum arquivo de código nesta
tarefa. Gere um relatório em /docs/auditoria-consistencia-docs.md apontando:

1. Informações contraditórias entre os documentos (ex: versões diferentes
   citadas para o mesmo componente, campos do Chatwoot descritos de forma
   diferente em documentos distintos)
2. Seções com numeração duplicada ou referências quebradas
3. Trechos com problemas de encoding (caracteres corrompidos tipo "Ã§Ã£o")
4. Decisões arquiteturais que parecem obsoletas (substituídas por decisões
   mais recentes no mesmo documento) mas que ainda não foram marcadas como tal

Não corrija nada ainda, apenas liste os achados com a localização exata
(arquivo + seção).
```

---

## PROMPT 2 — Corrigir encoding e numeração antes de formalizar regras

> Objetivo: higienizar a base antes de transformá-la em fonte de verdade
> operacional.

```
Com base no relatório de /docs/auditoria-consistencia-docs.md:

1. Corrija o encoding de ARCHITECTURE_mothership_orient.md para UTF-8 limpo,
   preservando 100% do conteúdo e formatação Markdown — apenas corrigindo os
   caracteres corrompidos.
2. Renumere a segunda ocorrência da seção "4.85" em ARCHITECTURE.md para o
   próximo número sequencial disponível, sem alterar o conteúdo.
3. Não faça nenhuma outra alteração de conteúdo nesta tarefa — apenas
   correções mecânicas de encoding e numeração.

Mostre o diff antes de aplicar.
```

---

## PROMPT 3 — Criar o AGENTS.md consolidando as Regras de Ouro já existentes

> Objetivo: o `ARCHITECTURE.md` já tem uma seção "3. Regras de Ouro" — o
> AGENTS.md deve elevar isso a regras que o Antigravity consulta
> automaticamente em toda sessão, sem duplicar conteúdo, apenas referenciando
> e reforçando com linguagem de instrução direta ao agente.

```
Crie um arquivo AGENTS.md na raiz do projeto. Baseie-se na seção "3. Regras
de Ouro" de ARCHITECTURE.md e formalize como instruções diretas ao agente,
incluindo no mínimo:

1. Manipulação de arquivos: nunca usar Storage::put/makeDirectory/url ou
   acesso direto ao disco local; sempre usar SaaS\Services\SaasFileService,
   incluindo getSignedUrl() para qualquer exibição de asset de tenant.
2. Rotas e Controllers: controllers devem ser "skinny" — lógica de negócio
   sempre em Services, nunca em Controllers.
3. Zero .env: qualquer credencial (Evolution, Chatwoot, S3) deve vir de
   MotherShipService, nunca de variáveis de ambiente hardcoded.
4. Imagem Docker: nunca referenciar suitezap/adv-crm (descontinuada); sempre
   suitezap/lawfirm com tag semântica, nunca :latest em produção.
5. Estrutura DDD: todo código novo deve respeitar a estrutura de domínio
   (Http/Controllers, Models, Services, Repositories dentro de {Domain}/),
   nunca em src/Http/Controllers/ na raiz (Zero Root Controllers desde v3.36).

Ao final, adicione uma nota: "Estas regras têm precedência sobre qualquer
padrão genérico de Laravel — em caso de dúvida, seguir o que está aqui, não
o que é comum em outros projetos Laravel."
```

---

## PROMPT 4 — Regra crítica de isolamento multi-tenant

> Objetivo: esta é a regra mais importante do projeto — precisa estar
> explícita e não apenas implícita nos exemplos de código.

```
Adicione ao AGENTS.md uma seção "Isolamento Multi-Tenant (Regra Crítica)":

1. Toda query que acessa dados de domínio (Legal, Financial, GED, Whatsapp,
   Atendimento) deve estar escopada por tenant_id, exceto tabelas
   explicitamente do banco `mothership` (documentar quais são).
2. Qualquer alteração em código de autenticação, resolução de tenant, ou
   queries dinâmicas deve justificar explicitamente, no plano apresentado
   antes da execução, como o isolamento entre tenants é preservado.
3. Nunca usar credenciais globais/master onde deveria haver credencial
   isolada por tenant, exceto os casos já documentados de herança de token
   (ex: MotherShipService::getEvolutionConfig() com fallback para token
   master — este é um caso aprovado e documentado, não abrir novos sem
   registrar aqui).
4. Toda migration que roda no banco `mothership` deve ser explicitamente
   marcada como tal (conexão `mothership` explícita), nunca assumida por
   omissão.
```

---

## PROMPT 5 — Proteger módulos suspensos (Whaticket) de reativação acidental

> Objetivo: `ARCHITECTURE_whats.md` já documenta muito bem a suspensão do
> Messenger Inbox — falta transformar isso numa regra que impede o agente de
> "limpar" ou reativar por engano.

```
Adicione ao AGENTS.md uma seção "Módulos Suspensos — Não Reativar Sem
Aprovação Explícita":

1. O submódulo Messenger Inbox (Whaticket), documentado em
   ARCHITECTURE_whats.md, está suspenso desde 29/05/2026. Rotas e
   controllers do Chat/Inbox estão desabilitados. Nunca reativar, restaurar
   rotas comentadas, ou remover o guard de desativação sem aprovação
   explícita minha.
2. O pacote packages/SuiteZap/Whaticket/ é mantido intencionalmente como
   scaffold vazio (migrations nunca executadas) apenas para não quebrar
   composer.json e docker/entrypoint.sh. Nunca deletar, remover do
   composer.json, ou remover seu path do entrypoint.sh sem aprovação
   explícita — isso quebraria o boot do container (Migration path not found).
3. Antes de qualquer tarefa que toque em arquivos dentro de
   packages/SuiteZap/LawFirm/src/Whatsapp/ ou packages/SuiteZap/Whaticket/,
   confirmar comigo se a tarefa envolve o módulo suspenso ou as
   funcionalidades ativas (Faturas, Alertas, Importação, Agendador de Prazos).
```

---

## PROMPT 6 — Criar o GUARDRAILS.md com o histórico de incidentes já documentado

> Objetivo: os documentos já contam a história de pelo menos 5 regressões
> reais. Isso deve virar memória ativa, não só prosa histórica.

```
Crie GUARDRAILS.md na raiz do projeto. Popule com entradas retroativas
baseadas nos incidentes já documentados em ARCHITECTURE.md e AUDIT_REPORT.md,
no formato:

## [DATA] — [Componente afetado]
**O que quebrou:**
**Causa raiz:**
**Regra criada para evitar repetição:**

Inclua no mínimo estas entradas retroativas:

1. Bug de account_id/inbox_id do Chatwoot (ARCHITECTURE.md §4.85) — coluna
   chatwoot_inbox_id usada ambiguamente causou cross-tenant event leakage
   potencial e erro 422 na criação de contatos.
2. "Array to String conversion" em field-type.blade.php (AUDIT_REPORT.md §7)
   — risco recorrente na tela de Configurações.
3. Chave de tradução crua exibida (lawfirm::app.deadlines.status) por
   estrutura incorreta de array em pt_BR/app.php.
4. Método inexistente uploadProcessAttachment chamado por ProcessoController
   (ARCHITECTURE.md §4.5) — corrigido para processUploads().

Adicione uma nota no topo: toda vez que uma correção quebrar algo que
funcionava, uma entrada deve ser registrada aqui, e a regra correspondente
adicionada ao AGENTS.md, antes de considerar a tarefa concluída.
```

---

## PROMPT 7 — Testes automatizados para a área de maior risco comprovado (Chatwoot Dual Inbox)

> Objetivo: a config do Chatwoot já tem 5 campos (`chatwoot_node_id`,
> `chatwoot_inbox_id`, `chatwoot_channel_inbox_id`,
> `chatwoot_assistant_inbox_id`, `chatwoot_webhook_token`) e já quebrou uma
> vez por confusão entre eles. É a área com maior probabilidade estatística
> de quebrar de novo.

```
Crie testes automatizados (Pest ou PHPUnit, seguir o que já é usado no
projeto) para MotherShipService::getChatwootConfig() e
ChatwootService::sendMessage()/sendAssistantMessage(), cobrindo:

1. account_id sempre mapeado de chatwoot_inbox_id (nunca de
   chatwoot_channel_inbox_id)
2. inbox_id sempre mapeado de chatwoot_channel_inbox_id
3. assistant_inbox_id mapeado de chatwoot_assistant_inbox_id, com fallback
   para inbox_id + Log::warning quando null
4. api_key (Bot Token) nunca usado em chamadas de /labels ou /contacts
5. access_token (User Access Token) nunca usado em POST /messages

Salve em tests/Feature/ChatwootConfigTest.php (ou caminho equivalente ao
padrão de testes já usado no projeto). Rode os testes e reporte o resultado
antes de considerar a tarefa concluída.
```

---

## PROMPT 8 — Fluxo obrigatório de mudanças (adaptado ao stack Laravel/DDD)

> Objetivo: mesma lógica do projeto n8n, adaptada às particularidades daqui
> (migrations idempotentes, versionamento sincronizado, DDD).

```
Adicione ao AGENTS.md uma seção "Fluxo obrigatório para qualquer alteração":

1. EXPLORAÇÃO: antes de propor código, explicar como o comportamento atual
   resolve o problema, referenciando a documentação de arquitetura relevante
   (ARCHITECTURE.md, ARCHITECTURE_dir.md ou ARCHITECTURE_mothership_orient.md
   conforme o domínio afetado). Não gerar código nesta fase.
2. PLANO: apresentar plano explícito listando arquivos/domínios tocados,
   confirmando isolamento multi-tenant preservado (Prompt 4) e se algum
   módulo suspenso está envolvido (Prompt 5). Aguardar aprovação.
3. EXECUÇÃO: aplicar apenas o aprovado, mostrando diff. Toda migration nova
   deve ser idempotente (usar hasColumn() ou equivalente) e declarar
   explicitamente a conexão de banco (tenant vs mothership).
4. VALIDAÇÃO: rodar os testes automatizados relevantes; se a mudança afetar
   Chatwoot, rodar especificamente ChatwootConfigTest.php.
5. SINCRONIZAÇÃO DE VERSÃO: se a mudança for estrutural, incrementar a versão
   no cabeçalho de ARCHITECTURE.md e na constante VERSION de
   LawFirmServiceProvider.php — ambas devem sempre coincidir.
6. REGISTRO: se algo quebrou durante o processo, registrar em GUARDRAILS.md
   antes de finalizar.
7. COMMIT: sugerir mensagem de commit atômica; commitar só após aprovação.

Este fluxo é obrigatório mesmo para correções que pareçam simples,
especialmente em Whatsapp/, Atendimento/ e SaaS/.
```

---

## PROMPT 9 — Regra de sincronização MotherShip ⇄ LawFirm

> Objetivo: `ARCHITECTURE_mothership_orient.md` existe justamente para
> documentar impacto cross-repo — falta a regra que obriga o agente a
> mantê-lo atualizado.

```
Adicione ao AGENTS.md a regra: toda alteração no LawFirm que crie ou altere
tabelas/colunas na conexão mothership, crie novos endpoints consumidos pelo
MotherShip, ou mude a semântica de campos já documentados em
ARCHITECTURE_mothership_orient.md, deve atualizar esse arquivo na mesma
tarefa — nunca deixar para depois. Se a tarefa não tiver certeza se afeta o
MotherShip, deve perguntar antes de assumir que não afeta.
```

---

## PROMPT 10 — Isolamento entre domínios DDD

> Objetivo: equivalente ao isolamento entre os dois workflows n8n, mas aqui
> aplicado aos 11 domínios (Legal, Financial, GED, SaaS, AI, Escavador,
> Atendimento, DataJud, TenantFinance, Whatsapp, Console).

```
Adicione ao AGENTS.md a regra: qualquer tarefa em um domínio (ex: Legal) não
deve alterar arquivos de outro domínio (ex: Financial, Whatsapp) a menos que
a tarefa exija integração explícita entre eles — e nesse caso, o plano deve
listar explicitamente os dois domínios afetados e o motivo. Ao iniciar
qualquer tarefa, confirmar comigo qual(is) domínio(s) estão em escopo antes
de propor um plano.
```

---

## Depois de concluir os 10 passos

Você terá:
- Documentação existente higienizada (encoding corrigido, numeração
  consistente) e transformada em fonte de verdade citável
- `AGENTS.md` — regras de ouro formalizadas + isolamento multi-tenant +
  proteção de módulos suspensos + fluxo obrigatório + isolamento DDD
- `GUARDRAILS.md` — histórico real de 4+ incidentes já documentados, pronto
  para crescer
- Testes automatizados na área de maior risco comprovado (Chatwoot Dual
  Inbox)
- Regra explícita de sincronização entre os dois repositórios (LawFirm ↔
  MotherShip)

**Diferença em relação ao projeto n8n:** aqui a base documental já existia e
era rica — o trabalho foi consolidar e formalizar, não criar do zero. Em
projetos futuros ainda mais complexos, comece sempre pelo Prompt 1
(auditoria de consistência) antes de qualquer outra coisa — é o que revela
se a documentação existente pode ser usada como fonte de verdade ou precisa
de faxina primeiro.
