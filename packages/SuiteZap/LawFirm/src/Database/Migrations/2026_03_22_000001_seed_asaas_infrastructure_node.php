<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed: Insere o nó Asaas na tabela infrastructure_nodes do MotherShip.
 *
 * IMPORTANTE: Substitua 'SUA_API_KEY_SANDBOX_AQUI' pela chave real
 * obtida em https://sandbox.asaas.com → Integrações → Chaves de API
 *
 * Para trocar de sandbox para produção:
 *   - base_url: https://api.asaas.com
 *   - meta_data.checkout_url: https://asaas.com
 *   - api_key: chave de produção
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::connection('mothership')
            ->table('infrastructure_nodes')
            ->where('type', 'asaas')
            ->exists();

        if (! $exists) {
            DB::connection('mothership')
                ->table('infrastructure_nodes')
                ->insert([
                    'name'     => 'Asaas Gateway',
                    'type'     => 'asaas',
                    'base_url' => 'https://api-sandbox.asaas.com',
                    // ⚠️ SUBSTITUIR PELA CHAVE REAL ANTES DE RODAR A MIGRATION
                    'api_key'   => 'SUA_API_KEY_SANDBOX_AQUI',
                    'meta_data' => json_encode([
                        'checkout_url' => 'https://sandbox.asaas.com',
                        'environment'  => 'sandbox',
                        'pix_key'      => '13748479-eac5-4d7f-90aa-8fe43d4a9081',
                        'notes'        => 'Para produção: base_url=https://api.asaas.com, checkout_url=https://asaas.com',
                    ]),
                    'status' => 'active',
                ]);
        }
    }

    public function down(): void
    {
        DB::connection('mothership')
            ->table('infrastructure_nodes')
            ->where('type', 'asaas')
            ->delete();
    }
};
