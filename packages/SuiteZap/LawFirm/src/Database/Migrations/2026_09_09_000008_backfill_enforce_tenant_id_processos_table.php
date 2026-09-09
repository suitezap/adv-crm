<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill + enforcement de tenant_id em processos (PRIV-AUDIT-001 pré-req).
 *
 * Mesmo padrão de FIN-COBRANCAS-001 (000005/000006): preenche NULLs com o
 * TENANT_ID do ambiente e só aplica NOT NULL sem NULLs restantes.
 * Em base compartilhada com >1 tenant, conferir o log antes de rodar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('mysql')->hasTable('processos')
            || ! Schema::connection('mysql')->hasColumn('processos', 'tenant_id')) {
            return;
        }

        $tenantId = config('lawfirm.tenant_id', env('TENANT_ID'));

        if (! empty($tenantId)) {
            $nulls = DB::connection('mysql')->table('processos')->whereNull('tenant_id')->count();

            if ($nulls > 0) {
                DB::connection('mysql')->table('processos')
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $tenantId]);
            }

            Log::info("Migration backfill_tenant_id_processos: {$nulls} registros preenchidos com tenant_id={$tenantId}.");
        } else {
            Log::warning('Migration backfill_tenant_id_processos: TENANT_ID não configurado. Nenhum registro atualizado.');
        }

        $remaining = DB::connection('mysql')->table('processos')->whereNull('tenant_id')->count();

        if ($remaining > 0) {
            Log::warning("Migration enforce_tenant_id_processos: {$remaining} registros sem tenant — NOT NULL pulado, conciliação manual pendente.");

            return;
        }

        Schema::connection('mysql')->table('processos', function (Blueprint $table) {
            $table->string('tenant_id')->nullable(false)->change();
        });

        Log::info('Migration enforce_tenant_id_processos: processos.tenant_id agora NOT NULL.');
    }

    public function down(): void
    {
        if (! Schema::connection('mysql')->hasColumn('processos', 'tenant_id')) {
            return;
        }

        Schema::connection('mysql')->table('processos', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->change();
        });
    }
};
