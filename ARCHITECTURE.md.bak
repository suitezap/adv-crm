# ⚖️ LawFirm CRM - Documento de Arquitetura (v3.54.1 - DDD & SaaS Multi-Tenant)

> [!NOTE]
> **Imagem Docker Oficial:** `suitezap/lawfirm` — única imagem canônica. `suitezap/adv-crm` foi descontinuada (v3.54.1). Ver ADR §4.85.
> [!IMPORTANT]
> **Manutenção do Documento:** Este arquivo **DEVE** ser atualizado (seção 4.x) e a versão incrementada no cabeçalho sempre que houver mudanças estruturais, novas funcionalidades core ou atualizações na constante de versão em `LawFirmServiceProvider.php`.

## 1. Visão Geral
Este projeto segue a arquitetura **Domain-Driven Design (DDD)** adaptada para **SaaS Multi-Tenant**.
**Namespace Base:** `SuiteZap\LawFirm`
**Localização:** `packages/SuiteZap/LawFirm/src/`

## 2. Mapa de Domínios (Bounded Contexts)
O código é estritamente separado por responsabilidade.

| Domínio | Namespace | Responsabilidade | Models Principais |
| :--- | :--- | :--- | :--- |
| **Legal** | `SuiteZap\LawFirm\Legal` | Core jurídico (Casos, Processos, Prazos, Checklists). | `Caso`, `Processo`, `Prazo`, `CaseChecklist` |
| **Financial**| `SuiteZap\LawFirm\Financial` | Honorários, Custas e Faturamento. | `Financial` |
| **GED** | `SuiteZap\LawFirm\GED` | Gestão de Arquivos, Anexos e Checklists. | `ProcessDocument`, `Anexo` |
| **SaaS** | `SuiteZap\LawFirm\SaaS` | Infraestrutura Multi-tenant. | `Tenant`, `Subscription`, `InfrastructureNode`, `SaasOrder` |
| **AI** | `SuiteZap\LawFirm\AI` | Assistentes e Automação. | `AiExecution`, `AssistantTemplate`, `AssistantHistory`, `LeadTriagem` |
| **Escavador** | `SuiteZap\LawFirm\Escavador` | Integração com a API do Escavador (v1/v2). |  `EscavadorRequest` |
| **Atendimento** | `SuiteZap\LawFirm\Atendimento` | Atendimento via Chatwoot (novo canal centralizado). |  |

## 3. Regras de Ouro (Development Standards)

### 3.1 Manipulação de Arquivos (Ironclad Rule)
⛔ **PROIBIDO:** Usar `Storage::put`, `Storage::makeDirectory`, `Storage::url` ou acesso direto ao disco local.
✅ **OBRIGATÓRIO:** Usar `SuiteZap\LawFirm\SaaS\Services\SaasFileService`.
*   Motivo: O sistema deve suportar S3/MinIO e isolamento lógico por Tenant.
*   **Privacidade e Segurança (S3/MinIO):** Todos os buckets de tenants no S3/MinIO são **estritamente privados** para proteger a confidencialidade de documentos jurídicos, procurações e contratos. Portanto, para exibir ou baixar qualquer ativo (como logos do escritório nas configurações, anexos de processos, etc.) no frontend ou painel administrativo, deve-se gerar uma URL temporária assinada usando `SaasFileService::getSignedUrl($path)` ou o método sobreposto `SaasFileService::url($path)` (que resolve automaticamente para URLs assinadas sob driver S3). Jamais use links de storage diretos ou `Storage::url()`.

### 3.2 Rotas e Controllers
*   **Rotas:** Devem ser registradas em `src/Http/Routes/admin-{dominio}.php`.
*   **Controllers:** Devem ser "Skinny" (Magros). Toda lógica de negócio (cálculos, validações complexas, salvamento em lote) deve residir em **Services**.
    *   Ex: `ProcessoController` delega para `DeadlineService`, `FinancialService` e `DocumentService`.

### 3.3 Estrutura de Pastas (Padrão)
```text
src/
├── {Domain}/           (Ex: Legal, Financial, SaaS)
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Repositories/
├── Http/
│   ├── routes.php      (Master Loader)
│   └── Routes/         (Arquivos de rota por domínio)
└── Resources/          (Views e Assets)
```

### 3.7 Imagem Docker Canônica (Regra desde v3.20)

⛔ **PROIBIDO:** Usar ou referenciar `suitezap/adv-crm` (imagem legada, descontinuada em v3.54.1).
✅ **OBRIGATÓRIO:** Usar `suitezap/lawfirm` com tag de versão semântica em todos os deploys.
*   **Docker Hub:** `https://hub.docker.com/r/suitezap/lawfirm`
*   **Tag de produção atual:** `suitezap/lawfirm:v3.54.0`
*   **Padrão Swarm/Portainer:** Sempre usar tag específica (`vX.Y.Z`), nunca `:latest` em produção.

## 4. Histórico de Refatoração (Architectural Decisions)

### 4.1 Unificação do Módulo GED (v2.1)
*   **Decisão:** Centralizar toda a lógica de manipulação de documentos no domínio **GED**.
*   **Mudanças:**
    *   **Removido:** `src/Http/Controllers/LegalDocumentController.php` (Raiz).
    *   **Atualizado:** `src/GED/Http/Controllers/ProcessDocumentController.php` absorveu a geração de PDFs (Procuração/Contratos).

### 4.2 SaaS Refactoring (v2.1)
*   **Decisão:** Mover controllers soltos de SaaS para o namespace correto.
*   **Mudanças:**
    *   **Movido:** `src/Http/Controllers/SaaSController.php` -> `src/SaaS/Http/Controllers/SaaSController.php`.
    *   **Verificado:** `LawFirmServiceProvider` injeta configurações de S3 dinamicamente via `MotherShipService::configureTenantStorage()`. Credenciais vêm do banco, não do código.

### 4.3 Limpeza de Rotas (v2.2)
*   **Decisão:** Remover arquivos de rota duplicados/obsoletos para evitar uso de controllers legados.
*   **Mudanças:**
    *   **Deletado:** `src/Http/admin-routes.php` (Obsoleto).
    *   **Deletado:** `src/Routes/admin.php` (Obsoleto).
    *   **Mantido:** `src/Http/routes.php` como **Master Route Loader**.

### 4.4 Validação de Storage (v2.3)
*   **Decisão:** Impedir uploads caso o armazenamento não esteja configurado corretamente (S3/MinIO).
*   **Mudanças:**
    *   **Adicionado:** `SaasFileService::isAvailable()` para verificar conectividade básica.
    *   **Atualizado:** `DocumentService::storeFile()` agora lança exceção amigável se o storage falhar.
    *   **Cleanup:** Executado `composer dump-autoload` e `route:clear` para limpar cache de classes movidas.

### 4.5 Correção de Bug Crítico (v2.4)
*   **Problema:** `ProcessoController` tentava chamar método inexistente `uploadProcessAttachment`.
*   **Correção:** Atualizado para usar `processUploads()` do `DocumentService` nos métodos `store()` e `update()`.
*   **Resultado:** Uploads via formulário principal de Processos agora funcionam corretamente e respeitam a validação de S3.

### 4.6 Melhoria na Validação de Arquivos (v2.5)
*   **Problema:** Arquivos de texto simples (`.txt`, `.log`, `.md`) eram rejeitados pela validação estrita de mimes.
*   **Solução:** 
    *   Expandida lista de extensões permitidas no `ProcessoController` (`log, md, xml, odt, ods`).
    *   Implementado `Validator::make` manual para capturar e logar falhas de validação com detalhes do arquivo (MIME type detectado), facilitando debug futuro.

### 4.7 Refatoração do Módulo de Checklist (v2.6)
*   **Decisão:** Integrar o módulo de Checklist ao domínio **Legal** e padronizar a UI.
*   **Mudanças:**
    *   **Movido:** Models (`CaseChecklist`, `ChecklistTemplate`) para `src/Legal/Models`.
    *   **Movido:** Controller para `src/Legal/Http/Controllers/Admin/ChecklistController.php`.
    *   **Criado:** `ChecklistRepository` em `src/Legal/Repositories` para abstração de dados.
    *   **Movido:** Service `ChecklistTemplates` para `src/Legal/Services`.
    *   **UI:** Componente `checklist-stepper.blade.php` refatorado para Tailwind CSS e Alpine.js (removido Bootstrap/jQuery).
    *   **Funcionalidade:** Adicionado suporte a Checklists em Processos (antes apenas em Leads) via `processo_id`.

### 4.8 Refatoração do Domínio de IA (v3.0)
*   **Decisão:** Consolidar lógica de IA (`AiExecution`, `Assistant*`) no domínio **AI**.
*   **Mudanças:**
    *   **Criado:** Estrutura `src/AI/Models` e `src/AI/Http/Controllers`.
    *   **Movido:** `AiExecution`, `AssistantHistory`, `AssistantTemplate` para `src/AI/Models`.
    *   **Movido:** `AssistantController` (Admin) para `src/AI/Http/Controllers`.
    *   **Namespaces:** Atualizados de `SuiteZap\LawFirm\Models` para `SuiteZap\LawFirm\AI\Models`.
    *   **Limpeza:** Removida dependência de models na raiz.

### 4.9 Automação de Leads e Pipeline (v3.5)
*   **Decisão:** Automatizar a transição de Lead para Processo e ocultar ferramentas de qualificação em Leads finalizados.
*   **Implementação:**
    *   **Listener:** `LeadWonListener` (escopo: `lead.update.after`). Detecta mudança para estágio **WON** e cria `Processo` automaticamente (copiando título, valor, cliente e vínculo).
    *   **UI Dinâmica:** `lead-tools-panel.blade.php` agora verifica o estágio do Lead (`$lead->stage->code`). Se **WON/LOST**, oculta ferramentas e exibe mensagem de status.
    *   **Auto-Reload:** Script `MutationObserver` injetado no blade detecta mudança visual de estágio (classes CSS do Krayin) e recarrega a página automaticamente para aplicar a lógica PHP de ocultação.

### 4.10 Evolução do Assistente de IA e Controle de Saldo (v3.6)
*   **Decisão:** Centralizar os templates de IA no banco de dados "Mothership" (Master) para distribuição multi-tenant dinâmica e implementar controle financeiro baseado em custo de tokens (OpenAI/Anthropic). A identificação dos templates no código passa a utilizar um campo `slug` (ex: `pre_triagem`), eliminando hardcodes de IDs.
*   **Mudanças:**
    *   **Seeders Especializados (Mothership):** Diversos templates foram componentizados em seeders individuais (`PropostaTemplateSeeder`, `ChecklistTemplateSeeder`, `PreTriagemTemplateSeeder`, `ArquitetoConvivenciaTemplateSeeder`, etc.) para popular o banco de dados centralizado com os prompts e variáveis do "Roadmap Jurídico".
    *   **Controle de Saldo:** Refatorada a lógica de assinatura para lidar e exibir o saldo de uso de IA calculado em Reais (BRL), integrado à interface do SaaS (`subscription/index.blade.php`).
    *   **Validação de Uso:** Implementada uma camada de proteção (Blocker) na execução de assistentes, garantindo que o acionamento de IA ocorra somente se o Tenant possuir saldo positivo (`ai_tokens_balance`).
    *   **Histórico de IA:** Implementado `AssistantHistoryDataGrid` para acompanhamento de execuções passadas.
    *   **Modais Dinâmicos (UI):** O Componente visual dos Assistentes foi reconstruído em Alpine.js/Blade para renderização dinâmica de inputs baseada em um esquema JSON (`variables` vindas do template no Mothership) em vez de formulários hardcoded.

### 4.11 Histórico e Execução de IA (v3.7)
*   **Decisão:** Registrar e expor toda interação do usuário com os assistentes de IA (via Prompt Local ou N8N) em um Datagrid administrativo, armazenando os dados no banco do Tenant, mas referenciando os templates no Mothership.
*   **Mudanças:**
    *   **Criado:** `AssistantHistoryController` e `AssistantHistoryDataGrid`.
    *   **DataGrid Cross-DB:** O datagrid de histórico mapeia o `template_id` (Tenant) para o `title` (Mothership) via Eloquent Closures para evitar problemas de segurança/permissão com JOINs cross-database SQL direto.
    *   **Views Dinâmicas:** Adicionadas views para `index` e `show`, aplicando um parse Markdown isolado via Vanilla JS (`marked.js`) para formatar seguramente o conteúdo gerado pela IA.

### 4.12 Identificação de Tenant em Execuções Remotas de IA (v3.8)
*   **Decisão:** Garantir que o `tenant_id` seja injetado exaustivamente e de forma segura (Server-Side via `MotherShipService::getTenantId()`) no *payload* de *todas* as requisições enviadas ao N8N Webhook, prevenindo falsificação pelo frontend e permitindo a identificação exata do cliente no n8n.
*   **Mudanças:**
    *   **Injeção Síncrona:** Atualizado `AssistantController::executeRemote` para mesclar o `tenant_id` no pacote de payload raiz.
    *   **Injeção Assíncrona:** Atualizado o Job `ProcessAiAssistant` para incluir `tenant_id` no payload de requisições enviadas pelas filas (Workers).

### 4.13 Especialização e Validação de IA (v3.8)
*   **Decisão:** Melhorar a organização visual, categorização e segurança no acionamento dos assistentes pela interface do CRM.
*   **Mudanças:**
    *   **Classificação por Área:** Introduzida a coluna `area` (ex: Família, Trabalhista, Contratos, etc.) nos templates Mothership, separando logicamente as IAs (Seeders atualizados e webhook namespadrones com prefixo `ai-`).
    *   **Filtros de Interface:** A UI de Assistentes (`index.blade.php`) foi aprimorada com uma barra de navegação interativa para filtrar templates por área de atuação usando `required_module`. Os cards são ocultados via JavaScript dinâmico (`lfFilterByArea`).
    *   **Validação Client-Side:** Implementada lógica nativa JS (`validateFirstField()`) dentro do modal de assistentes, que obriga o usuário a preencher, pelo menos, o primeiro input variável associado ao prompt antes de tentar executar, melhorando UX e reduzindo a perda indevida de Tokens.

### 4.14 Integração Escavador (v3.9)
*   **Decisão:** Criar um domínio isolado para a integração com a API do Escavador, suportando consultas de processos (V2), jurisprudência/termos (V1) e resumo via IA, sem impactar o domínio Legal principal.
*   **Mudanças:**
    *   **Backend:** Criado `EscavadorController` que responde exclusivamente via AJAX/JSON.
    *   **Service:** Implementado `EscavadorService` como Client para as APIs V1 e V2.
    *   **Autenticação Dinâmica (Mothership):** Os tokens de acesso da API não ficam no `.env`. Eles são lidos dinamicamente da tabela `infrastructure_nodes` (Mothership DB) pesquisando pelo node chamado `"LawFimr V1 e V2"`, seguindo a filosofia multi-tenant.

### 4.15 Filtragem de Módulos de IA e Cobrança (v3.10)
*   **Decisão:** Vincular a disponibilidade dos Assistentes de IA aos módulos ativos na assinatura (`active_modules` da tabela `subscriptions` do Mothership).
*   **Mudanças:**
    *   **Backend (`AssistantController`):** A busca e o cache (`ai_templates`) agora consideram os módulos que o Tenant possui. Templates que exigem módulos não contratados (campo `required_module`) são filtrados da visualização e bloqueados na execução (`execute`/`process`).
    *   **Frontend (`subscription/index.blade.php`):** O painel de assinaturas agora lista os módulos ativos dinamicamente e exibe os Assistentes de IA correspondentes, além de demonstrar o saldo de IA convertido em BRL (R$).

### 4.16 SaaS Config Seeder (v3.10)
*   **Decisão:** Padronizar os limites de cota iniciais quando um tenant é provisionado ou quando o ambiente de desenvolvimento é configurado.
*   **Mudanças:**
    *   Adicionado o `SaasConfigSeeder` que define valores padrão (`lawfirm.saas.storage.limit` = 4GB e `lawfirm.saas.ai.credits` = 1000) diretamente na tabela `core_config` do Krayin (banco do Tenant), caso não existam, garantindo que a aplicação sempre tenha limites-base lógicos.

### 4.17 Webhooks Assíncronos Escavador V2 (v3.10)
*   **Decisão:** Permitir a recepção de callbacks assíncronos da API V2 do Escavador de forma segura.
*   **Mudanças:**
    *   A rota `api/webhooks/escavador` foi adicionada às exceções do middleware `VerifyCsrfToken` para receber POST requests externos do Escavador relativos à conclusão de processamentos assíncronos (como resumos gerados por IA).

### 4.18 Integração WhatsApp Financeiro (v3.10)
*   **Decisão:** Centralizar e automatizar o envio de cobranças via WhatsApp diretamente do módulo financeiro, reutilizando o provedor corporativo (Evolution API) que atende ao Tenant.
*   **Mudanças:**
    *   **Backend (`FinancialController@sendWhatsappBilling`):** Implementada a geração dinâmica de mensagens de cobrança usando templates customizáveis do `core_config` e substituição de tags (`{cliente_nome}`, `{valor}`, etc.).
    *   **Service (`EvolutionService` \& `MotherShipService`):** O controller adquire a instância correta do WhatsApp buscando o plano do Tenant no banco Mothership (`MotherShipService::getEvolutionConfig()`), mantendo a arquitetura hermética e evadindo a dependência manual do `.env` por cliente.

### 4.19 Precificação Dinâmica Multi-Tenant do Escavador (v3.10)
*   **Decisão:** Centralizar a tabela de preços de revenda das consultas do Escavador (Busca, Capa, Resumo IA) no banco central (Mothership), permitindo que a administração altere os valores repassados aos Tenants.
*   **Mudanças:**
    *   **Tabela de Preços Granular:** 30+ chaves do tipo `escavador_price_{endpoint}` inseridas na tabela `app_config` do Mothership via `inject_massive_prices.php`.
    *   **Backend (`MotherShipService@getEscavadorPrices`):** Implementado método que lê as chaves dinâmicas da tabela `app_config` do banco `mothership`, com um cache de 60 minutos para alta performance.

### 4.20 Extrato Financeiro SaaS (Saas Transactions) (v3.10)
*   **Decisão:** Criar um "Ledger" (livro-razão) interno no banco do Tenant para registrar minuciosamente o consumo de saldos pré-pagos (como créditos de IA e chamadas à API do Escavador).
*   **Mudanças:**
    *   **Tabela `saas_transactions`:** Criada via migration no banco da aplicação (Tenant) contendo tipo (crédito/débito), valor, saldo após, e relacionamento morfopolítico.
    *   **Visualização (`SaasTransactionController` & `SaasTransactionsDataGrid`):** Implementada a interface administrativa.

### 4.21 Rastreamento de Requisições Assíncronas — EscavadorRequest (v3.11)
*   **Decisão:** Persistir cada chamada à API do Escavador no banco do Tenant como um "ticket" rastreável.
*   **Mudanças:**
    *   **Model `EscavadorRequest`** (`Escavador/Models/EscavadorRequest.php`): Criado para mapear a tabela `escavador_requests`. Registra status pendentes, finalizados e custos (`cost`).

### 4.22 Painel de Histórico do Assistente Jurídico (v3.11)
*   **Decisão:** Dar visibilidade ao usuário sobre cada consulta realizada via Escavador, incluindo status, custo e processo vinculado.
*   **Mudanças:**
    *   **`EscavadorHistoryController` & `EscavadorHistoryDataGrid`**: Controllers skinny + Datagrid via AJAX. Renderizam os 20 endpoints customizados formatando custo final e vinculação de Processos CNJ.

