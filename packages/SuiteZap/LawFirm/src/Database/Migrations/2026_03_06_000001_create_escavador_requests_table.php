<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela de controle de requisições ao Escavador.
 *
 * Resolve o problema da assincronicidade das requisições V2:
 * - POST → Recebe ID (external_id) → Espera Webhook.
 * - Também registra requisições V1 (síncronas) para auditoria e estorno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escavador_requests', function (Blueprint $table) {
            $table->id();

            // Tenant SaaS responsável pela requisição
            $table->string('tenant_id')->index();

            // FK opcional para o processo jurídico local
            $table->unsignedBigInteger('processo_id')->nullable()->index();

            // ID retornado pelo Escavador no aceite da requisição V2
            $table->string('external_id')->nullable()->index();

            // Tipo de serviço solicitado (ex: CAPA_PROCESSO, BUSCA_TERMO)
            $table->string('endpoint_type');

            // Status da requisição
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->index();

            // Valor debitado em R$ no momento da requisição
            $table->decimal('cost', 8, 2)->default(0.00);

            // Payload retornado pelo Escavador via webhook (V2) ou diretamente (V1)
            $table->json('payload_response')->nullable();

            $table->timestamps();

            // Foreign key suave (sem constraint cross-tenant para permitir multi-tenant)
            // processo_id aponta para law_processes.id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escavador_requests');
    }
};
