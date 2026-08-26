# 📂 LawFirm CRM - Arquitetura de Diretórios e Telas (UI) - Krayin v2.1.6 / LF v3.55.0

Este documento mapeia visualmente a estrutura de pastas do pacote **SuiteZap/LawFirm** (baseado na arquitetura Domain-Driven Design - DDD), a infraestrutura de qualidade viva (`quality/`) e detalha quais telas (Views) são entregues à interface do usuário.

---

## 0. Infraestrutura Permanente de Qualidade e Memória Operacional (`quality/`)

A infraestrutura de governança viva, catálogo de testes automatizados e memória operacional reside na raiz do repositório:

```text
quality/
├── AGENTS.md                   # Regras operacionais da suíte de qualidade
├── README.md                   # Índice central, arquitetura e guia de execução rápida
├── TEST_CATALOG.yaml           # Fonte da verdade do catálogo de testes (com ciclo de vida formal)
├── COVERAGE_MATRIX.md          # Matriz de rastreabilidade de cobertura gerada automaticamente
├── CHANGELOG.md                # Histórico de alterações e manutenções de testes
├── KNOWN_GAPS.md               # Lacunas conhecidas, débitos mapeados e riscos aceitos
├── RELEASE_CHECKLIST.md        # Checklist operacional para homologação e release
├── adr/                        # Architecture Decision Records específicos de Qualidade
│   ├── ADR-001-multi-database-isolation.md
│   ├── ADR-002-playwright-python-stack.md
│   ├── ADR-003-ai-testing-strategy.md
│   └── ADR-004-document-validation-gate.md
├── modules/                    # Documentação viva por módulo funcional (requisitos e testes)
│   ├── auth.md
│   ├── chatwoot.md
│   ├── lead.md
│   ├── legal-orchestrator.md
│   ├── ai-assistant.md
│   ├── tenant-isolation.md
│   └── governance.md
├── runbooks/                   # Guias práticos de diagnóstico e execução
│   ├── run-tests-local.md
│   ├── run-tests-docker.md
│   └── investigate-failures.md
└── scripts/                    # Automação de validação documental e geração de matriz
    ├── validate_test_docs.py   # Validador estático (12 regras de integridade)
    ├── generate_coverage_matrix.py
    ├── requirements-quality.txt
    └── tests/
        └── test_validate_test_docs.py
```

---

## 1. Estrutura Raiz de Domínios (`src/`)

Desde a versão v3.18, o pacote possui **Dívida Técnica Zero** na raiz. Desde v3.36, o diretório `src/Http/Controllers/` contém 0 arquivos PHP. Todos os arquivos de negócio estão encapsulados em seus domínios:

```text
packages/SuiteZap/LawFirm/src/
├── AI/                 # Domínio: Inteligência Artificial (Assistentes, Prompts, Triagem)
├── Atendimento/        # Domínio: Atendimento via Chatwoot (canal centralizado — v3.53.0)
├── Config/             # Configurações estáticas do pacote
├── Console/            # Comandos Artisan/CLI do módulo
├── Contracts/          # Interfaces (ex: Repository patterns)
├── DataJud/            # Domínio: Consulta Pública CNJ (DataJud API REST)
├── Database/           # Migrations e Seeders isolados do LawFirm
├── Escavador/          # Domínio: Integração com API Escavador (Monitoramentos)
├── Financial/          # Domínio: Gestão Financeira (Dashboard Interno — Receitas, Despesas)
├── GED/                # Domínio: Gestão Eletrônica de Documentos (Anexos, S3)
├── Http/               # Roteamento (Master loader + Routes dedicados por domínio)
│   └── Routes/           # ⚠️ Http/Controllers/ vazio desde v3.36 (Zero Root Controllers)
├── Legal/              # Domínio Principal: Processos, Prazos e Contatos
├── Providers/          # ServiceProviders e macros do pacote
├── Resources/          # Assets estáticos (Views Blade, Lang, CSS, JS)
├── SaaS/               # Domínio: Infraestrutura Multi-Tenant e Pagamentos (SaaS → Tenant)
├── TenantFinance/      # Domínio: Cobranças do Escritório para Clientes via Asaas (Tenant → Cliente) [v3.40]
└── Whatsapp/           # Domínio: Mensageria (Evolution API)
    * Nota de UI: Formulários de configuração (como Teste de Disparo e Settings) devem adotar obrigatoriamente o padrão "Krayin Native Two-Column Configuration Grid" (grid-cols-[1fr_2fr]) para manter consistência com módulos nativos como Cobranças Asaas.
```

> [!WARNING]
> **Sincronia com o Entrypoint Docker:** Ao refatorar, mover ou deletar diretórios do Master/Webkul (ex: `Webkul/Mail`), é estritamente obrigatório remover o `--path` correspondente no arquivo `docker/entrypoint.sh`. Deixar um caminho fantasma causa exceção de `Migration path not found` no boot do container, resultando em rejeição imediata da stack no Docker Swarm.
> 
> **Zero/Optional Dependencies (.env via Graceful Fallback):** Como os dados essenciais estão no `Mothership Service`, chamadas atreladas a chaves de api e `MOTHERSHIP_BASE_URL` possuem arquitetura "Fallback". O Painel ignora as paralisações HTTP ou ausência de rotas `.env` no arquivo da VPS providenciando tarifas fallback padrões sem derrubar as views, assegurando 100% integridade no dashboard.
> 
> **Nota de Processamento Assíncrono:** O `entrypoint.sh` agora intercepta comandos arbitrários (`$@`) despachados pelo `command:` da Stack do Swarm. Comandos que não sejam o servidor web nativo irão rodar sob a delegação de `su -s /bin/sh www-data -c` para preservar e isolar as permissões de gravação da pasta `storage/logs/laravel.log`.


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
*   **Ficha do Processo (Edição e Visualização):** A tela mais importante e complexa do sistema. Na versão 3.43+, abandona rolagens longas usando o padrão **Navigation Filter Bar** com abas isoladas que salvam o estado no `localStorage` do advogado. O design das divisões segue estritamente o componente `.lf-card` padronizado. É dividida nos módulos:
    *   **📋 Info. Processo:** Formulário primário via abas nativas (OAB, Tribunal, Juízo, Estratégia, DataJud).
    *   **⚖️ Prazos e Tarefas:** Timeline interativa para gerenciar agendas processuais (Vencimentos, Tipos e Status).
    *   **📝 Notas:** Bloco de anotações internas e drafts.
    *   **📎 Documentos e Anexos:** Gestão de arquivos isolados e checklist de documentos anexos via upload S3.
    *   **👥 Partes e Advogados:** Lookup dinâmico de conexões de CRM isolado.
    *   **💰 Financeiro:** Histórico granular de despesas e receitas ligadas à causa, links e Pix de pagamento (Asaas).
