<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: reference_id was bigint, truncating Asaas payment string IDs (pay_xxx) to 0,
 * which broke idempotency checks and prevented credit sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_transactions', function (Blueprint $table) {
            $table->string('reference_id', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('saas_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('reference_id')->default(0)->change();
        });
    }
};
