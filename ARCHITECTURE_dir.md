# 📂 LawFirm CRM - Arquitetura de Diretórios e Telas (UI) - Krayin v2.1.6 / LF v3.20

Este documento mapeia visualmente a estrutura de pastas do pacote **SuiteZap/LawFirm** (baseado na arquitetura Domain-Driven Design - DDD) e detalha quais telas (Views) são entregues à interface do usuário.

---

## 1. Estrutura Raiz de Domínios (`src/`)

Desde a versão v3.18, o pacote possui **Dívida Técnica Zero** na raiz. Todos os arquivos de negócio estão encapsulados em seus domínios:

```text
packages/SuiteZap/LawFirm/src/
├── AI/                 # Domínio: Inteligência Artificial (Assistentes, Prompts)
├── Config/             # Configurações estáticas do pacote
├── Console/            # Comandos Artisan/CLI do módulo
├── Contracts/          # Interfaces (ex: Repository patterns)
├── Database/           # Migrations e Seeders isolados do LawFirm
├── Escavador/          # Domínio: Integração com API Escavador (Monitoramentos)
├── Financial/          # Domínio: Gestão Financeira (Receitas, Despesas, Faturas)
├── GED/                # Domínio: Gestão Eletrônica de Documentos (Anexos, S3)
├── Http/               # Roteamento (Master loader e routes dedicados)
├── Legal/              # Domínio Principal: Processos, Prazos e Contatos
├── Providers/          # ServiceProviders e macros do pacote
├── Resources/          # Assets estáticos (Views Blade, Lang, CSS, JS)
├── SaaS/               # Domínio: Infraestrutura Multi-Tenant e Pagamentos
└── Whatsapp/           # Domínio: Mensageria (Evolution API)
```

> [!WARNING]
> **Sincronia com o Entrypoint Docker:** Ao refatorar, mover ou deletar diretórios do Master/Webkul (ex: `Webkul/Mail`), é estritamente obrigatório remover o `--path` correspondente no arquivo `docker/entrypoint.sh`. Deixar um caminho fantasma causa exceção de `Migration path not found` no boot do container, resultando em rejeição imediata da stack no Docker Swarm.

---

## 2. Anatomia Padrão de um Domínio (Subdiretórios)

Cada pasta de domínio (ex: `Legal/`, `Financial/`, `SaaS/`) obedece a uma hierarquia rigorosa MVC/DDD:

```text
{NomeDoDominio}/
├── DataGrids/          # Classes que geram as tabelas listáveis no Frontend (Vue/Krayin)
├── Events/             # Eventos locais disparados dentro do domínio
├── Http/
│   ├── Controllers/    # Orquestram as requisições HTTP e retornam as Views/JSON
│   └── Requests/       # FormRequests (Validação de dados do Laravel)
├── Listeners/          # Ouvintes que reagem aos Events
├── Models/             # Entidades Eloquent (Tabelas do Banco)
├── Observers/          # Gatilhos automáticos de banco (creating, updated, deleted)
└── Services/           # O coração da regra de negócio (Skinny Controllers delegam para cá)
```

---

## 3. Mapeamento de Telas Apresentadas ao Usuário

As interfaces visuais (HTML/Blade) ficam armazenadas globalmente em `src/Resources/views/`. Abaixo está o mapeamento funcional de como o usuário navega por essas telas.

### 🏛️ Módulo Jurídico Central (`views/Legal/` e `views/admin/processos/`)
*   **Listagem de Processos:** Uma tabela interativa (DataGrid) listando processos ativos, arquivados, status, clientes e datas.
*   **Ficha do Processo (Edição):** A tela mais importante e complexa do sistema. É dividida no padrão *External Tabs*:
    *   **Aba Prazos:** Timeline interativa para gerenciar agendas processuais.
    *   **Aba Notas:** Bloco de anotações internas.
    *   **Aba Informações:** Formulário primário com o OAB, Tribunal, Juízo e Partes.
*   **Gestão de Prazos:** Tela dedicada de calendário ou lista consolidando os prazos pendentes de todos os processos daquele advogado.
*   **Fichas de Clientes (Contacts/Leads):** Extensão das telas do CRM injetando dados específicos jurídicos (OAB do lead, cpf/cnpj).

### 💰 Módulo Financeiro (`views/Financial/`)
*   **Aba Financeiro (dentro do Processo):** Sub-tela para cadastrar receitas e despesas vinculadas a um caso (ex: Honorários, Custas Judiciais). 
    *   *Features UI:* Botões dinâmicos de parcelamento, Quick Pay (Baixa Rápida) e botões de cobrança via WhatsApp (que disparam via `FinancialController`).
*   **Dashboard Financeiro Global:** Gráficos e indicadores gerais de faturamento do escritório, fluxo de caixa e inadimplência.
*   **Fatura/Recibo (PDF):** Não é uma tala de navegação, mas um documento visual gerado via `DomPDF` em runtime. Acessa sempre a marca d'água e logotipo do tenant em questão via `SaasFileService`.

