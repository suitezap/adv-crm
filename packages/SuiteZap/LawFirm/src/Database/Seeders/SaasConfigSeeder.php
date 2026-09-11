<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaasConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Sets up default SaaS configuration values for storage quota and AI credits.
     */
    public function run(): void
    {
        $configs = [
            // Storage Quota Settings
            'lawfirm.saas.storage.limit' => '4294967296',  // 4GB in bytes
            'lawfirm.saas.storage.used'  => '0',           // Initial usage

            // AI Credits Settings
            'lawfirm.saas.ai.credits'   => '1000',        // Trial tokens
            'lawfirm.saas.ai.plan_type' => 'prepaid',     // prepaid or postpaid
        ];

        foreach ($configs as $code => $value) {
            // Only set if not already configured (don't overwrite existing values)
            $exists = DB::table('core_config')->where('code', $code)->exists();

            if (! $exists) {
                DB::table('core_config')->insert([
                    'code'       => $code,
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