*   **Gestão de Prazos:** Tela dedicada de calendário ou lista consolidando os prazos pendentes de todos os processos daquele advogado.
    *   *Features UI:* Integra o alternador rápido (ícone de notificação) do **Robô Agendador de Prazos** para automatizar avisos de SMS/WhatsApp via cronjob.
*   **Fichas de Clientes (Contacts/Leads):** Extensão das telas do CRM injetando dados específicos jurídicos (OAB do lead, cpf/cnpj).

### 💰 Módulo Financeiro (`views/Financial/`)
*   **Aba Financeiro (dentro do Processo):** Sub-tela para cadastrar receitas e despesas vinculadas a um caso (ex: Honorários, Custas Judiciais). 
    *   *Features UI:* Botões dinâmicos de parcelamento, Quick Pay (Baixa Rápida) e botões de cobrança via WhatsApp (que disparam via `FinancialController`).
*   **Dashboard Financeiro Global:** Gráficos e indicadores gerais de faturamento do escritório, fluxo de caixa e inadimplência.
*   **Fatura/Recibo (PDF):** Não é uma tala de navegação, mas um documento visual gerado via `DomPDF` em runtime. Acessa sempre a marca d'água e logotipo do tenant em questão via `SaasFileService`.

### 📂 Gestão de Documentos - GED (`views/GED/` e `views/documents/`)
*   **Aba Documentos (dentro do Processo):** Sub-tela onde o usuário faz o upload (S3/MinIO), visualiza, arquiva ou gera peças processuais a partir de templates dinâmicos.
*   *Nota:* Utiliza o padrão *AJAX FormData* para contornar o limite de formulários aninhados do HTML.

### 🤖 Assistentes (IA e Inteligência Jurídica) (`views/admin/assistants/` e `views/admin/escavador/`)
- Módulo consolidado em um menu raiz dedicado ("Assistentes").
*   **Painel da IA (Index):** Lista todos os agentes de IA disponíveis para o escritório (sincronizados com o Mothership).
*   **Chat/Console de IA:** Tela onde o advogado interage com o assistente (ex: Analisador de Sentenças, Criador de Petição) alimentando o contexto do Lead ou Processo correspondente.
*   **Histórico de Execuções IA:** DataGrid contendo respostas prévias e telemetria financeira avançada. Agora inclui a coluna **Origem (Lead)** e a coluna **Cliente**, cruzando dados para rastreabilidade imediata da Triagem. O layout possui links limpos (em formato badge) apontando direto para o registro base, e correções de espaçamento no visualizador de "Dados de Entrada".
*   **Assistente Jurídico (Painel do Escavador):** Dashboard com saldo de **SuiteCoins (Ƶ)** e um grid filtrável de consultas legais. A UI foi consolidada para focar apenas nas categorias de domínio (Processos, Pessoas, Empresas, Advogados, Jurisprudência, etc.). Os tradicionais botões de chaveamento técnico "API V1" e "API V2" foram suprimidos do painel para priorizar a experiência do usuário final.
*   **Histórico e Timeline:** Telas que exibem o log de movimentações capturadas nos Diários Oficiais, e permitem indexá-las rapidamente em Processos reais dentro do CRM.
*   **Status de Monitoramentos:** Tabela (Robôs) exibindo OABs e Diários escutados ativamente no background.

### 💳 Cobranças do Escritório - TenantFinance (`views/TenantFinance/`) [v3.40]
*   **Listagem de Cobranças:** Grid (`TenantInvoiceDataGrid`) exibindo as faturas emitidas pelo escritório para seus clientes (Pagas, Vencidas, Pendentes).
*   **Configurações do Asaas:** Tela com o Grid nativo Krayin (1fr 2fr) integrada em `Configurações > Jurídico`, onde o advogado insere sua própria `api_key` da conta Asaas, recebendo margens de `p-6`.
*   **Portal Dialog Modal (Em breve):** Interface embarcada dentro da aba "Financeiro" do Processo para emissão expressa (Faturas transparentes).

### ☁️ Minha Assinatura & Configurações (`views/admin/saas/` e `whatsapp/`)
*   **Painel da Assinatura (SaaS Dashboard):** Mostra os dados cadastrais do escritório, a assinatura Asaas vigente, consumo de espaço do bucket (HD) e créditos de Inteligência Artificial restantes.
*   **Checkout & Adicionais:** Telas e modais SPA (Single Page Application) onde o cliente insere o Cartão de Crédito ou escaneia QR Code PIX para upgrades (com validação `['DETACHED', 'INSTALLMENT']` no back-end).
*   **Meus Pedidos e Transações (`Orders` / `Additions`):** Telas com DataGrids (`SaasOrdersDataGrid` e `SaasAdditionsDataGrid`) contendo o histórico de tentativas de pagamento, compra de tokens/planos e extratos detalhados (com garantias estritas de isolamento do Tenant). A rastreabilidade atrela cada transação ao usuário final que a requisitou.
*   **Dados de Faturamento (`billing-info`):** Formulário dedicado para o preenchimento dos dados fiscais e de cobrança do assinante. Suporta dois modos:
    *   **Pessoa Física (PF):** Campos de Nome Completo e CPF separados.
    *   **Pessoa Jurídica (PJ):** Campos de Razão Social, Nome do Responsável e CNPJ separados.
    *   Toggle PF/PJ via radio buttons com troca dinâmica dos campos visíveis (Vanilla JS). Dados persistidos nos campos individuais (`cpf`, `cnpj`, `company_name`) no MotherShip, mantendo compatibilidade com o campo legado `cpf_cnpj`.
*   **WhatsApp / Conexões:** Tela técnica simples exigindo a leitura do QR Code do WhatsApp via API Evolution, para permitir disparos de Prazos, Avisos do Escavador e Faturas.
*   **Modais de Histórico (`admin/processos/modals/`):**
    *   **Import Modal:** Janela compacta (`max-w-sm`) para seleção de filtros de data para importação em background.
    *   **History Modal:** Visualizador de chat de alta fidelidade (`max-w-6xl`) usando o *Portal Dialog Pattern* para evitar conflitos com o Vue.js. 
    *   **Chat Viewer:** Componente Blade com estilos inline que renderiza bolhas de conversa (Advogado/Esquerda/Branco vs Cliente/Direita/Verde Pastel). Implementa o padrão de **Emoji Tagging** para mídia (`📷 [Imagem]`, `🎵 [Áudio]`).


