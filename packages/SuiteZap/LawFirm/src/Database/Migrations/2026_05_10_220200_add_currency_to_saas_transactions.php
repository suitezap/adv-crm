<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `currency` column to saas_transactions to differentiate
 * legacy BRL transactions from new SuiteCoins transactions.
 *
 * @since v3.47 — SuiteCoins Migration
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_transactions', function (Blueprint $table) {
            $table->string('currency', 16)->default('BRL')->after('balance_after')
                ->comment('BRL = legacy, SUITECOIN = virtual currency');
        });

        // Mark all existing transactions as BRL (they were created pre-SuiteCoins)
        // New transactions will default to SUITECOIN via the model
    }

    public function down(): void
    {
        Schema::table('saas_transactions', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
