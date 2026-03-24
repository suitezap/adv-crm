<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Certifica-se de que a conexão com o mothership funciona
        try {
            DB::connection('mothership')->table('infrastructure_nodes')->insert([
                'name' => 'Gateway Asaas (SaaS)',
                'type' => 'asaas',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'api_key' => '$aact_YTU5YTE0M2M2N2I4MTliNDQ4NTEyYjI0OTBkNGZhNmI6Ojk3M2U0YTJmLTViNDMtNGUxZC05ZmVhLTA2MTBlNTkwOGQxZTo6JGFhY2hfOThjYTY4NjEtNWJhYS00MTRiLTg0MzYtMGQ3MmY3MjBhMWJk', // Coloque sua API KEY real do Sandbox aqui
                'capacity_limit' => null,
                'current_load' => null,
                'status' => 'active',
                'meta_data' => json_encode(['environment' => 'sandbox']),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::warning("Migração Asaas Node pulada: Não foi possível acessar o MotherShip DB. Erro: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            DB::connection('mothership')->table('infrastructure_nodes')
                ->where('type', 'asaas')
                ->delete();
        } catch (\Exception $e) {
            \Log::warning("Rollback Asaas Node pulado: " . $e->getMessage());
        }
    }
};