---

## 4. Gráfico de Navegação e Fluxo do Usuário (UI/UX Flow)

O diagrama abaixo ilustra o mapa do site (Sitemap) sob a perspectiva do advogado logado no CRM (`/admin/juridico`). Cada nó principal representa um domínio de negócio no back-end.

```mermaid
graph TD
    %% Nós Principais (Menu Lateral do CRM)
    Dashboard[🏠 Dashboard Krayin]
    MenuJuridico[⚖️ Jurídico (Módulo LawFirm)]
    
    Dashboard --> MenuJuridico
    Dashboard --> MenuFinanceiro[💸 Financeiro]

    %% Submódulos Top-level MenuFinanceiro
    MenuFinanceiro --> DashboardFinanceiro[📊 Dashboard Gráfico]
    MenuFinanceiro --> CobrancasFinanceiras[💰 Cobranças Unificadas]

    %% Módulos do Sistema Jurídico
    MenuJuridico --> Processos[📁 Processos & Casos]
    MenuJuridico --> AgendaJuridica[📅 Agenda Jurídica]
    MenuJuridico --> Agenda[📅 Prazos e Agenda]
    MenuJuridico --> Assistentes[🤖 Assistentes (IA e Inteligência Jurídica)]
    MenuJuridico --> Servicos[📲 Serviços (WhatsApp)]
    MenuJuridico --> Configuracoes[⚙️ Assinatura & Setup]

    %% Detalhamento: Processos (Coração do Sistema)
    Processos -->|/admin/juridico/processos| ListaProcessos[Tabela de Processos]
    Processos -->|/admin/juridico/kanban| KanbanCasos[Kanban de Casos]
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

    %% Detalhamento: Agenda Jurídica (FullCalendar)
    AgendaJuridica -->|/admin/juridico/agenda| CalendarioFC[FullCalendar Vanilla JS]

    %% Detalhamento: Integracoes e Inteligencia
    Assistentes -->|/admin/juridico/escavador/termos| ListaBuscas[Monitoramentos OAB/Termos]
    ListaBuscas --> HistoricoEscavador[Timeline de Publicações]
    HistoricoEscavador --> VincularProcesso[Vincular ao Processo]

    Assistentes -->|/admin/juridico/assistants| ListaIA[Assistentes de IA Disponíveis]
    ListaIA --> ChatIA[Chatbot Especialista]
    
    Servicos -->|/admin/juridico/whatsapp| TelaZap[Conectar WhatsApp (QR Code)]

    %% Detalhamento: SaaS e Billing
    Configuracoes -->|/admin/juridico/assinatura| SaasDash[Gestão de Assinatura]
    SaasDash --> CheckoutPlan[Modal Pagamento Assinatura]
    SaasDash --> CheckoutCredit[Modal Compra Créditos IA]
    SaasDash -->|/admin/juridico/orders| OrdersDash[Meus Pedidos e Faturas]
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
| **Legal** | `/admin/juridico/kanban` | Quadro Kanban interativo para gestão ágil dos Casos (Drag & Drop via Vanilla JS). |
| **Legal** | `/admin/juridico/casos` | Lista global de casos (DataGrid Vue/Krayin). |
| **Legal** | `/admin/juridico/casos/create` | Formulário para registrar novo caso. |
| **Legal** | `/admin/juridico/casos/{id}/edit` | Hub central (Ficha do Caso) com processos vinculados. |
| **Legal** | `/admin/juridico/processos` | Lista global de processos (DataGrid Vue/Krayin). |
| **Legal** | `/admin/juridico/processos/create` | Formulário para registrar novo processo. |
| **Legal** | `/admin/juridico/processos/{id}/edit` | Hub central (Ficha do Processo) carregando os módulos *GED*, *Financial*, e *Checklist* via ajax/tabs. |
| **Legal** | `/admin/juridico/prazos` | Quadro global visualizando todos os prazos ordenados por urgência. |
| **Legal** | `/admin/juridico/agenda` | Agenda Jurídica unificada (FullCalendar Vanilla JS). Combina Atividades do Krayin e Prazos LawFirm. Suporta `?clean=true` para renderização em modo Modal/Window. |
| **Escavador** | `/admin/juridico/escavador/termos` | Configuração de monitoramentos de Nome e OAB. |
| **Escavador** | `/admin/juridico/escavador/historico` | Timeline diária das publicações capturadas nos Diários Oficiais. |
| **AI (Assistentes)** | `/admin/juridico/assistants` | Vitrine de Assistentes e Agentes configurados pelo Mothership Panel. |
| **AI (Assistentes)** | `/admin/juridico/assistants/{slug}` | Tela do Chatbot para usar prompts contextuais (ex: Resumo de Sentença). |
| **DataJud** | `/admin/juridico/datajud` | Consulta pública CNJ (número CNJ, classe+órgão, paginação). |
| **SaaS** | `/admin/juridico/assinatura` | Gestão do Tenant: Planos, limites de S3, saldo bancário Asaas e consumo de IA. |
| **SaaS** | `/admin/juridico/orders` | Tabela (DataGrid) exibindo o histórico de pedidos (Orders) e status de pagamento do usuário. |
| **SaaS** | `/admin/juridico/billing-info` | Dados de Faturamento: Formulário PF/PJ com campos individuais `cpf`/`cnpj`/`company_name`. Toggle dinâmico de tipo de pessoa. |
| **Whatsapp** | `/admin/juridico/whatsapp` | Status da Evolution API e espelhamento de QR Code para o smartphone do advogado. |
| **TenantFinance** | `/admin/juridico/cobrancas` | Grid de listagem (`TenantInvoiceDataGrid`) das cobranças emitidas pelo escritório para seus clientes finais. |
| **TenantFinance** | `/admin/juridico/cobrancas/settings` | Formulário encapsulado nativamente em `Configurações > Jurídico > Cobranças Asaas` com a API Key V3 configurada (Add-on). |
| **Legal** | `/admin/juridico/modelos-documentos` | Tela para o CRUD e gerenciamento de Modelos de Documentos do escritório. |
| **Legal** | `/admin/juridico/processos/{processoId}/modelos` | Aba e renderizador dinâmico de modelos de documentos específicos do processo. |

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

## 6.7 Hidratação de Componentes Vanilla JS (Evitando Blade Runtime Crash)
Ao iterar dezenas de itens no Blade (ex: quadros Kanban) que requerem dados extensos em JSON para tooltips ou modais Vanilla JS, o uso iterativo do `@pushOnce` ou injeção pesada em atributos HTML (`data-payload="{{ json_encode(...) }}"`) causa vazamentos e corrompimento na compilação do Blade ("startPush Null Pointers").

**✅ OBRIGATÓRIO (Global Hash Map):**
Toda hidratação massiva deve ser computada como Hash Map Associativo no Controller, transferida via var única e registrada explicitamente como `<script> window.__GLOBAL_MAP_{context} = {!! $json !!}; </script>` no fim do layout HTML da View. O FrontEnd deve resgatar os dados utilizando `window.map[data-id]`.

*Gerado pela auditoria de mapeamento em Maio/2026 (v3.46.0 - Kanban e Pipelines Consolidados).*

---

## 7. Regras de Exibição de SuiteCoins (Ƶ) — Referência Rápida

> [!IMPORTANT]
> Documentação completa e canônica em **`ARCHITECTURE.md` — Seção 4.69**.
> Esta seção é um resumo executivo para consulta rápida durante o desenvolvimento de UI.

### Fórmula Padrão (todos os serviços)
```
Ƶ_exibido = preço_BRL_bruto × 10 × 1.25
```
Aplicável a: cards do Escavador, DataJud, Monitoramentos, Assistentes Jurídicos.

**JavaScript:** `var pZ = p * 10 * 1.25;`

### ⚠️ Exceção — Painel "Minha Assinatura" (`subscription/index.blade.php`)
```
Ƶ_exibido = suitecoin_balance_BRL × 10   ← sem markup
```
**Motivação:** Se o usuário pagou R$ 10,00, deve ver **Ƶ 100,00** — nunca menos. O markup de 25% é recuperado nos serviços. Aplicar o markup aqui causaria sensação de perda de créditos no ato da compra.

**Onde se aplica:**
- `subscription/index.blade.php` → usa `SuiteCoinService::toVirtual($brl)` (apenas ×10)
- endpoint `lawfirm.escavador.saldo_cliente` → retorna `suitecoin_balance` em BRL + `suitecoin_rate = 10`
- `loadBalance()` JS no `escavador/index.blade.php` → aplica apenas `× suitecoin_rate`

### Tabela de Referência de Conversão

| Contexto | Fórmula | Resultado (base R$ 10,00 no banco) |
|:---|:---|:---|
| Saldo "Minha Assinatura" | `10 × 10` | **Ƶ 100,00** |
| Consulta OAB V2 (R$ 4,50 bruto) | `4.50 × 10 × 1.25` | Exibe **Ƶ 56,25**, debita R$ 5,625 |
| Monitoramento Diário (R$ 1,76 bruto) | `1.76 × 10 × 1.25` | Exibe **Ƶ 22,00/mês** |
| Monitoramento Semanal (R$ 0,85 bruto) | `0.85 × 10 × 1.25` | Exibe **Ƶ 10,63/mês** |
| Monitoramento Mensal (R$ 0,45 bruto) | `0.45 × 10 × 1.25` | Exibe **Ƶ 5,63/mês** |

---

## 8. Padronização de Renderização Markdown nos Assistentes de IA (v3.50.0)

> [!IMPORTANT]
> Documentação completa em **`ARCHITECTURE.md` — Seção 4.70**.

Todos os assistentes de IA do CRM **devem** renderizar suas respostas em Markdown. Os prompts de sistema de cada assistente incluem a instrução `SEMPRE formate sua resposta usando Markdown`. Esta padronização garante:

- Cabeçalhos hierárquicos (`##`, `###`) para estruturar análises
- Listas ordenadas/não-ordenadas para enumerações jurídicas
- **Negrito** para termos técnicos e valores financeiros
- Blocos de código para transcrições e excertos processuais

