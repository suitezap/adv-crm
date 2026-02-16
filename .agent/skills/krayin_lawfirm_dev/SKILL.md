---
name: krayin_lawfirm_architecture
description: Standards for SuiteZap/LawFirm package (DDD/SaaS).
---
# LawFirm CRM - Architecture Standards (v2.0)

## 1. Directory Structure (Domain-Driven)
All code lives in `packages/SuiteZap/LawFirm/src/`.
- **Legal/**: `SuiteZap\LawFirm\Legal` (Processos, Prazos)
- **Financial/**: `SuiteZap\LawFirm\Financial` (Fees, Invoices)
- **SaaS/**: `SuiteZap\LawFirm\SaaS` (Tenants, Subscriptions, Infra)
- **GED/**: `SuiteZap\LawFirm\GED` (Documents, Storage)

## 2. Strict Rules
- **Models:** Must live in `src/{Domain}/Models`.
- **Storage:** PROHIBITED to use `Storage::put` directly. Use `SaaS\Services\SaasFileService`.
- **Namespaces:** Follow `SuiteZap\LawFirm\{Domain}\{Type}`.
