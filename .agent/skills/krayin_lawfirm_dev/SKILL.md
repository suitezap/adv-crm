---
name: krayin_lawfirm_architecture
description: Standards for SuiteZap/LawFirm package (DDD/SaaS).
---
# LawFirm CRM - Architecture Standards (v3.28)

## 1. Directory Structure (Domain-Driven)
All code lives in `packages/SuiteZap/LawFirm/src/`. **Root is Zero-Debt** — no loose PHP files outside a domain.
- **Legal/**: `SuiteZap\LawFirm\Legal` — Processos, Prazos, Checklists
- **Financial/**: `SuiteZap\LawFirm\Financial` — Honorários, Custas, Faturas
- **GED/**: `SuiteZap\LawFirm\GED` — Documents, Attachments, S3 uploads
- **SaaS/**: `SuiteZap\LawFirm\SaaS` — Tenants, Subscriptions, MotherShipService, Asaas, Orders
- **AI/**: `SuiteZap\LawFirm\AI` — AssistantTemplate, AssistantHistory, ProcessAiAssistant Job
- **Escavador/**: `SuiteZap\LawFirm\Escavador` — Escavador API V1/V2, Webhooks, Monitoramentos
- **Whatsapp/**: `SuiteZap\LawFirm\Whatsapp` — EvolutionService, ConnectionController, Listeners

## 2. Strict Rules

### 2.1 Models & Controllers
- ⛔ **NEVER** place Models or Controllers at root `src/`. Must live inside `src/{Domain}/Models` or `src/{Domain}/Http/Controllers`.
- Namespace pattern: `SuiteZap\LawFirm\{Domain}\{Type}`.

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

## 3. Zero .env — MotherShipService (Mandatory Patterns)

### 3.1 WhatsApp / Evolution API
✅ **Always fetch from MotherShip:**
```php
$config = MotherShipService::getEvolutionConfig(); // ['base_url', 'instance', 'token'] or null
if (!$config || empty($config['instance'])) {
    Log::error('Evolution API não configurada no MotherShip.');
    return; // graceful abort — never throw uncaught
}
$instanceName = $config['instance'];
```
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

### 3.3 Mothership API Secret
✅ Read from `mothership.app_config` key `api_secret` (cached 5min via `getApiSecretFromDb()`).
🟡 `env('MOTHERSHIP_API_SECRET')` is **only** acceptable as a pre-migration fallback.

### 3.4 Acceptable env() Fallback Pattern
`env()` is allowed ONLY when MotherShipService is already the primary source:
```php
$config = MotherShipService::getEvolutionConfig();
$instanceName = ($config && !empty($config['instance']))
    ? $config['instance']
    : env('EVOLUTION_INSTANCE_NAME'); // dev/local fallback only
```

## 4. Graceful Degradation for Unconfigured Services
When Evolution, N8N, Asaas, or Escavador is not configured in MotherShip for a tenant:
- **Jobs / Listeners:** `Log::error(...)` + `return` — never throw, never crash the queue worker.
- **API Controllers:** Return HTTP `503` with a user-friendly JSON `error` message.
- **Web Controllers:** `session()->flash('warning', '...')` + `redirect()->back()` — never 500.

## 5. Database & SaaS (Mothership)
- API keys and config live in `mothership` DB: `infrastructure_nodes`, `app_config`, `tenants`, `subscriptions`.
- Each tenant has FKs: `n8n_node_id`, `evolution_node_id`, `storage_node_id`, `asaas_node_id`.
- Storage disk is resolved dynamically by `MotherShipService::configureTenantStorage()` at boot — `SaasFileService::getDisk()` always picks up the correct tenant bucket.
- Balance debits/credits must be registered in `saas_transactions` (tenant DB). Orders tracked in `saas_orders`.

## 6. Frontend & UI Patterns
- **Tailwind CSS** — standard for all new components.
- **Vue vs Vanilla JS:** Krayin's Vue instance controls the global DOM. ⛔ Do NOT use Alpine.js inside Blade views Vue manages. ✅ Use Vanilla JS with `MutationObserver` or event delegation.
- **AJAX & CSRF:** Read `X-CSRF-TOKEN` **at event time** (onclick), never on script init.
- **Complex Forms (External Tabs Pattern):** Use `window.appendExternalTabs(event, this)` to collect inputs from tabs outside the main `<form>`.
- **File Uploads:** ⛔ Do NOT nest `<form>` tags. Use `FormData` + `fetch`/`XMLHttpRequest`.
- **Routes in JS (REPLACE_ID Pattern):** Never pass empty params to Laravel routes in Blade:
```javascript
// ✅ CORRECT
const url = "{{ route('admin.route', 'REPLACE_ID') }}".replace('REPLACE_ID', id);
// ❌ WRONG — throws UrlGenerationException
const url = "{{ route('admin.route', '') }}" + id;
```
- **DataGrid Actions (Vue Interference):** When adding custom actions to Krayin DataGrids (`addAction` in `*DataGrid.php`), avoid using `method => 'POST'` for simple state toggles, as the Vue/Axios interceptor frequently drops CSRF validation silently or attempts to render missing icon fonts (e.g., `icon-bell` 0x0 bugs). **MANDATORY:** Use `method => 'GET'` and remove the `confirm_text` parameter so Krayin falls back to a standard `<a href="...">` native browser redirect for toggle endpoints.
- **Docker Schedulers & Workers:** Cron-based features (`schedule:run`) and Queue runners (`queue:work`) require their own container in the Swarm. The `docker/entrypoint.sh` automatically routes arbitrary `$@` commands through `www-data` to prevent root-owned `laravel.log` permission lockouts.
- **Redis Queue Isolation:** ⛔ In Docker Swarm deployments sharing a central `redis` instance, you MUST declare `REDIS_PREFIX: {tenant_id}_` in the `docker-compose.yml` ENV variables. Without it, Tenants will consume each other's asynchronous Queue Jobs across the overlay network.
- **Dynamic Menu Hiding:** When hiding default Krayin menus (e.g., 'mail') via `LawFirmServiceProvider`, always filter using a prefix check (`str_starts_with`). Removing only the parent key leaves orphaned child keys in the config, which causes a fatal error in Krayin's `Menu.php` reconstruction logic.
- **Granular ACL Definition:** To ensure checkboxes appear in the Admin Role matrix, `acl.php` entries MUST define explicit CRUD child nodes (e.g., `dominio.modulo.create`). Simple top-level keys will be registered but won't render checkboxes in the Vue tree-view component.