### Assistentes com Renderização Markdown Obrigatória

| Assistente (Slug) | Arquivo de Prompt | Tipo de Resposta |
|:---|:---|:---|
| `qualificacao_juridica` | Template via Mothership | Análise estruturada com seções |
| `sugestao_proposta` | Template via Mothership | Lista de itens e valores |
| `analise_viabilidade` | Template via Mothership | Relatório com cabeçalhos |
| `negociacao_conversao` | Template via Mothership | Estratégia em tópicos |

### Princípio de Renderização (Frontend)
A renderização ocorre **client-side** via `marked.js` (já integrado na view `admin/assistants/index.blade.php`). O servidor retorna o texto bruto do assistente; o JS converte para HTML seguro com `DOMPurify` antes de injetar no DOM.

---

## 9. Atualizações de UI — v3.51.0

### 9.1 Compactação de Labels na Navigation Filter Bar (Processos)

*   **Contexto:** Aprimoramento UX nas telas `show` e `edit` de Processos (`admin/juridico/processos/{id}`).
*   **Problema Resolvido:** Em viewports < 1440px (notebooks), os rótulos longos da barra de filtros causavam quebra de linha e sobreposição visual sobre os ícones de navegação.
*   **Arquivos Modificados:**

| Arquivo | Label Anterior | Label Atual |
|:---|:---|:---|
| `views/admin/processos/show.blade.php` | `Documentos e Anexos` | `Docs e Anexos` |
| `views/admin/processos/show.blade.php` | `Modelos de Docs` | `Model. Docs` |
| `views/admin/processos/edit.blade.php` | `Documentos e Anexos` | `Docs e Anexos` |
| `views/admin/processos/edit.blade.php` | `Modelos de Docs` | `Model. Docs` |

*   **Impacto:** Zero — alteração puramente cosmética de strings de texto em Blade. Nenhuma lógica PHP, rota, serviço ou regra de negócio foi modificada. As IDs dos `lf-section` targets permanecem inalteradas.

---

## 10. Atualizações de UI — v3.52.0

### 10.1 Gestão e Renderização de Modelos de Documentos Dinâmicos

*   **Contexto:** Inclusão de aba para uso e edição dinâmica de templates de documentos pré-preenchidos.
*   **Novas Views Blade Criadas:**
    *   `views/Legal/modelos/index.blade.php`: Listagem e gerenciamento (CRUD) de templates de documentos do escritório.
    *   `views/Legal/modelos/create.blade.php` e `views/Legal/modelos/edit.blade.php`: Telas de criação/edição contendo campos para título, tipo, área do direito, conteúdo (com suporte a tags/variáveis) e descrição.
    *   `views/Legal/processos/tabs/modelos-tab.blade.php`: Aba renderizada no painel do processo, listando os modelos ativos e compatíveis com a área do direito do caso.
