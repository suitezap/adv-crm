# 🏛 LawFirm CRM - Documento de Arquitetura (v2.0 - DDD & SaaS)

## 1. Visão Geral
Este projeto segue a arquitetura **Domain-Driven Design (DDD)** adaptada para **SaaS Multi-Tenant**.
**Namespace Base:** `SuiteZap\LawFirm`
**Localização:** `packages/SuiteZap/LawFirm/src/`

## 2. Mapa de Domínios (Bounded Contexts)
O código é estritamente separado por responsabilidade.

| Domínio | Namespace | Responsabilidade | Models Principais |
| :--- | :--- | :--- | :--- |
| **Legal** | `SuiteZap\LawFirm\Legal` | Core jurídico (Processos, Prazos). | `Processo`, `Prazo`, `LawPersonDetail` |
| **Financial**| `SuiteZap\LawFirm\Financial` | Honorários, Custas e Faturamento. | `Financial` |
| **GED** | `SuiteZap\LawFirm\GED` | Gestão de Arquivos e Anexos. | `ProcessDocument`, `Anexo` |
| **SaaS** | `SuiteZap\LawFirm\SaaS` | Infraestrutura Multi-tenant. | `Tenant`, `Subscription`, `InfrastructureNode` |
| **AI** | `SuiteZap\LawFirm\AI` | Assistentes e Automação. | `AiExecution` |

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