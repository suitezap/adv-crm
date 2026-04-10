<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Correção crítica: preenche tenant_id NULL em saas_transactions.
 *
 * Contexto: Antes da implementação do isolamento por tenant, registros
 * eram gravados sem tenant_id (NULL). Esses registros vazavam para todos
 * os tenants nas DataGrids (SaasTransactionsDataGrid e SaasAdditionsDataGrid).
 *
 * Esta migration atribui o tenant_id configurado no .env (TENANT_ID) a
 * todos os registros que estejam com NULL, assumindo que se há apenas um
 * tenant no banco, todos os registros pertencem a ele.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tenantId = config('lawfirm.tenant_id', env('TENANT_ID'));

        if (!$tenantId) {
            Log::warning('Migration fix_null_tenant_id: TENANT_ID não configurado. Nenhum registro atualizado.');
            return;
        }

        $affected = DB::table('saas_transactions')
            ->whereNull('tenant_id')
            ->update(['tenant_id' => $tenantId]);

        Log::info("Migration fix_null_tenant_id: {$affected} registros em saas_transactions preenchidos com tenant_id={$tenantId}.");
    }

    public function down(): void
    {
        // Irreversível por segurança: não queremos regredir o tenant_id para NULL.
        // Para desfazer manualmente (ambiente de dev):
        // UPDATE saas_transactions SET tenant_id = NULL WHERE tenant_id = '{SEU_TENANT_ID}';
    }
};
