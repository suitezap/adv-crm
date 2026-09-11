<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adiciona os preços de cobrança dos novos serviços do Escavador ao app_config no banco Mothership.
 */
return new class extends Migration
{
    protected $connection = 'mothership';

    public function up(): void
    {
        DB::connection('mothership')->table('app_config')->insertOrIgnore([
            [
                'key'         => 'escavador_price_busca_juris',
                'value'       => '3.00',
                'description' => 'Custo em Reais para realizar uma busca de jurisprudências (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_busca_diario',
                'value'       => '3.00',
                'description' => 'Custo em Reais para realizar uma busca em diários oficiais (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_info_inst',
                'value'       => '3.00',
                'description' => 'Custo em Reais para buscar informações de uma instituição (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_info_pessoa',
                'value'       => '3.00',
                'description' => 'Custo em Reais para buscar informações de uma pessoa (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_busca_oab',
                'value'       => '3.00',
                'description' => 'Custo em Reais para realizar uma busca por OAB (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_atualizar_processo',
                'value'       => '3.00',
                'description' => 'Custo em Reais para solicitar atualização de dados de um processo no tribunal (Escavador V2 Async).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_baixar_autos',
                'value'       => '0.18',
                'description' => 'Custo mínimo estimado em Reais para baixar os autos de um processo (Escavador V2 Async).',
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::connection('mothership')->table('app_config')
            ->whereIn('key', [
                'escavador_price_busca_juris',
                'escavador_price_busca_diario',
                'escavador_price_info_inst',
                'escavador_price_info_pessoa',
                'escavador_price_busca_oab',
                'escavador_price_atualizar_processo',
                'escavador_price_baixar_autos',
            ])
            ->delete();
    }
};