*   **Features de UI (A4 Document Previewer Modal):**
    *   Um modal estilizado foi construído para apresentar o documento em formato de folha A4 com fundo contrastante.
    *   Editor interativo `textarea` que permite ao advogado revisar e fazer alterações manuais de última hora antes de exportar.
    *   Botão "Copiar Texto" dinâmico (integrado à API de Clipboard) com feedback visual de sucesso temporário.
    *   Função de impressão inteligente formatada especificamente para folhas A4 via CSS `@media print`, ocultando elementos de interface e barras laterais do CRM Krayin.

---

## 11. Atualizações de UI — v3.52.2

### 11.1 Resiliência de Visualização com Storage S3 Privado

*   **Contexto:** Garantir que o upload de logotipos, imagens de faturamento e exibição de fotos de cabeçalho funcione perfeitamente com buckets privados S3/MinIO sem expor dados sensíveis do tenant.
*   **Views Blade Modificadas:**
    *   `views/configuration/field-type.blade.php`: O resolvedor do preview de imagem do tipo `image` ou `file` foi atualizado para carregar a URL temporária assinada gerada pelo `SaasFileService::getSignedUrl()`.
    *   `views/layouts/header/index.blade.php` e `views/layouts/sidebar/mobile/index.blade.php`: As chamadas de renderização da logo do escritório foram refatoradas para evitar `Storage::url()` e utilizar o método `getSignedUrl()` do `SaasFileService`.
*   **Resultados na UI:**
    *   O logotipo configurado do escritório agora é exibido corretamente no Header e no menu lateral sem quebrar a imagem ou retornar erros `403 AccessDenied`.
    *   A visualização/download de anexos e recibos do financeiro operam de forma isolada, gerando assinaturas temporárias válidas por 60 minutos.

---

## 12. Atualizações de UI — v3.52.3

    ListaIA --> ChatIA[Chatbot Especialista]
    
    Servicos -->|/admin/juridico/whatsapp| TelaZap[Conectar WhatsApp (QR Code)]

    %% Detalhamento: SaaS e Billing
    Configuracoes -->|/admin/juridico/assinatura| SaasDash[Gestão de Assinatura]
    SaasDash --> CheckoutPlan[Modal Pagamento Assinatura]
    SaasDash --> CheckoutCredit[Modal Compra Créditos IA]
    SaasDash -->|/admin/juridico/orders| OrdersDash[Meus Pedidos e Faturas]
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
| **Legal** | `/admin/juridico/kanban` | Quadro Kanban interativo para gestão ágil dos Casos (Drag & Drop via Vanilla JS). |
| **Legal** | `/admin/juridico/casos` | Lista global de casos (DataGrid Vue/Krayin). |
| **Legal** | `/admin/juridico/casos/create` | Formulário para registrar novo caso. |
| **Legal** | `/admin/juridico/casos/{id}/edit` | Hub central (Ficha do Caso) com processos vinculados. |
| **Legal** | `/admin/juridico/processos` | Lista global de processos (DataGrid Vue/Krayin). |
| **Legal** | `/admin/juridico/processos/create` | Formulário para registrar novo processo. |
| **Legal** | `/admin/juridico/processos/{id}/edit` | Hub central (Ficha do Processo) carregando os módulos *GED*, *Financial*, e *Checklist* via ajax/tabs. |
| **Legal** | `/admin/juridico/prazos` | Quadro global visualizando todos os prazos ordenados por urgência. |
| **Legal** | `/admin/juridico/agenda` | Agenda Jurídica unificada (FullCalendar Vanilla JS). Combina Atividades do Krayin e Prazos LawFirm. Suporta `?clean=true` para renderização em modo Modal/Window. |
| **Escavador** | `/admin/juridico/escavador/termos` | Configuração de monitoramentos de Nome e OAB. |
| **Escavador** | `/admin/juridico/escavador/historico` | Timeline diária das publicações capturadas nos Diários Oficiais. |
| **AI (Assistentes)** | `/admin/juridico/assistants` | Vitrine de Assistentes e Agentes configurados pelo Mothership Panel. |
| **AI (Assistentes)** | `/admin/juridico/assistants/{slug}` | Tela do Chatbot para usar prompts contextuais (ex: Resumo de Sentença). |
| **DataJud** | `/admin/juridico/datajud` | Consulta pública CNJ (número CNJ, classe+órgão, paginação). |
| **SaaS** | `/admin/juridico/assinatura` | Gestão do Tenant: Planos, limites de S3, saldo bancário Asaas e consumo de IA. |
| **SaaS** | `/admin/juridico/orders` | Tabela (DataGrid) exibindo o histórico de pedidos (Orders) e status de pagamento do usuário. |
| **SaaS** | `/admin/juridico/billing-info` | Dados de Faturamento: Formulário PF/PJ com campos individuais `cpf`/`cnpj`/`company_name`. Toggle dinâmico de tipo de pessoa. |
| **Whatsapp** | `/admin/juridico/whatsapp` | Status da Evolution API e espelhamento de QR Code para o smartphone do advogado. |
| **TenantFinance** | `/admin/juridico/cobrancas` | Grid de listagem (`TenantInvoiceDataGrid`) das cobranças emitidas pelo escritório para seus clientes finais. |
| **TenantFinance** | `/admin/juridico/cobrancas/settings` | Formulário encapsulado nativamente em `Configurações > Jurídico > Cobranças Asaas` com a API Key V3 configurada (Add-on). |
| **Legal** | `/admin/juridico/modelos-documentos` | Tela para o CRUD e gerenciamento de Modelos de Documentos do escritório. |
| **Legal** | `/admin/juridico/processos/{processoId}/modelos` | Aba e renderizador dinâmico de modelos de documentos específicos do processo. |

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

## 6.7 Hidratação de Componentes Vanilla JS (Evitando Blade Runtime Crash)
Ao iterar dezenas de itens no Blade (ex: quadros Kanban) que requerem dados extensos em JSON para tooltips ou modais Vanilla JS, o uso iterativo do `@pushOnce` ou injeção pesada em atributos HTML (`data-payload="{{ json_encode(...) }}"`) causa vazamentos e corrompimento na compilação do Blade ("startPush Null Pointers").

