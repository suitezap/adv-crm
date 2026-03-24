---
name: krayin_lawfirm_architecture
description: Standards for SuiteZap/LawFirm package (DDD/SaaS).
---
# LawFirm CRM - Architecture Standards (v3.10)

## 1. Directory Structure (Domain-Driven)
All code lives in `packages/SuiteZap/LawFirm/src/`.
- **Legal/**: `SuiteZap\LawFirm\Legal` (Processos, Prazos)
- **Financial/**: `SuiteZap\LawFirm\Financial` (Fees, Invoices)
- **GED/**: `SuiteZap\LawFirm\GED` (Documents, Storage, Attachments)
- **SaaS/**: `SuiteZap\LawFirm\SaaS` (Tenants, Subscriptions, Infra, MotherShipService)
- **AI/**: `SuiteZap\LawFirm\AI` (AiExecution, AssistantTemplate, AssistantHistory)
- **Escavador/**: `SuiteZap\LawFirm\Escavador` (Escavador API V1/V2 integrations)

## 2. Strict Rules
- **Models & Controllers:** Do NOT place in the root folder. Must live inside their Bounded Context (`src/{Domain}/Models`, `src/{Domain}/Http/Controllers`).
- **Namespaces:** Follow `SuiteZap\LawFirm\{Domain}\{Type}`.
- **Storage:** ⛔ **PROHIBITED** to use `Storage::put` or direct local disk access. ✅ **MANDATORY** to use `SuiteZap\LawFirm\SaaS\Services\SaasFileService` to ensure multi-tenant S3/MinIO compliance.
- **Controllers:** Must be "Skinny". All complex business logic must reside in **Services**.

## 3. Database & SaaS (Mothership)
- Tenant configurations, API keys, and global pricing are centralized in the `mothership` database (`infrastructure_nodes`, `app_config`).
- Never hardcode `.env` credentials for tenant-specific integrations. Use `MotherShipService`.

## 4. Frontend & UI Patterns
- **Tailwind CSS** is the standard for new components.
- **Vue vs Alpine:** Krayin uses Vue globally avoid using Alpine.js directly inside Blade views where Vue takes over. Use Vanilla JavaScript for DOM manipulation.
- **Forms:** For complex entities with multiple tabs, use `window.appendExternalTabs(event, this)` on submit to collect inputs outside the main `<form>`.
- **AJAX/Files:** Do NOT nest `<form>` tags. Use `FormData` and `fetch` or `XMLHttpRequest` for secondary actions.