### 📂 Gestão de Documentos - GED (`views/GED/` e `views/documents/`)
*   **Aba Documentos (dentro do Processo):** Sub-tela onde o usuário faz o upload (S3/MinIO), visualiza, arquiva ou gera peças processuais a partir de templates dinâmicos.
*   *Nota:* Utiliza o padrão *AJAX FormData* para contornar o limite de formulários aninhados do HTML.

### 🤖 Assistentes de IA e Automação (`views/admin/assistants/`)
*   **Painel da IA (Index):** Lista todos os agentes de IA disponíveis para o escritório (sincronizados com o Mothership).
*   **Chat/Console de IA:** Tela onde o advogado interage com o assistente (ex: Analisador de Sentenças, Criador de Petição) alimentando o contexto do Lead ou Processo correspondente.
*   **Histórico de Execuções:** DataGrid contendo respostas prévias e tokens gastos localmente (`AiExecution`).

### 🔎 Integração Escavador (`views/admin/escavador/`)
*   **Painel do Escavador:** Dashboard com créditos restantes da API, e opções para consultar Termos e OABs.
*   **Histórico e Timeline:** Telas que exibem o log de movimentações capturadas nos Diários Oficiais, e permitem indexá-las rapidamente em Processos reais dentro do CRM.
*   **Status de Monitoramentos:** Tabela exibindo OABs e Diários escutados ativamente.

### ☁️ Minha Assinatura & Configurações (`views/admin/saas/` e `whatsapp/`)
*   **Painel da Assinatura (SaaS Dashboard):** Mostra os dados cadastrais do escritório, a assinatura Asaas vigente, consumo de espaço do bucket (HD) e créditos de Inteligência Artificial restantes.
*   **Checkout & Adicionais:** Telas e modais SPA (Single Page Application) onde o cliente insere o Cartão de Crédito ou escaneia QR Code PIX para upgrades (com validação `['DETACHED', 'INSTALLMENT']` no back-end).
*   **Dados de Faturamento (`billing-info`):** Formulário dedicado para o preenchimento dos dados fiscais e de cobrança do assinante. Suporta dois modos:
    *   **Pessoa Física (PF):** Campos de Nome Completo e CPF separados.
    *   **Pessoa Jurídica (PJ):** Campos de Razão Social, Nome do Responsável e CNPJ separados.
    *   Toggle PF/PJ via radio buttons com troca dinâmica dos campos visíveis (Vanilla JS). Dados persistidos nos campos individuais (`cpf`, `cnpj`, `company_name`) no MotherShip, mantendo compatibilidade com o campo legado `cpf_cnpj`.
*   **WhatsApp / Conexões:** Tela técnica simples exigindo a leitura do QR Code do WhatsApp via API Evolution, para permitir disparos de Prazos, Avisos do Escavador e Faturas.

---

## 4. Gráfico de Navegação e Fluxo do Usuário (UI/UX Flow)

O diagrama abaixo ilustra o mapa do site (Sitemap) sob a perspectiva do advogado logado no CRM (`/admin/juridico`). Cada nó principal representa um domínio de negócio no back-end.

```mermaid
graph TD
    %% Nós Principais (Menu Lateral do CRM)
    Dashboard[🏠 Dashboard Krayin]
    MenuJuridico[⚖️ Jurídico (Módulo LawFirm)]
    
    Dashboard --> MenuJuridico

    %% Módulos do Sistema
    MenuJuridico --> Processos[📁 Processos & Casos]
    MenuJuridico --> Agenda[📅 Prazos e Agenda]
    MenuJuridico --> Financeiro[💸 Financeiro Ext.]
    MenuJuridico --> Escavador[🔎 Escavador (Diários)]
    MenuJuridico --> Servicos[🤖 Serviços (IA & WhatsApp)]
    MenuJuridico --> Configuracoes[⚙️ Assinatura & Setup]

    %% Detalhamento: Processos (Coração do Sistema)
    Processos -->|/admin/juridico/processos| ListaProcessos[Tabela de Processos]
    ListaProcessos -->|/processos/{id}/edit| FichaProcesso{Ficha do Processo}
    
    %% Abas Externas da Ficha do Processo
    FichaProcesso -->|Aba 1| AbaInfo[Info Básicas & Tribunal]
    FichaProcesso -->|Aba 2| AbaPrazos[Histórico de Prazos]
    FichaProcesso -->|Aba 3| AbaNotas[Anotações]
    FichaProcesso -->|Aba 4| AbaChecklists[Checklist de Tarefas]
    FichaProcesso -->|Aba 5 - GED| AbaDocumentos[Documentos & Uploads S3]
    FichaProcesso -->|Aba 6 - Financial| AbaFinanceira[Receitas & Despesas do Caso]

    %% Ações dentro das Abas
    AbaFinanceira -->|Ação| CobrancaZap[📲 Enviar Cobrança WhatsApp]
    AbaFinanceira -->|Ação| GerarRecibo[📄 Gerar PDF de Recibo]
    AbaDocumentos -->|Ação| GerarPeca[🧠 Gerar Peça com IA]

    %% Detalhamento: Integracoes e Inteligencia
    Escavador -->|/admin/juridico/escavador/termos| ListaBuscas[Monitoramentos OAB/Termos]
    ListaBuscas --> HistoricoEscavador[Timeline de Publicações]
    HistoricoEscavador --> VincularProcesso[Vincular ao Processo]

    Servicos -->|/admin/juridico/assistants| ListaIA[Assistentes de IA Disponíveis]
    ListaIA --> ChatIA[Chatbot Especialista]
    
    Servicos -->|/admin/juridico/whatsapp| TelaZap[Conectar WhatsApp (QR Code)]

    %% Detalhamento: SaaS e Billing
    Configuracoes -->|/admin/juridico/assinatura| SaasDash[Gestão de Assinatura]
    SaasDash --> CheckoutPlan[Modal Pagamento Assinatura]
    SaasDash --> CheckoutCredit[Modal Compra Créditos IA]
    SaasDash -->|/admin/juridico/billing-info| BillingInfo[Dados de Faturamento PF/PJ]
    
    %% Estilização
    style FichaProcesso fill:#2a9d8f,stroke:#fff,stroke-width:2px,color:#fff
    style AbaFinanceira fill:#e9c46a,color:#000
    style AbaDocumentos fill:#e9c46a,color:#000
    style CobrancaZap fill:#25D366,color:#fff
    style GerarPeca fill:#8338ec,color:#fff
```

