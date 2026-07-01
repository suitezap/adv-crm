<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adiciona os preços de cobrança dos serviços do Escavador ao app_config no banco Mothership.
 * Isso permite que o custo consumido do saldo "ai_tokens_balance" seja configurável
 * via painel sem a necessidade de deploy de código na aplicação (LawFirm).
 */
return new class extends Migration
{
    protected $connection = 'mothership';

    public function up(): void
    {
        DB::connection('mothership')->table('app_config')->insertOrIgnore([
            [
                'key'         => 'escavador_price_capa',
                'value'       => '3.00',
                'description' => 'Custo em Reais para baixar a capa completa de um processo do Escavador V2.',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_diario',
                'value'       => '3.00',
                'description' => 'Custo em Reais para realizar o download em PDF da íntegra do diário oficial (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_busca',
                'value'       => '3.00',
                'description' => 'Custo em Reais para realizar uma busca genérica de termos, entidades ou patentes (Escavador V1).',
                'updated_at'  => now(),
            ],
            [
                'key'         => 'escavador_price_resumo',
                'value'       => '0.08',
                'description' => 'Custo em Reais para gerar um resumo inteligente de um processo via IA (Escavador V2 Async).',
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::connection('mothership')->table('app_config')
            ->whereIn('key', [
                'escavador_price_capa',
                'escavador_price_diario',
                'escavador_price_busca',
                'escavador_price_resumo',
            ])
            ->delete();
    }
};