### 4.23 Webhook Receptor com Estorno Automático de Saldo (v3.11)
*   **Decisão:** Garantir idempotência nos callbacks assíncronos do Escavador V2 e proteger o tenant de débitos indevidos em caso de falha do processamento externo.
*   **Mudanças:**
    *   **`WebhookController@handle`**: Rota pública POST (isenta CSRF). Estorna o valor em caso de erro na geração e previne loops de status já processados.

### 4.24 Expansão de Preços do Escavador — Migration Complementar (v3.11)
*   **Decisão:** Complementar a tabela de precificação dinâmica com 7 novos endpoints não cobertos.

### 4.25 Controle Analítico de Custo e Preços Globais (Mothership Panel v3.12)
*   **Decisão:** Centralizar de forma visual e administrativa os componentes de negócio antes regidos por *seeders/tinkers*, tornando o SaaS perfeitamente escalável e gerenciável a quente pela camada global (Mothership Panel Nativo PHP).
*   **Mudanças:**
    *   **Configuração Zero .env Re-estruturada (`pages/config.php`):** Variáveis essENCIAIS (api_secret, webhooks) alteradas diretamente pela interface visual, alimentando a tabela `app_config` do Mothership e refletindo no ecossistema instantaneamente.
    *   **Dashboard Massivo Escavador (`pages/escavador.php`):** Gestão das 37 variáveis de preço de endpoints (V1 e V2) por meio de um editor numérico simultâneo.
    *   **Modificadores Avançados (APIs Core):** Requisições API com ações complexas: `mass_update` com suporte a ajustes lineares/percentuais dinâmicos da tarifa repassada por requisição ao Tenant.

### 4.26 Monitoramentos (Robôs) do Escavador e Alertas de WhatsApp (v3.13)
*   **Decisão:** Criar um painel de "Robôs" dentro da aba do Escavador permitindo a visibilidade completa e customização de monitoramentos assíncronos. Além de integrar o recebimento de atualizações (via Callback) automaticamente como mensagens para o número de WhatsApp do escritório.
*   **Mudanças:**
    *   **Model `EscavadorMonitoramento`**: Mapeia a tabela `escavador_monitoramentos`, guardando referências ao Tenant, identificador externo no Escavador e controle dinâmico (opt-in) pelo campo boolean `notify_whatsapp`.
    *   **Integração no Webhook** (`WebhookController`): A controller passa a reconhecer payloads de push que indicam alterações em monitoramentos (como novas movimentações). Se ativo, delega para `EvolutionService` realizar o dispatch da mensagem baseada num Custom Template.
    *   **Template Dinâmico**: A notificação é moldada por um texto padrão inserido nativamente em `Config/system.php` sob o namespace `escavador_monitoramento_update`.

### 4.27 Extinção do Legado Raiz (v3.14)
*   **Decisão:** Extinguir definitivamente a dívida técnica residual da migração original, promovendo as últimas classes (relíquias) da raiz de infraestrutura para seus respectivos Domínios de negócio nativos.
*   **Mudanças:**
    *   `SafeActivityDataGrid` relocada do legado `src/DataGrids` para `src/Legal/DataGrids`.
    *   Pastas estruturais obsoletas na raiz (`src/Events`, `src/Rules`, `src/Observers`, `src/DataGrids`, `src/Services/Whatsapp`) formalmente extintas e deletadas do root do LawFirm.
    *   O Service Provider (`LawFirmServiceProvider`) foi ajustado para injetar os namespaces corretos sob seus Bounded Contexts, atingindo isolamento arquitetural (Zero Root Debt).

### 4.28 Integração de Pagamentos Asaas e Créditos de IA (v3.15)
*   **Decisão:** Integrar o gateway de pagamentos Asaas para gerenciar o faturamento de assinaturas e venda avulsa de créditos de inteligência artificial, mantendo a filosofia "Zero .env" e os padrões "Skinny Controller" no domínio SaaS.
*   **Mudanças:**
    *   **Backend (`AsaasService` & `SubscriptionCheckoutController`):** Serviço dedicado criado para intermediar a gestão de clientes e pagamentos na API V3 do Asaas. As requisições ganham dinamicidade ao buscar as credenciais direto no nó de infraestrutura tipo `asaas` (banco MotherShip).
    *   **Frontend (Vanilla JS):** O painel do Krayin CRM (Aba Minha Assinatura) agora abriga os planos de renovação e opções de recarga de créditos. Os botões acionam métodos `fetch` injetando o CSRF para se proteger do Vue.js invasivo da dashboard ("Financial Tab Pattern").
    *   **Webhooks Assíncronos (`AsaasWebhookController`):** Controller que consome payloads isentos do CSRF e consolida recebimentos. Automatiza o preenchimento de `ai_tokens_balance` global no MotherShip e notifica a requisição de reset de cache.

### 4.29 Dados de Faturamento Multi-Tenant SaaS (v3.15)
*   **Decisão:** Centralizar os dados de identificação do comprador (Razão Social, CNPJ, Email, CEP, etc.) na camada global MotherShip (Tabela 1:1 `tenant_billing_infos`), separando-os da configuração local do tenant (`core_config`). Isso garante que operações vitais (como gateway Asaas) consumam regras e dados de faturamento desatrelados do CRM.
*   **Mudanças:**
    *   **Backend (`TenantBillingController` & `TenantBillingInfo`):** Controller restrito para CRUD destas chaves no Model Eloquent vinculado à connection `mothership`. O `AsaasService` também repriorizou sua origem de dados, usando `core_config` apenas como fallback legado de último recurso.
    *   **Frontend Modular:** A UI de "Dados do Comprador" atua como um grid embeddado direto na aba `Minha Assinatura`, operando de forma autônoma via AJAX REST, com fetch automático de logradouros através da integração API Viacep/OpenCep.

### 4.30 Precificação Dinâmica de Checkouts e Parcelamento (v3.16)
*   **Decisão:** Expandir o Gateway de Pagamentos para suportar pacotes customizáveis de créditos de IA e parcelamentos via Cartão de Crédito, blindando a conversão numérica com validação estrita no Backend.
*   **Mudanças:**
    *   **Backend (`AsaasService` & `SubscriptionCheckoutController`):** O método `createCreditCheckout` injeta ativamente as flags conjuntas `['DETACHED', 'INSTALLMENT']` no payload do Asaas quando a opção de Parcelamento é eleita. Adicionalmente, o Controller atua como Firewall, ignorando os preços manipulados no FrontEnd e recalculando a taxa real: `Preço = Créditos / 100`, forçando a conversão mandatória e prevenindo interceptação de request.
    *   **Frontend:** Refatoração de botões do Modal de Checkout utilizando estilos HEX inline injetados diretamente na View, suprimindo o problema do JIT Compiler do Tailwind CSS com dependências nativas "Dark Mode". Campos de "Outro Valor" interativos incluídos com cálculo assíncrono antes do *submit*.

### 4.31 Hardening Arquitetural e Conformidade Total (v3.17)
*   **Decisão:** Eliminar as 4 violações residuais identificadas em auditoria estrutural pós-v3.16: `env()` fallback silencioso, acesso direto ao `Storage::` fora do `SaasFileService`, Controller gordo com lógica de negócio inline e ausência de arquivo de rota dedicado para o domínio `Whatsapp`.
*   **Mudanças:**
    *   **`env()` Removido (`FinancialController`):** O fallback `env('EVOLUTION_INSTANCE_NAME')` foi eliminado. Quando o `MotherShipService::getEvolutionConfig()` não encontra a instância, o sistema retorna HTTP **503** com mensagem clara ao usuário — sem vazamento silencioso de credenciais do servidor entre tenants.
    *   **`Storage::` Proibido (`FinancialController`):** As chamadas diretas `Storage::exists()`, `Storage::get()` e `Storage::mimeType()` no método `downloadReceipt()` foram substituídas por delegação ao `SaasFileService`, garantindo acesso ao bucket S3/MinIO correto de cada tenant.
    *   **`SaasFileService` Expandido:** Adicionados métodos `get(string $path): ?string`, `mimeType(string $path): ?string` e `storeRaw(string $path, string $contents): bool` para completar a interface de manipulação de arquivos sem expor o `Storage::` direto a callers externos.
    *   **Skinny Controller (`FinancialController`):** Toda a lógica de composição de mensagem WhatsApp (extração de telefone, template lookup, substituição de tags `{cliente_nome}`, `{valor}`, `{data_vencimento}`) foi extraída para `FinancialService::prepareBillingWhatsapp(Financial $financial): array`. O controller reduziu de ~90 para ~35 linhas no método `sendWhatsappBilling()`.
    *   **Segregação de Rotas (`Whatsapp`):** Criado `src/Http/Routes/admin-whatsapp.php` dedicado ao domínio `Whatsapp` (antes as rotas viviam em `admin-saas.php`). Registrado no master loader `routes.php`; bloco duplicado removido de `admin-saas.php`.
    *   **Diretórios Fantasma Deletados:** As pastas `src/Events/`, `src/Observers/`, `src/Rules/`, `src/Listeners/` e `src/Services/` (incluindo `Services/Whatsapp/`) — que persistiam como estruturas vazias desde a v3.14 — foram definitivamente removidas do filesystem.

### 4.32 Security Hardening do ACL (Jurídico + Financeiro) (v3.18)
*   **Decisão:** Alinhar os módulos Jurídico e Financeiro à hierarquia nativa de Controle de Acesso (Bouncer / ACL) do Krayin CRM, mitigando vulnerabilidades de escalation de privilégios.
*   **Mudanças:**
    *   **Hierarquia `acl.php`:** O módulo `financeiro` (antes um nó órfão) foi movido para dentro de `lawfirm`. A hierarquia correta agora permite que o Krayin renderize o checkbox de Financeiro na UI de Configurações de Funções.
    *   **Proteção de Rotas Faltantes:** As rotas `quick_pay`, `receipt`, `process.store`, e `send_whatsapp` do `FinancialController` foram explicitly mapeadas no array do `acl.php`.
    *   **Enforcement Granular (Controllers):** Adicionamos proteções `bouncer()->allow(...)` nativas dentro dos métodos de escrita/view nos controllers `ProcessoController`, `PrazoController` e `FinancialController`. Isso cria dupla validação (Middleware + Controller).
    *   **Scoping por `view_permission` (DataGrids):** Os Grids (`ProcessoDataGrid` e `PrazoDataGrid`) substituíram o bloqueio manual hardcoded (`role_id != 1`) por `bouncer()->getAuthorizedUserIds()`. Com essa mudança, o Krayin processa com segurança os scopes de visualização Nativos: *"Global", "Grupo" ou "Individual"*, respeitando rigorosamente o limite de quem vê o quê.
    *   **Sincronização de Menus:** O `menu.php` agora referenciar a key correta (`lawfirm.financeiro`) no campo `permission`.
    *   **Migração de Funções (`Roles`):** Foi executada uma database migration transformando strings antigas de `"financeiro"` para `"lawfirm.financeiro"`, preservando o acesso de todos os clientes sem exigir login/re-save manual em cada Tenant.

### 4.33 Consolidação Top-Level do Módulo Financeiro (v3.45)
*   **Decisão:** Extrair os submenus "Dashboard Financeiro" e "Cobranças" do menu Jurídico para dar-lhes protagonismo em um novo Menu Pai (`Financeiro`). Além disso, consolida-los...
*   **Mudanças:**
    *   **Unified Union DataGrid:** `TenantInvoiceDataGrid` ajustado para compilar resultados simultâneos de `tenant_invoices` (Transações Asaas) e `financials` (Lançamentos Manuais sem link externo) em uma view unificada. Etiquetas condicionais inseridas dependendo da `Origem`.
    *   **Gráficos no Dashboard (`admin.lawfirm.financial.index`):** Transplante do DataGrid antigo para a página central de Cobranças. O Dashboard passa a focar somente nos KPIs gerenciais acompanhados de Chart.js isolados mostrando divisão via "Pizza" (Formas de pagamento) e comparativo de "Barras" mensais.
    *   **Emissão Pragmática Assíncrona:** Checkbox "Emitir via Asaas" condicional na Aba Financeiro nos processos; aparece somente em marcações em Receitas marcadas via (PIX, Boleto, Cartão), conectando automaticamente a trigger base no `FinancialController`.

### 4.33 Adoção de Masking via Watchers Vue Resilientes vs Krayin `v-mask` (v3.45)
*   **Decisão:** Substituir bibliotecas globais de injetamento (v-mask) que conflitam com distribuições fechadas Vee-Validate/Krayin, adotando listeners de JS Puro (`watch`) processando limpeza e reformatação em tempo real diretamente na submissão de componentes dinâmicos no ecossistema e formulários.
*   **Mudanças:**
    *   **Fim da Limitação Global Vee-Validate (`vee-validate.js`):** Regra global `phone` teve seu Regex afrouxado de apens `/^\+?\d+$/` (que barraria submissão de valores formatados) para suporte em permissões de espaços, parênteses e hífens.
    *   **Vigilantes Nativos do Blade (Vue 3 Component Mask):** Components `phone.blade` e `users/index.blade.php` tiveram máscaras dinâmicas de 10 vs 11 dígitos nativas incluídas detectando DDD e convertendo a prop do V-Model nos métodos `watch` sob Lifecycle real-time — assegurando armazenamento constante e legível. 
    *   **Prefixo Invísivel DB-driven:** Inputs nativos independentes forçam a exclusão do `+` e aplicam higienização limpa (excluindo os chars formatados em endpoints brutos customizados) mas para uso no envio do form Krayin ou API Whatsapp Tester ocultam um "55" invisível no back/front.

### 4.34 Integração Orientada a Identidade: Migração do `whatsapp_responsavel` (v3.45)
*   **Decisão:** Eliminar redundâncias e desvio de consistência ao remover o controle local manual de telefones dos processos, forçando o módulo inteiro a consumir a variável do próprio advogado já cadastrado (User identity relation).
*   **Mudanças:**
    *   O campo legado do model de Processo `whatsapp_responsavel` e seus requests validadores `StoreProcessoRequest/UpdateProcessoRequest` sofreram decape total — garantindo que nenhuma parte do código dependa ou crie a prop antiga.
    *   Background Jobs como `SendScheduledPrazoNotifications` operam interceptando os envios ao verificar puramente se `$processo->responsavel->whatsapp` existe. 
    *   Na página de visualização administrativa isolada (`show.blade.php`), a chamada do componente "WhatsApp do Advogado Responsável" puxa automaticamente os dados da tabela `users` do Advogado assinador, sem exigir salvamento manual na interface de "Processos".

### 4.65 Unificação de Status de Entidades Legais (v3.46.1)
*   **Decisão:** Unificar e padronizar o pipeline de status em 12 etapas (1 ao 12) transversais a toda a plataforma para Caso e Processo, abandonando o modelo anterior de strings de estágio fragmentadas e garantindo coesão absoluta desde "Novo Caso" até "Encerrado".
*   **Mudanças:**
    *   **Single Source of Truth (`LegalOrchestrator`):** Mapeou a constante de estágios como fonte central para as instâncias de status que populam formulários, regras de migração e APIs, protegendo o ciclo de vida processual do cliente.
    *   Extinta a possibilidade do usuário setar stages ad-hoc ou desregulados através da inserção direta; toda API ou interface acionadora filtra forçadamente pela lista unitária aprovada pela orquestração.
    *   Toda view e datagrid adaptados para espelhar as badges correspondentes.

### 4.66 Organização do Menu Assistentes (v3.47)
*   **Decisão:** Melhorar a UX agrupando todas as ferramentas inteligentes ("Assistentes IA" e "Assistente Jurídico/Escavador") sob um único menu top-level no CRM, tornando a descoberta mais óbvia e aliviando a barra lateral.
*   **Mudanças:**
    *   Um menu raiz unificado ("Assistentes") com o ícone `icon-user text-3xl`.
    *   Históricos e relatórios internos do escavador ou execuções passadas de IA organizados dentro de sub-tópicos nesse novo cluster.

### 4.67 Conversão Escavador para SuiteCoins (v3.48)
*   **Decisão:** Ampliar a adoção da moeda virtual (Ƶ) abarcando também os fluxos de consulta inteligente de dados do Escavador, para não assustar o usuário com conversões flutuantes em BRL puro no HUD jurídico.
*   **Mudanças:**
    *   `EscavadorController` instruído a retornar os responses injetados com `suitecoin_balance` para consumir padronizado no front.
    *   Substituição total dos ícones (R$) nas telas V1 e V2 (`escavador-tab.blade.php`) por representativas de SuiteCoins Ƶ x10 (visual display format).

### 4.68 ExchangeRateService e Câmbio Soberano MotherShip (v3.47)
*   **Decisão:** Eliminar cálculos manuais de PTAX ou Markup internos no LawFirm. A taxa de câmbio soberana (USD→BRL) passa a ser lida exclusivamente do endpoint `api/exchange_rate.php` do MotherShip, via o campo `billing_consensus.consumer_rate`.
*   **Mudanças:**
    *   **`MotherShipService::getExchangeRate()` (novo):** Consome o endpoint HTTP do MotherShip com cache de 5 minutos e fallback graceful (defaults 5.75 / ×10) quando o serviço está offline — nunca derruba o CRM (503-safe).
    *   **`ExchangeRateService` (novo, `Financial/Services/`):** Serviço do domínio financeiro com métodos `getConsumerRate()`, `usdToBrl()`, `brlToSuiteCoins()`, `usdToSuiteCoins()` e formatadores. Delega para `SuiteCoinService` para conversão Ƶ, mantendo o `SuiteCoinService` como única fonte de verdade do multiplicador.
    *   **`ProcessAiAssistant` (Job):** Validação de `suitecoin_balance` adicionada **antes** do dispatch ao N8N. Saldo insuficiente → `history.status = 'failed'` com mensagem clara em Ƶ, sem consumir nenhum token de IA.
    *   **`menu.php` (fix):** Chaves dos itens "Criar Monitoramentos" e "Monitoramentos/Robôs" corrigidas de `lawfirm.escavador_monitoramentos*` para `assistants.escavador_monitoramentos*`, fazendo-os aparecer aninhados corretamente sob o menu top-level "Assistentes".
*   **Segurança .env (Fallback Offline):** O método utiliza a variável `MOTHERSHIP_BASE_URL` declarada localmente. O sistema previne a quebra da aplicação, e caso este Environment não tenha sido criado ou validado no locatário (`env` nulo, vazia, ou servidor MotherShip intermitente), a infraestrutura atua em *Graceful Degradation* (adotando uma tarifa fixa provisória na cotação) preservando o CRM intacto e funcional no dashboard administrativo.

### 4.69 Regras de Cálculo e Exibição de SuiteCoins (v3.48)

> [!IMPORTANT]
> Esta seção define as **regras canônicas** para conversão, exibição e débito de SuiteCoins (Ƶ) em todo o sistema. Qualquer nova tela ou serviço DEVE seguir estas regras sem exceção.

#### Conceito
`suitecoin_balance` é armazenado na tabela `subscriptions` (MotherShip) **sempre em BRL (Real Brasileiro)**. A moeda virtual Ƶ é **apenas uma camada de exibição** calculada em runtime — nunca persistida.

