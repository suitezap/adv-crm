# 🤖 AGENTS.md — Regras de Processo para Agentes de IA (LawFirm SaaS)

## Hierarquia de Fontes de Verdade

1. **SKILL.md** (.agent/skills/krayin_lawfirm_dev/SKILL.md) é a **fonte primária** para QUALQUER regra de código, padrão de domínio, ou convenção de frontend. Sempre consultar antes de escrever ou alterar código.

2. **ARCHITECTURE.md**, **ARCHITECTURE_dir.md** e **ARCHITECTURE_mothership_orient.md** são a fonte para histórico de decisões (ADRs), mapas de diretório/rotas, e integrações cross-repo — **não** para regras de código do dia a dia.

3. **Se um documento parecer contradizer o outro, PARE** e reporte o conflito explicitamente antes de agir — nunca escolher um lado silenciosamente.

4. **Este AGENTS.md nunca deve conter uma regra de código que já existe no SKILL.md.** Se uma regra nova de código for necessária, ela deve ser adicionada ao SKILL.md, não aqui.

---

## Isolamento Multi-Tenant (Regra Crítica)

> [!CAUTION]
> **Qualquer código que quebre o isolamento entre tenants é uma falha de segurança — não apenas um bug de funcionalidade.**

### 1. Escopo de Tenant em Queries de Domínio

Toda query que acessa dados dos domínios **Legal, Financial, GED, Whatsapp, Atendimento, AI, Escavador, TenantFinance** ou tabelas tenant-scoped do domínio **SaaS** (ex: `saas_transactions`) deve estar escopada por `tenant_id` ou equivalente de isolamento.

**Exceção documentada — tabelas do banco `mothership` que permitem acesso cross-tenant por design:**

| Tabela | Motivo |
|:---|:---|
| `tenants` | Cadastro global de tenants |
| `subscriptions` | Assinaturas e saldo SuiteCoins |
| `infrastructure_nodes` | Nós de infraestrutura (Evolution, N8N, Asaas, Storage, Chatwoot) |
| `app_config` | Configurações globais da plataforma |
| `saas_orders` | Intenções de compra cross-tenant |
| `lawfirm_document_templates` | Templates globais de documentos (somente leitura pelo tenant) |
| `chatwoot_nodes` | Nós Chatwoot globais |
| `asaas_nodes` | Nós Asaas globais |
| `evolution_nodes` | Nós Evolution globais |
| `storage_nodes` | Nós de Storage globais |
| `n8n_nodes` | Nós N8N globais |
| `admin_users` | Usuários administrativos do MotherShip |

### 2. Alterações em Autenticação, Resolução de Tenant ou Queries Dinâmicas

Qualquer alteração nessas áreas deve justificar **explicitamente no plano** (antes da execução) como o isolamento entre tenants é preservado. Não é suficiente afirmar "não afeta isolamento" — é necessário demonstrar qual mecanismo garante o escopo correto.

### 3. Credenciais — Nunca Global Onde Deveria Ser Por Tenant

Nunca usar credenciais globais/master onde deveria haver credencial isolada por tenant.

**Casos aprovados e documentados de herança de token (não criar novos sem registrar aqui):**

| Caso | Mecanismo | Referência |
|:---|:---|:---|
| `MotherShipService::getEvolutionConfig()` | Fallback para token master do nó quando tenant não tem token próprio | `ARCHITECTURE.md §4.35` |

Para abrir um novo caso de herança: registrar nesta tabela + justificar em `ARCHITECTURE_mothership_orient.md` antes de implementar.

### 4. Migrations no Banco `mothership`

Toda migration que roda no banco `mothership` deve declarar a conexão **explicitamente**:

```php
// OBRIGATORIO — nunca omitir
Schema::connection('mothership')->create('tabela', function (Blueprint $table) { ... });

// PROIBIDO — assume conexao padrao (tenant), nao mothership
Schema::create('tabela', function (Blueprint $table) { ... });
```

