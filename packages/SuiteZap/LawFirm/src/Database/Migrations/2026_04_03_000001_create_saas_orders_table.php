<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SuiteZap\LawFirm\SaaS\Models\SaasOrder;

/**
 * Cria a tabela saas_orders — "Intenção de Compra" do módulo SaaS.
 *
 * Registra cada pedido ANTES de chamar o gateway Asaas,
 * permitindo rastrear qual usuário iniciou a compra, o valor exato,
 * e o status do pagamento (PENDING → PAID / EXPIRED / CANCELED).
 *
 * Proporção: R$ 1,00 = 1 Crédito de IA (1:1).
 *
 * @see SaasOrder
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saas_orders')) {
            return;
        }

        Schema::create('saas_orders', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->enum('type', ['ai_credits', 'subscription'])->default('ai_credits');
            $table->decimal('value', 10, 2)->comment('Valor em R$ (proporção 1:1 com créditos)');

            $table->string('asaas_payment_id')->nullable()->index()->comment('pay_xxx retornado pelo Asaas');
            $table->string('asaas_checkout_session_id')->nullable()->index()->comment('ID da sessão de checkout');

            $table->string('status', 20)->default('PENDING')->index()
                ->comment('PENDING, PAID, EXPIRED, CANCELED');

            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_orders');
    }
};
