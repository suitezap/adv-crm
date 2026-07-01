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
        Schema::create('saas_transactions', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2)->nullable();

            $table->string('service_type')->index(); // 'ESCAVADOR_V2', 'GPT_4', 'RECARGA_MANUAL'
            $table->string('description');

            $table->unsignedBigInteger('user_id')->nullable()->index(); // Quem originou o custo (se logado)

            $table->morphs('reference'); // reference_id e reference_type, pra atrelar escavador_requests.id por exemplo

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_transactions');
    }
};