#### Fórmula Padrão — Todos os Serviços

Aplicável a: Escavador, Monitoramentos, Assistentes Jurídicos, DataJud e qualquer serviço futuro.

```
Ƶ_exibido = preço_BRL_bruto × 10 × 1.25
```

| Componente | Valor | Descrição |
|:---|:---|:---|
| `preço_BRL_bruto` | `p` | Custo real da API/serviço lido do MotherShip (`app_config`) |
| `× 10` | `rate` | Taxa de conversão BRL → Ƶ (`SuiteCoinService::getRate()`) |
| `× 1.25` | `markup` | Margem de 25% da plataforma (`SuiteCoinService::getMarkup()`) |
| **Resultado** | `Ƶ_exibido` | Valor exibido ao usuário na interface |

**Implementação no front-end (JavaScript):**
```javascript
var pZ = p * 10 * 1.25; // p = preço BRL bruto vindo do MotherShip
```

**Implementação no back-end (PHP — Escavador/DataJud):**
```php
$costBrlWithMarkup = SuiteCoinService::calculateServicePriceBrl($costBrlRaw); // × 1.25
$subscription->decrement('suitecoin_balance', $costBrlWithMarkup); // débito em BRL
```

#### ⚠️ Exceção: Painel "Minha Assinatura" — Créditos de IA

Esta é a **única exceção** à fórmula padrão, aplicável exclusivamente à exibição do saldo na tela `subscription/index.blade.php` e na resposta da rota `lawfirm.escavador.saldo_cliente`.

**Regra da Exceção:**
```
Ƶ_exibido = suitecoin_balance_BRL × 10   (sem markup)
```

**Motivação:** Quando o usuário paga R$ 10,00, o sistema deve exibir **Ƶ 100,00** — e não Ƶ 125,00 nem Ƶ 80,00. A percepção de "receber menos do que pagou" deve ser evitada. O markup de 25% é recuperado naturalmente no consumo dos serviços, onde a fórmula completa `× 10 × 1.25` é aplicada.

**Implementação (PHP — Subscription View):**
```php
// CORRETO — sem markup na exibição do saldo comprado
$aiBalanceVirtual = SuiteCoinService::toVirtual($aiBalanceBrl); // apenas × rate(10)
```

**Implementação (JavaScript — loadBalance):**
```javascript
var suitecoinsRate = parseFloat(d.suitecoin_rate || 10);
currentBalance = balanceBrl * suitecoinsRate; // apenas × 10, sem × 1.25
```

#### Exemplo Completo com R$ 10,00 de Saldo

| Ponto do Sistema | Valor |
|:---|:---|
| Banco (`suitecoin_balance`) | `R$ 10,00` |
| Exibição em "Minha Assinatura" | **Ƶ 100,00** (× 10, sem markup) |
| Consulta OAB V2 (custo bruto R$ 4,50) | Exibe **Ƶ 56,25** (× 10 × 1.25) |
| Débito real após consulta | `– R$ 5,625` do banco |
| Saldo após consulta | `R$ 4,375` → exibe **Ƶ 43,75** |

#### Fonte de Verdade dos Serviços (SuiteCoinService)
*   `SuiteCoinService::getRate()` → taxa de conversão (padrão: `10`)
*   `SuiteCoinService::getMarkup()` → markup da plataforma (padrão: `1.25`)
*   `SuiteCoinService::toVirtual(float $brl)` → converte BRL → Ƶ (sem markup — para exibição de saldo)
*   `SuiteCoinService::toBrl(float $virtual)` → converte Ƶ → BRL (para validação de saldo)
*   `SuiteCoinService::calculateServicePriceBrl(float $raw)` → aplica markup: `$raw × getMarkup()`
*   `SuiteCoinService::formatFromBrl(float $brl)` → formata saldo BRL como string Ƶ



### 4.32 Idempotência do Mothership e Hardening do Deploy Docker (v3.17)
*   **Decisão:** Tornar o ambiente Docker resiliente a falhas de boot de container e resolver permanentemente o provisionamento de novos tenants no SaaS.
*   **Problema 1 (Container Crash Loop):** O `entrypoint.sh` rodava `artisan migrate` forçando o caminho `Webkul/Mail`, que havia sido deletado no refactoring anterior, resultando em "Migration path not found" e derrubando a stack imediatamente no Docker Swarm.
*   **Problema 2 (Não-Idempotência):** A tabela de controle `migrations` vive no banco do Tenant, enquanto as migrations do Mothership alteram o banco central. Sempre que um *novo* tenant era provisionado, o Laravel tentava rodar as migrations do Mothership de novo, gerando erro `Table already exists` e quebrando o deploy (Erro 42S01).
*   **Mudanças:**
    *   **Entrypoint Limpo:** Removidos caminhos órfãos do `docker/entrypoint.sh`.
    *   **Migrations Blindadas:** Todas as migrations da connection `mothership` (como `app_config` e `lawfirm_assistant_templates`) receberam blocos `hasTable()` e `hasColumn()`, tornando o ecossistema 100% idempotente a deploys paralelos ou novos tenants.
    *   **Configuração via Compose:** Adotado o modelo `.env.docker` (passagem explícita de `DB_MOTHERSHIP_*` via `environment:` do `docker-compose.yml`), abandonando o antipattern de `COPY .env` para a imagem e evitando conexões recusas por *localhost*.

### 4.33 Krayin 2.1.6 Localization e Estabilização de Rotas (v3.17)
*   **Decisão:** Corrigir os DataGrids e formulários corrompidos no idioma Português (PT-BR) após a atualização do Core do Krayin (v2.1.6) e estabilizar Views do sistema como o Dashboard Financeiro.
*   **Problema 1 (Seeders Destrutivos e Locale):** Seeders do Krayin (ex: `PipelineSeeder`, `AttributeSeeder`) usam a função `trans()` para preencher o banco de dados. No boot do Docker, se o locale (`pt_BR`) não estava no cache, o Laravel salvava chaves brutas de tradução (ex: `admin::app.seeders...`) corrompendo 100% da interface do usuário baseada nesses atributos.
*   **Problema 2 (Erro 500 no Dashboard Financeiro):** O Laravel 10/11 passou a lançar a exceção fatal `UrlGenerationException` ao compilar Views Blade que injetam rotas JavaScript com o ID dinâmico omitido (ex: `route('admin.lawfirm.financial.quick_pay', '')`). Isso causava a quebra massiva das telas do sistema.
*   **Mudanças:**
    *   **Data Fixer (`entrypoint.sh`):** Adicionado um bloco de execução forçada via `artisan tinker` após o boot dos containers. Este bloco audita e corrige silenciosamente 7 tabelas do CRM (pipelines, estágios, papéis, origens de lead, tipos, workflows, templates e meia centena de atributos), forçando-os para os nomes consolidados em Português-BR independente da tradução nativa do momento do build.
    *   **Padrão REPLACE_ID (`Blade` e JS):** Enraizado nas regras de Frontend do pacote que rotas geradas pelo Blade para consumo assíncrono do JavaScript nunca usem o id vazio nas views. Devem usar um string placeholder explícito injetado via route: `route('nome_da_rota', 'REPLACE_ID')` com substituição `.replace('REPLACE_ID', id)` puramente *client-side*.
    *   **Branding Control (`LawFirmServiceProvider`):** Adicionada constante `VERSION` = `3.18` injetada globalmente no Header (exibindo `Versão 2.1.6 | LF 3.18`).

### 4.34 Integração Asaas: Sincronização Local, CheckoutSession e Idempotência (v3.18)
*   **Decisão:** Eliminar falhas de perda de créditos de IA (race conditions, bloqueios de webhook em localhost ou checkouts de cartão de crédito) mapeando rigidamente os nós de pagamento na infraestrutura.
*   **Mudanças:**
    *   **Nó Asaas Estrutural:** Adicionado `asaas_node_id` na tabela `tenants` do banco Mothership e na model genérica `Tenant`, equiparando o gateway financeiro ao nível arquitetural de deploy do N8N e MinIO.
    *   **Fallback Síncrono (`SaaSController` & `AsaasService`):** Adicionado `AsaasService::syncTenantPayments()`. Quando o usuário retorna de um checkout via parâmetro `?payment=success` (ou até sem parâmetro na listagem da assinatura com debounce de 60s), o sistema consulta ativamente a API do Asaas filtrando as últimas faturas recentes para auditar aprovações no status `RECEIVED` ou `CONFIRMED`. Isso garante recebimento de créditos **instantaneamente** em ambientes locais impermeáveis a Webhooks.
    *   **Injeção Reversa de CheckoutSession:** Resolvido o *bug* em que faturas geradas através de `checkouts` avulsos não transferiam as metadados do `externalReference`. Os algoritmos do Webhook mapeiam o ID do `checkoutSession`, buscam a sessão pai pendente localmente (que foi criada no ato do click do botão pagar) e transferem o vínculo seguro para o tenant, reabilitando os pagamentos por Cartão de Crédito.
    *   **Idempotência Robusta e Proporção 1:1:** O schema inicial armazenava o ID de pagamento do Asaas (`pay_xxxx`) em colunas inteiras, truncando strings para `0` e quebrando chaves únicas. O tipo de `reference_id` na `saas_transactions` foi convertido para `VARCHAR(255)`. Simultaneamente, a plataforma convergiu o câmbio de Créditos de IA para a proporção **1:1 (R$ 1,00 = 1 Crédito)**, garantindo espelhamento fiscal e clareza total no extrato da plataforma.
    *   **Fallback Definitivo (Single-Tenant Asaas):** Cada cliente possui sua própria subconta (InfrastructureNode) do Asaas. Diante disto, caso um pagamento perca completamente o tracking do `externalReference`, uma heurística assume que a transação inevitavelmente pertence ao dono da conta, creditando a proporção BRL exata baseando-se exclusivamente no valor pago.

### 4.34.1 Bugfix: Filtro Inválido no syncTenantPayments (v3.20)
*   **Problema:** O `AsaasService::syncTenantPayments()` passava `externalReference => $tenantId` no filtro da API `/v3/payments`. A API do Asaas realiza **busca exata** neste campo, mas o formato salvo é `"{tenantId}|tipo|valor"`. O filtro nunca retornava resultados e nenhum crédito era sincronizado após a compra.
*   **Segundo Problema:** O `SaaSController::index()` não invalidava o cache da `subscription` após o sync, e não distinguia o retorno de um checkout (`?payment=success`) de visitas comuns — o throttle de 60s podia bloquear o primeiro sync crítico pós-compra.
*   **Correções:**
    *   **`AsaasService::syncTenantPayments()`:** Removido o filtro `externalReference` da query HTTP. A busca passa a usar `status=RECEIVED,CONFIRMED` + `dateCreatedFrom` (últimos 30 dias). A filtragem por tenant permanece local (via `externalReference` do payload + fallback por valor). Adicionado fallback que usa o `value` do pagamento para crédito (`tenantId|credit|valor`) quando nenhuma outra referência é encontrada (arquitetura single-tenant Asaas).
    *   **`SaaSController::index()`:** Quando `?payment` está presente na URL (retorno de checkout), os caches `asaas_sync_last_run_*`, `tenant_*_subscription` e `asaas_node_config` são invalidados **antes** do sync, garantindo exibição imediata do saldo atualizado.

### 4.35 Resiliência de Storage S3 e Herança de Tokens WhatsApp (v3.19)
*   **Decisão:** Eliminar erros do tipo `500 Internal Server Error` no provisionamento a frio de novos Tenants garantindo resiliência total nos adaptadores de Storage e instâncias de WhatsApp Web.
*   **Problema (S3/MinIO):** A criação Just-In-Time de buckets falhava pois nomes atrelados ao tenant (ex: `TsT_Local_S3`) violavam a sintaxe restrita da AWS/MinIO (proibição de letras maiúsculas e underlines), causando `InvalidBucketName (400)`.
*   **Problema (WhatsApp API):** A injeção vazia do `token` da Evolution API lançava Exceção na interface administrativa quando o tenant não possuía chave individual.
*   **Mudanças:**
    *   **Auto-Sanitização (`MotherShipService`):** O `minio_bucket_name` lido do banco é agressivamente convertido usando `preg_replace('/[^a-z0-9.-]/', '-', strtolower(...))` em tempo de execução. O nome sanitizado alimenta de forma segura a variável `filesystems.disks.s3.bucket`.
    *   **Criação JIT (`SaasFileService`):** O método `store()` agora intercepta retornos `false` da Facade `Storage` e força a criação física do Bucket (`ensureBucketExists()`) usando as próprias credenciais empossadas da Facade, retentando o upload na sequência.
    *   **Herança de Nó (`MotherShipService`):** O método `getEvolutionConfig()` adota o comportamento de herança. Se o Tenant não tiver o seu próprio `evolution_api_key`, a plataforma envia o Token Master do seu respectivo Servidor Hospedeiro (`$node->api_key`).
    *   **UX (Auto-Refresh):** O QR Code da view `admin/whatsapp/index.blade.php` recebeu um daemon que auto-atualiza o payload `base64` a cada 40 segundos, suportando a expiração natural da sessão remota da Evolution API sem recarregamento manual da página.

### 4.36 Suporte a Pessoa Física e Jurídica no Billing (v3.20)
*   **Decisão:** Compatibilizar o módulo de Dados de Faturamento (`admin/juridico/billing-info`) com as novas colunas `company_name`, `cpf` e `cnpj` adicionadas à tabela `tenant_billing_infos` do banco MotherShip em Abr/2026, substituindo o campo unificado legado `cpf_cnpj` por campos distintos e tipados.
*   **Mudanças:**
    *   **Model (`TenantBillingInfo`):** Adicionados `company_name`, `cpf` e `cnpj` ao `$fillable`. Coluna `cpf_cnpj` mantida na lista para retrocompatibilidade.
    *   **Controller (`TenantBillingController@store`):** Validação expandida com os 3 novos campos (todos `nullable`). Lógica de preenchimento automático do campo legado `cpf_cnpj` implementada: prioriza `cnpj` (PJ) > `cpf` (PF) > valor de `cpf_cnpj` enviado — garantindo zero breaking changes em integrações existentes (ex: `AsaasService`).
    *   **View (`billing-info.blade.php`):** Formulário reestruturado com toggle **PF / PJ**. Em modo PJ, o formulário exibe campos de `Razão Social` e `CNPJ`; em modo PF, exibe `Nome Completo` e `CPF`. Máscaras de input separadas (CPF: `000.000.000-00`, CNPJ: `00.000.000/0000-00`). Validação client-side via `checkDocUx()`. Modo leitura exibe seções distintas por tipo de pessoa detectado via `$isPJ` (PHP-side, baseado na presença do campo `cnpj`). Campos do tipo oposto são limpos no toggle para evitar envio cruzado.

### 4.37 Orders — Intenção de Compra e Rastreio de Usuário (v3.21)
*   **Decisão:** Refatorar o fluxo completo de compra de Créditos/Assinatura para registrar a "Intenção de Compra" (Order) antes de chamar o gateway Asaas, resolver a ausência de rastreio do usuário que origina a compra, e corrigir o bug de conversão que inflava créditos em 100x.
*   **Problemas Resolvidos:**
    *   **Bug Crítico:** O JavaScript (`index.blade.php`) calculava `Math.floor(price * 100)` para "créditos", resultando em cobranças 100x maiores no Asaas (R$ 5,00 → 500 créditos → R$ 500,00 de cobrança).
    *   **Ausência de `user_id`:** Nenhuma transação de crédito (`saas_transactions`) guardava qual usuário iniciou a compra. O DataGrid sempre exibia "(Sistema)".
    *   **Ausência de "Fonte da Verdade" local:** O sistema só gravava algo no banco APÓS o pagamento cair, criando um "voo cego" sobre intenções de compra não concluídas.
    *   **Workaround frágil:** O `AsaasService` criava registros `credit_pending` na tabela `saas_transactions` como hack para rastrear sessões de checkout, poluindo o ledger.
*   **Mudanças:**
    *   **Tabela `saas_orders` (Nova):** Criada via migration no banco do Tenant. Campos: `tenant_id`, `user_id` (FK → `users`), `type` (ENUM: `ai_credits`, `subscription`), `value` (DECIMAL — proporcional 1:1), `asaas_payment_id`, `asaas_checkout_session_id`, `status` (`PENDING` → `PAID` / `EXPIRED` / `CANCELED`), `description`, timestamps.
    *   **Model `SaasOrder`:** Eloquent com relationships `user()` (BelongsTo) e `transactions()` (HasMany), helpers `isPending()`, `isPaid()`, `markAsPaid()`.
    *   **Controller `SubscriptionCheckoutController`:** Refatorado para criar uma `SaasOrder` com `status=PENDING` e `user_id=auth()->id()` ANTES de chamar a API do Asaas. Recebe apenas `value` (R$, float) em vez de `credits` + `price` separados. Proporção 1:1 forçada no backend.
    *   **`AsaasService`:** (a) Removido workaround de `SaasTransaction` com `type=credit_pending`. (b) Novo formato de `externalReference`: `"order_{id}"` em vez de `"{tenantId}|tipo|valor"`. (c) Assinaturas de `createCreditCheckout` e `createSubscriptionCheckout` agora retornam `array{checkout_url, session_id}` e recebem `$orderId`. (d) `syncTenantPayments()` decomcomposto em dois processadores: `processOrderBasedPayment()` (v3.21+) e `processLegacyPayment()` (retrocompatibilidade).
    *   **`AsaasWebhookController`:** Refatorado com 4 rotas de resolução priorizadas: (1) Order-based via `"order_{id}"` no externalReference, (2) Lookup de `SaasOrder` por `checkoutSession`, (3) Parser legado `"{tenantId}|tipo|valor"`, (4) Fallback single-tenant por valor.
    *   **Frontend (`index.blade.php`):** (a) Removido `Math.floor(price * 100)` — agora passa o valor direto (1:1). (b) Payload do fetch alinhado: envia `value` em vez de `credits` + `price`. (c) Fix do bug no `.then()` que executava `redirect` E `alert` simultaneamente. (d) Botão "Ver Meus Pedidos" adicionado ao cabeçalho da seção de checkout.
    *   **`SaasOrdersDataGrid` (Novo):** DataGrid para listagem de pedidos com colunas de Tipo (badge), Valor, Status (badge colorido), Usuário, ID Asaas e Data. Filtro por `tenant_id` canônico.
    *   **`SaasOrderController` (Novo):** Controller skinny para renderizar a view de pedidos e servir o DataGrid via AJAX.
    *   **Rota `admin/juridico/orders`:** Registrada em `admin-saas.php` como `admin.lawfirm.saas.orders.index`.

### 4.38 Hardening de Isolamento Multi-Tenant e Identidade de Usuário (v3.22)
*   **Decisão:** Eliminar os riscos de vazamento de dados inter-tenants (cross-tenant data leakage) nos históricos financeiros e garantir a rastreabilidade completa (`user_id`) das adições de crédito para responsabilização precisa.
*   **Mudanças:**
    *   **Isolamento Estrito (`SaasTransactionsDataGrid` e `SaasAdditionsDataGrid`):** Refatorados para forçar filtragens canônicas utilizando `core()->getCurrentTenantId()` em todas as queries. Prevenção absoluta contra envenenamento de chave onde um usuário poderia visualizar transações ou integrações de outro escritório pelo painel SaaS.
    *   **Identidade e Auditoria:** Coluna `user_id` propagada rigorosamente por todo o ciclo de vida da transação. As requisições de compras geradas pelo `SubscriptionCheckoutController` atrelam o cliente autenticado até a conversão final validada no `AsaasWebhookController`.
    *   **Rateio de Crédito Preciso (1:1):** Eliminação de inflacionamentos residuais nas conversões de gateways de pagamento, solidificando a lógica arquitetural de paridade integral (1 BRL = 1 Token de IA).