## 5. Detalhamento de Rotas e URLs (Endpoints de UI)

Para suporte ao desenvolvimento e *debugging*, esta é a taxonomia padrão das URLs apresentadas no Front-End:

| Domínio / Módulo | Base URL (Endpoint Front-End) | Descrição da Tela / View Blade |
| :--- | :--- | :--- |
| **Legal** | `/admin/juridico/processos` | Lista global de processos (DataGrid Vue/Krayin). |
| **Legal** | `/admin/juridico/processos/create` | Formulário para registrar novo caso. |
| **Legal** | `/admin/juridico/processos/{id}/edit` | Hub central (Ficha do Processo) carregando os módulos *GED*, *Financial*, e *Checklist* via ajax/tabs. |
| **Legal** | `/admin/juridico/prazos` | Quadro global visualizando todos os prazos ordenados por urgência. |
| **Escavador** | `/admin/juridico/escavador/termos` | Configuração de monitoramentos de Nome e OAB. |
| **Escavador** | `/admin/juridico/escavador/historico` | Timeline diária das publicações capturadas nos Diários Oficiais. |
| **AI (Assistentes)** | `/admin/juridico/assistants` | Vitrine de Assistentes e Agentes configurados pelo Mothership Panel. |
| **AI (Assistentes)** | `/admin/juridico/assistants/{slug}` | Tela do Chatbot para usar prompts contextuais (ex: Resumo de Sentença). |
| **SaaS** | `/admin/juridico/assinatura` | Gestão do Tenant: Planos, limites de S3, saldo bancário Asaas e consumo de IA. |
| **SaaS** | `/admin/juridico/billing-info` | Dados de Faturamento: Formulário PF/PJ com campos individuais `cpf`/`cnpj`/`company_name`. Toggle dinâmico de tipo de pessoa. |
| **Whatsapp** | `/admin/juridico/whatsapp` | Status da Evolution API e espelhamento de QR Code para o smartphone do advogado. |

> Todas estas rotas são agrupadas sob os middlewares `['web', 'admin_locale', 'user']` do Krayin (garantindo que apenas usuários autenticados daquele Tenant específico tenham acesso).

---

## 6.6 Chamadas de Rotas Laravel no JavaScript (REPLACE_ID Pattern)
No Laravel 10/11 (Krayin v2.x), gerar rotas em views Blade passando parâmetros dinâmicos vazios (`route('admin.name', '')`) em funções JavaScript **lança uma exceção fatal (`UrlGenerationException`)** durante a compilação da tela, resultando em um **Erro 500 total**.

**Solução Padrão (REPLACE_ID):**
Nunca deixe parâmetros obrigatórios de rota vazios no compilador Blade quando for utilizar Javascript em seguida. Use um placeholder seguro em caixa alta (ex: `REPLACE_ID`) e substitua via String nativo antes de realizar o fetch.

**❌ PROIBIDO (Gera Erro 500 no Back-end):**
```javascript
const response = await fetch("{{ route('admin.api.action', '') }}/" + jsId);
```

**✅ OBRIGATÓRIO (REPLACE_ID):**
```javascript
const baseRoute = "{{ route('admin.api.action', 'REPLACE_ID') }}";
const finalUrl = baseRoute.replace('REPLACE_ID', jsId);
const response = await fetch(finalUrl);
```

*Gerado pela auditoria de mapeamento em Abril/2026 (v3.20 SaaS Compliance).*
