# 💬 MotherShip ⇄ LawFirm ── Orientações de Sincronização (v3.54.1)

> [!SUCCESS]
> **Auditoria de Integração (Junho/2026):** O ecossistema foi revisado em conjunto e este documento atesta que as orientações técnicas para o MotherShip (v1.18) estão **100% implementadas e alinhadas** com as mudanças estruturais e módulos recém-lançados no LawFirm (até v3.53).
> Última atualização: **04/06/2026** — Sistema Misto de Modelos de Documentos (Mothership + Local) **totalmente implementado** no painel MotherShip.

> [!IMPORTANT]
> **Manutenção:** Este documento deve ser atualizado sempre que uma funcionalidade do LawFirm tiver impacto na infraestrutura, provisionamento ou lógica do MotherShip. Ele serve como ponte de comunicação entre os dois repositórios.

## 1. Propósito

Documentar **o que mudou no LawFirm** desde a última sincronização (v3.30) e quais ações, ajustes ou considerações o **MotherShip** deve adotar para manter a operação harmoniosa do ecossistema SaaS.

---

## 2. Resumo de Mudanças no LawFirm (v3.30 → v3.31)

| Área | Mudança | Impacto no MotherShip |
|:---|:---|:---|
| **Database** | Nova tabela `law_whatsapp_imports` (sessões de importação) | ⚠️ Médio — afeta deploy e backup |
| **Database** | Nova coluna `import_id` (FK) em `law_processo_whatsapp_messages` | ⚠️ Médio — migration obrigatória no deploy |
| **Model** | `WhatsappImport` (novo modelo Eloquent) | 🟢 Baixo — contido no tenant |
| **Endpoint** | `DELETE whatsapp/imports/{processo_id}/{import_id}` | 🟢 Baixo — operação interna do tenant |
| **Endpoint** | `GET whatsapp/imports/{processo_id}` (listagem de importações) | 🟢 Baixo — não afeta Mothership diretamente |
| **UI** | Botões WhatsApp agora são always-on (sem condição `@if`) | 🟢 Nenhum |
| **UI** | Modal de histórico com tabs por importação + botão de exclusão 🗑️ | 🟢 Nenhum |
| **UI** | Modal de importação com largura reduzida (60vw) | 🟢 Nenhum |

---

## 2.1 Resumo de Mudanças no LawFirm (v3.31 → v3.32)

| Área | Mudança | Impacto no MotherShip |
|:---|:---|:---|
| **Backend** | Consolidação completa de 51 rotas da API Escavador V1 e V2 no `EscavadorService`. | ⚠️ Médio — O Mothership Panel deve ter 51 chaves `escavador_price_*` mapeadas no `app_config` caso decida tarifar todas elas. |
| **UI** | Ocultados os filtros visuais de chaves (API V1 / API V2). | 🟢 Nenhum — A camada visual agora foca no termo de negócio, o backend cuida da chave. |
| **UI** | Ocultados os cards de Infraestrutura (Monitoramento, Assíncrono, Callbacks). | 🟢 Nenhum — Estas rotas operam exclusivamente em background. |
| **Doc API** | Refatoração robusta do dashboard de documentação das rotas (UI e Alpine.js), alinhando descrições ricas, constraints e regras CNJ para IAs. | 🟢 Nenhum — O MotherShip apenas acompanha a precificação correspondente, se invocada. |

---

## 3. Ações Requeridas no MotherShip

### 3.1 Deploy / Provisionamento de Novos Tenants

> [!WARNING]
> **Obrigatório:** Ao provisionar um novo Tenant (criação de banco MySQL), o script de seed/migration deve incluir as duas novas migrations do LawFirm v3.31:
> - `2026_04_17_000000_create_law_whatsapp_imports_table`
> - `2026_04_17_000001_add_import_id_to_whatsapp_messages`

Essas tabelas residem **no banco do Tenant** (não no Mothership). O `php artisan migrate` do LawFirm cria ambas automaticamente, mas o pipeline de deploy deve garantir que migrations sejam executadas após o pull da imagem Docker.

### 3.2 Backup & Restore

A tabela `law_whatsapp_imports` contém metadados das sessões de importação (status, período, contagem). Em cenários de restore de banco do tenant, as mensagens órfãs (sem `import_id`) continuarão funcionando normalmente graças à constraint `NULLABLE` na FK.

### 3.3 Evolution API — Consumo de Volume

Com o multi-import, advogados podem disparar **múltiplas importações** por processo. Cada importação consome chamadas à Evolution API (`fetchMessagesByDateRange`). O MotherShip deve monitorar:

- **Rate Limiting:** Se a instância Evolution compartilhada atender muitos tenants importando simultaneamente, pode haver throttling.
- **`infrastructure_nodes.current_load`:** Considerar atualizar este campo com métricas de consumo de API.

### 3.4 Docker Image — Atualização de Versão

O `LawFirmServiceProvider` deve ter sua constante de versão alinhada com `3.37`. Ao emitir a nova imagem Docker (`suitezap/lawfirm`), garantir que o tag corresponda:

```bash
docker build -t suitezap/lawfirm:v3.37 -t suitezap/lawfirm:latest -t suitezap/lawfirm:stable .
docker push suitezap/lawfirm:v3.37
docker push suitezap/lawfirm:latest
docker push suitezap/lawfirm:stable
```

---

## 4. Tabelas do Tenant Afetadas (Referência Rápida)

### `law_whatsapp_imports` (NOVA)

| Coluna | Tipo | Descrição |
|:---|:---|:---|
| `id` | BigInt PK | Auto-increment |
| `processo_id` | UnsignedBigInt FK | → `processos.id` (cascade) |
| `remote_jid` | VARCHAR | Número do contato (formato: `55XXXXXXXXXXX@s.whatsapp.net`) |
| `contact_name` | VARCHAR NULL | Nome do contato (capturado da 1ª mensagem) |
| `start_date` | DATE NULL | Filtro "A partir de" |
| `end_date` | DATE NULL | Filtro "Até" |
| `message_count` | UINT DEFAULT 0 | Total de mensagens importadas |
| `status` | ENUM | `processing` / `completed` / `failed` |
| `imported_by` | UnsignedInt FK NULL | → `users.id` (set null) |
| `created_at` / `updated_at` | Timestamps | |

### `law_processo_whatsapp_messages` (ALTERADA)

