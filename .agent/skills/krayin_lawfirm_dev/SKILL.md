---
name: krayin_lawfirm_architecture
description: Standards for SuiteZap/LawFirm package (DDD/SaaS).
---
# LawFirm CRM - Architecture Standards (v3.53)

## 1. Directory Structure (Domain-Driven)
All code lives in `packages/SuiteZap/LawFirm/src/`. **Root is Zero-Debt** — no loose PHP files outside a domain. `src/Http/Controllers/` **must remain empty** (Zero Root Controllers since v3.36).
- **Legal/**: `SuiteZap\LawFirm\Legal` — Casos, Processos, Prazos, Checklists, LegalOrchestrator
- **Financial/**: `SuiteZap\LawFirm\Financial` — Honorários, Custas, Faturas
- **GED/**: `SuiteZap\LawFirm\GED` — Documents, Attachments, S3 uploads
- **SaaS/**: `SuiteZap\LawFirm\SaaS` — Tenants, Subscriptions, MotherShipService, Asaas, Orders
- **AI/**: `SuiteZap\LawFirm\AI` — AssistantTemplate, AssistantHistory, LeadTriagem, ProcessAiAssistant Job
- **Escavador/**: `SuiteZap\LawFirm\Escavador` — Escavador API V1/V2, Webhooks, Monitoramentos
- **DataJud/**: `SuiteZap\LawFirm\DataJud` — Consulta Pública CNJ (DataJud REST API)
- **Whatsapp/**: `SuiteZap\LawFirm\Whatsapp` — EvolutionService, **ConnectionController** (canonical), Listeners
- **TenantFinance/**: `SuiteZap\LawFirm\TenantFinance` — TenantAsaasService, Invoices, Webhooks, Customers
- **Atendimento/**: `SuiteZap\LawFirm\Atendimento` — ChatwootService, webhook handlers and routing

## 2. Strict Rules

### 2.1 Models & Controllers
- ⛔ **NEVER** place Models or Controllers at root `src/`. Must live inside `src/{Domain}/Models` or `src/{Domain}/Http/Controllers`.
- ⛔ **NEVER** place PHP files in `src/Http/Controllers/` — this directory **must remain empty** at all times (Zero Root Controllers).
- Namespace pattern: `SuiteZap\LawFirm\{Domain}\{Type}`.
- **WhatsApp canonical controller:** `SuiteZap\LawFirm\Whatsapp\Http\Controllers\ConnectionController` — routes in `admin-whatsapp.php` point here. Do not create any other WhatsApp controller outside this domain.

### 2.2 File Storage (Ironclad Rule)
⛔ **PROHIBITED anywhere except inside `SaasFileService` itself:**
```
Storage::put(), Storage::get(), Storage::download(), Storage::disk(),
Storage::exists(), Storage::mimeType(), Storage::makeDirectory(), Storage::temporaryUrl()
```
✅ **MANDATORY** — use `SuiteZap\LawFirm\SaaS\Services\SaasFileService`:
```php
$fileService->store(UploadedFile $file, string $path): string  // Upload
$fileService->storeRaw(string $path, string $contents): bool   // Raw upload (PDF, JSON)
$fileService->get(string $path): ?string                        // Read contents
$fileService->mimeType(string $path): ?string                   // MIME type
$fileService->exists(string $path): bool                        // Check existence
$fileService->delete(string $path): bool                        // Delete
$fileService->url(string $path): string                         // Public/signed URL
```
**File downloads in Controllers** — use `get()` + `mimeType()` + `response()`, NOT `Storage::download()`:
```php
$contents = $this->fileService->get($path);
$mime     = $this->fileService->mimeType($path) ?? 'application/octet-stream';
return response($contents, 200, [
    'Content-Type'        => $mime,
    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
]);
```

### 2.3 Skinny Controllers
All business logic lives in **Services**. Controllers only orchestrate request → service → response.

### 2.4 No Debug Logs in Production
⛔ **PROHIBITED in Controllers and Services shipped to production:**
```php
\Log::debug(...)  // removes before committing
```
✅ Use `Log::info()` or `Log::error()` for legitimate operational logging only.

## 3. Zero .env — MotherShipService (Mandatory Patterns)

### 3.1 WhatsApp / Evolution API
✅ **Always fetch from MotherShip:**
```php
// Primary instance (notifications/system)
$config = MotherShipService::getEvolutionConfig('primary');
// ['base_url', 'instance', 'token'] or null

// Secondary instance (human support — uses evolution_assistente_name or fallback _atendimento)
$config = MotherShipService::getEvolutionConfig('atendimento');
```
- Se `evolution_assistente_name` estiver preenchido no tenant → usa esse valor.
- Se `NULL` → fallback automático: `{instance_name}_atendimento` (retrocompatível com §19).
⛔ **NEVER:** `env('EVOLUTION_INSTANCE_NAME')` as the only source.

### 3.2 N8N Webhooks
✅ **Always fetch from MotherShip:**
```php
$n8nConfig = MotherShipService::getN8nConfig(); // ['url', 'api_key'] or null
if (!$n8nConfig) {
    return response()->json(['error' => 'N8N não configurado para sua conta.'], 503);
}
$url = rtrim($n8nConfig['url'], '/') . '/' . ltrim($template->n8n_webhook_url, '/');
```
⛔ **NEVER:** `env('N8N_WEBHOOK_BASE_URL')`.

### 3.3 Chatwoot
✅ **Always fetch from MotherShip:**
```php
$chatwootConfig = MotherShipService::getChatwootConfig();
// Returns: ['url', 'api_key', 'account_id', 'inbox_id', 'assistant_inbox_id', 'access_token'] or null
if (!$chatwootConfig) {
    return response()->json(['error' => 'Chatwoot não configurado para sua conta.'], 503);
}
```

**Distinção crítica de tokens (Jul/2026):**

| Operação | Token | Header |
|---|---|---|
| `POST /messages` (enviar mensagem) | `api_key` (Bot token) | `botHeaders()` |
| `POST /labels` (atribuir label) | `access_token` (User Access Token) | `managementHeaders()` |
| `GET /contacts/search` | `access_token` (User Access Token) | `managementHeaders()` |
| `sendAssistantMessage()` | usa `assistant_inbox_id` (5º campo Jul/2026) | `botHeaders()` |
| Validação `X-Chatwoot-Signature` | `access_token` como secret HMAC | — |

> ⛔ Usar `api_key` (bot token) em `/labels` ou `/contacts` → **HTTP 401**. Usar `access_token` em `/messages` → contexto errado de conta.

⛔ **NEVER:** `env('CHATWOOT_BASE_URL')` or `env('CHATWOOT_API_KEY')`.

### 3.4 Mothership API Secret
✅ Read from `mothership.app_config` key `api_secret` (cached 5min via `getApiSecretFromDb()`).
🟡 `env('MOTHERSHIP_API_SECRET')` is **only** acceptable as a pre-migration fallback.

### 3.5 Acceptable env() Fallback Pattern
`env()` is allowed ONLY when MotherShipService is already the primary source:
```php
$config = MotherShipService::getEvolutionConfig();
$instanceName = ($config && !empty($config['instance']))
    ? $config['instance']
    : env('EVOLUTION_INSTANCE_NAME'); // dev/local fallback only
```

## 4. Graceful Degradation for Unconfigured Services
When Evolution, N8N, Asaas, Escavador, or Chatwoot is not configured in MotherShip for a tenant:
- **Jobs / Listeners:** `Log::error(...)` + `return` — never throw, never crash the queue worker.
- **API Controllers:** Return HTTP `503` with a user-friendly JSON `error` message.
- **Web Controllers:** `session()->flash('warning', '...')` + `redirect()->back()` — never 500.

## 5. Database & SaaS (Mothership)
- API keys and config live in `mothership` DB: `infrastructure_nodes`, `app_config`, `tenants`, `subscriptions`.
- Each tenant has FKs: `n8n_node_id`, `evolution_node_id`, `storage_node_id`, `asaas_node_id`.
- Storage disk is resolved dynamically by `MotherShipService::configureTenantStorage()` at boot — `SaasFileService::getDisk()` always picks up the correct tenant bucket.
- Balance debits/credits must be registered in `saas_transactions` (tenant DB). Orders tracked in `saas_orders`.
- **SuiteCoins (Ƶ):** All AI token balances are managed via `suitecoin_balance` in the `subscriptions` table. MotherShip stores balances in BRL (1:1), and LawFirm converts it purely for UI display using the `suitecoin_multiplier` from `app_config`. Do not alter balances natively outside this rule.
- **Hybrid Document Templates (v3.53.0+):** LawFirm uses a mixed system for document templates.
  - Global templates: Served from `mothership_db` (`lawfirm_document_templates`), fetched via `MothershipDocumentTemplate` (connection `'mothership'`), with `global-{id}` prefix. Any attempt to write/edit a `global-` template from the tenant CRM must be blocked (HTTP 403).
- **Whaticket Decommissioned:** The legacy Whaticket Messenger Inbox was completely removed. Live customer chat and multi-channel inbox are handled exclusively by the `Atendimento` domain (Chatwoot Dual Inbox). The `Whatsapp` domain handles transactional notifications only (Faturas, Prazos, Alertas, Import).

### 5.1 Escavador & DataJud (v3.32+ Refactoring)
*   **MANDATORY "Zero .env" Policy:** Never use `env()` for Escavador tokens or prices. Use `MotherShipService::getTenantConfig()` and `MotherShipService::getEscavadorPrices()`.
*   **DataJud API:** Public consultations via DataJud CNJ API are supported. Pricing keys: `datajud_price_*`.
*   **Cost Hierarchy Strategy:** LawFirm CRM uses a cost-savings hierarchical query pattern for Legal Process Intelligence.
    *   **Level 1: Local Cache** (`escavador_processos`, `escavador_movimentacoes`, `escavador_envolvidos`, `escavador_documentos`, `escavador_autos`, `escavador_id_map`). Always check local cache first.
    *   **Level 2: V1 Term Search** (Lower cost, fallback).
    *   **Level 3: V2 Capa / Autos** (Highest cost, triggered only when explicitly requested/sync'd).
*   **Async processing:** Heavy requests like `Resumo IA` and `Capa de Tribunal` are processed asynchronously. Webhooks are handled in `WebhookController` without CSRF verification.

## 6. Frontend & UI Patterns
- **Tailwind CSS** — standard for all new components.
- **DOMPurify:** Always sanitize user-provided HTML with DOMPurify before rendering it dynamically in the DOM or Vue components to prevent XSS.
- **Vue vs Vanilla JS:** Krayin's Vue instance controls the global DOM. ⛔ Do NOT use Alpine.js inside Blade views Vue manages. ✅ Use Vanilla JS with `MutationObserver` or event delegation.
- **AJAX & CSRF:** Read `X-CSRF-TOKEN` **at event time** (onclick), never on script init.
- **Complex Forms (External Tabs Pattern):** Use `window.appendExternalTabs(event, this)` to collect inputs from tabs outside the main `<form>`.
- **Navigation Filter Bar Pattern (v3.43+):** To prevent visual overload in long detail pages like Processos (`edit`/`show`), inject a horizontal Filter Bar mapping to `.lf-section` divs toggled via Vanilla JS. **Always** persist the active filter using `localStorage` keyed by the entity ID (e.g., `lf_processo_section_{id}`) to preserve user context across form submissions and reloads.
- **Design System Constraints (.lf-card):** All logical content modules in the CRM core views must be wrapped symmetrically using `<div class="lf-card flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow duration-200">` for coherent spacing. Headers should be separated using `border-b pb-3` and tight tracking.
- **File Uploads:** ⛔ Do NOT nest `<form>` tags. Use `FormData` + `fetch`/`XMLHttpRequest`.
- **Routes in JS (REPLACE_ID Pattern):** Never pass empty params to Laravel routes in Blade:
```javascript
// ✅ CORRECT
const baseRoute = "{{ route('admin.api.route', 'REPLACE_ID') }}";
const finalUrl = baseRoute.replace('REPLACE_ID', id);
```
- **Form Phone Masking (Global Override Fallback):** `v-mask` is not loaded dynamically to match standard global forms in Vue 3 Krayin correctly across environments. Instead, capture form states using raw `watch` JS handlers inside nested components to dynamically swap the regex for length sizes natively across fields like user numbers! Global Vee-Validate `phone` definition regex has been purposely modified via `vee-validate.js` to allow parentheses and generic text spacing submission limits instead of strictly breaking valid formatted values.
- **DataGrid Actions (Vue Interference):** When adding custom actions to Krayin DataGrids (`addAction` in `*DataGrid.php`), avoid using `method => 'POST'` for simple state toggles, as the Vue/Axios interceptor frequently drops CSRF validation silently or attempts to render missing icon fonts (e.g., `icon-bell` 0x0 bugs). **MANDATORY:** Use `method => 'GET'` and remove the `confirm_text` parameter so Krayin falls back to a standard `<a href="...">` native browser redirect for toggle endpoints.
- **Docker Schedulers & Workers:** Cron-based features (`schedule:run`) and Queue runners (`queue:work`) require their own container in the Swarm. The `docker/entrypoint.sh` automatically routes arbitrary `$@` commands through `www-data` to prevent root-owned `laravel.log` permission lockouts.
- **Redis Queue Isolation:** ⛔ In Docker Swarm deployments sharing a central `redis` instance, you MUST declare `REDIS_PREFIX: {tenant_id}_` in the `docker-compose.yml` ENV variables. Without it, Tenants will consume each other's asynchronous Queue Jobs across the overlay network.
- **Dynamic Menu Hiding:** When hiding default Krayin menus (e.g., 'mail') via `LawFirmServiceProvider`, always filter using a prefix check (`str_starts_with`). Removing only the parent key leaves orphaned child keys in the config, which causes a fatal error in Krayin's `Menu.php` reconstruction logic.
- **Granular ACL Definition:** To ensure checkboxes appear in the Admin Role matrix, `acl.php` entries MUST define explicit CRUD child nodes (e.g., `dominio.modulo.create`). Simple top-level keys will be registered but won't render checkboxes in the Vue tree-view component.
- **The Portal Dialog and Clean Window Pattern (SPA-Safe Modals):** When injecting HTML via AJAX that contains Tailwind classes or interactive elements, the Krayin SPA router may hijack the DOM. **MANDATORY:** (1) For simple overlays, use only inline `style=""` for the modal container and append directly to `document.body` on open. (2) For heavy, complex components (like FullCalendar or advanced grids), avoid iframes inside the current window and instead use a native `window.open` popup routing to a `?clean=true` layout (`admin::layouts.anonymous`) parameter to provide a detached, full-width experience immune to Sidebar clipping.
- **Vue DOM and Dynamic Elements:** Do not attempt to initialize native Vue components inside elements appended via `insertAdjacentHTML()`. If a native component like a Date Picker is needed in an infinite list (e.g., repeating a row of inputs), generate the required HTML wrapping directly and instantiate the dependency (e.g. `new window.Flatpickr`) explicitly inside a `load` hook or `setTimeout` function to correctly override Vue.js's mounting lifecycle limitations.
- **Global JS Map Injection (Hydrating Components):** When iterating hundreds of objects in Blade (e.g. Kanban boards) that need complex JSON data on hover or click, ⛔ DO NOT pass JSON arrays via HTML datasets (`data-attr="{{ json_encode() }}"`) and ⛔ DO NOT use `@pushOnce('scripts')` to emit dynamically iterating JSON vars inside loops (Blade Engine will compile strictly the first loop occurrence and swallow the rest, leading to `startPush` Null Pointer bugs). ✅ **MANDATORY**: Produce a unified Hash Map of the objects in the Controller Server-Side, and inject it unconditionally under a standard `<script>` block placed **at the bottom** of the layout view (e.g., `window.__LF_GLOBAL_MAP = {!! $var ?? '{}' !!}`). Components read it statelessly via `dataset.id`.
- **WhatsApp Template Keys:** 🟢 **MANDATORY:** All dynamic WhatsApp message templates must be stored under the `lawfirm.whatsapp_templates.messages` group in `system.php`. Do not create separate groups like `processos` or `financeiro` for templates, as the application code expects the `.messages.` hierarchy for retrieval.
- **TenantFinance vs SaaS Asaas (Critical Distinction):** 🔴 Existem **duas integrações Asaas** no pacote — nunca misturá-las:
  - `SuiteZap\LawFirm\SaaS\Services\AsaasService` → Plataforma cobra o Tenant (SaaS). Usa `infrastructure_nodes` via `MotherShipService`.
  - `SuiteZap\LawFirm\TenantFinance\Services\TenantAsaasService` → Tenant cobra seus clientes finais. Usa `tenant_asaas_settings` no banco local.
  - ⛔ **PROIBIDO:** Usar `AsaasService` para criar cobranças de clientes do escritório. A api_key é diferente por conta.
- **TenantFinance Webhook Route:** A rota `/api/webhooks/tenant-asaas` está isenta de CSRF em `VerifyCsrfToken.php` e registrada no grupo `middleware(['api'])` em `routes.php`. Ao adicionar novos módulos com webhooks, seguir o mesmo padrão.
- **TenantFinance Module Gate:** O módulo requer a chave `TENANT_FINANCE` em `active_modules` (tabela `subscriptions` no MotherShip). Sem ela, menu e rotas devem retornar 403. ACL granular em `acl.php` sob `lawfirm.cobrancas.*`.

### 6.1 CRITICAL: `v-lookup-component` Registration & Initial Value Pattern (LF v3.42)

> This is a hard-won lesson. Violating these rules causes non-obvious silent failures and JS syntax errors.

#### Problem: Component Not Registered
`v-lookup-component` is only registered via `@pushOnce('scripts')` inside the Blade partial `x-admin::attributes.edit.lookup`. If your Blade view uses `<v-lookup-component>` without triggering that partial, Vue silently skips the component — the field renders as an empty element with no search, no click, no output.

#### MANDATORY: Always register the component first (before the lookup grid):
```blade
{{-- Trigger v-lookup-component registration --}}
<x-admin::attributes.edit.lookup />
```
> Safe: The component has `@if (isset($attribute))` guard — empty include renders nothing visible.

#### MANDATORY: Correct initial value pattern for entity edit forms:
```blade
@php
    $personLookup = optional($processo->person)->id
        ? app('Webkul\Attribute\Repositories\AttributeRepository')
            ->getLookUpEntity('persons', $processo->person->id)
        : null;
@endphp

<v-lookup-component
    :attribute="{{ json_encode(['code' => 'person_id', 'name' => 'Pessoa', 'lookup_type' => 'persons']) }}"
    :value="{{ json_encode($personLookup) }}"
    validations=""
></v-lookup-component>
```
> **Key:** `{{ json_encode() }}` inside a raw HTML element attribute (not a Blade x-component) is the correct way to pass object values to Vue in Krayin. The same pattern is used in `Webkul/Admin/src/Resources/views/components/attributes/edit/lookup.blade.php`.

#### PROHIBITED approaches (cause JS errors or silent failures):
| Approach | Failure Mode |
| :--- | :--- |
| `v-bind:value='@json($var)'` | `Call to undefined function json()` — `@json` doesn't work inside Blade x-component attribute syntax |
| `v-bind:value='({!! json_encode($var) !!})'` | Vue `SyntaxError: Invalid or unexpected token` — Vue 3 template compiler evaluates `{}` as statement block, not expression |
| `v-bind:value="window.processoData.x"` | `Cannot read properties of undefined` — Vue 3 sandboxes template scope and does not expose `window` |
| `value="{{ json_encode($var) }}"` (without v-bind) | Field renders empty — attribute is passed as a STRING, Vue prop type is Object |

#### Supported `lookup_type` values in Krayin Core:
- `persons` → `Webkul\Contact\Models\Person`
- `organizations` → `Webkul\Contact\Models\Organization`
- `leads` → `Webkul\Lead\Models\Lead`
- `products` → `Webkul\Product\Models\Product`

---

## 7. Legal Domain — Processo Model Relationships (v3.42+)

The `Processo` model (`SuiteZap\LawFirm\Legal\Models\Processo`) supports the following Krayin Core relationships:

```php
// Pessoa Física (Contact)
public function person(): BelongsTo
{
    return $this->belongsTo(\Webkul\Contact\Models\Person::class);
}

// Pessoa Jurídica (Organization) — added v3.42
public function organization(): BelongsTo
{
    return $this->belongsTo(\Webkul\Contact\Models\Organization::class);
}
```

Both fields are optional (`nullable`) in the DB and marked **(Opcional)** in the UI, ensuring backward compatibility with all existing processes.

**Search routes registered in `admin-legal.php`:**
- `GET admin/juridico/processos/search-person` → `ProcessoController@searchPerson`
- `GET admin/juridico/processos/search-organization` → `ProcessoController@searchOrganization`

---

## 8. Domain-Driven Design: Caso Entity (v3.44+)

At the top of the Legal domain hierarchy resides the **Caso** (Case) entity.
Hierarchy flow: `Client -> Caso -> Processo`

### Architectural Standards for Custom LawFirm Tables
*   **Foreign Keys explicitly matching Krayin Type:** When creating migrations for custom tables (e.g., `law_casos`) that relate to Krayin's Core entities (`users`, `persons`, `organizations`), the Foreign Keys **MUST** be defined as `unsignedInteger` (e.g. `$table->unsignedInteger('user_id')`). The Krayin core uses standard `integer` for these Primary Keys, so using `unsignedBigInteger` will cause MySQL 8+ `Constraint Error 3780`.
*   **Custom AJAX Lookups over Core UI modifications:** To link custom entities (like `Caso` to `Processo`), avoid hardcoding the entity into the native `v-lookup-component` mapped in Krayin's Vue files across `Webkul/Admin/src/Resources/assets/js/components`. Instead, create a specialized AJAX component using JS Vanilla with a hidden input in the Blade view, passing the selected `caso_id` transparently to the REST controller.

### 8.1 LegalOrchestrator — Transactional Domain Service (v3.45)

When a Lead is WON, the `LeadWonListener` delegates to `LegalOrchestrator::convertLeadToLegalStructure()` which runs inside `DB::transaction()`:
1. Creates a `Caso` (parent entity) from Lead data.
2. Creates a `Processo` (child entity) linked to the new Caso via `caso_id`.
3. Links the responsible lawyer (`user_id`) to both entities.

**Golden Rules enforced:**
- **Skinny Listeners:** `LeadWonListener` contains zero creation logic — only guards and delegation.
- **Atomicity:** If either creation fails, the entire transaction rolls back.
- **Zero-Copy Documents:** Files uploaded to any Processo within a Caso are stored under `casos/{caso_id}/documents/` and visible across all sibling processos via `caso_id` query.
- **Tag-Driven Metadata Prioritization:** Uses standard Lead Tags (e.g. `Trabalhista`, `Crítica`) to populate canonical metadata like Área and Prioridade before falling back to `LeadTriagem` AI metrics.
- **Canonical Pipelines (v3.46+):** All Legal Status validation must strictly rely on `LegalOrchestrator::VALID_STATUSES` (the 12-stage unified canonical set from "Novo Caso" to "Encerrado") and avoid hard-coded pipeline matching arrays via request rules directly. The Legal Entity assumes standard 12-stage lifecycles, and Processos fallback to canonical mappings over UI inputs.

---

## 9. Top-Level Menus & Layout Organization

- **Assistentes:** The IA and Escavador features reside under a unified "Assistentes" Top-level menu (`icon-user`).
- **Financeiro:** Cobranças Asaas (TenantFinance) and Dashboard Financeiro now reside inside an exclusive "Financeiro" top-level node.
- **Do NOT** re-create fragmented UI menus per integration. Keep features nestled within their specific domain clusters or the centralized categories (Legal, Financeiro, Assistentes, Configuração).

## 10. Multi-Tenant Isolation & Migration Rules

> [!CAUTION]
> **Any code breaking tenant isolation is a security vulnerability, not just a bug.**

### 10.1 Tenant Scoping
All queries accessing domain data (Legal, Financial, GED, Whatsapp, Atendimento) **MUST** be scoped by `tenant_id` or equivalent isolation.
- ✅ Correct: `Processo::where('tenant_id', tenant('id'))->get();`
- ❌ Prohibited: `Processo::all();`

**Allowed cross-tenant (mothership) tables**:
`tenants`, `subscriptions`, `app_config`, `infrastructure_nodes`, `lawfirm_assistant_templates`, `lawfirm_document_templates`, `tenant_billing_infos`, `core_config`.

### 10.2 Migrations and Idempotency
- All migrations **MUST** be idempotent (use `hasColumn()`, `hasTable()`).
- All migrations **MUST** explicitly declare the connection (`Schema::connection('tenant')` or `Schema::connection('mothership')`).
- ❌ **Prohibited:** `Schema::create('table', ...)` (omitting connection creates tables in the wrong DB during tenant provisioning).

### 10.3 Credentials & Redis Isolation
- **Redis Swarm Isolation**: Every tenant's docker-compose stack MUST include `REDIS_PREFIX: "${TENANT_ID}_"` to prevent cross-tenant job execution.
- **Credentials**: Never use global master credentials where a tenant-isolated credential should exist (e.g. `evolution_api_key`), except for the documented fallback in `MotherShipService::getEvolutionConfig()`.

## 11. Governance & Operations

### 11.1 Decommissioned Modules (Whaticket Removed)
- The legacy Whaticket / Messenger Inbox module and its scaffold (`packages/SuiteZap/Whaticket/`) have been **completely removed** from the repository, `composer.json`, and `docker/entrypoint.sh`.
- ❌ **Do NOT** attempt to create or restore Whaticket routes/controllers.
- **Active WhatsApp Domain Scope** (`packages/SuiteZap/LawFirm/src/Whatsapp/`): Reserved strictly for transactional notifications (Faturas, Alertas de Processos, WhatsappImport, Agendador de Prazos, Kanban Sync Labels). Omnichannel chat lives in `Atendimento/` (Chatwoot). Always confirm scope before modifying WhatsApp files.

### 11.2 MotherShip Architecture Sync
If you create/alter a table in the `mothership` connection, create an endpoint consumed by MotherShip, or alter `MotherShipService` return contracts, you **MUST** update `ARCHITECTURE_mothership_orient.md` in the same task.

### 11.3 Domain Boundaries
- **Do not cross domains** without explicit justification in the execution plan. A Legal task shouldn't alter Financial services unless integration is the specific goal.

### 11.4 Jobs, Listeners & DataGrid
- **Jobs & Listeners**: Always fail gracefully with `Log::error()`. Never throw bare Exceptions that kill the queue worker.
- **DataGrid Actions (Vue)**: Always use `['method' => 'GET']` for single-click actions to prevent CSRF silent failures in Krayin V2.
- **Docker Image**: Never use `:latest` in production stacks. Always fix the semantic tag (e.g. `image: suitezap/lawfirm:vX.Y.Z`).