### 5. Isolamento de Filas Redis (crítico — ver também SKILL.md §6)

Em deployments Docker Swarm com Redis compartilhado, toda configuração de fila **deve** declarar `REDIS_PREFIX: {tenant_id}_` no `docker-compose.yml`. Sem isso, tenants consomem Jobs de fila uns dos outros pela rede overlay.

```yaml
# OBRIGATORIO em todo docker-compose.yml de tenant
x-environment: &tenant-env
  REDIS_PREFIX: "${TENANT_ID}_"   # isola filas por tenant no Swarm compartilhado
```

> [!IMPORTANT]
> Qualquer tarefa que toque em configuração de fila, Docker Swarm ou deploy deve verificar **explicitamente** que `REDIS_PREFIX` está presente e correto antes de considerar a tarefa concluída.

---

## Módulos Suspensos — Não Reativar Sem Aprovação Explícita

> [!CAUTION]
> **Reativar qualquer item desta lista sem aprovação explícita é uma alteração proibida, independentemente do contexto da tarefa.**

### 1. Messenger Inbox (Whaticket) — Suspenso desde 29/05/2026

O submódulo Messenger Inbox (Whaticket), documentado em `ARCHITECTURE_whats.md`, está **suspenso desde 29/05/2026**. Rotas e controllers do Chat/Inbox estão desabilitados.

**É proibido, sem aprovação explícita:**
- Reativar o módulo ou qualquer parte dele
- Restaurar rotas comentadas relacionadas ao Chat/Inbox
- Remover o guard de desativação que bloqueia o acesso

### 2. `packages/SuiteZap/Whaticket/` — Scaffold Vazio Intencional

O pacote `packages/SuiteZap/Whaticket/` é mantido intencionalmente como **scaffold vazio** (migrations nunca executadas) apenas para não quebrar `composer.json` e `docker/entrypoint.sh`.

**É proibido, sem aprovação explícita:**
- Deletar o pacote ou qualquer arquivo dele
- Removê-lo do `composer.json`
- Remover seu path do `docker/entrypoint.sh`

> ⚠️ Remover o path do `entrypoint.sh` quebraria o boot do container com erro fatal: **`Migration path not found`**.

### 3. Gate Obrigatório — Confirmação Antes de Tocar nos Paths Afetados

Antes de qualquer tarefa que toque em arquivos dentro de:
- `packages/SuiteZap/LawFirm/src/Whatsapp/`
- `packages/SuiteZap/Whaticket/`

**Confirmar explicitamente** se a tarefa envolve o módulo suspenso ou as funcionalidades **ativas** que convivem nesses paths:

| Funcionalidade ativa | Localização |
|:---|:---|
| Faturas WhatsApp | `Whatsapp/` — notificações de cobrança |
| Alertas de Prazo | `Whatsapp/` — alertas jurídicos automáticos |
| Importação de Histórico | `Whatsapp/` — `WhatsappImport` |
| Agendador de Prazos | `Whatsapp/` — `SendScheduledPrazoNotifications` |

---

## Isolamento de Domínio — Não Cruzar Sem Justificativa

> [!IMPORTANT]
> Qualquer tarefa em um domínio (ex: Legal) não deve alterar arquivos de outro domínio (ex: Financial, Whatsapp) a menos que a tarefa exija integração explícita entre eles.

- **No Plano**: caso haja necessidade de cruzar domínios, o plano deve listar explicitamente os dois (ou mais) domínios afetados e o motivo técnico da integração.
- **Antes do Plano**: ao iniciar qualquer tarefa, confirmar explicitamente com o usuário qual(is) domínio(s) estão em escopo antes de propor uma solução.

---

## Sincronização Obrigatória de Arquitetura MotherShip