**✅ OBRIGATÓRIO (Global Hash Map):**
Toda hidratação massiva deve ser computada como Hash Map Associativo no Controller, transferida via var única e registrada explicitamente como `<script> window.__GLOBAL_MAP_{context} = {!! $json !!}; </script>` no fim do layout HTML da View. O FrontEnd deve resgatar os dados utilizando `window.map[data-id]`.

*Gerado pela auditoria de mapeamento em Maio/2026 (v3.46.0 - Kanban e Pipelines Consolidados).*

---

## 7. Regras de Exibição de SuiteCoins (Ƶ) — Referência Rápida

> [!IMPORTANT]
> Documentação completa e canônica em **`ARCHITECTURE.md` — Seção 4.69**.
> Esta seção é um resumo executivo para consulta rápida durante o desenvolvimento de UI.

### Fórmula Padrão (todos os serviços)
```
Ƶ_exibido = preço_BRL_bruto × 10 × 1.25
```
Aplicável a: cards do Escavador, DataJud, Monitoramentos, Assistentes Jurídicos.

**JavaScript:** `var pZ = p * 10 * 1.25;`

### ⚠️ Exceção — Painel "Minha Assinatura" (`subscription/index.blade.php`)
```
Ƶ_exibido = suitecoin_balance_BRL × 10   ← sem markup
```
**Motivação:** Se o usuário pagou R$ 10,00, deve ver **Ƶ 100,00** — nunca menos. O markup de 25% é recuperado nos serviços. Aplicar o markup aqui causaria sensação de perda de créditos no ato da compra.

**Onde se aplica:**
- `subscription/index.blade.php` → usa `SuiteCoinService::toVirtual($brl)` (apenas ×10)
- endpoint `lawfirm.escavador.saldo_cliente` → retorna `suitecoin_balance` em BRL + `suitecoin_rate = 10`
- `loadBalance()` JS no `escavador/index.blade.php` → aplica apenas `× suitecoin_rate`

### Tabela de Referência de Conversão

| Contexto | Fórmula | Resultado (base R$ 10,00 no banco) |
|:---|:---|:---|
| Saldo "Minha Assinatura" | `10 × 10` | **Ƶ 100,00** |
| Consulta OAB V2 (R$ 4,50 bruto) | `4.50 × 10 × 1.25` | Exibe **Ƶ 56,25**, debita R$ 5,625 |
| Monitoramento Diário (R$ 1,76 bruto) | `1.76 × 10 × 1.25` | Exibe **Ƶ 22,00/mês** |
| Monitoramento Semanal (R$ 0,85 bruto) | `0.85 × 10 × 1.25` | Exibe **Ƶ 10,63/mês** |
| Monitoramento Mensal (R$ 0,45 bruto) | `0.45 × 10 × 1.25` | Exibe **Ƶ 5,63/mês** |

---

## 8. Padronização de Renderização Markdown nos Assistentes de IA (v3.50.0)

> [!IMPORTANT]
> Documentação completa em **`ARCHITECTURE.md` — Seção 4.70**.

Todos os assistentes de IA do CRM **devem** renderizar suas respostas em Markdown. Os prompts de sistema de cada assistente incluem a instrução `SEMPRE formate sua resposta usando Markdown`. Esta padronização garante:

- Cabeçalhos hierárquicos (`##`, `###`) para estruturar análises
- Listas ordenadas/não-ordenadas para enumerações jurídicas
- **Negrito** para termos técnicos e valores financeiros
- Blocos de código para transcrições e excertos processuais

### Assistentes com Renderização Markdown Obrigatória

| Assistente (Slug) | Arquivo de Prompt | Tipo de Resposta |
|:---|:---|:---|
| `qualificacao_juridica` | Template via Mothership | Análise estruturada com seções |
| `sugestao_proposta` | Template via Mothership | Lista de itens e valores |
| `analise_viabilidade` | Template via Mothership | Relatório com cabeçalhos |
| `negociacao_conversao` | Template via Mothership | Estratégia em tópicos |

### Princípio de Renderização (Frontend)
A renderização ocorre **client-side** via `marked.js` (já integrado na view `admin/assistants/index.blade.php`). O servidor retorna o texto bruto do assistente; o JS converte para HTML seguro com `DOMPurify` antes de injetar no DOM.

---

## 9. Atualizações de UI — v3.51.0

### 9.1 Compactação de Labels na Navigation Filter Bar (Processos)

*   **Contexto:** Aprimoramento UX nas telas `show` e `edit` de Processos (`admin/juridico/processos/{id}`).
*   **Problema Resolvido:** Em viewports < 1440px (notebooks), os rótulos longos da barra de filtros causavam quebra de linha e sobreposição visual sobre os ícones de navegação.
*   **Arquivos Modificados:**

| Arquivo | Label Anterior | Label Atual |
|:---|:---|:---|
| `views/admin/processos/show.blade.php` | `Documentos e Anexos` | `Docs e Anexos` |
| `views/admin/processos/show.blade.php` | `Modelos de Docs` | `Model. Docs` |
| `views/admin/processos/edit.blade.php` | `Documentos e Anexos` | `Docs e Anexos` |
| `views/admin/processos/edit.blade.php` | `Modelos de Docs` | `Model. Docs` |

*   **Impacto:** Zero — alteração puramente cosmética de strings de texto em Blade. Nenhuma lógica PHP, rota, serviço ou regra de negócio foi modificada. As IDs dos `lf-section` targets permanecem inalteradas.

---

## 10. Atualizações de UI — v3.52.0

### 10.1 Gestão e Renderização de Modelos de Documentos Dinâmicos

*   **Contexto:** Inclusão de aba para uso e edição dinâmica de templates de documentos pré-preenchidos.
*   **Novas Views Blade Criadas:**
    *   `views/Legal/modelos/index.blade.php`: Listagem e gerenciamento (CRUD) de templates de documentos do escritório.
    *   `views/Legal/modelos/create.blade.php` e `views/Legal/modelos/edit.blade.php`: Telas de criação/edição contendo campos para título, tipo, área do direito, conteúdo (com suporte a tags/variáveis) e descrição.
    *   `views/Legal/processos/tabs/modelos-tab.blade.php`: Aba renderizada no painel do processo, listando os modelos ativos e compatíveis com a área do direito do caso.
*   **Features de UI (A4 Document Previewer Modal):**
    *   Um modal estilizado foi construído para apresentar o documento em formato de folha A4 com fundo contrastante.
    *   Editor interativo `textarea` que permite ao advogado revisar e fazer alterações manuais de última hora antes de exportar.
    *   Botão "Copiar Texto" dinâmico (integrado à API de Clipboard) com feedback visual de sucesso temporário.
    *   Função de impressão inteligente formatada especificamente para folhas A4 via CSS `@media print`, ocultando elementos de interface e barras laterais do CRM Krayin.

