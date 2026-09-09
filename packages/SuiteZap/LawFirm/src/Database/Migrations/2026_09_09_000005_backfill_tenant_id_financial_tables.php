<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill de tenant_id nas tabelas financeiras (FIN-COBRANCAS-001).
 *
 * Precedente: 2026_04_01_000001_fix_null_tenant_id_in_saas_transactions.
 * Preenche registros legados (tenant_id NULL) com o TENANT_ID do ambiente.
 * Operador multi-tenant compartilhado: conferir o relatório de log antes de
 * rodar em base com mais de um tenant (atribuição manual nesse caso).
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
        $tenantId = config('lawfirm.tenant_id', env('TENANT_ID'));

        if (empty($tenantId)) {
            Log::warning('Migration backfill_tenant_id_financial: TENANT_ID não configurado. Nenhum registro atualizado.');

            return;
        }

        foreach ($this->tables as $table) {
            if (! Schema::connection('mysql')->hasTable($table)
                || ! Schema::connection('mysql')->hasColumn($table, 'tenant_id')) {
                continue;
            }

            $nulls = DB::connection('mysql')->table($table)->whereNull('tenant_id')->count();

            if ($nulls > 0) {
                DB::connection('mysql')->table($table)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $tenantId]);
            }

            Log::info("Migration backfill_tenant_id_financial: {$nulls} registros em {$table} preenchidos com tenant_id={$tenantId}.");
        }
    }

    public function down(): void
    {
        // Irreversível por segurança: não regredir tenant_id para NULL.
    }
};
