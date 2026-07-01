<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_asaas_settings')) {
            return;
        }

        Schema::create('tenant_asaas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_key', 255);
            $table->string('wallet_id', 100)->nullable();
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('webhook_token', 255)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_asaas_settings');
    }
};