### 4.39 Restrição de Funcionalidades IA para Contas Trial (v3.23)
*   **Decisão:** Limitar a geração de prompts avulsos na interface dos Assistentes de IA para tenants que estão no modelo "Trial", preservando todavia a capacidade de "Execução com IA" para degustação do fluxo completo.
*   **Mudanças:**
    *   **Frontend (Assistentes e Leads):** As views `index.blade.php`, `show.blade.php` e `lead-tools-panel.blade.php` agora consultam `$isTrial` iterando o status via `MotherShipService::getTenantConfig()->classification`. Quando ativo, o botão "Gerar Prompt" é substituído por um alerta 🚫 visual indicando indisponibilidade no trial. O botão "Executar com IA" não sofre restrições.

### 4.40 Melhorias de UX — Assistentes de IA (v3.24)
*   **Decisão:** Aprimorar a usabilidade das interfaces de Assistentes de IA com exportação para PDF, janela modal mais ampla e melhor aproveitamento de espaço entre colunas.
*   **Mudanças:**
    *   **Botão "Salvar PDF" (`index.blade.php`, `show.blade.php`, `lead-tools-panel.blade.php`):** Adicionado botão `📄 Salvar PDF` ao lado do botão `📋 Copiar` na seção de resultado. O botão só aparece após a geração de conteúdo (segue a visibilidade de `lf-assist-copy-btn`). A exportação usa a API nativa `window.open()` + `window.print()` do browser: o conteúdo Markdown renderizado é injetado em uma janela nova com estilos tipográficos limpos, e o diálogo de impressão/PDF do SO é acionado automaticamente. Nenhuma dependência de biblioteca backend (ex: DomPDF) foi necessária.
    *   **Aumento da Janela Modal (`max-width`):** O `.lf-modal-dialog` em `index.blade.php` e `lead-tools-panel.blade.php` teve o `max-width` elevado de `960px`/`900px` para **`1200px`**, proporcionando área visual significativamente maior para leitura de documentos jurídicos gerados.
    *   **Layout Assimétrico das Colunas (40% / 60%):** O `grid-template-columns` do `.lf-modal-body` foi alterado de `1fr 1fr` (50/50) para `1fr 1.5fr` (40/60) em todas as três views. A coluna de resultado/resumo da IA recebe ~60% da largura total, priorizando a leitura do conteúdo gerado sobre o formulário de entrada.
    *   **Padrão de Botões de Ação:** Os botões de resultado (`Salvar PDF`, `Copiar`, `Salvar como Nota`) são agrupados em um `.flex.gap-2` dentro do `.lf-result-header`, garantindo alinhamento visual consistente.
    *   **`window.lfAssistants.pdf()` e `window.lfToolsPanel.pdf()`:** Métodos adicionados aos objetos JS globais respectivos, seguindo o padrão de extensão de `window.*` já estabelecido na arquitetura de frontend (Seção 6.1).

### 4.42 Hardening de Conformidade — Zero Storage Direto e Zero env() em Produção (v3.26)
*   **Decisão:** Eliminar as últimas violações das Regras de Ouro identificadas em auditoria estrutural pós-v3.25: uso direto de `Storage::` fora do `SaasFileService` e chamadas `env()` sem fallback via `MotherShipService` em código de produção crítico.
*   **Mudanças:**
    *   **`ProcessoObserver` (Legal):** As chamadas `Storage::disk('s3')->delete()` nos hooks `deleting()` para remoção de `Anexos` e `ProcessDocuments` foram substituídas por `$this->fileService->delete()` via injeção de dependência do `SaasFileService`. O observer agora resolve sempre o bucket correto do tenant ao excluir arquivos em cascata.
    *   **`ProcessDocumentController` (GED) — Downloads:** Os métodos `download()` e `downloadAttachment()` foram refatorados de `Storage::download()` / `Storage::disk()` para `SaasFileService::get()` + `SaasFileService::mimeType()` com resposta HTTP streaming manual (`Content-Disposition: attachment`), garantindo acesso ao bucket isolado por tenant.
    *   **`ProcessDocumentController` (GED) — WhatsApp:** Os fallbacks `env('EVOLUTION_INSTANCE_NAME')` nos métodos `importTemplate()` e `sendChecklist()` foram substituídos por `MotherShipService::getEvolutionConfig()` com abort elegante (log de erro + flash `warning`) quando a instância não está configurada no MotherShip. O fluxo principal (importação do template de documentos) não é interrompido.
    *   **`SendPrazoWhatsapp` (Whatsapp):** Substituído `env('EVOLUTION_INSTANCE_NAME')` direto por `MotherShipService::getEvolutionConfig()`. Quando não configurado, o listener retorna com `Log::error()` sem exception — prevenindo falhas silenciosas em ambientes SaaS.
    *   **`AssistantController::execute()` (AI):** Substituído `env('N8N_WEBHOOK_BASE_URL')` por `MotherShipService::getN8nConfig()`. Retorna HTTP **503** com mensagem clara ao usuário quando o nó N8N não está configurado para o tenant — sem vazar variáveis de infraestrutura entre clientes.

### 4.43 Robô Agendador de Prazos e Hardening de Ações Vue (v3.27)
*   **Decisão:** Automatizar o envio de lembretes de prazos no WhatsApp para Clientes e Advogados, mitigando o uso invasivo do Javascript do Krayin nas DataGrids para garantir o disparo seguro de eventos de opt-in.
*   **Mudanças:**
    *   **Tabelas & Modelos:** Adicionada `whatsapp_responsavel` em `Processos` com cast estrito de `varchar(50)` e um Mutator. O Mutator higieniza nativamente a máscara do frontend preservando apenas os números inteiros antes do banco. Adicionada a flag `notificar_whatsapp` e 3 timestamps de controle idempotente (`ultima_notificacao_5d`, `1d`, `0d`) em `Prazo`.
    *   **Scheduler e Idempotência:** Criado o comando Artisan `SendScheduledPrazoNotifications` atrelado ao `Kernel.php` (dailyAt 07:00). O script consolida lembretes diários, agrupa os envios matinais por Advogado (reduzindo SPAM via array formatting) e despacha os blocos de texto para as APIS do MotherShip/Evolution baseando-se nos 7 templates `system.php` do Tenant.
    *   **Docker Swarm (Novo Padrão):** O stack de deployment impõe estritamente que, para tarefas agendadas, deve existir um container parceiro e avulso nomeado `scheduler`, rodando um loop infinito isolado de `artisan schedule:run`, sem misturar responsabilidades com os workers do FPM.
    *   **Blindagem de Ações Vue (DataGrids):** Em Krayin V2 (VueJS), injeções dinâmicas de botões dependentes de `addAction(['method' => 'POST'])` falham de forma silenciosa ou caem em loops de Axios 500 caso o form oculto perca sincronia do Token CSRF. **Padrão Estabelecido:** Toda "ação de um-clique" no DataGrid (ex: *Ativar Robô*) deve obrigatoriamente forçar o tipo `GET`, excluindo o atributo `confirm_text`. Isso oblitera a intercepção nativa do Javascript e devolve o controle ao link nativo `<a href...>` para recarregar com segurança em rotas do controller que possuam `redirect()->back()`.

### 4.41 Rastreamento de Custos e Metadados do n8n (v3.25)
*   **Decisão:** Rastrear os custos reais de consumo de IA (`total_cost`, `real_cost`) e metadados de execução (`execution_id`, `model`, `node_name`) extraídos diretamente do webhook de retorno do n8n para a tabela `lawfirm_assistant_history` (Tenant DB).
*   **Mudanças:**
    *   **Migration e Model:** Adicionadas colunas nativas e suportadas por `$fillable` ao model `AssistantHistory` em "AI/Models", com suporte a decimais de precisão máxima (`decimal:4`) para lidar com micropagamentos da OpenAI/Anthropic.
    *   **Parser Desacoplado no Job:** O `ProcessAiAssistant.php` atuando como Worker Assíncrono agora intercepta o payload final do n8n buscando um sufixo opcional (apendado) no formato ` - [{JSON}]`. Este acoplamento garante retrocompatibilidade: separa graciosamente o conteúdo textual jurídico (apresentável ao advogado) dos metadados de bilhetagem técnica, salvando as informações exatas da transação do nó no banco do Tenant, alimentadas através das APIs de conversão USD/BRL do MotherShip.

### 4.44 Resiliência Docker e Isolamento Redis Swarm (v3.28)
*   **Decisão:** Impedir a colisão inter-tenant de Filas Assíncronas (Jobs/WhatsApp) no Docker Swarm e evitar o corrompimento de logs causados por permissões `root` nos Workers.
*   **Problema (Permissão de Logs):** A execução do Queue Worker do Laravel na stack Docker (via `command: php artisan queue:work`) ocorria primariamente como usuário Root, gerando um `laravel.log` blindado que o `apache` (`www-data`) não conseguia editar, estourando erro 500 em produção.
*   **Problema (Colisão Redis):** Múltiplos Tenants apontando o `QUEUE_CONNECTION` para o mesmo container de `redis` no Swarm compartilhavam a mesma fila, fazendo com que o Worker do Tenant A consumisse disparos de WhatsApp solicitados pelo Tenant B.
*   **Mudanças:**
    *   **Dockerfile:** Instrução `pecl install redis` adicionada para garantir o carregamento ultra-rápido via cache Nativo PHP (abandonado o fallback para arquivos de disco).
    *   **Entrypoint Inteligente (`docker/entrypoint.sh`):** Implementado interceptador de argumentos (`$@`). Se o comando enviado pela stack for de processamento em background (workers/schedulers), o script adota encapsulamento via `su -s /bin/sh www-data -c "$*"`. Isso garante que tudo criado no container rode legitimamente sob o usuário restrito do web-server.
    *   **Orientação Stack Docker Compose:** Obrigatório estipular o `REDIS_PREFIX: {tenant_id}_` nas âncoras (`x-environment`) de subida de novos Tenants para isolar atomicamente chaves e chaves de lock para instâncias isoladas, impossibilitando cross-tenant job execution.

### 4.45 Traceabilidade de IA e Origem de Lead (v3.28)
*   **Decisão:** Melhorar a rastreabilidade das execuções de IA permitindo identificar instantaneamente de qual Lead a análise foi solicitada.
*   **Mudanças:**
    *   **Persistência:** O campo `lead_id` foi adicionado ao fluxo de salvamento em `AssistantController`, garantindo que execuções disparadas pelo painel de ferramentas de leads sejam vinculadas ao registro pai. 
    *   **UI (DataGrid):** Adicionada a coluna "Origem" ao `AssistantHistoryDataGrid`, exibindo o ID do Lead com link direto para a ficha de edição.
    *   **UX (Detalhes):** A view de detalhes da execução (`show.blade.php`) agora exibe um link de contexto para o Lead e teve o padding interno da caixa "Dados de Entrada" corrigido para manter a consistência visual com os demais blocos de resultado.

*   **ACL Granular:** O arquivo `Config/acl.php` foi expandido para incluir ramificações explícitas de CRUD (`create`, `edit`, `delete`, `view`) para cada módulo do LawFirm. Isso é mandatório para que o componente `v-tree-view` da interface de Roles renderize os checkboxes de ativação individual.

### 4.47 Integração WhatsApp CRM: Importação de Histórico e Portal Modals (v3.29)
*   **Decisão:** Permitir a importação direcionada (por intervalo de datas) do histórico de conversas do WhatsApp para dentro do Processo, operando em segundo plano e blindado contra interceptações do SPA Vue.js.
*   **Mudanças:**
    *   **Persistência (`law_processo_whatsapp_messages`):** Nova tabela para armazenar mensagens, vinculada ao `Processo`. Inclui campo `payload` (JSON) para telemetria bruta e `is_from_me` para distinção entre Advogado (Sistema) e Cliente.
    *   **Background Jobs (`ImportProcessoWhatsappMessages`):** Lógica de ingestão assíncrona que consome a Evolution API v2, processa o histórico e envia uma notificação no WhatsApp pessoal do Advogado (via robô) ao concluir o processamento.
    *   **Tagging de Mídia:** Como anexos binários (áudio/vídeo) não são baixados para o servidor local, o sistema aplica um parser visual que injeta emojis de identificação (`📷 [Imagem]`, `🎵 [Áudio]`, `🎥 [Vídeo]`) no texto da mensagem baseando-se no tipo do WAMessage.
    *   **Portal Dialog Pattern (UI):** Interfaces de chat complexas reescritas com estilos **100% inline** e IDs únicos, forçando a ancoragem no `document.body` via clique para evitar que o Vue Router do Krayin intercepte os blocos HTML como se fossem trocas de página.

### 4.48 Refinamentos de Interface e Resolução de Depreciações (v3.30)
*   **Decisão:** Melhorar a legibilidade das tabelas de auditoria de IA e garantir a compatibilidade do sistema com versões modernas do PHP (8.4+).
*   **Mudanças:**
    *   **DataGrid de IA (`AssistantHistoryDataGrid`):** 
        *   Adicionada a coluna **Cliente**, realizando um `LEFT JOIN` com a tabela `persons` através de `leads` para exibir explicitamente o nome da pessoa física/jurídica na triagem.
        *   Largura da coluna `ID` fixada em `60px` e coluna de `Origem` fixada em `100px`. O link longo (título do lead) foi movido do texto principal para o atributo `title` (tooltip), mantendo a visualização da tabela mais enxuta e densa.
    *   **PHP 8.4+ Compatibilidade (`config/database.php`):** A constante estática `PDO::MYSQL_ATTR_SSL_CA` disparava erro de depreciação. O artefato foi substituído ativamente pelo seu valor literal inteiro (`1007`), mantendo retrocompatibilidade com o driver interno do MySQL sem triggar warnings no terminal e logs do servidor.

### 4.49 Multi-Import de Histórico do WhatsApp (v3.31)
*   **Decisão:** Permitir múltiplas importações de histórico de WhatsApp por Processo, organizadas por período e contato.
*   **Mudanças:**
    *   **Tabela `law_whatsapp_imports`:** Nova tabela pai que registra cada sessão de importação (processo, contato, período, contagem de msgs, status `processing/completed/failed`). Cada importação é rastreada individualmente.
    *   **FK `import_id` em `law_processo_whatsapp_messages`:** Cada mensagem agora pertence a uma sessão de importação específica (nullable para retrocompatibilidade).
    *   **Model `WhatsappImport`:** Novo modelo Eloquent com `HasMany → ProcessoWhatsappMessage`, `BelongsTo → Processo`, `BelongsTo → User` (imported_by), e helpers `formattedPeriod()`, `displayPhone()`.
    *   **Job `ImportProcessoWhatsappMessages`:** Refatorado com try/catch completo. Cria `WhatsappImport` no início com `status=processing`, marca `completed` ao final com `message_count` e `contact_name` capturado da primeira mensagem do interlocutor.
    *   **Controller:** Novo endpoint `listImports()` retorna JSON com lista de importações. `fetchMessages()` aceita `?import_id=X` para filtragem por sessão.
    *   **Frontend (Portal Dialog):** Modal de histórico agora exibe uma **barra de tabs horizontais** com pills dinâmicos para cada importação (contato, período, qty msgs, status). O botão "📋 Todas" exibe a conversa unificada.
    *   **Botões Always-On:** Os botões "Importar WhatsApp" e "Histórico WhatsApp" agora são **sempre visíveis** nas telas de `edit` e `show`, mesmo sem importações anteriores.

### 4.50 Consolidação e Supressão UI do Escavador (v3.32)
*   **Decisão:** Padronizar e mapear de forma completa todos os 51 endpoints das APIs V1 e V2 do Escavador para suporte a operações automatizadas no backend, enquanto a interface (UI) do "Assistente Jurídico" é purgada de todo o ruído visual e endpoints de infraestrutura.
*   **Mudanças:**
    *   **Backend (`EscavadorService`):** O `SERVICE_MAP` foi ampliado cobrindo 51 rotas, com resoluções precisas de méotodos HTTP (ex: correção do `SOLICITARATUALIZAODEUMPROCESSO` para `POST`) e autenticações locais.
    *   **Frontend (`index.blade.php`):** A matriz local de serviços  (`$allCards`, `SVC_INFO`) reteve a totalidade dos endpoints (garantindo estabilidade e possibilidade de consultas via código), porém a renderização do DOM foi suprimida condicionalmente usando `@continue` no Blade.
    *   **UX (Limpeza da Filter Bar):** Filtros puramente técnicos ("Monitoramento", "Callbacks", "Assíncronos", "Outros", bem como as opções de chaveamento técnico "API V1" e "API V2") foram completamente obliterados do HMTL. O design do painel Escavador agora foca exlusivamente no *Business Domain* (Processo, Pessoa, Empresa, Advogado, Relatórios Jurídicos, Jurisprudência, Legislações).

### 4.51 Integração DataJud Pública e Assistente Jurídico (v3.33)
*   **Decisão:** Integrar o serviço público do CNJ (DataJud) como o primeiro provedor alternativo agnóstico dentro do painel de Assistentes Jurídicos, utilizando isolamento completo de domínio e precificação dinâmica.
*   **Mudanças:**
    *   **MotherShipService & Rotas Core:** Criado o método `MotherShipService::getDataJudConfig()` para resgatar dinamicamente credenciais do nó `datajud` (URL e API Key), além dos custos baseados na chave `datajud_price_consulta_publica`, livrando o módulo inteiramente de hardcodes de credenciais ou dependência de `.env`.
    *   **UI/UX (Assistente Jurídico):** O card `DATAJUD_CONSULTA_PUBLICA` foi injetado na grid via `index.blade.php`. Graças ao refactoring prévio do Alpine.js / Vanilla JS, não houve conflito e todas as chamadas assíncronas fetch roteadas para `lawfirm.datajud.servico` foram unificadas.
    *   **Arquitetura Isolada de Domínio:** Refletindo princípios de Clean Code, toda a lógica de `DataJud` foi encapsulada:
        *   `src/DataJud/Services/DataJudService.php` responsável pelo request a `api-publica.datajud.cnj.jus.br`.
        *   `src/DataJud/Http/Controllers/DataJudController.php` como Skinny Controller.
    *   **Tratamento Financeiro (Ledger):** Como o CRM opera via SaaS, cada clique na UI do DataJud é interceptado na camada do Service, validando se o token da assinatura é suficiente (`ai_tokens_balance`). Ocorrendo a transação, preparamos o estorno via `catch` em caso de falha da request ou registramos permanentemente o custo como `debit` nas `saas_transactions`, emulando fidedignamente o controle financeiro e histórico de consumo.

