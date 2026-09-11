<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renames ai_tokens_balance → suitecoin_balance on MotherShip subscriptions table
 * and converts existing BRL values to SuiteCoins (× 10).
 *
 * @since v3.47 — SuiteCoins Migration
 */
return new class extends Migration
{
    public function up(): void
    {
        $conn = 'mothership';
        // 1. Rename column
        if (Schema::connection($conn)->hasColumn('subscriptions', 'ai_tokens_balance') && ! Schema::connection($conn)->hasColumn('subscriptions', 'suitecoin_balance')) {
            Schema::connection($conn)->table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('ai_tokens_balance', 'suitecoin_balance');
            });
        } elseif (Schema::connection($conn)->hasColumn('subscriptions', 'ai_tokens_balance') && Schema::connection($conn)->hasColumn('subscriptions', 'suitecoin_balance')) {
            Schema::connection($conn)->table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('ai_tokens_balance');
            });
        }
    }

    public function down(): void
    {
        $conn = 'mothership';

        // Rename back
        if (Schema::connection($conn)->hasColumn('subscriptions', 'suitecoin_balance') && ! Schema::connection($conn)->hasColumn('subscriptions', 'ai_tokens_balance')) {
            Schema::connection($conn)->table('subscriptions', function (Blueprint $table) {
                $table->renameColumn('suitecoin_balance', 'ai_tokens_balance');
            });
        }
    }
};
