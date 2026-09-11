# Runbook: Data-Plane de QA Local (local-qa-dataplane.md)

Replica os bancos `tenant_a_test`, `tenant_b_test` e `mothership_test` no MySQL
local (Laragon ou Docker) para executar o Pest com `DatabaseSafetyGuard` ativo,
sem depender da VPS. Validado em 2026-09-09 (suíte 117/117).

> [!CAUTION]
> Nunca rode a suíte contra o banco de dev (`advdf2g`) ou qualquer banco sem
> sufixo `_test` — a trava aborta, e o `RefreshDatabase` apagaria dados reais.

---

## 1. Pré-requisitos

- MySQL local no ar (`127.0.0.1:3306`) com usuário com `CREATE DATABASE`.
- Branch com as migrations `2026_09_09_*` (tenant_id) aplicadas no código.

## 2. Criar os bancos

```powershell
php artisan tinker --execute='DB::statement("CREATE DATABASE IF NOT EXISTS tenant_a_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); DB::statement("CREATE DATABASE IF NOT EXISTS tenant_b_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); DB::statement("CREATE DATABASE IF NOT EXISTS mothership_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); echo "DBS OK" . PHP_EOL;'
```

## 3. Migrar (tenant + mothership)

```powershell
$env:DB_DATABASE="tenant_a_test"
$env:DB_MOTHERSHIP_HOST="127.0.0.1"
$env:DB_MOTHERSHIP_DATABASE="mothership_test"
$env:DB_MOTHERSHIP_USERNAME="<usuario>"
$env:DB_MOTHERSHIP_PASSWORD="<senha>"
php artisan migrate --force   # repita com DB_DATABASE=tenant_b_test
```

### 3.1 Base mínima do mothership (só existe no MotherShip real)

Se `migrate` falhar com tabela `subscriptions`, `tenants` ou
`infrastructure_nodes` inexistente, crie o mínimo via tinker na conexão
`mothership` apontada para `mothership_test`:

- `subscriptions`: `id`, `tenant_id` (string, index), `status`, `expires_at`,
  `max_users`, `storage_limit_gb`, `current_usage_bytes`,
  `suitecoin_balance` (decimal 15,2), `active_modules` (json), timestamps.
- `tenants`: `id` **string(50)** `utf8mb4_0900_ai_ci` PK (compatível com a FK
  `tenant_billing_infos_tenant_id_foreign`) + campos de `Tenant::$fillable`.
- `infrastructure_nodes`: `id`, `name`, `type`, `base_url`, `api_key`,
  `meta_data` (json), `status`, timestamps.

## 4. Executar o Pest

```powershell
$env:APP_ENV="testing"
$env:TEST_ENVIRONMENT_ACK="LAW_FIRM_ISOLATED_TEST"
$env:DB_HOST="127.0.0.1"; $env:DB_DATABASE="tenant_a_test"
$env:DB_USERNAME="<usuario>"; $env:DB_PASSWORD="<senha>"
$env:DB_TEST_TENANT_A_HOST="127.0.0.1"; $env:DB_TEST_TENANT_A_DATABASE="tenant_a_test"
$env:DB_TEST_TENANT_A_USERNAME="<usuario>"; $env:DB_TEST_TENANT_A_PASSWORD="<senha>"
$env:DB_TEST_TENANT_B_HOST="127.0.0.1"; $env:DB_TEST_TENANT_B_DATABASE="tenant_b_test"
$env:DB_TEST_TENANT_B_USERNAME="<usuario>"; $env:DB_TEST_TENANT_B_PASSWORD="<senha>"
$env:DB_MOTHERSHIP_HOST="127.0.0.1"; $env:DB_MOTHERSHIP_DATABASE="mothership_test"
$env:DB_TEST_MOTHERSHIP_HOST="127.0.0.1"; $env:DB_TEST_MOTHERSHIP_DATABASE="mothership_test"
$env:DB_MOTHERSHIP_USERNAME="<usuario>"; $env:DB_MOTHERSHIP_PASSWORD="<senha>"
$env:DB_TEST_MOTHERSHIP_USERNAME="<usuario>"; $env:DB_TEST_MOTHERSHIP_PASSWORD="<senha>"
php artisan test
```

## 5. Armadilhas conhecidas

- `RefreshDatabase` limpa **só** a conexão default. Tabelas `mothership`
  acumulam linhas entre testes — testes que criam `Tenant`/`Subscription`
  devem ser idempotentes (`firstOrCreate`/`updateOrCreate`).
- MySQL do Laragon não sobe sozinho no boot — ver incidente
  `.ai/incidents/INC-2026-09-09-tenant-scope-500.md`.
- Credenciais acima são do ambiente local: nunca commitar valores reais.