### 4.52 Auditoria de Conformidade DDD e Consolidação do Controlador WhatsApp (v3.34)
*   **Decisão:** Corrigir as últimas violações residuais de bounded context identificadas em auditoria automática pós-v3.33: `WhatsappController` com namespace errado fora do domínio, `BaseController` fantasma, `Log::debug` de desenvolvimento em produção e falta de clareza nos comentários da configuração do `.env` do Evolution.
*   **Mudanças:**
    *   **Consolidação (Whatsapp):** O arquivo `src/Http/Controllers/WhatsappController.php` (namespace `SuiteZap\LawFirm\Http\Controllers` — ERRADO) foi **eliminado**. Toda a sua lógica foi absorvida pelo `src/Whatsapp/Http/Controllers/ConnectionController.php` (namespace correto `SuiteZap\LawFirm\Whatsapp\Http\Controllers`). O método `testNotification()` que existia apenas no arquivo legado foi portado para o `ConnectionController`.
    *   **Refatoração (ConnectionController):** Métodos renomeados de `connect()`/`status()` para `getQrCode()`/`getStatus()` (casando com os nomes de rota existentes). Dois blocos `Log::info` de debug removidos do `index()`. Import `MotherShipService` movido para o topo (elimina FQCN inline). Tipo de retorno de `getInstanceName()` declarado como `: string`.
    *   **Rota Atualizada (`admin-whatsapp.php`):** O `use` e o `Route::controller` foram atualizados de `WhatsappController` para `ConnectionController`. Nomes de rota e verbos HTTP idênticos — zero breaking change.
    *   **Remoção (BaseController):** O arquivo `src/Http/Controllers/Admin/BaseController.php` (14 linhas, classe fantasma não herdada por nenhuma classe do pacote) foi **deletado**. Confirmado: zero referências `extends BaseController` no codebase.
    *   **Zero Root Controllers:** O diretório `src/Http/Controllers/` agora contém **0 arquivos PHP** — bounded context 100% limpo.
    *   **Config Documentada (`Config/lawfirm.php`):** O bloco `evolution` foi redocumentado com "REGRA ZERO", deixando explícito que `env('EVOLUTION_*')` são **dev fallback only** e que `MotherShipService::getEvolutionConfig()` é a fonte canônica mandatória em produção.
    *   **Cleanup (`ProcessoController`):** Dois blocos `Log::debug` (STORE/UPLOAD DEBUG) removidos dos métodos `store()` e `update()` — artefatos de desenvolvimento não adequados para produção.

### 4.53 Tabela de Triagem de Leads (LeadTriagem) (v3.35)
*   **Decisão:** Criar a tabela `lead_triagem` para armazenar o resultado estruturado das triagens realizadas pelos assistentes de IA (área, assunto, urgência, tipo, tipo de agente e objetivo).
*   **Mudanças:**
    *   **Model:** Criada a Entidade `LeadTriagem` na pasta `src/AI/Models`, vinculada à tabela `lead_triagem` e relacionando o `lead_id` com o Krayin.
    *   **Isolamento:** A modificação atende aos padrões de Bounded Context do Domínio AI ao focar dados provenientes ou interpretados pelas rotinas automáticas de Triagem.

### 4.54 Refinamento da Tabela de Triagem de Leads (v3.36)
*   **Decisão:** Separação dos campos avançados num novo arquivo de modelagem (`add_extra_fields...`) garantindo aplicação das colunas (`risco`, `probabilidade`, `cta`, etc.) em ambientes distribuídos.

### 4.55 Controle de Limite de Usuários por Status Ativo (v3.36)
*   **Decisão:** Desacoplar a criação de contas da verificação de limite do plano. O sistema deve permitir a criação de quantas contas forem necessárias, bloqueando apenas a **ativação** de contas quando o plano atingiu seu limite de usuários ativos (`max_users`).
*   **Problema:** O `UserObserver` bloqueava a criação de qualquer novo usuário, independentemente do status (`ativo`/`inativo`) pretendido. Não havia verificação no evento de edição (ativação).
*   **Mudanças:**
    *   **`UserObserver::creating`:** A guarda passou a ser condicional — o limite só é verificado se `$user->status == 1`. Criação de contas inativas é sempre permitida.
    *   **`UserObserver::updating` (novo):** Intercepta a edição de um usuário. Se o campo `status` foi alterado (`isDirty('status')`) e o novo valor é `1` (ativo), verifica o limite. Lança `ValidationException` impedindo a reativação caso o plano já esteja no limite.
    *   **Campo de erro:** Alterado de `email` para `status` para apresentar o aviso de limite próximo ao campo correto no formulário.

### 4.56 Consolidação de Templates WhatsApp (v3.37)
*   **Decisão:** Harmonizar a estrutura de configuração de templates de mensagens WhatsApp no arquivo `system.php` para coincidir com as chamadas de API de configuração espalhadas pelo código-fonte.
*   **Problema:** O arquivo `system.php` utilizava grupos descritivos (`processos`, `financeiro`, `juridico`, `agendador`), enquanto os controladores e listeners buscavam sistematicamente sob o grupo genérico `messages` (ex: `lawfirm.whatsapp_templates.messages.new_prazo_client`). Isso causava retorno vazio em consultas críticas e o erro "Template não configurado".
*   **Mudanças:**
    *   **Consolidação:** Todas as subseções de templates foram fundidas em um único grupo com a chave `lawfirm.whatsapp_templates.messages`.
    *   **UI Dinâmica:** A interface de Ajustes agora exibe todos os templates de mensageria em uma única tela unificada ("Templates de Mensagens").
    *   **Estabilidade:** Garante que qualquer recurso do sistema que dispare WhatsApp consiga resgatar o texto configurado sem disparar erros de template inexistente.

### 4.57 Agenda Jurídica — FullCalendar Vanilla JS (v3.38)
*   **Decisão:** Criar uma Agenda Jurídica unificada no domínio **Legal** que combina Atividades do Krayin (Reuniões/Ligações da tabela `activities`) com Prazos do LawFirm (tabela `law_processo_prazos`) em uma única interface de calendário interativa, usando FullCalendar v6 em Vanilla JS para evitar conflitos com o Vue.js do Krayin (Regra 6.1).
*   **Mudanças:**
    *   **Service (`AgendaService`):** Busca atividades do Krayin (`Activity::where('user_id', auth()->id())`) e prazos do LawFirm (`Prazo::whereHas('processo')`), unificando ambos no formato JSON do FullCalendar com cores diferenciadas (🔵 Atividades, 🔴 Prazos Pendentes, 🟢 Prazos Concluídos, ⚫ Atividades Concluídas). Suporta atualização de datas via drag-and-drop para ambos os tipos.
    *   **Controller (`AgendaController`):** Skinny controller delegando toda lógica ao `AgendaService`. 3 rotas: `index` (view), `getEventos` (JSON), `updateDragDrop` (POST).
    *   **View (`Legal/agenda/index.blade.php`):** FullCalendar v6 via CDN (jsdelivr), 4 views (Mês/Semana/Dia/Lista), locale `pt-br`, drag-and-drop com REPLACE_ID Pattern (Regra 6.6), CSRF lido at event time (Regra 6.2), tooltips com nome do processo, e legenda visual com badges coloridos.
    *   **Menu:** Item "Agenda" adicionado ao menu lateral com `sort => 2` (entre Processos e Prazos), usando `icon-calendar`.
    *   **ACL:** Grupo `lawfirm.agenda` com permissões granulares `view` (visualizar calendário) e `edit` (drag-and-drop de eventos).
*   **Imunidade ao Krayin:** O calendário usa bibliotecas independentes (Vanilla JS) e vive em rota protegida sob o domínio Legal. Atualizações do Krayin Core (Vue.js, Pipeline, Calendar) não afetam esta agenda.
*   **Zero Impacto Mothership:** Nenhuma alteração no banco `mothership` ou em `app_config`. Feature 100% tenant-side.

### 4.58 Harmonização UI/UX de Prazos e Agenda (v3.39)
*   **Decisão:** Padronizar a seleção de datas e modais complexos no painel de Prazos com o design system do Krayin, abandonando a tag genérica `<input type="date">` e modais iframe instáveis em favor de janelas desacopladas.
*   **Mudanças:**
    *   **Flatpickr e Dinamismo Vue.js:** Adotado o componente `<x-admin::flat-picker.datetime>` para inputs de Processos. No formulário de Prazos (injeção dinâmica via `insertAdjacentHTML`), o Laravel não transpila componentes Vue em runtime. Como contorno seguro, injetamos a estrutura visual HTML (wrapper `<span>` e ícone `<i>`) e acionamos manualmente `new window.Flatpickr()` com `setTimeout`, mitigando o ciclo de vida do SPA.
    *   **Clean Modal Pattern:** Eliminado o modal de iframe para exibir o calendário dentro dos processos. Foi adotado o uso de `window.open` acionando a rota `/admin/juridico/agenda?clean=true`. Esta flag carrega o layout `admin::layouts.anonymous` (desprovido de navbars e menus asides), permitindo total responsividade sem sobreposições.
    *   **Harmonização Prazos x Atividades:** Prazos passaram a suportar a entrada de **Horas**. O `AgendaService.php` agora serializa prazos com `toIso8601String()` e `allDay: false`. Metadados antes exclusivos das Atividades (`isDone`, `comment` e `processo_id`) foram incluídos nos `extendedProps` e o emoji canônico mapeado como `🏛️ Audiência / Prazos`, tornando ambos isomórficos aos olhos do EventClick da Agenda Jurídica.

## 5. Auditoria Estrutural e Mapa de Dívida Técnica (v3.23)

O projeto atingiu seu nível máximo de maturidade no isolamento de domínios. Todos os controllers órfãos e arquivos residuais que inflavam artificialmente a raiz foram completamente migrados.

### 5.1 Diagrama de Classes (Situação Atual)

```mermaid
classDiagram
    direction TB

    %% --- DOMÍNIOS CORE SÓLIDOS (verde) ---
    namespace Legal_Domain {
        class Processo
        class Prazo
        class CaseChecklist
        class ProcessoController
        class ChecklistController
        class DeadlineService
    }

    namespace Financial_Domain {
        class Financial_Model
        class FinancialService
        class FinancialController
        class FinancialDashboardService
    }

    namespace GED_Domain {
        class ProcessDocument
        class DocumentService
        class ProcessDocumentController
        class SaasFileService
    }

    namespace Whatsapp_Domain {
        class ConnectionController
        class EvolutionService
        class SendPrazoWhatsapp
    }

    %% --- DOMÍNIOS EM MATURAÇÃO (amarelo) ---
    namespace AI_Domain {
        class AssistantController_Admin
        class AssistantHistoryController_Admin
        class ProcessAiAssistant_Job
        class AssistantTemplate
        class AssistantHistory
        class LeadTriagem
        class N8nService
    }

    namespace Escavador_Domain {
        class EscavadorController
        class EscavadorService
        class EscavadorRequest
        class EscavadorMonitoramento
        class WebhookController
        class EscavadorHistoryController_Admin
    }

    %% --- DOMÍNIO EM MATURAÇÃO: DataJud ---
    namespace DataJud_Domain {
        class DataJudController
        class DataJudController_Admin
        class DataJudService
    }

    %% --- INFRAESTRUTURA SAAS (azul) ---
    namespace SaaS_Domain {
        class MotherShipService
        class SaasTransactionController
        class SaasOrderController
        class Subscription
        class Tenant
        class AsaasService
        class SaasOrder
        class UserObserver
    }

    %% Relações
    Legal_Domain ..> GED_Domain : DocumentService
    Legal_Domain ..> Financial_Domain : Ajax
    Financial_Domain ..> Whatsapp_Domain : prepareBillingWhatsapp
    Financial_Domain ..> GED_Domain : SaasFileService
    Financial_Domain ..> SaaS_Domain : MotherShipService
    Escavador_Domain ..> Whatsapp_Domain : Alertas Callback
    Escavador_Domain ..> SaaS_Domain : infrastructure_nodes
    AI_Domain ..> SaaS_Domain : ai_tokens_balance
    DataJud_Domain ..> SaaS_Domain : MotherShipService
    DataJud_Domain ..> SaaS_Domain : saas_transactions
    GED_Domain ..> SaaS_Domain : SaasFileService → S3
    Whatsapp_Domain ..> SaaS_Domain : getEvolutionConfig

    style Legal_Domain fill:#2a9d8f,color:#fff
    style Financial_Domain fill:#2a9d8f,color:#fff
    style GED_Domain fill:#2a9d8f,color:#fff
    style Whatsapp_Domain fill:#2a9d8f,color:#fff
    style AI_Domain fill:#e9c46a,color:#000
    style Escavador_Domain fill:#e9c46a,color:#000
    style DataJud_Domain fill:#e9c46a,color:#000
    style SaaS_Domain fill:#264653,color:#fff
```

### 5.2 Histórico de Dívida Técnica (Resolvida)

**✅ Limpeza v3.8 – v3.14:**
*   `src/Models/*`: Removidos models duplicados e realocados.
*   `src/Listeners/`, `src/Observers/`, `src/Events/`, `src/Rules/`, `src/DataGrids/`: Arquivos órfãos migrados integralmente para seus domínios. A última relíquia (`SafeActivityDataGrid`) alocada em `Legal` na v3.14, pastas deletadas.
*   `src/Services/Whatsapp`: Consolidado no domínio `Whatsapp`.
*   `src/Services/N8nService.php`: Movido para `AI`.

**✅ Hardening v3.17:**
*   `FinancialController`: `env()` fallback removido (→ HTTP 503 explícito). `Storage::` direto substituído por `SaasFileService`. Método `sendWhatsappBilling()` esvaziado (~90→~35 linhas) com lógica extraída para `FinancialService::prepareBillingWhatsapp()`.
*   `SaasFileService`: Expandido com `get()`, `mimeType()` e `storeRaw()` — interface pública completa para manipulação de arquivos sem expor `Storage::` a callers.
*   `admin-whatsapp.php`: Criado como arquivo de rota dedicado ao domínio `Whatsapp`; removido de `admin-saas.php`.
*   Diretórios fantasma `src/Events/`, `src/Observers/`, `src/Rules/`, `src/Listeners/`, `src/Services/` deletados definitivamente.

**✅ Hardening v3.18:**
*   Idempotência implementada para o ecossistema Asaas visando concorrência atômica nos creditamentos `saas_transactions` (Zero Race Conditions/Double Spend).
*   Expansão formal do schema `mothership.tenants` para acomodar o `asaas_node_id`, resolvendo infraestruturas fragmentadas em multi-franquia SaaS.

**🟢 Status Atual — Dívida Técnica Zero (v3.36):**
A pasta `src/Http/Controllers/` contém **0 arquivos PHP** — todos os Controllers estão dentro de seus respectivos Bounded Contexts. Todos os domínios possuem estrutura `Models/Http/Controllers/Services/DataGrids` completa. Nenhum arquivo de lógica/negócio reside fora de seu domínio. O pacote está preparado para escala SaaS multi-tenant corporativa com escopo e auditorias de usuário robustas.

## 6. Padrões de Frontend (UI/UX)

### 6.1 Conflito Vue.js vs Alpine.js
O Krayin (Core) utiliza uma instância global do Vue.js que processa todo o DOM. Isso causa conflitos fatais ao tentar usar Alpine.js dentro de views Blade (`.blade.php`), pois o Vue remove as tags `<template>` e diretivas antes que o Alpine possa processá-las.

**Solução Padrão (The "Financial Tab" Pattern):**
Para componentes complexos dentro do Admin (ex: Gestão Financeira, Checklists, Painel de Ferramentas de Lead):
1.  **Server-Side Rendering:** Use Blade (`@foreach`) para renderizar o estado inicial.
2.  **Interatividade:** Use **Vanilla JavaScript** com Event Delegation ou `MutationObserver` para reagir a mudanças no DOM do Vue.
3.  **Estado:** Armazene estado simples em variáveis globais ou `dataset` attributes.
4.  **Avoid:** Não use Alpine.js (`x-data`, `x-for`) nem componentes Vue misturados nessas views, a menos que sejam componentes Vue registrados globalmente no Krayin.

### 6.2 Requisições AJAX & CSRF
Devido ao wrapper `$.extend.bindToFetch` do Krayin e à re-renderização do DOM pelo Vue (que pode invalidar meta tags):
1.  **CSRF Token:** Ler o token (`X-CSRF-TOKEN`) **no momento do evento** (onclick), nunca na inicialização do script.
2.  **Transport:** Preferir `XMLHttpRequest` ou `fetch` nativo sem os wrappers do Krayin para evitar interceptações indesejadas.
3.  **Headers:** Sempre enviar `X-Requested-With: XMLHttpRequest` para que o Laravel identifique a requisição como AJAX.

### 6.3 CSS Framework
*   **Tailwind CSS:** Padrão para novos componentes.
*   **Bootstrap:** Legado (evitar em novos desenvolvimentos).
*   **Ícones:** Usar classes de ícone do Krayin (`icon-save`, `icon-plus`) ou SVGs inline para consistência.

### 6.4 Formulários Complexos e Abas Externas (External Tabs Pattern)
Em formulários extensos como a criação e edição de **Processos**, separar a UI em abas (ex: Prazos, Notas, Financeiro, Documentos) resulta em inputs HTML que ficam de fora da tag `<form>` principal, não sendo enviados no submit nativo.

**Solução Padrão (The "Append External Tabs" Pattern):**
1.  **Interceptação do Submit:** O formulário principal (`<x-admin::form>`) dispara um evento no submit via `onsubmit="window.appendExternalTabs(event, this)"`.
2.  **Clonagem de Inputs:** A função JavaScript `window.appendExternalTabs` localiza os inputs dentro das `divs` e `tables` externas (ex: `#container-notas`, `#tbody-prazos`).
3.  **Injeção Dinâmica:** Os inputs são clonados (como `hidden`) usando `cloneNode(true)`, seus valores são copiados (para garantir persistência de selects/textareas dinâmicos) e então anexados ao `<form>` principal.
4.  **Prevenção de Duplicidade:** Um flag (`dataset.appended = 'true'`) é usado para evitar conexões repetidas caso ocorra um duplo clique.
*   **Vantagem:** Permite manter a organização visual em abas limpas e isoladas, sem quebrar o fluxo de salvamento nativo e automático do Krayin CRM.

### 6.5 Sub-formulários via AJAX (Non-Nested Forms Pattern)
O HTML5 proíbe estritamente a existência de tags `<form>` aninhadas. Em contextos onde uma view inteira já está delimitada por um `<form>` principal (como na Edição de Processos), tentar criar um formulário secundário (ex: para upload síncrono de documentos) causará quebras no DOM.

**Solução Padrão (AJAX + FormData):**
Componentes complexos como `documents.blade.php` devem ser arquitetados **sem tags `<form>` próprias**:
1.  **Inputs Soltos:** Os `<input type="file">` e selects são renderizados livremente no HTML.
2.  **JavaScript e FormData:** Botões de ação (`onclick`) disparam funções (ex: `window.lfDocsUploadFiles()`) que coletam os dados dos inputs em memória via API `FormData`.
3.  **Segurança (CSRF):** O token CSRF deve lido dinamicamente da meta tag (ex: `window.lfDocsGetCsrfToken()`) e injetado nos *headers* da requisição (`X-CSRF-TOKEN`).
4.  **XMLHttpRequest/Fetch:** A submissão ocorre de forma totalmente assíncrona. Em caso de sucesso (status 200), realiza-se um `window.location.reload()` ou uma atualização controlada do DOM para exibir os novos registros.

### 6.6 Diálogos Portais e SPA-Safe Modals
Em sistemas Krayin (Vue.js SPA), a injeção de HTML via AJAX frequentemente causa efeitos colaterais: o Vue intercepta classes Tailwind ou tenta re-renderizar o conteúdo removendo bindings.