---

## 11. Atualizações de UI — v3.52.2

### 11.1 Resiliência de Visualização com Storage S3 Privado

*   **Contexto:** Garantir que o upload de logotipos, imagens de faturamento e exibição de fotos de cabeçalho funcione perfeitamente com buckets privados S3/MinIO sem expor dados sensíveis do tenant.
*   **Views Blade Modificadas:**
    *   `views/configuration/field-type.blade.php`: O resolvedor do preview de imagem do tipo `image` ou `file` foi atualizado para carregar a URL temporária assinada gerada pelo `SaasFileService::getSignedUrl()`.
    *   `views/layouts/header/index.blade.php` e `views/layouts/sidebar/mobile/index.blade.php`: As chamadas de renderização da logo do escritório foram refatoradas para evitar `Storage::url()` e utilizar o método `getSignedUrl()` do `SaasFileService`.
*   **Resultados na UI:**
    *   O logotipo configurado do escritório agora é exibido corretamente no Header e no menu lateral sem quebrar a imagem ou retornar erros `403 AccessDenied`.
    *   A visualização/download de anexos e recibos do financeiro operam de forma isolada, gerando assinaturas temporárias válidas por 60 minutos.

---

## 12. Atualizações de UI — v3.52.3

### 12.1 Resiliência do Filtro de Modelos & Gestão de Layouts Locais

*   **Contexto:** Garantir que as interações JavaScript para pesquisa e seleção de modelos permaneçam ativas na Ficha do Processo, mesmo após atualizações dinâmicas do DOM promovidas pelo Vue.js ou Livewire, além de restabelecer o gerenciamento dos layouts de cabeçalho e rodapé.
*   **Modificações de Componentes de UI:**
    *   `views/Legal/processos/tabs/modelos-tab.blade.php`: O script do componente foi convertido para delegação de eventos global. Listeners para ações como `input` (pesquisa/filtro), `click` (seleção de opção), `focus` (abrir dropdown), `mousedown` (fechar ao clicar fora) e `keydown` (navegação por setas e ESC) agora escutam no objeto `document`, contornando problemas onde o Vue apagava listeners atrelados diretamente a elementos recriados.
    *   `views/Legal/modelos/index.blade.php`: Corrigida a listagem "Meus Modelos Locais" para não sobrescrever a variável `$localTemplates` com a coleção de templates de documentos ativos. Com isso, os registros de layouts locais (`is_layout = true`), tais como o Cabeçalho Padrão e o Rodapé Padrão, voltaram a ser renderizados na tabela de gerenciamento com seus respectivos botões para edição.
    *   **Atualização do Cabeçalho Padrão:** O HTML do layout padrão do cabeçalho de documentos gerado por `DocumentTemplateController::createDefaultLayout` foi atualizado para uma tabela sem bordas, com altura definida, contendo a logomarca corporativa hospedada no S3 e o nome do escritório (`{{escritorio_nome}}`).

---

## 13. Atualizações de UI — v3.52.4

### 13.1 Campo Chave Secreta em Informações Básicas (Processos)

*   **Contexto:** Inclusão do campo `sercreta` nas visualizações de criação e edição de processos para dar transparência ao código numérico utilizado pelos assistentes de IA na identificação de canais de comunicação com clientes.
*   **Views Modificadas:**
    *   `views/admin/processos/create.blade.php`: Inserido o campo de entrada para Chave Secreta (IA) dentro do card "Iniciando Processo".
    *   `views/admin/processos/edit.blade.php`: Inserido o campo de entrada sob a seção "Informações Básicas".
*   **Comportamento:** O campo é gerado automaticamente pelo observer do model se for deixado em branco, mas permite que o usuário o edite ou especifique uma chave personalizada de até 7 dígitos.

---

## 14. Atualizações de Backend — v3.54.0

### 14.1 Sincronização Automática de Labels Chatwoot via Kanban

*   **Contexto:** Integração bidirecional entre os Kanbans do CRM (Leads e Jurídico) e o Chatwoot, sincronizando labels de estágio automaticamente sempre que um card é movido.
*   **Fluxo:** Totalmente assíncrono via `ShouldQueue` — o HTTP response nunca é bloqueado.

#### Novos Arquivos no Domínio `Legal/`

| Tipo | Arquivo | Responsabilidade |
|:---|:---|:---|
| **Event** | `Legal/Events/CasoStageUpdated.php` | Evento tipado disparado por `LegalPipelineService::moveCaseToStage()` após o commit da transaction. Carrega o `Caso` atualizado via `readonly` property. |
| **Listener** | `Legal/Listeners/SyncLeadStageToChatwootListener.php` | Escuta `lead.update.after`. Mapeia `$lead->stage->code` → label Chatwoot via `STAGE_LABEL_MAP` estático. Extrai telefone de `$person->contact_numbers` (JSON array). Chama `ChatwootService::findOrCreateContact()` + `syncContactLabels()`. |
| **Listener** | `Legal/Listeners/SyncCasoStageToChatwootListener.php` | Escuta `CasoStageUpdated`. Mapeia `Str::slug($caso->stage->name)` → label via mapa com 12 stages jurídicos. Lógica idêntica ao Listener de Leads. |

#### Arquivos do Domínio `Atendimento/` Modificados

| Arquivo | Mudança |
|:---|:---|
| `Atendimento/Services/ChatwootService.php` | +4 métodos: `createContact()`, `findOrCreateContact()`, `getContactConversations()`, `syncContactLabels()` |

#### Arquivos de Domínios Adjacentes Modificados

| Arquivo | Mudança |
|:---|:---|
| `Legal/Services/LegalPipelineService.php` | `moveCaseToStage()` refatorado — result da `DB::transaction` capturado em `$updatedCaso`; `Event::dispatch(new CasoStageUpdated($updatedCaso))` disparado pós-commit |
| `Providers/EventServiceProvider.php` | `CasoStageUpdated → SyncCasoStageToChatwootListener` adicionado ao `$listen` |
| `Providers/LawFirmServiceProvider.php` | `SyncLeadStageToChatwootListener` registrado em `lead.update.after` (L270); `VERSION` bumped para `3.54.0` |

#### Notas Críticas de Implementação

