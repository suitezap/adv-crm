<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection('mothership')->hasTable('tenant_billing_infos')) {
            Schema::connection('mothership')->create('tenant_billing_infos', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id', 50)->collation('utf8mb4_0900_ai_ci')->unique()->comment('ID global do Tenant do MotherShip');
                
                // Dados básicos do assinante/empresa
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('cpf_cnpj')->nullable();
                $table->string('phone')->nullable();
                
                // Dados de endereço exigidos por gateways como Asaas
                $table->string('postal_code', 20)->nullable();
                $table->string('address')->nullable();
                $table->string('address_number')->nullable();
                $table->string('complement')->nullable();
                $table->string('province')->nullable();
                $table->string('city')->nullable();
                $table->string('state', 2)->nullable(); // UF com 2 letras
                
                $table->timestamps();

                // Chave estrangeira conceitual ou física para a tabela tenants
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mothership')->dropIfExists('tenant_billing_infos');
    }
};