**Solução Padrão (The "Portal Dialog" Pattern):**
Para modais injetados dinamicamente (ex: Histórico WhatsApp):
1.  **Estilos Inline:** Use atributos `style=""` em vez de classes de framework nos containers pai do modal para evitar a detecção reativa do Vue/Alpine.
2.  **Ancoragem Forçada (Portal):** No momento do clique (`open`), o JavaScript deve verificar se o ID do modal está no `document.body`. Caso contrário, use `document.body.appendChild(modal)` para "teletransportar" o modal para fora do container do Vue.
3.  **IDs Únicos:** Use prefixos proprietários (ex: `lf-wa-hist-*`) para evitar colisões com IDs gerados pelo Krayin Core.
4.  **JSON Wrapping:** Se o controlador retornar HTML para o modal, empacote-o em uma estrutura JSON (`return response()->json(['html' => $html])`). Isso impede que o interceptador global do Krayin receba HTML puro e interprete erroneamente como uma navegação de página cheia.

### 4.59 TenantFinance — Cobranças do Escritório via Asaas (v3.40)

**Propósito:** Módulo add-on que permite ao escritório de advocacia (Tenant) emitir cobranças para seus clientes finais (honorários, custas, mensalidades) usando a API Asaas V3, integrado ao Krayin CRM.

**Separação de Responsabilidades:**

| Aspecto | SaaS Asaas (existente) | TenantFinance (novo) |
|:---|:---|:---|
| Quem cobra | Plataforma SuiteZap | Escritório de Advocacia |
| Quem paga | Tenant (advogado) | Cliente do Tenant |
| Chaves API | `infrastructure_nodes` (MotherShip) | `tenant_asaas_settings` (Banco do Tenant) |
| Domínio DDD | `SuiteZap\LawFirm\SaaS` | `SuiteZap\LawFirm\TenantFinance` |

**Novas Tabelas (banco do Tenant):**
- `tenant_asaas_settings` — Credenciais Asaas do escritório (api_key, environment, webhook_token)
- `tenant_asaas_customers` — Mapeamento `Person.id` → `asaas_customer_id` (caching local)
- `tenant_invoices` — Cobranças emitidas (single/installment/subscription) com link de pagamento e PIX QR code

**Ativação (Module Gate):**
- Controlado via `active_modules` na tabela `subscriptions` do MotherShip.
- Chave: `TENANT_FINANCE`. Se ausente, o menu e as rotas retornam 403.

**Fluxo de Criação:**
1. Advogado clica "💳 Cobrar via Asaas" na aba Financeiro do Processo (Portal Dialog Modal)
2. `InvoiceController` → `TenantAsaasService::createInvoice()`
3. Service: find/create customer no Asaas (`POST /v3/customers`) → cria pagamento/parcelamento/assinatura
4. Resposta: `invoice_url` (boleto) e/ou `pix_qrcode` exibidos no modal

**Webhook:** Rota pública `/api/webhooks/tenant-asaas` → `TenantAsaasWebhookController` → atualiza `tenant_invoices.status` e sincroniza `law_financials.status` quando vinculado.

**ACL:** Permissões granulares em `Config/acl.php` sob o grupo `lawfirm.cobrancas.*` (create, view, edit, delete, settings).

### 4.60 Supressão do Menu Orçamentos e Botão de Importação (v3.41.0)
*   **Decisão (Menu):** Ocultar permanentemente o menu "Orçamentos" (Quotes) das versões futuras SaaS para simplificar a interface e focar no core jurídico.
*   **Decisão (Importação):** Suprimir o botão "Criar Importação" na tela de transferência de dados (`admin/settings/data_transfer/imports`) para prevenir importações manuais não controladas.
*   **Implementação:** 
    1. Filtragem dinâmica no `LawFirmServiceProvider` que remove as chaves `mail` e `quotes` do array de menus.
    2. Comentado o bloco do botão "Criar" na view `imports/index.blade.php`.
*   **Liberação:** Estas funcionalidades permanecem ocultas por padrão no bundle SaaS, podendo ser reativadas futuramente via configuração.

### 4.61 Pessoas Jurídicas (Empresas) em Processos (v3.42.0)
*   **Motivação:** O formulário de Processos só permitia vincular Pessoas Físicas (Contacts). Necessidade de vincular também Pessoas Jurídicas (Organizations, tabela `organizations` do Krayin Core).
*   **Mudanças no Backend:**
    *   **Migration:** `2026_05_06_150431_add_organization_id_to_processos_table.php` — adicionou coluna `organization_id` nullable (FK para `organizations` com `onDelete('set null')`).
    *   **Model (`Processo`):** Incluído `organization_id` em `$fillable` e definido relacionamento `organization()` (`BelongsTo → Webkul\Contact\Models\Organization`).
    *   **Controller (`ProcessoController`):** Injetado `OrganizationRepository` (Krayin Core). Implementado `searchOrganization()` que segue o mesmo padrão de `searchPerson()`. Métodos `store()` e `update()` incluem fallback `null` para `organization_id` ausente.
    *   **Form Requests:** `StoreProcessoRequest` e `UpdateProcessoRequest` atualizados para validar `organization_id` como `nullable|integer|exists:organizations,id`.
    *   **Rota:** `search-organization` registrada em `admin-legal.php`.
*   **Mudanças na UI (`create.blade.php` e `edit.blade.php`):**
    *   Os campos Pessoa e Empresa foram organizados em um `grid-cols-2`, side-by-side.
    *   Ambos são marcados como **(Opcional)** para não quebrar fluxos existentes.

### 4.62 Harmonização UX e Navigation Filter Bar em Processos (v3.43.0)
*   **Decisão:** Eliminar complicação e rolagem excessiva ("scroll fatigue") na visualização e edição de processos complexos, unificando a identidade visual das abas separadas em uma lógica de "single window" componentizada orientada a cards minimalistas.
*   **Mudanças na Arquitetura UI:**
    *   **Navegação Inteligente (Filter Bar):** Introdução de uma barra horizontal fixa de filtros no topo das views `edit` e `show` do domínio Legal/Processos, ordenando visualmente as dependências principais (Dados Oficiais, Info Processo, Prazos, Notas, Documentos, Partes e Financeiro). Ela permite isolar a renderização no display sob os componentes base ocultáveis (`lf-section`), reduzindo drasticamente a carga visual sem corromper envios combinados do AJAX nativo.
    *   **Persistência de Estado Local:** O script da view ativamente acopla no recurso `localStorage` do navegador salvando incondicionalmente a aba visual da navegação de filtro eleita pelo utilizador (`lf_processo_section_{id}`). A interface de navegação portanto nunca se perde quando ocorre o recarregamento normal local (retorno F5 ou submit the salvamento tradicional). Incluiu-se função especial `⧉ Todos` salvando o override temporário para pesquisa generalizada no arquivo DOM (Find).
    *   **Design System (`.lf-card`):** Instituição estrita de componentes padronizados enclausurando o visual das dependências de domínios. A diretiva `.lf-card` unifica estéticas: `gap-6` (24px) generalizado para clusters estruturais, borders em `xl` nas laterais do card container, sombras reativas em transição combinadas com topografia `tracking-tight` com separadores em base `border-b` consolidando componentes e harmonizando esteticamente inputs visuais de leitura ou gravação.

> [!IMPORTANT]
> **Padrão de Lookup com Valor Inicial (`v-lookup-component`) — Lição Aprendida:**
>
> O componente de busca/seleção nativo do Krayin (`v-lookup-component`) **NÃO é** registrado por padrão em todas as páginas. Ele é incluído via `@pushOnce('scripts')` dentro do template parcial `x-admin::attributes.edit.lookup`. Se uma Blade view usar `<v-lookup-component>` sem antes incluir este parcial, o Vue não encontrará o componente e os campos ficam inertes (sem pesquisa e sem seleção).
>
> **OBRIGATÓRIO:** Antes de qualquer bloco de `<v-lookup-component>`, incluir o gatilho de registro:
> ```blade
> {{-- Trigger v-lookup-component registration --}}
> <x-admin::attributes.edit.lookup />
> ```
> O componente tem um guard `@if (isset($attribute))` que previne qualquer renderização visual indesejada quando chamado sem parâmetros — portanto é seguro incluir vazio.
>
> **Padrão correto de passagem de valor inicial (edição de entidades existentes):**
> ```blade
> @php
>     $entityLookup = $entityId
>         ? app('Webkul\Attribute\Repositories\AttributeRepository')
>             ->getLookUpEntity('persons', $entityId)
>         : null;
> @endphp
>
> <v-lookup-component
>     :attribute="{{ json_encode(['code' => 'person_id', 'name' => 'Pessoa', 'lookup_type' => 'persons']) }}"
>     :value="{{ json_encode($entityLookup) }}"
>     validations=""
> ></v-lookup-component>
> ```
>
> **Por que NÃO usar `v-bind:value='...'` ou `v-bind:value="window.xxx"`:**
> - `v-bind:value='@json($var)'` — o Blade processa `@json` como uma chamada PHP, mas em contexto de atributo de componente Blade anônimo pode resultar em `Call to undefined function json()`.
> - `v-bind:value='({!! json_encode(...) !!})'` — passa o template Vue, mas o compilador de templates Vue 3 não tem acesso a `window` nem avalia blocos `{}` de objetos já que os chaves são interpretadas como blocos de código em escopo de statement.
> - `v-bind:value="window.processoData.x"` — Vue 3 sandboxeia o escopo de templates e não expõe o objeto global `window`.
> - **A solução correta** é passar via atributo HTML `{{ json_encode() }}` diretamente para um elemento nativo (não-Blade-component), como faz o próprio sistema de atributos do Krayin.

### 4.63 Entidade Caso e FK Lookup Customizado (v3.44.0)

*   **Motivação:** Escalar a capacidade do sistema em organizar operações maiores do que os processos em si. Exemplo: Um "Caso de Revisão Criminal" que agrupa múltiplos "Processos Anexos" ou recursos associados a ele.
*   **Modelagem de Dados e Limitação de Tipagem (`unsignedInteger`):**
    *   **Importante:** Ao estender as tabelas nativas do Krayin (`users`, `persons`, `organizations`), lembre-se que o Krayin as constrói baseadas em Primary Keys configuradas como **`integer`** (não `bigint`).
    *   Portanto, as foreign keys na criação de novas tabelas (ex: `law_casos.user_id`) devem ser geradas usando `$table->unsignedInteger()`. Usar `$table->unsignedBigInteger()` ocasionará uma restrição do MySQL 8 de tipos incompatíveis (`General error: 3780 Incompatible type`).
*   **Decisão de Interface UI (Custom AJAX Lookup Selector):**
    *   A componentização nativa de Busca do Krayin (`v-lookup-component`) não permite a injestão de tabelas Customizadas do Add-on (ex. `law_casos`) a não ser via hacking do Package Vue e re-build de NPM (`npm run build`). Nós decidimos manter o zero footprint debt da UI e implementamos a Busca dos "Casos Vinculados" como um Componente customizado (AJAX fetch API via JavaScript Vanilla), que pesquisa e insere no Form como um Element Input Hidden normal, garantindo integridade visual com Blade.

### 4.64 LegalOrchestrator e Zero-Copy Documents (v3.45.0)

*   **Decisão:** Centralizar a coordenação transacional da criação hierárquica `Lead → Caso → Processo` em um Domain Service dedicado (`LegalOrchestrator`), e implementar compartilhamento de documentos no nível do Caso sem duplicação física de arquivos (Zero-Copy).
*   **Mudanças:**
    *   **`LegalOrchestrator` (Novo, `Legal/Services`):** Método `convertLeadToLegalStructure(Lead $lead)` que executa em `DB::transaction()`: (1) Cria o `Caso` com dados do Lead, (2) Cria o `Processo` vinculado ao `caso_id` recém-criado, (3) Vincula o advogado responsável a ambas as entidades.
    *   **`LeadWonListener` (Refatorado):** Agora delega ao `LegalOrchestrator` via injeção de dependência em vez de criar `Processo` diretamente. A guarda de duplicidade (verificação `Processo::where('lead_id', ...)->exists()`) permanece no listener.
    *   **Migration `caso_id` em Documentos:** Adicionada coluna `caso_id` (nullable, indexed, FK → `law_casos`) nas tabelas `law_processo_anexos` e `law_process_documents` para permitir compartilhamento de arquivos entre processos do mesmo caso.
    *   **`DocumentService.storeFile()` (Atualizado):** Quando o processo pertence a um caso, o path de storage é `casos/{caso_id}/documents/{nome}` em vez de `processos/{id}/{nome}`. O `caso_id` é auto-populado no record `Anexo` criado. Processos sem caso mantêm o path legado (retrocompatibilidade).
    *   **Zero-Copy Query (View `documents.blade.php`):** A listagem de documentos na aba de Documentos do Processo agora busca `WHERE processo_id = {id} OR caso_id = {caso_id}`, exibindo documentos de todos os processos-irmãos do mesmo caso sem copiar ou mover arquivos no storage.
*   **Regras de Ouro Garantidas:**
    *   **Skinny Controllers:** Toda lógica de criação multi-entidade vive no `LegalOrchestrator`, não em Controllers ou Listeners.
    *   **Atomicidade:** Toda criação Caso → Processo é transacional (`DB::transaction`).
    *   **Zero-Copy:** Documentos do Caso são acessíveis por todos os processos-filhos sem mover ou copiar arquivos no storage.
    *   **Tag-Driven Metadata Prioritization:** Uses standard Lead Tags (e.g. `Trabalhista`, `Crítica`) to populate canonical metadata like Área and Prioridade before falling back to `LeadTriagem` AI metrics.
    *   **Canonical Pipelines (v3.46+):** All Legal Status validation must strictly rely on `LegalOrchestrator::VALID_STATUSES` and avoid hard-coded pipeline matching arrays via `UpdateCasoRequest` rules. The Legal Entity assumes standard 12-stage lifecycles, and Processos fallback to canonical mappings over UI inputs.

### 4.65 Global JS State Injection Pattern (Kanban Data Hydration) (v3.46.0)

*   **Decisão:** Eliminar acessos indiretos e bugs de ciclo-de-vida no Blade ao hidratar dependências Vanilla JS (Tooltips e Modais) com grandes volumes de dados de Banco (ex: Listagem de Processos exibida via hover nos cards de Casos do Kanban).
*   **Problema (Blade Compilation Scope):** Ao iterar centenas de cards (ex: Kanban) e tentar passar um array JSON por atributo HTML (`data-processos="{{ json_encode(...) }}"`), ocorriam escapes incorretos de aspas. Ao tentar injetar scripts via `@pushOnce('scripts')` no meio do loop, o compilador do Blade executava apenas a primeira iteração e ignorava variáveis de view se compiladas prematuramente, resultando em Null Pointers (`Undefined property: Illuminate\View\Factory::$startPush`) ou `[object Object]` vazio.
*   **Mudanças:**
    *   **Controller Batch:** O `LegalKanbanController` consulta todos os dados (ex: `DB::table('processos')`) e formata um único `$tooltipMap` em PHP com índice seguro (array associativo por `caso_id`). O JSON já sai codificado em Server-Side via `json_encode()` como string limpa.
    *   **Inline Scoped Script (View):** A string JSON é injetada numa tag `<script>` explicitamente ao **final do corpo HTML**, exposta como uma variável global incondicional no browser: `window.__LF_PROCESSOS_MAP = {!! $processosTooltipJson ?? '{}' !!};`.
    *   **Event Delegation (JS):** O componente visual apenas recebe a key referencial (ex: `<div class="card" data-caso-id="5">`), consumida via Event Listener Vanilla JS: `const procData = window.__LF_PROCESSOS_MAP[casoId]`.
*   **Performance:** Zerou os problemas N+1 na renderização HTML e diminuiu os KBs totais de payload retornado, segregando estado (State) de apresentação (DOM). A abordagem impede crashes no render do Krayin (Vue), assegurando que o `script` injetado nunca interfira na árvore virtual (Virtual DOM).

### 4.66 Kanban Operacional de Casos e Padronização de Pipeline (v3.46.0)

*   **Decisão:** Introduzir um quadro Kanban visual para `Caso`s, operando com um pipeline rigoroso de 12 estágios operacionais padronizados (do "Novo Caso" ao "Encerrado"), unificando as paletas de cores de Área, Status e Prioridade em todo o sistema.
*   **Mudanças:**
    *   **Vanilla JS Kanban:** A UI de Kanban (`kanban/index.blade.php`) foi implementada 100% em Vanilla HTML5 Drag-and-Drop. Devido ao ambiente Krayin onde o Vue.js destrói eventos, listeners foram atrelados tardiamente com injeção explícita de `csrf_token()` nos headers do `fetch()`, mitigando os erros 419 (Page Expired) na interface.
    *   **Single Source of Truth (`LegalOrchestrator`):** O orquestrador passou a manter a constante `VALID_STATUSES` com os 12 stages canônicos. Validações de requisição em `UpdateCasoRequest` agora dependem destas constantes via `Rule::in()`, impedindo silenciosamente o salvamento de status legados hard-coded e propiciando a edição sem bugs na UI.
    *   **Lifecycle Assíncrono:** Ao dropar um card em uma nova coluna, JavaScript executa requisição via REPLACE_ID e insere visualmente o Card no topo utilizando `prepend()` ao confirmar sucesso do status 200 via API JSON, sem aguardar recarregamento da página (Zero page Refresh).
    *   **Orquestração Lead -> Processo (Atualizada):** Ao converter um Lead GANHO, o novo `Caso` entra com status "Novo Caso", enquanto o `Processo` inter-relacionado criado entra condicionalmente em "Em Análise", respeitando a realidade cartorária.

### 4.70 Padronização de Renderização Markdown nos Assistentes de IA (v3.50.0)

*   **Decisão:** Garantir que 100% das respostas dos Assistentes de IA sejam renderizadas em Markdown formatado, eliminando inconsistências visuais onde alguns assistentes retornavam texto plano e outros retornavam HTML estruturado.
*   **Problema:** Os assistentes `qualificacao_juridica`, `sugestao_proposta` e `analise_viabilidade` não possuíam a instrução de formatação Markdown em seus prompts de sistema. Apenas o assistente `negociacao_conversao` exibia Markdown corretamente, criando inconsistência visual na interface do CRM.
*   **Mudanças:**
    *   **Prompts (MotherShip DB):** Os templates dos três assistentes afetados foram atualizados no banco de dados do MotherShip para incluir a instrução explícita de formatação: `SEMPRE formate sua resposta usando Markdown estruturado com cabeçalhos (##), listas e **negrito** para pontos críticos.`
    *   **Sem mudanças de código:** A camada de renderização (`marked.js` + `DOMPurify`) já estava integrada na view `admin/assistants/index.blade.php` a partir da v3.7 (seção 4.11). A correção requereu apenas atualização dos prompts-fonte no Mothership.
    *   **Efeito Cascata:** Como os templates são distribuídos dinamicamente para todos os Tenants via `MotherShipService::getTemplates()`, a correção propagou-se automaticamente sem necessidade de deploy por Tenant.
*   **Teste de Regressão:** Verificado que as quatro views de Assistentes (`index.blade.php`, `show.blade.php`, `lead-tools-panel.blade.php` e o modal de detalhes da execução) renderizam Markdown corretamente via `window.marked.parse()` com sanitização `DOMPurify`.

### 4.71 Manutenção — Docker Hub Update v3.50.0 (v3.50.0)

