<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_invoices')) {
            return;
        }

        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('processo_id')->nullable();
            $table->unsignedBigInteger('financial_id')->nullable();
            $table->unsignedBigInteger('tenant_asaas_customer_id');
            $table->string('asaas_payment_id', 50)->nullable()->index();
            $table->string('asaas_installment_id', 50)->nullable();
            $table->string('asaas_subscription_id', 50)->nullable();
            $table->enum('type', ['single', 'installment', 'subscription'])->default('single');
            $table->string('description', 500);
            $table->decimal('value', 10, 2);
            $table->tinyInteger('installment_count')->nullable();
            $table->decimal('installment_value', 10, 2)->nullable();
            $table->enum('billing_type', ['BOLETO', 'PIX', 'CREDIT_CARD', 'UNDEFINED'])->default('UNDEFINED');
            $table->string('status', 30)->default('PENDING');
            $table->date('due_date');
            $table->dateTime('payment_date')->nullable();
            $table->text('invoice_url')->nullable();
            $table->text('pix_qrcode')->nullable();
            $table->timestamps();

            $table->index('processo_id');
            $table->index('financial_id');
            $table->index('tenant_asaas_customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invoices');
    }
};
