# 🏛 LawFirm CRM - Documento de Arquitetura (v2.0 - DDD & SaaS)

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

## 5. Auditoria Estrutural e Mapa de Dívida Técnica (v3.1)

Realizada em 16/02/2026. O projeto atingiu um nível maduro de separação de domínios, mas ainda resistem "bolsões" de código legado.

### 5.1 Diagrama de Classes (Situação Atual)

```mermaid
classDiagram
    direction TB

    %% --- DOMÍNIOS CORE (SOLID) ---
    namespace Legal_Domain {
        class Processo
        class Prazo
        class CaseChecklist
        class ProcessoController
        class ChecklistController
    }

    namespace Financial_Domain {
        class Financial
        class FinancialService
        class FinancialController
    }

    namespace AI_Domain {
        class AiExecution
        class AssistantTemplate
        class AssistantHistory
        class AssistantController_Admin
        class ProcessAiAssistant_Job
    }

    namespace GED_Domain {
        class ProcessDocument
        class DocumentService
        class ProcessDocumentController
        class Anexo_Model
    }

    namespace Whatsapp_Domain {
        class ConnectionController
    }

    %% --- DÍVIDA TÉCNICA (LOOSE CODE) ---
    %% (Reduzida Drasticamente em v3.1)
    namespace Legacy_Loose_Code {
        class Root_Models_AuditLog
        class Root_Models_HumanDecision
    }

    %% Relações
    Legal_Domain ..> GED_Domain : Utiliza
    Legal_Domain ..> Financial_Domain : Utiliza (via Ajax)
    ProcessoController --> MotherShipService : Verifica Tenant
    AssistantController_Admin --> ProcessAiAssistant_Job : Despacha

    style Legal_Domain fill:#2a9d8f,color:#fff
    style Financial_Domain fill:#2a9d8f,color:#fff
    style AI_Domain fill:#e9c46a,color:#000
    style GED_Domain fill:#2a9d8f,color:#fff
    style SaaS_Domain fill:#264653,color:#fff
    style Whatsapp_Domain fill:#2a9d8f,color:#fff
    style Legacy_Loose_Code fill:#e76f51,color:#fff,stroke-dasharray: 5 5
```

### 5.2 Pontos de Atenção (Technical Debt)

**✅ Resolvidos (v3.1):**
*   ~~`src/Http/Controllers/AssistantController.php`~~: Removido.
*   ~~`src/Http/Controllers/Api/*`~~: Movidos para `Legal` e `GED`.
*   ~~`src/Http/Controllers/Admin/Whatsapp`~~: Criado domínio `Whatsapp`.
*   ~~`SaasDashboardController`~~: Movido para `SaaS`.

**🟠 Pendentes (Média Prioridade):**
*   `src/Models/*`: Models soltos na raiz (`AuditLog`, `HumanDecision`) ainda precisam de destino.

### 5.3 Histórico de Refatoração Estrutural (v3.1)
*   **Data:** 16/02/2026
*   **Ação:** "The Great Migration" - Eliminação de controllers órfãos na raiz.
*   **Resultado:** A pasta `src/Http/Controllers` agora contém apenas o `Controller.php` base. Todo o resto reside em seus respectivos domínios.

### 5.4 Refatoração da Aba de Documentos (v3.2)
*   **Decisão:** Adotar o "Financial Tab Pattern" para gestão de documentos.
*   **Mudanças:**
    *   **Frontend:** `documents.blade.php` reescrito com Vanilla JS + Tailwind CSS.
    *   **Backend:** `ProcessDocumentController` expandido para gerenciar uploads (`store`), deleção de anexos (`destroy`) e deleção de checklists (`destroyChecklistItem`).
    *   **Rota:** Adicionada `documentos/store` e corrigida `documentos/delete`.

## 6. Padrões de Frontend (UI/UX - v3.2)

### 6.1 Conflito Vue.js vs Alpine.js
O Krayin (Core) utiliza uma instância global do Vue.js que processa todo o DOM. Isso causa conflitos fatais ao tentar usar Alpine.js dentro de views Blade (`.blade.php`), pois o Vue remove as tags `<template>` e diretivas antes que o Alpine possa processá-las.

**Solução Padrão (The "Financial Tab" Pattern):**
Para componentes complexos dentro do Admin (ex: Gestão Financeira, Checklists):
1.  **Server-Side Rendering:** Use Blade (`@foreach`) para renderizar o estado inicial.
2.  **Interatividade:** Use **Vanilla JavaScript** com Event Delegation.
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