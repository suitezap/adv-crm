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
        Schema::create('escavador_monitoramentos', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('type'); // e.g., 'diario', 'tribunal', 'termo', 'processo'
            $table->string('query_value'); // e.g., the name, CNJ, term being monitored
            $table->string('frequency')->nullable(); // e.g., 'DIARIA', 'SEMANAL'
            $table->boolean('notify_whatsapp')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escavador_monitoramentos');
    }
};
