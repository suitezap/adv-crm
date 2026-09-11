<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Torna tenant_id obrigatório nas tabelas financeiras (FIN-COBRANCAS-001).
 *
 * Só aplica NOT NULL quando não restar nenhum NULL (pós-backfill 000005).
 * Com NULLs remanescentes (base multi-tenant compartilhada sem conciliação),
 * registra warning e pula — o isolamento segue garantido em app via
 * BelongsToTenant/TenantScope até a conciliação manual.
 */
return new class extends Migration
{
    private array $tables = [
        'law_financials',
        'tenant_invoices',
        'tenant_asaas_settings',
        'tenant_asaas_customers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::connection('mysql')->hasTable($table)
                || ! Schema::connection('mysql')->hasColumn($table, 'tenant_id')) {
                continue;
            }

            $nulls = DB::connection('mysql')->table($table)->whereNull('tenant_id')->count();

            if ($nulls > 0) {
                Log::warning("Migration enforce_tenant_id_not_null: {$table} ainda tem {$nulls} registros sem tenant — NOT NULL pulado, conciliação manual pendente.");

                continue;
            }

            Schema::connection('mysql')->table($table, function (Blueprint $table) {
                $table->string('tenant_id')->nullable(false)->change();
            });

            Log::info("Migration enforce_tenant_id_not_null: {$table}.tenant_id agora NOT NULL.");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::connection('mysql')->hasTable($table)
                || ! Schema::connection('mysql')->hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::connection('mysql')->table($table, function (Blueprint $table) {
                $table->string('tenant_id')->nullable()->change();
            });
        }
    }
};
