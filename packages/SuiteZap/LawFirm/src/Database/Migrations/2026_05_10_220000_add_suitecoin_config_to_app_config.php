<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds SuiteCoin configuration keys to the MotherShip app_config table.
 *
 * Keys:
 *   suitecoin_rate            → R$ 1 = Ƶ 10
 *   suitecoin_markup          → 1.25 (25% sobre custo real)
 *   suitecoin_min_recharge_brl → R$ 25,00 mínimo
 *
 * @since v3.47 — SuiteCoins Migration
 */
return new class extends Migration
{
    public function up(): void
    {
        $keys = [
            ['key' => 'suitecoin_rate',              'value' => '10',    'group' => 'suitecoin'],
            ['key' => 'suitecoin_markup',             'value' => '1.25',  'group' => 'suitecoin'],
            ['key' => 'suitecoin_min_recharge_brl',   'value' => '25.00', 'group' => 'suitecoin'],
        ];

        foreach ($keys as $item) {
            DB::connection('mothership')->table('app_config')->updateOrInsert(
                ['key' => $item['key']],
                $item
            );
        }
    }

    public function down(): void
    {
        DB::connection('mothership')->table('app_config')
            ->where('group', 'suitecoin')
            ->delete();
    }
};