| Coluna Adicionada | Tipo | Descrição |
|:---|:---|:---|
| `import_id` | UnsignedBigInt FK NULL | → `law_whatsapp_imports.id` (cascade). Retrocompatível: mensagens antigas têm `NULL`. |

---

## 5. Endpoints Novos no LawFirm (Para Referência do n8n)

Estes endpoints operam **dentro do contexto do Tenant** (autenticação web) e não requerem API Keys do MotherShip:

| Rota | Método | Descrição |
|:---|:---|:---|
| `whatsapp/imports/{processo_id}` | GET | Lista sessões de importação (JSON) |
| `whatsapp/imports/{processo_id}/{import_id}` | DELETE | Remove importação + mensagens |
| `whatsapp/mensagens/{processo_id}?import_id=X` | GET | Filtra mensagens por sessão |

---

## 6. Compatibilidade Retroativa

A v3.31 é **100% retrocompatível** com a v3.30:
- A FK `import_id` é **nullable** — mensagens existentes sem agrupamento continuam funcionando
- O modal exibe "📋 Todas" por padrão quando não há importações agrupadas
- Nenhuma rota ou endpoint existente foi removido ou renomeado

---

## 7. Próximos Pontos de Atenção (Roadmap)

| Item | Prioridade | Observação |
|:---|:---|:---|
| Monitoramento de consumo Evolution API por tenant | Média | Métricas de importações podem ser agregadas usando `law_whatsapp_imports` |
| Limpeza automática de importações `failed` | Baixa | Considerar CRON job para limpar sessões travadas em `processing` há +24h |
| Dashboard Mothership: widget de importações WA | Baixa | Possibilidade futura de exibir métricas de uso cross-tenant |

---

*Gerado em Maio/2026 — Alinhamento LawFirm v3.37 ↔ MotherShip v1.7.*

---

## 8. Módulo TenantFinance — Cobranças do Escritório (v3.40)

### 8.1 Contexto

O LawFirm v3.40 introduz o módulo **TenantFinance**: permite que escritórios de advocacia emitam cobranças para seus clientes (honorários, custas, mensalidades) diretamente pelo CRM, usando a conta Asaas **própria do escritório**.

> [!IMPORTANT]
> Este módulo é **completamente separado** do Asaas da plataforma SuiteZap:
> - **Asaas SaaS (existente):** Plataforma cobra o Tenant → credenciais em `infrastructure_nodes`
> - **Asaas TenantFinance (novo):** Tenant cobra seus clientes → credenciais em `tenant_asaas_settings` (banco do tenant)
>
> O MotherShip **não precisa de nenhuma nova tabela** para este módulo.

### 8.2 Ações Requeridas no MotherShip

#### 8.2.1 Adicionando o Módulo como Add-on

O módulo TenantFinance é controlado pela coluna `active_modules` da tabela `subscriptions`:

```sql
-- Ativar TenantFinance para um tenant
UPDATE subscriptions
SET active_modules = JSON_ARRAY_APPEND(
    COALESCE(active_modules, JSON_ARRAY()),
    '$',
    'TENANT_FINANCE'
)
WHERE tenant_id = {TENANT_ID};
```

No **painel MotherShip** (página `?page=tenants` → modal de edição do tenant), adicionar um toggle visual:
- **Label:** "💳 Cobranças para Clientes (Asaas)"
- **Chave:** `TENANT_FINANCE`
- **Descrição:** "Permite ao escritório emitir cobranças para seus clientes via conta Asaas própria."

Quando desativado, o menu "Cobranças" some do sidebar do CRM e as rotas retornam 403.

#### 8.2.2 Deploy / Provisionamento de Novos Tenants

> [!WARNING]
> Ao provisionar um novo Tenant (criação de banco MySQL), o script de seed/migration deve incluir as três novas migrations do LawFirm v3.40:
> - `2026_05_03_180000_create_tenant_asaas_settings_table`
> - `2026_05_03_180100_create_tenant_asaas_customers_table`
> - `2026_05_03_180200_create_tenant_invoices_table`

Estas tabelas residem **no banco do Tenant** (não no MotherShip). O `php artisan migrate` do LawFirm cria automaticamente, mas o pipeline de deploy deve garantir que migrations sejam executadas após o pull da imagem Docker.

#### 8.2.3 Webhook do Asaas — Protocolo de Configuração

O escritório deve configurar o webhook **na conta Asaas própria** (não no painel MotherShip) para a URL:

```
POST https://{tenant-domain}/api/webhooks/tenant-asaas
```