*   **Decisão:** Atualizar a imagem oficial do Docker Hub (`suitezap/lawfirm`) para refletir as mudanças consolidadas nas versões v3.48, v3.49 e v3.50.
*   **Mudanças:**
    *   **`docker/entrypoint.sh` (linha 4):** String de startup atualizada de `LF v3.49.0` para `LF v3.50.0`.
    *   **`CHANGELOG.md`:** Adicionada entrada da release v3.50.0 com as três mudanças: manutenção de Docker Hub, padronização Markdown de IA e consistência financeira SuiteCoins.
    *   **Imagens publicadas:** `suitezap/lawfirm:v3.50.0` e `suitezap/lawfirm:latest` atualizadas no Docker Hub Registry.
*   **Verificação:** Executado `docker run --rm suitezap/lawfirm:v3.50.0` confirmando a string `🚀 Iniciando LawFirm SaaS v6.2 (LF v3.50.0)...` no startup do container.

### 4.72 WhatsApp Messenger — Inbox de Atendimento tipo Whaticket (v3.50.0+)

> [!WARNING]
> **PROJETO SUSPENSO (29/05/2026):** Esta funcionalidade de Messenger/Inbox (Whaticket) foi colocada em suspensão nesta data e **não fará parte das versões posteriores**. Seus endpoints e controladores foram desativados.
> As demais funcionalidades de WhatsApp (Envio de faturas, alertas de monitoramento, importação de histórico por processo e agendador de prazos) permanecem 100% ativas e funcionais.

> [!NOTE]
> Documentação completa e histórico de evolução em **`ARCHITECTURE_whats.md`**.

*   **Contexto:** O LawFirm CRM incorporou um sistema de atendimento via WhatsApp inspirado no projeto open-source Whaticket, permitindo que advogados gerenciem conversas de clientes como "tickets" com ciclo de vida `pending → open → closed`.
*   **Avaliação de Isolamento:** O módulo é **HÍBRIDO**. Apesar de existir um diretório `packages/SuiteZap/Whaticket/` (com migrations não registradas), a implementação completa vive dentro do domínio `Whatsapp` do pacote `SuiteZap/LawFirm`.
*   **Pontos de Integração com outros Domínios:**
    *   **`SaasS/MotherShipService`:** Credenciais da Evolution API obtidas via `getEvolutionConfig()` (sem `.env`). Isolamento multi-tenant via `getTenantId()` em todas as queries.
    *   **`Legal/persons` (Krayin Core):** `MessengerService::findKrayinPersonId()` tenta auto-vincular o número de telefone do contato WhatsApp a um `Person` existente no CRM, criando um link `whaticket_contacts.person_id`.
    *   **`core_config`:** Mensagem de despedida configurável por tenant via `lawfirm.whatsapp_templates.messages.farewell_message`.
*   **Componentes-chave (`LawFirm/src/Whatsapp/`):**
    *   **`MessengerService`:** Serviço central — `processIncoming()` (idempotente via `evolution_message_id`), `sendText()`, `sendMedia()`, `acceptTicket()`, `closeTicket()`, `getOrCreateTicket()`.
    *   **`WhatsappChatController`:** Serve a view do Messenger e 8 endpoints JSON (tickets, messages, accept, close, send, sendMedia, uploadMedia, startConversation).
    *   **`WhatsappWebhookController`:** Receptor público de eventos da Evolution API (`messages.upsert`, `messages.update` para ACK).
    *   **`SendWhatsappMessageJob`:** Envio assíncrono de mensagens de texto (queued — requer worker ativo).
    *   **Messenger View (`Whatsapp/messenger.blade.php`):** Interface split-view estilo WhatsApp Web com Vanilla JS, polling a 10s, suporte a mídia e ACK visual.
*   **Tabelas do Banco (prefixo `whaticket_`, criadas por migrations do LawFirm):**
    | Tabela | Propósito |
    |:---|:---|
    | `whaticket_contacts` | Contatos vinculados (`phone` + `person_id`) |
    | `whaticket_tickets` | Conversas (`pending` / `open` / `closed`) |
    | `whaticket_messages` | Mensagens com `evolution_message_id` único e `ack` (0–4) |
    | `whaticket_queues` (scaffold) | Setores/departamentos (não implementados) |
    | `whaticket_tags` (scaffold) | Etiquetas (não implementadas) |
*   **Alerta Crítico:** O pacote `packages/SuiteZap/Whaticket/` contém apenas migrations nunca executadas e não está registrado em nenhum `ServiceProvider`. Deve ser **removido ou formalmente integrado** para evitar confusão.

### 4.73 UX — Compactação de Labels na Navigation Filter Bar de Processos (v3.51.0)

*   **Decisão:** Compactar os rótulos da barra de navegação por seções (`Navigation Filter Bar`) nas telas de **visualização** (`show.blade.php`) e **edição** (`edit.blade.php`) de Processos, sem alterar lógica ou funcionalidade, com objetivo de melhorar a legibilidade em telas menores e reduzir quebras de linha nos filtros.
*   **Problema:** Os rótulos `Documentos e Anexos` e `Modelos de Docs` causavam overflow visual em viewports de notebook (< 1440px), resultando em quebra de linha na barra de filtros e sobreposição de ícones.
*   **Mudanças:**
    *   **`views/admin/processos/show.blade.php`:** Labels da barra de filtros ajustados:
        *   `Documentos e Anexos` → `Docs e Anexos`
        *   `Modelos de Docs` → `Model. Docs`
    *   **`views/admin/processos/edit.blade.php`:** Idem às mesmas labels espelhadas na tela de edição.
*   **Escopo:** Alteração puramente de UI (string de texto em Blade). Nenhuma lógica PHP, rota ou regra de negócio foi modificada.

### 4.74 Manutenção — Docker Hub Update v3.51.0 (v3.51.0)

*   **Decisão:** Publicar a imagem oficial do Docker Hub (`suitezap/lawfirm`) atualizada para consolidar as melhorias UX da v3.51.0.
*   **Mudanças:**
    *   **`LawFirmServiceProvider.php`:** Constante `VERSION` atualizada de `3.50.0` para `3.51.0`.
    *   **Imagens publicadas:** `suitezap/lawfirm:v3.51.0` e `suitezap/lawfirm:latest` publicadas no Docker Hub Registry.
        *   Digest: `sha256:36051669679c8444a1ef450945e203e8ab3e4bf06ba61894aad4b6579545795e`
*   **Verificação:** Push confirmado com sucesso para ambas as tags (`v3.51.0` e `latest`) via `docker push`.

### 4.75 Modelos de Documentos Dinâmicos (v3.52.0)
*   **Decisão:** Permitir que os advogados criem, gerenciem e renderizem modelos/templates de documentos (Contratos, Petições, Procurações, Notificações e outros) pré-preenchidos dinamicamente com dados do processo e do cliente em tempo de execução.
*   **Mudanças no Backend:**
    *   **Migration:** `2026_05_22_000001_create_law_document_templates_table.php` — cria a tabela `law_document_templates` associando cada template a um criador (`user_id` FK unsignedInteger).
    *   **Model (`DocumentTemplate`):** Criado sob o domínio Legal (`Legal/Models/DocumentTemplate.php`) suportando escopos dinâmicos (`scopeActive()`, `scopeForArea()`).
    *   **Repository & Service:** Criados `DocumentTemplateRepository` e `DocumentTemplateService` para abstração de consultas e mecanismo de interpolação robusto de variáveis por chaves em formato duplo (`{{variavel}}` e `{{ variavel }}`).
    *   **Controller:** Criado `DocumentTemplateController` sob o namespace `SuiteZap\LawFirm\Legal\Http\Controllers\Admin`.
    *   **Rotas dedicadas:** Rotas para CRUD de Modelos de Documentos registradas em `admin-legal.php` sob o prefixo `modelos-documentos`.
*   **Mudanças na UI & AlpineJS:**
    *   Criada a nova aba **Model. Docs** (`modelos-tab.blade.php`) na Navigation Filter Bar de Processos.
    *   Implementado modal flutuante estilizado no padrão folha A4 com editor integrado em textarea livre e botões de ação para rápida cópia de texto (`navigator.clipboard`) e impressão limpa via `@media print`.
    *   Renderização assíncrona (AJAX JSON) evitando conflitos com o DOM compilado.

### 4.76 Migration de Inicialização Idempotente de Checklists (v3.52.0)
*   **Decisão:** Resolver o provisionamento de novos ambientes (locais e de produção Swarm) injetando de forma resiliente e 100% automatizada os kits padrão de documentos de checklist para todas as áreas (Trabalhista, Cível, Família, Criminal, Previdenciário, Empresarial e Geral) sem depender de sementes manuais atreladas a `db:seed`.
*   **Mudanças:**
    *   **Migration Idempotente:** Criada a migration `2026_05_23_000001_seed_law_checklist_templates.php`. Ela executa uma validação preventiva de existência antes de popular a tabela `law_checklist_templates` com 13 kits mestre de documentos parametrizados no formato JSON no banco local de cada tenant.



### 4.77 Auditoria Arquitetural DDD/SaaS — Resultado e Correções (v3.52.1)

*   **Data:** 2026-05-29 | **Auditor:** Antigravity Senior Architect
*   **Escopo:** `packages/SuiteZap/LawFirm/src/` — 9 domínios DDD, v3.40 → v3.52.0
*   **Motivação:** Validar integridade da arquitetura DDD pós-Great-Migration (v3.36) e garantir que nenhum "bolsão" de código legado tenha surgido nos domínios novos (GED, TenantFinance, Messenger, DocumentTemplates).

#### Score de Conformidade Final

| # | Dimensão | Status | Nota |
|---|----------|--------|------|
| 1 | Zero Root Controllers (`src/Http/Controllers/*.php = 0`) | ✅ PASS | 10/10 |
| 2 | `Storage::` isolation (somente via `SaasFileService`) | ✅ CORRIGIDO | 9/10 |
| 3 | `env()` banido fora de Config e MotherShipService | ✅ PASS | 9/10 |
| 4 | `Log::debug` em produção | ✅ CORRIGIDO | 9/10 |
| 5 | Namespace DDD `SuiteZap\LawFirm\{Domain}\{Type}` | ✅ PASS | 10/10 |
| 6 | Skinny Controllers (lógica em Services/Orchestrators) | ✅ PASS | 9/10 |
| 7 | Padrão `abort(503)` para serviços externos | ✅ PASS | 9/10 |
| 8 | Qualidade de Observers e Listeners | ✅ PASS | 10/10 |
| 9 | Isolamento multi-tenant (SaasFileService em cascatas) | ✅ PASS | 9/10 |
| 10 | Suspensão Whaticket (rotas + docs) | ✅ PASS | 10/10 |

**Score Final: 9.5 / 10** *(3 violações corrigidas durante a auditoria)*

#### Violações Corrigidas

1.  **VIO-1 — `Storage::url()` direto** em `Legal/Http/Controllers/PublicPortal/CustomerPortalController.php:52`
    *   **Antes:** `Storage::url($settings['logo'])`
    *   **Depois:** `app(SaasFileService::class)->url($settings['logo'])` — Regra 2.2 do SKILL.md atendida.

2.  **VIO-2 — Import `use Storage` não utilizado** em `Legal/Models/Anexo.php:7`
    *   **Antes:** `use Illuminate\Support\Facades\Storage;` presente (import fantasma após refactor que migrou para `route()`)
    *   **Depois:** Import removido. Docblock atualizado para refletir implementação real (proxy interno via `route()`).

3.  **VIO-3 — `Log::debug` ativo** em `Whatsapp/Http/Controllers/WhatsappWebhookController.php:57,85`
    *   **Antes:** `Log::debug('[WhatsappWebhook] Message saved.')` e `Log::debug('[WhatsappWebhook] ACK updated.')`
    *   **Depois:** `Log::info(...)` — eventos de sucesso visíveis com `LOG_LEVEL=info` padrão de produção.

#### Riscos Residuais (Não Bloqueantes — Backlog)

*   **`ProcessoObserver::forceCleanupCalendarEvent`** — `findWhere(['type'=>'meeting'])` sem filtro de tenant. Risco de performance em instâncias com muitos registros. Recomendação: adicionar filtro por `user_id` antes do loop.
*   **`auth()->guard('admin')->id() ?? 1`** em `ProcessoObserver::ensureCalendarEvent` — Fallback silencioso para `user_id=1` em contextos sem sessão (ex: jobs em fila). Refatorar para emitir `Log::warning` em vez de usar fallback.

### 4.78 Hardening de Visibilidade S3 e URLs Temporárias Assinadas (v3.52.2)
*   **Decisão:** Manter os buckets S3/MinIO de cada tenant estritamente privados para proteção de dados sensíveis (GED, contratos, anexos) e forçar a utilização de URLs temporárias assinadas para qualquer visualização de imagem, logo ou arquivo na UI.
*   **Problema:** Ao carregar logotipos customizados do tenant para cabeçalho ou barra lateral, a aplicação gerava links diretos usando `Storage::url()` (ou fallbacks locais). No ambiente de produção, com buckets S3 configurados como privados, os usuários recebiam erros `AccessDenied` da API MinIO ao tentar exibir ou baixar essas imagens.
*   **Mudanças:**
    *   **`SaasFileService`:** Implementado o método `getSignedUrl(string $path, int $minutes = 60)` e sobrescrito o método `url(string $path)` para retornar automaticamente uma URL assinada quando o driver ativo for S3/MinIO.
    *   **Views Administrativas (`Krayin Admin`):** Refatoradas as blades `configuration/field-type.blade.php`, `layouts/header/index.blade.php` e `layouts/sidebar/mobile/index.blade.php` substituindo chamadas diretas de `Storage::url()` por resolvedores compatíveis com `SaasFileService::getSignedUrl()`.
    *   **Notificações de Envio:** Corrigida a classe `SendWhatsappNotification` no domínio `Whatsapp` para recuperar corretamente a URL assinada da imagem do recibo de faturamento antes do dispatch.

### 4.79 Event Delegation em Modelos e Ajuste de Cabeçalho (v3.52.3)
*   **Decisão:** Melhorar a resiliência do seletor de modelos de documentos na ficha de processos contra o ciclo de vida e reconstruções de DOM induzidas pelo Vue.js, além de corrigir a exibição de layouts no gerenciamento de modelos e atualizar o cabeçalho padrão.
*   **Mudanças:**
    *   **JS Event Delegation:** O script de busca e filtro de modelos de documentos na tab (`modelos-tab.blade.php`) foi totalmente refatorado para utilizar delegação de eventos global no objeto `document`. Ao ouvir `focus`, `click`, `input`, `mousedown` e `keydown` no nível do documento, o filtro e as interações do dropdown funcionam estavelmente mesmo se o Vue/Livewire destruir e recriar os elementos de formulário na view.
    *   **Correção de Sobrescrita de Coleção:** Corrigido bug na view do CRUD de modelos (`index.blade.php`) onde a coleção `$localTemplates` gerada pelo controller (que inclui os templates de Cabeçalho e Rodapé locais) era sobrescrita com a lista de modelos ativos filtrada (que os exclui), fazendo com que sumissem do gerenciamento.
    *   **Atualização do Cabeçalho Padrão:** O HTML do layout padrão do cabeçalho de documentos gerado por `DocumentTemplateController::createDefaultLayout` foi atualizado para uma tabela sem bordas, com altura definida, contendo a logomarca corporativa hospedada no S3 e o nome do escritório (`{{escritorio_nome}}`).

### 4.80 Campo Chave Secreta (sercreta) em Processos para Controle de IA (v3.52.4)
*   **Decisão:** Introduzir um campo dinâmico e seguro de chave secreta (`sercreta`) na tabela `processos` e na ficha de processos ("Informações Básicas") para possibilitar que os assistentes de IA realizem o controle e validação de comunicações remotas via WhatsApp ou ligações telefônicas.
*   **Mudanças no Backend:**
    *   **Migration:** `2026_06_05_223106_add_sercreta_to_processos_table.php` — adiciona a coluna `sercreta` (VARCHAR(7), nullable) na tabela `processos` logo após a coluna `id`.
    *   **Model (`Processo`):** O campo `sercreta` foi incluído no array `$fillable` para permitir a gravação do valor a partir do formulário de criação/edição.
    *   **Geração Automática (`ProcessoObserver`):** Adicionado o método `creating(Processo $processo)` ao `ProcessoObserver` para gerar automaticamente uma chave aleatória numérica de 5 dígitos formatada com zeros à esquerda se o campo estiver vazio ao persistir o registro.
*   **Mudanças na UI (`create.blade.php` e `edit.blade.php`):**
    *   Inclusão do campo de input **Chave Secreta (IA)** na ficha de processos sob o painel de **Informações Básicas** (ao lado do status e do responsável).

### 4.81 Integração Chatwoot e Criação do Domínio Atendimento (v3.52.5)
*   **Decisão:** Integrar o Chatwoot como canal de atendimento gerido centralmente pelo Mothership. O CRM NUNCA hardcoda credenciais.
*   **Mudanças no Backend:**
    *   **Domínio `Atendimento`:** Criado para gerir toda a integração e fluxos do Chatwoot.
    *   **Service `ChatwootService`:** Consome a configuração via `MotherShipService::getChatwootConfig()` (que une dados do nó do servidor `infrastructure_nodes` e do tenant `tenants`).
    *   **Webhook Seguro:** Endpoint `/api/webhooks/chatwoot` isento de CSRF e protegido pelo `webhook_token` do tenant validando a assinatura criptográfica (`X-Chatwoot-Signature`).
*   **Regras de Ouro:**
    *   NUNCA hardcodar `api_key` ou `inbox_id` no `.env`.
    *   Sempre valide a assinatura do webhook usando o `webhook_token`.
    *   Retorne HTTP 200 imediatamente para webhooks do Chatwoot para evitar timeout, delegando o processamento pesado a Jobs nas filas.

### 4.82 Implementação Completa do Domínio Atendimento e Ajustes v3.53.0 (v3.53.0)
*   **Data:** 2026-06-22 | **Auditor:** Antigravity Senior Architect
*   **Motivação:** Concluir a implementação física do domínio `Atendimento/` (documentado na seção 4.81 mas não existia fisicamente em `src/`), implementar `getChatwootConfig()` no `MotherShipService`, registrar nova chave de precificação Escavador V1 e reforçar guards de escrita em templates globais.

#### Mudanças no Backend

1.  **Domínio `Atendimento/` — Criação Física** (`src/Atendimento/`)
    *   **`ChatwootService`** (`Atendimento/Services/ChatwootService.php`): Service completo de integração com Chatwoot. Config injetada exclusivamente via `MotherShipService::getChatwootConfig()` (Zero-.env). Implementa `sendMessage()`, `addLabels()`, `getLabels()`, `findContactByPhone()`. Mantém distinção crítica de tokens: `botHeaders()` usa `api_key` do nó; `managementHeaders()` usa `access_token` (User Access Token do tenant) — obrigatório para `/labels` e `/contacts`.
    *   **`ChatwootWebhookController`** (`Atendimento/Http/Controllers/ChatwootWebhookController.php`): Receptor de eventos do Chatwoot. Implementa 3 camadas de validação: (1) Assinatura HMAC-SHA1 via `X-Chatwoot-Signature`, (2) Cross-tenant guard via `inbox_id`, (3) Retorno HTTP 200 imediato com dispatch para Jobs. Rota `POST /api/webhooks/chatwoot` registrada no grupo `api` (CSRF-exempt).