*   **`contact_numbers` é JSON array**, não relação Eloquent. Acesso: `$person->contact_numbers[0]['value']` (cast `array` no model `Person`).
*   **`emails` também é JSON array** no `Person` model. Acesso: `$person->emails[0]['value'] ?? null`.
*   **Stage do Lead** mapeado por `code` (`new`, `follow-up`, `prospect`, `negotiation`, `won`, `lost`).
*   **Stage do Caso** mapeado por `Str::slug($stage->name)` — locale-independente, compatível com nomes PT-BR acentuados.
*   **`tries = 1`** em ambos os Listeners — sem retentativas para evitar flood na API do Chatwoot.
*   **Degradação graciosa**: `MotherShipService::getChatwootConfig() === null` → `Log::info` + `return` — nunca `throw`.

---

## 15. Atualizações de Backend — v3.54.1

### 15.1 Separação de Account ID e Inbox ID no Chatwoot

*   **Contexto:** Correção conceitual e estrutural da integração Chatwoot. Anteriormente, a coluna `chatwoot_inbox_id` era utilizada para armazenar tanto o ID da Conta quanto o ID do Inbox, gerando colisões de validação nos Webhooks e falha na API de criação de contato.
*   **Banco de Dados (Conexão `mothership`):**
    *   Nova migration: `2026_07_01_000001_add_chatwoot_channel_inbox_id_to_tenants.php`.
    *   Coluna criada: `chatwoot_channel_inbox_id INT UNSIGNED NULL`.
*   **Modificações de Componentes de Backend:**
    *   `SaaS/Models/Tenant.php`: Adicionados os campos `chatwoot_node_id`, `chatwoot_inbox_id`, `chatwoot_channel_inbox_id`, e `chatwoot_webhook_token` ao array `$fillable` para permitir mass-assignment seguro. Implementada a relação `chatwootNode()`.
    *   `SaaS/Services/MotherShipService.php`: Método `getChatwootConfig()` ajustado para mapear `inbox_id` a partir da coluna `chatwoot_channel_inbox_id` (real ID da caixa de entrada) e mapear `account_id` a partir da coluna legada `chatwoot_inbox_id` ou via metadados do nó.

---

## 16. Atualizações de Backend — v3.54.1+ / MotherShip v1.21 (Jul/2026)

### 16.1 Dual Inbox Chatwoot — `chatwoot_assistant_inbox_id`

*   **Contexto:** O MotherShip v1.21 (Jul/2026) adicionou um **5º campo Chatwoot** na tabela `tenants`: `chatwoot_assistant_inbox_id`. Isso suporta escritórios com duas instâncias Evolution distintas — uma para atendimento humano e outra para o Assistente de IA.
*   **Banco de Dados (Conexão `mothership`):**
    *   Migration: `migrations/add_chatwoot_assistant_inbox_id.php` (script PHP idempotente).
    *   Coluna: `chatwoot_assistant_inbox_id INT UNSIGNED NULL` — **5º campo Chatwoot do tenant**.
*   **Modelo `SaaS/Models/Tenant.php`:** `chatwoot_assistant_inbox_id` deve ser adicionado ao `$fillable`.
*   **`SaaS/Services/MotherShipService::getChatwootConfig()`** — retorno expandido:

```php
return [
    'url'                => $node->base_url,
    'api_key'            => $node->api_key,                            // Bot Token — POST /messages
    'account_id'         => $tenantConfig->chatwoot_inbox_id,          // Account ID global
    'inbox_id'           => $tenantConfig->chatwoot_channel_inbox_id,  // Inbox Atendimento Humano 📥
    'assistant_inbox_id' => $tenantConfig->chatwoot_assistant_inbox_id ?? null,  // Inbox IA 🤖 — NOVO
    'access_token'       => $tenantConfig->chatwoot_webhook_token,     // User Access Token (gestão)
];
```

*   **`Atendimento/Services/ChatwootService.php`** — novos métodos esperados:
    *   `sendMessage(int $conversationId, string $message)` — usa `inbox_id` (atendimento humano)
    *   `sendAssistantMessage(int $conversationId, string $message)` — usa `assistant_inbox_id`; se `null`, faz fallback para `inbox_id` com `Log::warning()`

> [!CAUTION]
> **Distinção de Tokens é CRÍTICA:**
> - `api_key` (Bot Token) → `POST /messages`, `GET /status` — nunca usar em `/labels` ou `/contacts`
> - `access_token` (User Access Token `chatwoot_webhook_token`) → `/labels`, `/contacts/search`, validação HMAC do webhook

---

### 16.2 `evolution_assistente_name` — Instância de Atendimento Explícita

*   **Contexto:** O MotherShip v1.21 adicionou `evolution_assistente_name` à tabela `tenants`. Isso supera o modelo de **sufixo mágico** (`_atendimento`) da seção §19, permitindo configurar um nome completamente arbitrário para a instância de atendimento.
*   **Migration:** `migrations/add_evolution_assistente_name.php` (script PHP idempotente).
*   **Modelo `SaaS/Models/Tenant.php`:** `evolution_assistente_name` deve ser adicionado ao `$fillable`.
*   **`SaaS/Services/MotherShipService::getEvolutionConfig($type)`** — lógica de resolução:

```php
// $type = 'primary' | 'atendimento'
if ($type === 'atendimento') {
    $instanceName = $tenantConfig->evolution_assistente_name
        ?? ($tenantConfig->evolution_instance_name . '_atendimento'); // fallback retrocompatível
} else {
    $instanceName = $tenantConfig->evolution_instance_name;
}
```

*   **Retrocompatibilidade total:** Tenants com `evolution_assistente_name = NULL` continuam funcionando via fallback automático. Nenhuma migration de dados necessária.

---

### 16.3 Referência Completa dos 5 Campos Chatwoot (mothership `tenants`)

| Campo DB | Label UI MotherShip | Uso no CRM |
|---|---|---|
| `chatwoot_node_id` | Nó Chatwoot | FK para `infrastructure_nodes` (URL + bot token) |
| `chatwoot_inbox_id` | Account ID | `$config['account_id']` — ID numérico da conta global |
| `chatwoot_channel_inbox_id` | Inbox Instância 📥 | `$config['inbox_id']` — caixa de entrada humana |
| `chatwoot_assistant_inbox_id` | Inbox Assist. 🤖 | `$config['assistant_inbox_id']` — caixa de entrada IA |
| `chatwoot_webhook_token` | User Access Token | `$config['access_token']` — operações de gestão (labels/contacts) |

---

*Atualizado em 08/07/2026 — MotherShip v1.21 ↔ LawFirm v3.54.1 — Dual Inbox Chatwoot + evolution_assistente_name.*