> [!WARNING]
> Toda alteração no LawFirm que crie ou altere tabelas/colunas na conexão `mothership`, crie novos endpoints consumidos pelo MotherShip, ou mude a semântica de campos já documentados em `ARCHITECTURE_mothership_orient.md`, deve **atualizar esse arquivo na mesma tarefa** — nunca deixar para depois. 
> 
> Se a tarefa não tiver certeza se afeta o MotherShip, **deve perguntar antes de assumir que não afeta**.

---

## Fluxo Obrigatório para Qualquer Alteração

1. **EXPLORAÇÃO**: antes de propor código, explicar como o comportamento atual resolve o problema, referenciando a documentação de arquitetura relevante (`ARCHITECTURE.md`, `ARCHITECTURE_dir.md` ou `ARCHITECTURE_mothership_orient.md` conforme o domínio afetado). Não gerar código nesta fase.
2. **PLANO**: apresentar plano explícito listando arquivos/domínios tocados, confirmando isolamento multi-tenant preservado e se algum módulo suspenso está envolvido. Aguardar aprovação.
3. **EXECUÇÃO**: aplicar apenas o aprovado, mostrando diff. Toda migration nova deve ser idempotente (usar `hasColumn()` ou equivalente) e declarar explicitamente a conexão de banco (`tenant` vs `mothership`).
4. **VALIDAÇÃO**: rodar os testes automatizados relevantes; se a mudança afetar Chatwoot, rodar especificamente `ChatwootConfigTest.php`. Comandos de validação (testes automatizados, scans de integridade) que precedem um commit devem ser executados de forma síncrona/foreground, com a saída real colada na resposta antes do commit ser proposto. É proibido declarar um commit como pronto enquanto uma validação ainda roda em background — nesse caso, aguardar a conclusão real e colar a saída bruta antes de prosseguir para git add/commit, nunca narrar o resultado como fato consumado antes de tê-lo em mãos.
5. **SINCRONIZAÇÃO DE VERSÃO**: se a mudança for estrutural, incrementar a versão no cabeçalho de `ARCHITECTURE.md` e na constante `VERSION` de `LawFirmServiceProvider.php` — ambas devem sempre coincidir.
6. **REGISTRO**: se algo quebrou durante o processo, registrar em `GUARDRAILS.md` antes de finalizar.
7. **COMMIT**: sugerir mensagem de commit atômica; commitar só após aprovação.

> [!IMPORTANT]
> Este fluxo é obrigatório mesmo para correções que pareçam simples, especialmente em `Whatsapp/`, `Atendimento/` e `SaaS/`.

---

## Higiene de Commits

> [!IMPORTANT]
> **Regras obrigatórias de versionamento e commit:**
> - **Nunca usar `git add -A` ou `git add .`** sem antes rodar `git status` e revisar a lista completa de arquivos que serão incluídos no stage.
> - **Atomicidade Estrita:** Um commit deve conter apenas os arquivos relacionados à tarefa em execução. Arquivos não relacionados encontrados no working directory durante uma tarefa devem ser reportados ao usuário, nunca commitados juntos.
> - **Artefatos Proibidos em Git:** Nunca commitar arquivos de backup (`*.bak`, `*.bak2`), bancos de sincronização local (`*.ffs_db`), arquivos de coleção soltos, ou build artifacts compilados (`public/**/build/`) — esses arquivos devem residir obrigatoriamente no `.gitignore`.

---

## Verificação de Integridade em Arquivos de Governança

> [!IMPORTANT]
> **Após qualquer escrita em `GUARDRAILS.md`, `AGENTS.md` ou `SKILL.md`**, reabrir o arquivo e confirmar visualmente que o conteúdo está íntegro (sem caracteres de controle, sem perda de palavras ou variáveis) antes de considerar a tarefa concluída — esses três arquivos são fonte de verdade para sessões futuras, então corrupção neles é mais cara que em qualquer outro arquivo do projeto.

---

> Este arquivo trata de **COMO o agente deve se comportar** (processo, aprovação, memória de incidentes). Para **O QUE é permitido ou proibido no código**, a fonte é sempre o **SKILL.md**.

