# 🏛 LawFirm CRM - Documento de Arquitetura (v3.20 - DDD & SaaS Multi-Tenant)
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
| **Legal** | `SuiteZap\LawFirm\Legal` | Core jurídico (Processos, Prazos, Checklists). | `Processo`, `Prazo`, `CaseChecklist` |
| **Financial**| `SuiteZap\LawFirm\Financial` | Honorários, Custas e Faturamento. | `Financial` |
| **GED** | `SuiteZap\LawFirm\GED` | Gestão de Arquivos, Anexos e Checklists. | `ProcessDocument`, `Anexo` |
| **SaaS** | `SuiteZap\LawFirm\SaaS` | Infraestrutura Multi-tenant. | `Tenant`, `Subscription`, `InfrastructureNode` |
| **AI** | `SuiteZap\LawFirm\AI` | Assistentes e Automação. | `AiExecution`, `AssistantTemplate`, `AssistantHistory` |
| **Escavador** | `SuiteZap\LawFirm\Escavador` | Integração com a API do Escavador (v1/v2). |  `EscavadorRequest` |

## 3. Regras de Ouro (Development Standards)

### 3.1 Manipulação de Arquivos (Ironclad Rule)
⛔ **PROIBIDO:** Usar `Storage::put`, `Storage::makeDirectory` ou acesso direto ao disco local.
✅ **OBRIGATÓRIO:** Usar `SuiteZap\LawFirm\SaaS\Services\SaasFileService`.
*   Motivo: O sistema deve suportar S3/MinIO e isolamento lógico por Tenant.

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

## 5. Auditoria Estrutural e Mapa de Dívida Técnica (v3.18)

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

    %% --- INFRAESTRUTURA SAAS (azul) ---
    namespace SaaS_Domain {
        class MotherShipService
        class SaasTransactionController
        class Subscription
        class Tenant
        class AsaasService
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
    GED_Domain ..> SaaS_Domain : SaasFileService → S3
    Whatsapp_Domain ..> SaaS_Domain : getEvolutionConfig

    style Legal_Domain fill:#2a9d8f,color:#fff
    style Financial_Domain fill:#2a9d8f,color:#fff
    style GED_Domain fill:#2a9d8f,color:#fff
    style Whatsapp_Domain fill:#2a9d8f,color:#fff
    style AI_Domain fill:#e9c46a,color:#000
    style Escavador_Domain fill:#e9c46a,color:#000
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

**🟢 Status Atual — Dívida Técnica Zero (v3.20):**
A pasta `src/` está 100% esterilizada. Todos os Bounded Contexts possuem estrutura `Models/Http/Controllers/Services/DataGrids` completa. Nenhum arquivo de lógica/negócio reside fora de seu domínio. O pacote está preparado para escala SaaS multi-tenant corporativa.

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
3.  **Segurança (CSRF):** O token CSRF deve ser lido dinamicamente da meta tag (ex: `window.lfDocsGetCsrfToken()`) e injetado nos *headers* da requisição (`X-CSRF-TOKEN`).
4.  **XMLHttpRequest/Fetch:** A submissão ocorre de forma totalmente assíncrona. Em caso de sucesso (status 200), realiza-se um `window.location.reload()` ou uma atualização controlada do DOM para exibir os novos registros.