2.  **`MotherShipService::getChatwootConfig()`** — Novo método estático seguindo o padrão de `getEvolutionConfig()`. Consulta `tenants.chatwoot_node_id` → `infrastructure_nodes` com cache de 300s. Retorna array com `base_url`, `api_key` (bot), `account_id`, `inbox_id`, `access_token` e `webhook_token` (user access token).

3.  **Precificação Escavador — Nova chave V1 Autos** — Chave `API_V1_AUTOS_PROCESSO` adicionada ao array de `getEscavadorPrices()`, mapeando `escavador_price_v1_autos_processo` (fallback R$ 1,50). Distingue o endpoint `POST /v1/processos/{id}/autos` com download de peças dos demais endpoints de autos.

4.  **DocumentTemplateController — Guards HTTP 403** — Métodos `edit()`, `update()` e `destroy()` agora verificam `$template->is_global` antes de qualquer operação de escrita. Templates do Mothership (com `unique_id` prefixado por `global-`) são imutáveis pelo tenant: retornam `abort(403)` ou `response()->json([...], 403)` com mensagem clara.

#### Mudanças de Infra e Configuração

*   **`app/Http/Middleware/VerifyCsrfToken.php`:** `api/webhooks/chatwoot` adicionado ao array `$except`.
*   **`src/Http/routes.php`:** Rota `POST api/webhooks/chatwoot` registrada no grupo `api` público junto aos demais webhooks.
*   **`src/Providers/LawFirmServiceProvider.php`:** `VERSION` bumped de `3.52.5` para `3.53.0`.
*   **`ARCHITECTURE_dir.md`:** Domínio `Atendimento/` marcado como ativo com nota de versão `v3.53.0`.

#### Score de Conformidade DDD pós-v3.53.0

| # | Dimensão | Status |
|---|----------|--------|
| 1 | Zero Root Controllers | ✅ PASS |
| 2 | Storage via SaasFileService | ✅ PASS |
| 3 | `env()` banido fora de Config/MotherShipService | ✅ PASS |
| 4 | Namespace DDD `SuiteZap\LawFirm\{Domain}\{Type}` | ✅ PASS |
| 5 | Config Chatwoot via MotherShipService | ✅ PASS |
| 6 | Webhook com HMAC-SHA1 + cross-tenant guard | ✅ PASS |
| 7 | Templates globais imutáveis (403 guards) | ✅ PASS |

#### Fechamento de Riscos Residuais (backlog v3.52.1)

*   ✅ **FECHADO — `ProcessoObserver::forceCleanupCalendarEvent`** — `findWhere` agora filtra por `tenant_id` via `MotherShipService::getTenantId()` (linhas 188-191). Risco de performance cross-tenant eliminado. Auditado em 2026-06-30.
*   ✅ **FECHADO — fallback silencioso `user_id=1`** — `ProcessoObserver::ensureCalendarEvent` agora emite `Log::warning()` e executa `return` quando `user_id` não é resolvido, em vez de usar `user_id=1`. Auditado em 2026-06-30.

---

### 4.83 Auditoria de Conformidade DDD e Mitigação de Débitos Técnicos (v3.53.1)

*   **Data:** 2026-06-30 | **Auditor:** Antigravity Senior Architect
*   **Score Auditoria:** 9.8 / 10 → 9.9 / 10 pós-correção
*   **Motivação:** Auditoria de conformidade pós-v3.53.0. Três débitos técnicos não-bloqueantes identificados e mitigados no mesmo ciclo.

#### Mudanças Aplicadas

1.  **DÉBITO-1 — `AI/Jobs/ProcessAiAssistant.php` — Regra 4 (Graceful Degradation em Jobs)**
    *   **Antes:** `throw new \Exception()` para condições de configuração inválida (`N8N não configurado`, `Webhook URL vazia`), capturado internamente pelo `catch` mas violando a letra da Regra 4.
    *   **Depois:** Substituídos por `Log::error() + history->update(['status' => 'failed']) + return`. Bloco `catch` externo mantido como safety-net exclusivo com contexto enriquecido (`class`, `file`, `line`). Comentário `// throw $e;` removido.
    *   **Regra aplicada:** SKILL.md §4 — "Jobs/Listeners usam `Log::error()` + `return`. Nunca propagam throw."

2.  **DÉBITO-2 — `Console/Commands/CalculateStorageUsage.php` — Docblock ambíguo**
    *   **Antes:** Docblock mencionava `Storage::disk('s3')` de forma que podia ser interpretada como chamada existente no arquivo.
    *   **Depois:** Reescrito com afirmação explícita: `"Este comando NÃO contém nenhuma chamada direta a Storage::"` com `@see SaasFileService`.

3.  **DÉBITO-3 — `src/Http/Controllers/Admin/` e `src/Http/Controllers/Api/` removidas**
    *   **Antes:** Dois subdiretórios vazios vestigiais desde a migração v3.14/v3.17.
    *   **Depois:** Ambas removidas. `src/Http/Controllers/` agora está **completamente vazio** (verificado via `Get-ChildItem` pós-remoção). Zero Root Controllers agora é semanticamente perfeito.
    *   **Regra aplicada:** ARCHITECTURE.md §4.36 — "Zero Root Controllers: `src/Http/Controllers/` vazio desde v3.36".

4.  **Doc — `ARCHITECTURE_dir.md`** — Cabeçalho atualizado de `v3.52.4` → `v3.53.0`.

#### Score de Conformidade DDD pós-v3.53.1

| # | Dimensão | Status |
|---|----------|--------|
| 1 | Zero Root Controllers (dir completamente vazio) | ✅ PASS |
| 2 | Storage via SaasFileService | ✅ PASS |
| 3 | `env()` banido fora de Config/MotherShipService | ✅ PASS |
| 4 | Log::debug banido em produção | ✅ PASS |
| 5 | Jobs: `Log::error()` + `return` sem `throw` | ✅ PASS |
| 6 | Docblocks sem menções ambíguas a APIs proibidas | ✅ PASS |
| 7 | ARCHITECTURE_dir.md sincronizado com VERSION | ✅ PASS |
| **TOTAL** | **Score Final** | **🏆 9.9 / 10** |

### 4.84 Manutenção — Docker Hub Update v3.54.0 (v3.54.0)

*   **Decisão:** Atualizar a imagem oficial do Docker Hub (`suitezap/lawfirm`) para consolidar as integrações de triagem, escavador, Chatwoot e correções acumuladas até a versão v3.54.0.
*   **Mudanças:**
    *   **`docker/entrypoint.sh` (linha 4):** String de startup atualizada para `LF v3.54.0`.
    *   **`CHANGELOG.md`:** Adicionada entrada para a versão v3.54.0.
    *   **Imagens publicadas:** `suitezap/lawfirm:v3.54.0` e `suitezap/lawfirm:latest` publicadas no Docker Hub Registry.
*   **Verificação:** Executado `docker run --rm suitezap/lawfirm:v3.54.0` confirmando a string `🚀 Iniciando LawFirm SaaS v6.2 (LF v3.54.0)...` no startup do container.

### 4.83 Sincronização Automática de Labels Chatwoot via Movimentação de Kanban (v3.54.0)

*   **Data:** 2026-06-30 | **Implementação:** Antigravity Senior Architect
*   **Motivação:** Quando um Lead é arrastado entre etapas do Kanban de Vendas (`/admin/leads`) ou um Caso é movido no Kanban Jurídico (`/admin/juridico/kanban`), a label do contato correspondente no Chatwoot deve ser atualizada automaticamente. Isso permite que as equipes de atendimento visualizem em tempo real o estágio de cada cliente nas conversas WhatsApp, sem precisar abrir o CRM.

#### Arquitetura da Solução

O fluxo é totalmente **assíncrono e não-bloqueante** — o HTTP response retorna imediatamente e a sincronização ocorre via queue worker.

```
[Kanban Lead — drag card]
    → LeadController::updateStage()                           (Krayin nativo)
    → Event::dispatch('lead.update.after', $lead)
    → SyncLeadStageToChatwootListener::handle($lead)          [ShouldQueue]
        ├── MotherShipService::getChatwootConfig() → null? log + return
        ├── Resolve $lead->stage->code → label via STAGE_LABEL_MAP
        ├── Resolve $lead->person->contact_numbers[0]['value'] → phone
        ├── ChatwootService::findOrCreateContact(phone, name)
        └── ChatwootService::syncContactLabels(contactId, label, LEAD_POOL)

[Kanban Jurídico — drag card]
    → LegalKanbanController::updateStage()
    → LegalPipelineService::moveCaseToStage($caso, $stageId)  [DB::transaction]
    → Event::dispatch(new CasoStageUpdated($caso))            ← NOVO (pós-commit)
    → SyncCasoStageToChatwootListener::handle($event)         [ShouldQueue]
        ├── MotherShipService::getChatwootConfig() → null? log + return
        ├── Str::slug($caso->stage->name) → label via STAGE_LABEL_MAP
        ├── Resolve $caso->person->contact_numbers[0]['value'] → phone
        ├── ChatwootService::findOrCreateContact(phone, name)
        └── ChatwootService::syncContactLabels(contactId, label, CASO_POOL)
```

#### Mapeamento de Estágios → Labels Chatwoot

**Kanban de Leads** (`lead_pipeline_stages.code` → label):

| Stage Code | Label Chatwoot |
|:---|:---|
| `new` | `LD_NOVO` |
| `follow-up` | `LD_ACOMP` |
| `prospect` | `LD_QUAL` |
| `negotiation` | `LD_NEG` |
| `won` | `LD_GANHO` |
| `lost` | `LD_PERD` |

**Kanban Jurídico** (`Str::slug(stage->name)` → label) — 12 stages cobertos: `CAS_NOVO`, `CAS_ANAL`, `CAS_AGCLI`, `CAS_PROD`, `CAS_PROT`, `CAS_AGJUD`, `CAS_PRAZO`, `CAS_AUD`, `CAS_SENT`, `CAS_RECUR`, `CAS_EXEC`, `CAS_ENCER`.

#### Estratégia `syncContactLabels` — Replace Parcial de Labels

O método opera em **todas as conversas abertas** do contato:
1. `GET /conversations/{id}/labels` — lê labels atuais
2. Remove apenas labels do pool da categoria (ex: todas as `LD_*` para Leads)
3. Adiciona a nova label de stage
4. `PUT /conversations/{id}/labels` — salva o array resultante

Labels de outras categorias (`ORG_WHATS`, `CLI_PF`, `FIN_ADIM`, etc.) são **sempre preservadas**.

#### Arquivos Criados / Modificados

| Operação | Arquivo | Descrição |
|:---|:---|:---|
| **CRIADO** | `Legal/Events/CasoStageUpdated.php` | Evento tipado disparado após movimentação de Caso no Kanban Jurídico |
| **CRIADO** | `Legal/Listeners/SyncLeadStageToChatwootListener.php` | Escuta `lead.update.after` — mapeia `stage->code`, resolve telefone do `Person` (JSON column `contact_numbers`), sincroniza labels |
| **CRIADO** | `Legal/Listeners/SyncCasoStageToChatwootListener.php` | Escuta `CasoStageUpdated` — mapeia via `Str::slug(stage->name)`, idêntica lógica de sincronização |
| **MODIFICADO** | `Atendimento/Services/ChatwootService.php` | +4 métodos: `createContact`, `findOrCreateContact`, `getContactConversations`, `syncContactLabels` |
| **MODIFICADO** | `Legal/Services/LegalPipelineService.php` | `moveCaseToStage()` captura resultado da `DB::transaction` em `$updatedCaso` e despacha `CasoStageUpdated` após commit |
| **MODIFICADO** | `Providers/EventServiceProvider.php` | Registra `CasoStageUpdated → SyncCasoStageToChatwootListener` no array `$listen` |
| **MODIFICADO** | `Providers/LawFirmServiceProvider.php` | Adiciona `SyncLeadStageToChatwootListener` ao evento `lead.update.after` (linha 270) |

#### Regras de Ouro Aplicadas

*   **Zero `.env`:** Credenciais Chatwoot exclusivamente via `MotherShipService::getChatwootConfig()`.
*   **Degradação Graciosa:** Se `getChatwootConfig()` retorna `null` (Chatwoot não configurado para o tenant), o Listener registra `Log::info` silencioso e retorna — **nunca lança exceção**.
*   **`ShouldQueue` com `tries = 1`:** Listeners enfileirados; nenhuma retentativa para evitar flood na API do Chatwoot.
*   **Evento pós-commit:** `CasoStageUpdated` é disparado **após** o `DB::transaction` commits — Listeners enxergam estado persistido.
*   **Idempotência:** `findOrCreateContact` não duplica contatos no Chatwoot.
*   **Dados do Person:** Telefones extraídos de `$person->contact_numbers` (JSON column com cast `array`, estrutura `[['value' => '...', 'label' => '...'], ...]`). Sem relação Eloquent — acesso direto ao array.

#### Score de Conformidade DDD pós-v3.54.0

| # | Dimensão | Status |
|---|----------|--------|
| 1 | Zero Root Controllers | ✅ PASS |
| 2 | Zero `.env` / MotherShipService | ✅ PASS |
| 3 | `ShouldQueue` + `tries=1` (sem bloquear HTTP) | ✅ PASS |
| 4 | Evento pós-commit (não dentro da transaction) | ✅ PASS |
| 5 | Degradação graciosa (sem throw para o caller) | ✅ PASS |
| 6 | Namespace DDD `SuiteZap\LawFirm\{Domain}\{Type}` | ✅ PASS |
| **TOTAL** | **Score Final** | **🏆 10 / 10** |

### 4.85 Correção de Bug Crítico — Separação de `account_id` e `inbox_id` no Chatwoot (v3.54.1)

*   **Data:** 2026-07-01 | **Implementação:** Antigravity Senior Architect
*   **Motivação:** A coluna `chatwoot_inbox_id` na tabela `tenants` do Mothership estava sendo usada de forma ambígua — guardava ora o `account_id` (ID da conta Chatwoot), ora o `inbox_id` (ID da caixa de entrada). Isso causava dois bugs críticos:
    1.  `ChatwootWebhookController` — guard de `inbox_id` comparava valor errado → risco de **cross-tenant event leakage**.
    2.  `ChatwootService::createContact()` — `inbox_id` errado passado ao criar contatos no Chatwoot (erro `422` ou criação em inbox incorreto).

#### Solução Implementada

| Coluna | Semântica definitiva |
|:---|:---|
| `chatwoot_inbox_id` | **account_id** — ID numérico da conta Chatwoot (legado, mantida) |
| `chatwoot_channel_inbox_id` | **inbox_id** — ID real da Caixa de Entrada (NOVA) |

#### Arquivos Criados / Modificados

| Operação | Arquivo | Descrição |
|:---|:---|:---|
| **CRIADO** | `Database/Migrations/2026_07_01_000001_add_chatwoot_channel_inbox_id_to_tenants.php` | Migration Laravel que adiciona `chatwoot_channel_inbox_id INT UNSIGNED NULL` na tabela `tenants` (conexão `mothership`). Idempotente via `hasColumn()`. |
| **MODIFICADO** | `SaaS/Models/Tenant.php` | `$fillable` atualizado com `chatwoot_node_id`, `chatwoot_inbox_id`, `chatwoot_channel_inbox_id`, `chatwoot_webhook_token`. Adicionado relationship `chatwootNode()`. |
| **MODIFICADO** | `SaaS/Services/MotherShipService.php` | `getChatwootConfig()` corrigido — `inbox_id` agora lido de `chatwoot_channel_inbox_id` (correto) e `account_id` mantido em `chatwoot_inbox_id` / `meta_data.account_id`. |

#### Fluxo de Dados Corrigido

```
MotherShip UI
  chatwoot_inbox_id         → account_id (ID da conta — ex: 1)
  chatwoot_channel_inbox_id → inbox_id   (ID da caixa — ex: 3)

getChatwootConfig() retorna:
  account_id ← $meta['account_id'] ?? $tenant->chatwoot_inbox_id
  inbox_id   ← $tenant->chatwoot_channel_inbox_id   ← CORRIGIDO

ChatwootService::createContact()  usa config['inbox_id'] ← correto
ChatwootWebhookController         valida payload.inbox_id == config['inbox_id'] ← correto
```

#### Score de Conformidade DDD pós-v3.54.1

| # | Dimensão | Status |
|---|----------|--------|
| 1 | Migration idempotente com `hasColumn()` | ✅ PASS |
| 2 | Conexão explícita `mothership` na migration | ✅ PASS |
| 3 | `Tenant.$fillable` completo (sem mass-assignment risk) | ✅ PASS |
| 4 | `getChatwootConfig()` documentado via PHPDoc com semântica de colunas | ✅ PASS |
| 5 | Mothership UI (dashboard + tenants) com campos separados | ✅ PASS |
| 6 | Retrocompatibilidade preservada (`chatwoot_inbox_id` mantida) | ✅ PASS |
| **TOTAL** | **Score Final** | **🏆 10 / 10** |

---

### 4.85 Consolidação do Docker Hub — Imagem Canônica `suitezap/lawfirm` (v3.54.1)

*   **Decisão:** Consolidar em **uma única imagem Docker oficial** para eliminar ambiguidade entre as duas imagens que existiam no Docker Hub: `suitezap/adv-crm` (legada, descontinuada) e `suitezap/lawfirm` (canônica, ativa).

*   **Contexto:** A imagem `suitezap/adv-crm` foi criada nas primeiras versões do projeto quando o repositório ainda se chamava `adv-crm`. Com a adoção do nome de produto **LawFirm / SuiteZap**, a imagem `suitezap/lawfirm` tornou-se a referência oficial desde a v3.20. A coexistência das duas causava confusão na hora do deploy.

*   **Ação Realizada:**
    *   **Descontinuada:** `suitezap/adv-crm` — removida do Docker Hub (repositório arquivado).
    *   **Canônica:** `suitezap/lawfirm` — única imagem oficial, sempre atualizada.
    *   **`docker-stack-template.yml`:** Corrigida referência de `v3.53.1` → `v3.54.0`.

*   **Regra Definitiva — Imagem Docker:**

    > ⛔ **NUNCA** usar ou referenciar `suitezap/adv-crm` em qualquer contexto.
    > ✅ **SEMPRE** usar `suitezap/lawfirm` com tag semântica de versão.

    ```bash
    # Padrão de build e push
    docker build -t suitezap/lawfirm:vX.Y.Z -t suitezap/lawfirm:latest .
    docker push suitezap/lawfirm:vX.Y.Z
    docker push suitezap/lawfirm:latest

    # Docker Stack (Swarm / Portainer)
    image: suitezap/lawfirm:vX.Y.Z   # sempre versão específica, nunca :latest em produção
    ```

*   **Tags publicadas no Docker Hub (histórico):**

    | Tag | Versão LawFirm | Data | Status |
    |---|---|---|---|
    | `latest` | v3.54.0 | 2026-06-30 | ✅ Ativo |
    | `v3.54.0` | v3.54.0 | 2026-06-30 | ✅ Ativo |
    | `v3.51.0` | v3.51.0 | 2026-06 | ✅ Ativo |
    | `v3.50.0` | v3.50.0 | 2026-06 | ✅ Ativo |
    | `v3.20` / `v1.7` | v3.20 | 2026-04 | ⚠️ Legado |

