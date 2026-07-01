<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Altera o tipo da coluna de BIGINT para DECIMAL(20,0) para compatibilidade com NocoDB.
     */
    public function up(): void
    {
        Schema::connection('mothership')->table('subscriptions', function (Blueprint $table) {
            $table->decimal('current_usage_bytes', 20, 0)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mothership')->table('subscriptions', function (Blueprint $table) {
            $table->bigInteger('current_usage_bytes')->default(0)->change();
        });
    }
};
