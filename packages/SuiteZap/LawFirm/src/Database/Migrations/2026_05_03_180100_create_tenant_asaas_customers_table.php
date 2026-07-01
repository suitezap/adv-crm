<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_asaas_customers')) {
            return;
        }

        Schema::create('tenant_asaas_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->string('asaas_customer_id', 50)->unique();
            $table->string('name', 200);
            $table->string('cpf_cnpj', 20);
            $table->string('email', 200)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();

            $table->index('person_id');
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_asaas_customers');
    }
};