O MotherShip pode orientar o advogado com uma seção de ajuda no painel, informando:
1. Acessar [painel Asaas](https://app.asaas.com) → Configurações → Notificações/Webhooks
2. Criar webhook apontando para a URL acima
3. Copiar o **Access Token** gerado pelo Asaas e inserir em **Configurações do Asaas** no CRM (`/admin/juridico/cobrancas/settings`)

### 8.3 Tabelas do Tenant Afetadas (Referência Rápida)

#### `tenant_asaas_settings` (NOVA — banco do Tenant)

| Coluna | Tipo | Descrição |
|:---|:---|:---|
| `id` | BigInt PK | Auto-increment |
| `api_key` | VARCHAR(255) | Token Asaas do escritório |
| `wallet_id` | VARCHAR(100) NULL | Wallet ID (Split) |
| `environment` | ENUM | `sandbox` ou `production` |
| `webhook_token` | VARCHAR(255) NULL | Token para validar header `asaas-access-token` |
| `is_active` | BOOLEAN | Flag de ativação |

#### `tenant_asaas_customers` (NOVA — banco do Tenant)

| Coluna | Tipo | Descrição |
|:---|:---|:---|
| `id` | BigInt PK | Auto-increment |
| `person_id` | BigInt NULL | FK suave → `persons.id` |
| `lead_id` | BigInt NULL | FK suave → `leads.id` |
| `asaas_customer_id` | VARCHAR(50) UNIQUE | ID gerado pelo Asaas |
| `name` / `cpf_cnpj` / `email` / `phone` | Strings | Dados do cliente |

#### `tenant_invoices` (NOVA — banco do Tenant)

| Coluna | Tipo | Descrição |
|:---|:---|:---|
| `id` | BigInt PK | Auto-increment |
| `processo_id` / `financial_id` | BigInt NULL | FK suaves para vincular ao processo |
| `asaas_payment_id` | VARCHAR(50) | ID do pagamento no Asaas (`pay_xxxx`) |
| `type` | ENUM | `single`, `installment`, `subscription` |
| `value` / `due_date` | Decimal / Date | Valor e vencimento |
| `status` | VARCHAR(30) | `PENDING`, `RECEIVED`, `OVERDUE`, `CANCELED`, etc |
| `invoice_url` / `pix_qrcode` | TEXT NULL | Link do boleto e payload PIX |

### 8.4 Compatibilidade Retroativa

O módulo TenantFinance é **100% opcional e retrocompatível**:
- Tenants sem a chave `TENANT_FINANCE` em `active_modules` continuam funcionando normalmente
- Nenhuma rota ou endpoint existente foi alterado
- As novas tabelas são criadas automaticamente pelo `migrate`, mas ficam vazias até o tenant configurar suas credenciais Asaas

### 8.5 Docker — Tag de Versão

```bash
docker build -t suitezap/lawfirm:v3.40 -t suitezap/lawfirm:latest .
docker push suitezap/lawfirm:v3.40
docker push suitezap/lawfirm:latest
```

---

## 9. Entidade Caso — Agrupador de Processos (v3.44)

Dado a introdução da Tabela `law_casos` e seus sub-registros de interligações (Hierarquia Cliente -> Caso -> Processo), foi validada a robustez sem impactos nos recursos de faturamento ou orquestramento do MotherShip.

> [!WARNING]
> Ao provisionar um novo Tenant (criação de banco MySQL), o script de seed/migration do deploy container Docker deve puxar as novas migrations do LawFirm v3.44:
> - `2026_05_08_200000_create_law_casos_table.php` 
> - `2026_05_08_200100_add_caso_id_to_processos_table.php`

Qualquer processo de Reset / Backup Tenant no MotherShip cobrirá este recurso nativamente devido ao schema regular MySQL.

### 9.1 Captura de Tags em Leads convertidos (LeadTriagem)
O `LegalOrchestrator` agora prioriza a extração das "Tags" anotadas pelo agente no Lead para forçar o preenchimento canônico de Área (ex: Trabalhista, Penal) e Prioridade (ex: Alta, Crítica) no Caso, usando a IA da tabela `lead_triagem` apenas como fallback. Do lado do MotherShip, isso não afeta os limites de cobrança (`ai_tokens_balance`), já que o LeadTriagem segue rodando normalmente e as Tags funcionam apenas como "Override" estrutural local.

---

## 10. Kanban Operacional e Pipelines (v3.46)

O sistema introduz um **Kanban Interativo de Casos**, substituindo pipelines isolados por um workflow canônico de 12 estágios (de "Novo Caso" a "Encerrado").

### 10.1 Ações Requeridas no MotherShip (Deploy)

> [!WARNING]
> A interface do Kanban quebrará e exibirá a mensagem de "Nenhum pipeline configurado" caso o banco do Tenant não seja semeado.

Ao provisionar novos Tenants (ou durante o *deploy rollout* desta versão para Tenants existentes), o pipeline de migração do MotherShip **DEVE** executar obrigatoriamente o seguinte seeder no banco do Tenant:

```bash
php artisan db:seed --class="SuiteZap\\LawFirm\\Database\\Seeders\\LegalPipelineSeeder"
```

Este comando insere os blocos mestre de pipeline (`law_pipelines`, `law_pipeline_stages`) que parametrizam o Drag & Drop e as paletas de cores do Kanban (Cinza, Azul, Amarelo, Vermelho, Verde). Como as chaves (`code`) destes estágios são consumidas pelo `LegalOrchestrator`, a ausência deste seed resulta em 500 nulo ao tentar salvar ou editar a Fase de novos processos.

*Gerado em Maio/2026 — Alinhamento Kanban e Pipelines v3.46.*

---

## 11. Validações e Ajustes do MotherShip Concluídos (Maio/2026)

✅ **Ajustes Verificados:**
*   **Módulo TenantFinance (v3.40):** O MotherShip implementou com sucesso o toggle visual para `TENANT_FINANCE` na sua interface de tenants (`pages/tenants.php`), salvando a flag no payload JSON de módulos ativos na subscription.
*   **Kanban Operacional (v3.46):** Homologada a orientação de `db:seed` obrigatório para o `LegalPipelineSeeder` pipeline de rotinas Cloud.
*   **Integração Asaas e SuiteCoins:** O painel inseriu um arcabouço tarifário detalhado para gateway Asaas (Pix, Boletos e Cartões) a nível de `app_config`.

> [!IMPORTANT]
> **Orientações Atualizadas para o LawFirm:**
> Com a adoção das **SuiteCoins** (Ƶ) como moeda visual e o novo painel de Gateway Fees, os desenvolvedores do LawFirm DEVEM ler e seguir as diretrizes adicionadas ao documento de orientação interno do MotherShip.
> 
> 🔗 **Consulte e Siga as Diretrizes em:**
> `C:\laragon\www\mothership\ARCHITECTURE_LawFirm_orient.md`

---

## 12. Transição para Sistema SuiteCoins (Mai/2026 - LawFirm v3.47)

### O que mudou no LawFirm

O sistema de créditos do CRM LawFirm foi migrado para a moeda virtual **SuiteCoins (Ƶ)** na sua camada de negócio e interface, operando com uma taxa global de mercado (ex: `1 BRL = 10 Ƶ`).

> [!WARNING]
> **Atenção Máxima para o MotherShip / Robôs n8n:**
> Para **não quebrar** os fluxos financeiros do ecossistema e dos robôs que cobram na paridade `1:1` (BRL), o banco de dados do MotherShip **não sofreu inflação de saldo**. Ele continua operando em BRL absoluto.
> O LawFirm `SuiteCoinService.php` faz a mutação *em runtime* estritamente na interface de usuário.

### Ações Requeridas no MotherShip

| Área | Mudança / Ação | Impacto |
|:---|:---|:---|
| **Database** `mothership` | Coluna `ai_tokens_balance` da tabela `subscriptions` **renomeada** para `suitecoin_balance`. | 🛑 **Alto** — Qualquer rotina (incluindo painel MotherShip e fluxos n8n) que apontava para `ai_tokens_balance` irá quebrar. Elas DEVEM ser alteradas para ler/gravar em `suitecoin_balance`. O valor gravado ainda é 1:1 BRL. |
| **Database** `mothership` | Seeder no painel das chaves na `app_config`: `suitecoin_rate` (default `10`), `suitecoin_markup` (default `1.25`), `suitecoin_min_recharge_brl` (default `25.00`). | 🟢 Baixo — Parametrização dinâmica das moedas virtuais do LawFirm. |
| **Database** `tenant` | Tabela `saas_transactions`: nova coluna `currency` (`BRL` ou `SUITECOIN`). Se for repassado do MotherShip, enviar como `BRL`. Se gerado pelo CRM, será guardado nativo como `BRL` (mas consumido visualmente como `SUITECOIN`). | 🟡 Médio |

---

## 13. Precificação de Assistentes Jurídicos em SuiteCoins (v3.48 — Mai/2026)

> [!IMPORTANT]
> **Nova Migration no MotherShip DB:** A tabela `lawfirm_assistant_templates` (que reside no banco `mothership`) recebeu três novas colunas para suportar o sistema de preços por assistente.

### 13.1 Novas Colunas em `lawfirm_assistant_templates`

| Coluna | Tipo | Padrão | Descrição |
|:---|:---|:---|:---|
| `base_cost_brl` | `DECIMAL(8,4)` | `0.0000` | Custo técnico base (tokens LLM + infra) em BRL |
| `markup_factor` | `DECIMAL(5,4)` | `1.2500` | Multiplicador de represase (+25% = custo Asaas diluído) |
| `price_virtual` | `DECIMAL(8,4)` | `0.0000` | **Preço final BRL** cobrado do tenant. Display = `× 10` = Ƶ |

> [!NOTE]
> **Fórmula:** `price_virtual = ceil(base_cost_brl × markup_factor × 10000) / 10000`
> **Display Ƶ:** `price_virtual × suitecoin_rate (10)` — calculado apenas na camada de UI.
> **O n8n NÃO PRECISA SER ALTERADO** — continua debitando BRL de `suitecoin_balance` como antes.

### 13.2 Preços Iniciais Aplicados (Mai/2026)

| Módulo | `base_cost_brl` | `price_virtual` (BRL) | Display Ƶ |
|:---|:---|:---|:---|
| Lead / Pré-venda (sem módulo) | R$ 0,05 | R$ 0,0625 | **Ƶ 0,63** |
| Verif (assistentes processuais) | R$ 0,10 | R$ 0,1250 | **Ƶ 1,25** |
| IA-Trabalhista | R$ 0,15 | R$ 0,1875 | **Ƶ 1,88** |
| IA-Previdencia | R$ 0,15 | R$ 0,1875 | **Ƶ 1,88** |
| IA-Familia_Sucessoes | R$ 0,15 | R$ 0,1875 | **Ƶ 1,88** |
| IA-Civil | R$ 0,15 | R$ 0,1875 | **Ƶ 1,88** |
| IAWhatsApp | R$ 0,10 | R$ 0,1250 | **Ƶ 1,25** |

### 13.3 Ações no Painel MotherShip

| Ação | Detalhe |
|:---|:---|
| ✅ Migration já aplicada | `2026_05_11_140000_add_pricing_to_lawfirm_assistant_templates` — roda via `php artisan migrate` |
| ✅ Campos expostos no editor de templates | `pages/templates.php` exibe e permite editar `base_cost_brl` e `markup_factor` por template. Badge Ƶ adicionado aos cards. O campo `price_virtual` é calculado em tempo real e exibido como somente-leitura no modal. |
| ✅ `price_virtual` incluído no JSON de `api/templates.php` | O endpoint `GET` calcula e retorna `price_virtual` em runtime a partir de `base_cost_brl × markup_factor`. O `UPDATE` recalcula e persiste automaticamente `price_virtual` ao alterar qualquer campo de precificação. |
| 🟢 Reajuste de preços | Para reajustar todos os assistentes após alterar `base_cost_brl` no painel, execute no CRM: `php artisan lawfirm:sync-assistant-pricing` |

### 13.4 Fluxo de Débito (Atualizado)

```
Usuário clica em "Usar Assistente"
  → AssistantController::execute()
      ├─ Verifica saldo: subscription.suitecoin_balance >= template.price_virtual
      ├─ Debita: subscription.suitecoin_balance -= template.price_virtual  (BRL 1:1)
      â—â”€ Registra: saas_transactions (type=debit, currency=SUITECOIN, amount=price_virtual)
         â—â”€ n8n recebe payload → processa → responde → AssistantHistory.status = 'completed'
```

### 13.5 Onde o Badge Ƶ Aparece na UI

| Tela | Componente | Descrição |
|:---|:---|:---|
| `/admin/juridico/assistentes` | `assistants/index.blade.php` | Badge por card (Ƶ 1,25 ou Gratuito) |
| Processos (aba Checklist) | `components/assistant-modal.blade.php` | Badge no rodapé do modal de execução |
| Leads (aba Checklist) | `components/assistant-modal.blade.php` | Mesmo componente, mesma exibição |
| **Assistente Escavador** | `escavador-tab.blade.php` | Migração das etiquetas nativas (BRL) do modal do Escavador para consumir o payload em JSON `suitecoin_balance`, com tratamento e conversão UI de (`BRL * 10 Ƶ`). |

---

## 14. Gateways e Taxas Dinâmicas Asaas (Configurações Global, Mai/2026)

> [!IMPORTANT]
> A atualização da camada de pagamentos transferiu as margens de tarifa (Gateway Fees) e juros de parcelamento diretamente para a tabela `app_config` do MotherShip.

### 14.1 Novas Entradas Injetadas em `app_config` (Painel)
*   **`asaas_fee_pix`**: Custo/Taxa base para pagamentos via PIX.
*   **`asaas_fee_boleto`**: Custo/Taxa base para boletos bancários gerados.
*   **`asaas_credit_card_fees_*`**: Array de taxas ou strings definindo as margens de percentual para operações à vista ou parceladas no Cartão de Crédito.
*   **Impacto no LawFirm CRM**: O `SubscriptionCheckoutController` ou as faturas do `TenantFinance` vão consumir ativamente estas taxas do painel via chamada API/Db em Background, realizando a precificação elástica antes do repasse final ou acréscimo pro-rata ao tenant, assegurando integridade na geração de tokens/SuiteCoins e nos Extratos (Ledger).

---

*Atualizado em 04/06/2026 — Sistema Misto de Modelos de Documentos v3.53 — Todos os itens implementados.*

---

## 15. Atualizações Recentes do LawFirm (v3.53.0)

### 15.1 Sistema Misto de Modelos de Documentos (Mothership + Local)
*   **Contexto:** Transição de um sistema puramente local para um sistema misto (híbrido) no LawFirm v3.53.0. O painel central da Mothership agora serve templates globais padronizados, enquanto cada tenant ainda gerencia seus próprios templates locais exclusivos.
*   **Impacto no MotherShip:**
    *   **Alto (Necessita Atualização):** O MotherShip precisa de uma tabela central `lawfirm_document_templates` na base de dados `mothership_db`, uma rota ativa `document_templates`, menu sidebar e uma API REST em [document_templates.php](file:///c:/laragon/www/mothership/api/document_templates.php) para servir os templates via JSON.
    *   **Onde os dados são salvos:**
        *   **Modelos Globais/Padrão:** Salvos na base central da **Mothership** (tabela `lawfirm_document_templates`).
        *   **Modelos Locais/Tenants:** Salvos na base local de **cada Tenant** (tabela `law_document_templates` do VPS do respectivo tenant).
    *   **Esquema de Banco de Dados da Mothership (Tabela `lawfirm_document_templates`):**
        *   `id` (BigInt, PK)
        *   `titulo` (VARCHAR)
        *   `tipo` (VARCHAR) - e.g., `contrato`, `declaracao`, `procuracao`
        *   `area_direito` (VARCHAR)
        *   `conteudo` (TEXT/HTML)
        *   `descricao` (TEXT, NULL)
        *   `ativo` (TINYINT, DEFAULT 1)
        *   `created_at`/`updated_at` (Timestamps)
    *   **Esquema de Resolução (Unique IDs):**
        *   Para evitar colisão de chaves primárias, os IDs são prefixados pelo LawFirm:
            *   `global-{id}` (ex: `global-1`) -> Busca do banco central `mothership_db`.
            *   `local-{id}` (ex: `local-1`) -> Busca do banco do próprio tenant.
        *   O model `MothershipDocumentTemplate` faz uso da conexão `'mothership'` configurada no tenant.
        *   Ações de escrita (Edit/Delete) para templates com prefixo `global-` são travadas na VPS do tenant retornando `HTTP 403 Forbidden`.

### 15.2 Injeção Idempotente de Checklists nos Deploys
*   **Contexto:** Migração autônoma que sementa kits de documentos de checklists direto nas tabelas do tenant ao rodar as migrações no boot da imagem Docker.
*   **Impacto no MotherShip:**
    *   **Nenhum.** O processo é 100% autônomo e executado no banco do tenant via `php artisan migrate`, isentando a necessidade de comandos adicionais de seed no painel ou pipelines de provisionamento.

### 15.3 Suspensão do Whaticket (Messenger Inbox)
*   **Contexto:** O submódulo Messenger Inbox (Whaticket) foi colocado em suspensão permanente a partir de **29/05/2026** e não fará parte das versões posteriores.
*   **Impacto no MotherShip / Deploy:**
    *   **Nenhum imediato.** Para evitar que o pacote seja ignorado ou cause quebras no Docker build e no Composer install, mantivemos o autoloading de `SuiteZap\Whaticket` no `composer.json` e a migration correspondente (`packages/SuiteZap/Whaticket/src/Database/Migrations`) ativa no `docker/entrypoint.sh`. 
    *   As demais funcionalidades do WhatsApp (como alertas de prazos, avisos e cobranças/faturas) continuam ativas, funcionais e devidamente configuradas.
    *   Não há necessidade de desprovisionar tabelas já existentes nos tenants ativos, pois elas permanecerão inativas e sem rotas públicas.

---

## 16. ✅ Implementação do Domínio Atendimento — Status Concluído (v3.53.0 — Jun/2026)

> [!IMPORTANT]
> Esta seção registra a **conclusão das orientações** das seções 14 e 15 do orient do Mothership (`ARCHITECTURE_LawFirm_orient.md`). Os itens marcados como "Dev LawFirm" foram implementados em **22/06/2026** e verificados com `php artisan route:list`.

### 16.1 O que foi implementado no LawFirm CRM

| Item | Status | Arquivo no CRM |
|---|---|---|
| `MotherShipService::getChatwootConfig()` | ✅ Implementado | `src/SaaS/Services/MotherShipService.php` |
| `ChatwootService` (bounded context `Atendimento`) | ✅ Implementado | `src/Atendimento/Services/ChatwootService.php` |
| `ChatwootWebhookController` com HMAC-SHA1 + cross-tenant guard | ✅ Implementado | `src/Atendimento/Http/Controllers/ChatwootWebhookController.php` |
| Rota `POST /api/webhooks/chatwoot` (CSRF-exempt, grupo `api`) | ✅ Verificada | `src/Http/routes.php` |
| Exceção CSRF em `VerifyCsrfToken::$except` | ✅ Implementada | `app/Http/Middleware/VerifyCsrfToken.php` |
| Chave `API_V1_AUTOS_PROCESSO` em `getEscavadorPrices()` | ✅ Implementada | `src/SaaS/Services/MotherShipService.php` |
| Guards HTTP 403 em `DocumentTemplateController` (edit/update/destroy) | ✅ Implementados | `src/Legal/Http/Controllers/Admin/DocumentTemplateController.php` |

### 16.2 Divergência do Scaffold Original — Namespace

> [!NOTE]
> O scaffold do orient do Mothership (seção 14.3) usava `App\Atendimento\Services\ChatwootService`.
> A implementação real segue a **arquitetura DDD do pacote LawFirm** (Zero Root Controllers — v3.36+):

```
// ❌ Scaffold do orient (orientação conceitual)
namespace App\Atendimento\Services;

// ✅ Implementação real (DDD correto)
namespace SuiteZap\LawFirm\Atendimento\Services;
namespace SuiteZap\LawFirm\Atendimento\Http\Controllers;
```

### 16.3 Distinção de Tokens — Correção em Relação ao Scaffold

O scaffold do orient (seção 14.3, método `sendMessage()`) usava **`managementHeaders()`** (User Access Token) para envio de mensagens. A implementação correta usa **`botHeaders()`** (Bot/Agent token) para `/messages` e reserva o User Access Token para endpoints de gestão:

| Operação | Token correto | Header |
|---|---|---|
| `POST /messages` (enviar mensagem) | `api_key` (Bot token) | `botHeaders()` |
| `POST /labels` (atribuir label) | `chatwoot_webhook_token` (User Access Token) | `managementHeaders()` |
| `GET /contacts/search` (buscar contato) | `chatwoot_webhook_token` (User Access Token) | `managementHeaders()` |
| Validação do `X-Chatwoot-Signature` | `chatwoot_webhook_token` como secret HMAC | — |

> [!CAUTION]
> Usar `api_key` (bot token) em `/labels` ou `/contacts` retorna **HTTP 401**. Esta distinção é crítica e foi documentada nos comentários PHPDoc de `ChatwootService`.

### 16.4 Verificação via `artisan route:list`

```
POST  api/webhooks/chatwoot  webhooks.chatwoot
      → SuiteZap\LawFirm\Atendimento\Http\Controllers\ChatwootWebhookController@handle
```

### 16.5 Checklist de Produção — Itens Pendentes (Admin Mothership)

Os seguintes itens ainda dependem de ação manual no painel Mothership antes de ativar o módulo em produção:

| # | Ação | Responsável | Status |
|---|---|---|---|
| 1 | Criar **Nó Chatwoot** (`?page=nodes`) com `base_url`, `api_key` (bot token) e `meta_data.account_id` | Admin Mothership | ⏳ Pendente |
| 2 | Editar **Tenant** → vincular `chatwoot_node_id`, `chatwoot_inbox_id`, `chatwoot_webhook_token` (User Access Token) | Admin Mothership | ⏳ Pendente |
| 3 | Acionar botão **"Tags"** do tenant para sincronizar as 17 labels padrão LawFirm | Admin Mothership | ⏳ Pendente |
| 4 | No **Chatwoot** → Configurações → Integrações → Webhooks → apontar para `https://{tenant-domain}/api/webhooks/chatwoot` com o `chatwoot_webhook_token` como secret | Admin Mothership | ⏳ Pendente |
| 5 | Garantir a migração SQL `escavador_price_v1_autos_processo = 1.50` em produção | Admin Mothership | ⏳ Pendente (SQL disponível na seção 9.3 do orient do Mothership) |

### 16.6 Nota sobre `VERSION`

```php
// packages/SuiteZap/LawFirm/src/Providers/LawFirmServiceProvider.php
public const VERSION = '3.54.1';
```

*Atualizado em 18/08/2026 — Versão confirmada no código: `LawFirmServiceProvider::VERSION = '3.54.1'`. O checklist de produção atualizado está em §20.5 (que substitui o checklist de implantação Chatwoot de §16.5).*

---

## 17. 🛠️ Migrações e Ajustes Requeridos no Repositório MotherShip (v3.53.0)

> [!IMPORTANT]
> Para que a integração de Atendimento (Chatwoot) do LawFirm CRM funcione corretamente, as seguintes migrações de banco de dados e rotas/telas administrativas devem ser implementadas no painel **MotherShip**.

### 17.1 Migração de Banco de Dados (`mothership_db` central)

Execute as seguintes queries no banco central do ecossistema:

```sql
-- 1. Adicionar colunas de relacionamento com Chatwoot na tabela tenants
ALTER TABLE tenants 
ADD COLUMN chatwoot_node_id INT UNSIGNED NULL AFTER storage_node_id,
ADD COLUMN chatwoot_inbox_id INT UNSIGNED NULL AFTER chatwoot_node_id,
ADD COLUMN chatwoot_channel_inbox_id INT UNSIGNED NULL AFTER chatwoot_inbox_id,
ADD COLUMN chatwoot_webhook_token VARCHAR(255) NULL AFTER chatwoot_channel_inbox_id;

-- 2. Adicionar Chave Estrangeira de Integridade Referencial
ALTER TABLE tenants
ADD CONSTRAINT fk_tenants_chatwoot_node 
FOREIGN KEY (chatwoot_node_id) REFERENCES infrastructure_nodes(id) 
ON DELETE SET NULL;

-- 3. Inserir a nova chave de preço do Escavador (v1_autos_processo)
INSERT INTO app_config (`key`, `value`, `type`, `group`, `description`, `updated_at`)
VALUES ('escavador_price_v1_autos_processo', '1.50', 'float', 'pricing',
        'V1: Autos de um Processo com download completo (assíncrono) — POST api/v1/processos/{id}/autos', NOW())
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW();
```

### 17.2 Validação de Nós no Painel Administrativo (`pages/nodes.php` ou `api/nodes.php`)

No arquivo do painel do MotherShip que gerencia nós de infraestrutura (`infrastructure_nodes`), a validação para o novo tipo de nó `chatwoot` deve exigir obrigatoriamente:
*   `base_url` (URL da instância Chatwoot)
*   `api_key` (Bot / Agent Token)
*   `meta_data.account_id` (ID numérico global da conta no Chatwoot)

### 17.3 Implementação do Botão "Tags" no Cadastro do Tenant (Mothership Panel)

No painel de edição do Tenant, deve ser adicionado um botão para **Sincronizar Labels Padrão**. O fluxo do script backend do MotherShip ao acionar este botão deve ser:

1. Obter a configuração do Chatwoot para o Tenant (Node + Inbox + Webhook Token).
2. Fazer requisições HTTP `POST` para o endpoint de labels do Chatwoot:
   `POST {base_url}/api/v1/accounts/{account_id}/labels`
   *   **Header Autenticação:** `api_access_token: {chatwoot_webhook_token}` (Token do Tenant)
   *   **Payload JSON:**
       ```json
       {
         "name": "Nome da Label",
         "description": "Descrição opcional",
         "color": "#HEX_COR"
       }
       ```
3. Lista das 17 labels padrão do LawFirm a serem cadastradas:
   *   **Áreas Jurídicas:** `Administrativo`, `Ambiental`, `Bancário`, `Consumidor`, `Cível`, `Digital-LGPD`, `Empresarial`, `Família`, `Imobiliário`, `Penal`, `Previdenciário`, `Trabalhista`, `Tributário`.
   *   **Urgência:** `Alta`, `Baixa`, `Crítica`, `Média`.
4. Tratar o retorno HTTP 422 ("taken") como sucesso, pois indica que a label já existia.

### 17.4 Configuração do Webhook no Chatwoot

Após configurar o Tenant no MotherShip, o Administrador deve entrar no painel do Chatwoot e configurar o Webhook para o Tenant:
1. Ir em **Configurações → Integrações → Webhooks**.
2. Criar um Webhook apontando para `https://{tenant-domain}/api/webhooks/chatwoot`.
3. Certificar-se de marcar os eventos `conversation_created` e `message_created`.
4. Garantir que o secret gerado pelo Chatwoot no Webhook seja ignorado, e o `chatwoot_webhook_token` gerado pela conta seja usado para validação no LawFirm.

### 17.5 Fim do Fallback de Ambiente (Evolution API)

Para assegurar conformidade total com a arquitetura Multi-Tenant SaaS, a funcionalidade de *fallback* local no CRM via arquivo `.env` e `config/lawfirm.php` para a Evolution API **foi totalmente desativada** (v3.53.1).
*   O MotherShip Service (tabela `infrastructure_nodes` e settings do tenant) **é agora a única e exclusiva fonte da verdade** para a integração com a Evolution API.
*   Tenants sem configuração no MotherShip receberão `HTTP 503` imediatamente caso tentem operar rotinas de WhatsApp.

---

## 18. 💬 Separação de Account ID e Inbox ID no Chatwoot (v3.54.1 — Jul/2026)

> [!IMPORTANT]
> A partir da versão **v3.54.1**, a coluna `chatwoot_inbox_id` armazena estritamente o **Account ID** (ID da conta global do Chatwoot), enquanto a nova coluna `chatwoot_channel_inbox_id` armazena o **Inbox ID** real (Caixa de Entrada).

### 18.1 Ações Requeridas no Painel e API do MotherShip

1.  **Exposição na Interface (`pages/dashboard.php` e `pages/tenants.php`):**
    *   O painel do Mothership deve disponibilizar dois campos distintos no cadastro e edição de Tenants:
        *   **Account ID (Chatwoot):** Salvo em `chatwoot_inbox_id` (mantendo o nome da coluna de banco herdada).
        *   **Inbox ID (Caixa de Entrada):** Salvo na nova coluna `chatwoot_channel_inbox_id`.
    *   Ambos os campos devem ser exibidos de forma clara para evitar confusão entre o ID da conta global e o ID do canal/caixa de entrada do tenant.
2.  **API (`api/tenants.php`):**
    *   Certificar-se de que o campo `chatwoot_channel_inbox_id` esteja nos arrays de campos permitidos (`$fields` no CREATE e `$allowedTenant` no UPDATE) para permitir que a API do Mothership salve a informação enviada pela interface.

---

## 19. Dupla Conexão WhatsApp — Evolution API (Jul/2026)

> [!IMPORTANT]
> O LawFirm agora suporta duas conexões simultâneas de WhatsApp: uma principal (notificações/sistemas) e uma secundária dedicada para Atendimento.

### 19.1 Como o MotherShip deve provisionar a segunda conexão

Não foram criadas novas colunas no banco de dados do MotherShip (`mothership_db`) para armazenar o nome da instância da segunda conexão. Em vez disso, o sistema adota um **sufixo padronizado (`_atendimento`)**.

1.  **Sufixo Mágico:** Se o nome da instância primária do tenant for `meutenant123`, a instância de atendimento será **obrigatoriamente** `meutenant123_atendimento`.
2.  **API Key (Token):** A chave de API utilizada para ambas as instâncias no servidor Evolution é a mesma (a chave global da `infrastructure_nodes` atrelada ao tenant).
3.  **Criação da Instância:** Se o painel do MotherShip criar a instância Evolution para o cliente via API do Evolution, ele pode (opcionalmente) criar as duas instâncias ao mesmo tempo, ou permitir que o cliente leia o QR Code das duas instâncias pelo painel do CRM (`/admin/juridico/whatsapp`).
4.  **Integração no CRM:** O CRM consome a segunda conexão internamente buscando os dados da primária e concatenando `_atendimento` no parâmetro `evolution_instance_name` (via `$type` em `MotherShipService::getEvolutionConfig`).

---

## 20. 🆕 Atualizações do MotherShip (Jul/2026) — Auditoria v1.21

> [!IMPORTANT]
> Esta seção registra as **atualizações implementadas no MotherShip v1.21** (Jul/2026) e seus impactos no LawFirm CRM. Auditoria realizada em 08/07/2026.

### 20.1 Nova Coluna `chatwoot_assistant_inbox_id` (5º Campo Chatwoot)

A tabela `tenants` do mothership_db ganhou um **5º campo Chatwoot** para suportar o modelo Dual Inbox:

| Campo | Tipo | Descrição |
|---|---|---|
| `chatwoot_node_id` | INT FK | Nó de infraestrutura Chatwoot |
| `chatwoot_inbox_id` | INT | **Account ID** da conta global Chatwoot |
| `chatwoot_channel_inbox_id` | INT | Inbox da instância de **Atendimento Humano** 📥 |
| `chatwoot_assistant_inbox_id` | INT | **NOVO Jul/2026** — Inbox do **Assistente de IA** 🤖 |
| `chatwoot_webhook_token` | VARCHAR | User Access Token (operações de gestão) |

**Impacto no CRM (`MotherShipService::getChatwootConfig()`):**

O retorno do método deve ser expandido para incluir o novo campo:
```php
// Retorno atualizado de getChatwootConfig()
return [
    'url'                 => $node->base_url,
    'api_key'             => $node->api_key,           // Bot Token — para /messages
    'account_id'          => $tenantConfig->chatwoot_inbox_id,
    'inbox_id'            => $tenantConfig->chatwoot_channel_inbox_id,
    'assistant_inbox_id'  => $tenantConfig->chatwoot_assistant_inbox_id ?? null,  // NOVO
    'access_token'        => $tenantConfig->chatwoot_webhook_token,               // User Token
];
```

**Uso no `ChatwootService`:**
- `sendMessage()` → usa `inbox_id` (`chatwoot_channel_inbox_id`) — Atendimento humano
- `sendAssistantMessage()` → usa `assistant_inbox_id` (`chatwoot_assistant_inbox_id`) — Assistente IA
- Se `assistant_inbox_id === null`: fallback para `inbox_id` com `Log::warning()`

**Migration aplicada:**
```bash
php migrations/add_chatwoot_assistant_inbox_id.php
```
> ⚠️ Verificar se esta migration foi executada em produção antes de ativar `ChatwootService::sendAssistantMessage()`.

---

### 20.2 `evolution_assistente_name` — Nome Explícito da Instância de Atendimento

A seção 19 do orient descrevia o **sufixo mágico** `_atendimento` como única estratégia. Isso foi **superado**: o MotherShip v1.21 permite configurar um nome explícito para a instância de atendimento.

| Campo | Tipo | Descrição |
|---|---|---|
| `evolution_instance_name` | VARCHAR | Nome da instância principal (notificações) |
| `evolution_assistente_name` | VARCHAR NULL | **NOVO** — Nome explícito da instância de atendimento |

**Fallback automático:** Se `evolution_assistente_name` for `NULL`, o CRM deriva `{instance_name}_atendimento` automaticamente (retrocompatível).

**Impacto no `MotherShipService::getEvolutionConfig($type)`:**
```php
// $type = 'primary' → usa evolution_instance_name
// $type = 'atendimento' → usa evolution_assistente_name ?? "{instance_name}_atendimento"
```

**Migration aplicada:**
```bash
php migrations/add_evolution_assistente_name.php
```

---

### 20.3 Correções no MotherShip (Bugs Corrigidos em Jul/2026)

#### C-1: Cascade DELETE agora limpa `lawfirm_document_templates`
`api/tenants.php` — ação `delete` — passou a incluir:
```php
db_execute("DELETE FROM lawfirm_document_templates WHERE tenant_id = ?", [$id]);
```
Antes: templates de documentos localizados do tenant ficavam órfãos no mothership_db após exclusão.

#### C-2: Validação de Dependência de Módulos em `api/subscriptions.php`
- Ativar `WhatsApp_Triagem` sem `WHATSAPP` → retorna **HTTP 422**
- Ativar `CHATWOOT` sem `chatwoot_node_id` configurado no tenant → retorna **HTTP 422**

**Impacto no CRM:** O LawFirm não precisa de nenhuma alteração. Estas validações são no painel admin do MotherShip. O CRM continua validando módulos via `CheckWhatsappModule` e `CheckChatwootModule`.

---

### 20.4 Sync de Tags Chatwoot — 64 Labels Padrão (Atualização §17.3)

> [!NOTE]
> A seção 17.3 deste documento documentava apenas 17 labels. O MotherShip v1.21 expande o sync para **64 labels** e implementa **upsert real** (PATCH em existentes, POST em novas).

O conjunto completo de labels sincronizadas via botão "🏷️ Tags" no painel do tenant:

| Categoria | Labels |
|---|---|
| Áreas Jurídicas (13) | Administrativo, Ambiental, Bancário, Consumidor, Cível, Digital-LGPD, Empresarial, Família, Imobiliário, Penal, Previdenciário, Trabalhista, Tributário |
| Urgência (4) | Alta, Baixa, Crítica, Média |
| Leads (6) | LD_NOVO, LD_ACOMP, LD_QUAL, LD_NEG, LD_GANHO, LD_PERD |
| Casos Jurídicos (12) | CAS_NOVO, CAS_ANAL, CAS_AGCLI, CAS_PROD, CAS_PROT, CAS_AGJUD, CAS_PRAZO, CAS_AUD, CAS_SENT, CAS_RECUR, CAS_EXEC, CAS_ENCER |
| Situação Comercial (5) | COM_ATIVO, COM_RECORR, COM_PREM, COM_INAT, COM_ARQ |
| Contratual (4) | CTR_PROP, CTR_PEND, CTR_ASSIN, CTR_RENOV |
| Financeiro (4) | FIN_ADIM, FIN_INAD, FIN_ACORD, FIN_PROB |
| Perfil (5) | CLI_CONV, CLI_INDIC, CLI_PARC, CLI_PF, CLI_PJ |
| Origem (7) | ORG_EVENT, ORG_GOOG, ORG_INDIC, ORG_META, ORG_OUTRO, ORG_SITE, ORG_WHATS |
| Pendências (4) | PEN_ASSIN, PEN_DOC, PEN_INFO, PEN_PGTO |

> [!CAUTION]
> O sync usa `strtolower()` no lookup. Labels com formato MAIÚSCULAS (LD_NOVO, CAS_*, etc.) dependem do Chatwoot retornar o `title` em lowercase no GET. Verificar comportamento da versão instalada antes de usar `syncContactLabels()` com labels prefixadas.

---

### 20.5 Checklist de Produção Atualizado (Substitui §16.5)

| # | Ação | Responsável | Status |
|---|---|---|---|
| 1 | Criar **Nó Chatwoot** (`?page=nodes`) com `base_url`, `api_key` (bot token) e `meta_data.account_id` | Admin Mothership | ⏳ Pendente |
| 2 | Editar **Tenant** → vincular `chatwoot_node_id`, `chatwoot_inbox_id` (Account ID), `chatwoot_channel_inbox_id` (Inbox Instância), `chatwoot_assistant_inbox_id` (Inbox Assistente IA) e `chatwoot_webhook_token` | Admin Mothership | ⏳ Pendente |
| 3 | Configurar `evolution_assistente_name` no tenant (ou aceitar fallback `{instance_name}_atendimento`) | Admin Mothership | ⏳ Pendente |
| 4 | Acionar botão **"🏷️ Tags"** no tenant para sincronizar as 64 labels padrão LawFirm | Admin Mothership | ⏳ Pendente |
| 5 | No **Chatwoot** → Configurações → Integrações → Webhooks → apontar para `https://{tenant-domain}/api/webhooks/chatwoot` com o `chatwoot_webhook_token` como secret | Admin Mothership | ⏳ Pendente |
| 6 | Executar `php migrations/add_chatwoot_assistant_inbox_id.php` em produção *(Parte LawFirm implementada — `chatwoot_assistant_inbox_id` presente em `Tenant.php` fillable e consumido por `MotherShipService.php`. Confirmar execução da migration no MotherShip antes de marcar concluído.)* | DevOps | ⏳ Pendente |
| 7 | Executar `php migrations/add_evolution_assistente_name.php` em produção *(Parte LawFirm implementada — `evolution_assistente_name` presente em `Tenant.php` fillable e consumido por `MotherShipService.php`. Confirmar execução da migration no MotherShip antes de marcar concluído.)* | DevOps | ⏳ Pendente |
| 8 | Garantir `escavador_price_v1_autos_processo = 1.50` no `app_config` | Admin Mothership | ✅ Aplicado |

---

*Atualizado em 08/07/2026 — Auditoria MotherShip v1.21 ↔ LawFirm v3.54.1 — Dual Inbox Chatwoot + evolution_assistente_name + Correções D-2 e D-3.*