<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * tenant_id em lawfirm_assistant_history (PRIV-AUDIT-001 Onda 1c).
 *
 * Fecha IDOR horizontal no histórico de IA. Backfill com TENANT_ID do env
 * + NOT NULL condicional (mesmo padrão FIN-COBRANCAS-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('lawfirm_assistant_history', function (Blueprint $table) {
            if (! Schema::connection('mysql')->hasColumn('lawfirm_assistant_history', 'tenant_id')) {
                $table->string('tenant_id')->nullable()->index()->after('id');
            }
        });

        $tenantId = config('lawfirm.tenant_id', env('TENANT_ID'));

        if (! empty($tenantId)) {
            $nulls = DB::connection('mysql')->table('lawfirm_assistant_history')->whereNull('tenant_id')->count();

            if ($nulls > 0) {
                DB::connection('mysql')->table('lawfirm_assistant_history')
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $tenantId]);
            }

            Log::info("Migration tenant_id_assistant_history: {$nulls} registros preenchidos com tenant_id={$tenantId}.");
        }

        $remaining = DB::connection('mysql')->table('lawfirm_assistant_history')->whereNull('tenant_id')->count();

        if ($remaining > 0) {
            Log::warning("Migration tenant_id_assistant_history: {$remaining} sem tenant — NOT NULL pulado.");

            return;
        }

        Schema::connection('mysql')->table('lawfirm_assistant_history', function (Blueprint $table) {
            $table->string('tenant_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::connection('mysql')->hasColumn('lawfirm_assistant_history', 'tenant_id')) {
            return;
        }

        Schema::connection('mysql')->table('lawfirm_assistant_history', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->change();
        });
    }
};
