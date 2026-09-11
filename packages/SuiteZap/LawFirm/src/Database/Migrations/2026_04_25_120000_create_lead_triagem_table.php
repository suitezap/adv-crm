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
        Schema::create('lead_triagem', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('lead_id')->nullable()->comment('Relacionamento com Lead');
            $table->string('area')->nullable();
            $table->string('assunto')->nullable();
            $table->string('urgencia')->nullable();
            $table->string('tipo')->nullable();
            $table->string('tipo_agente')->nullable();
            $table->text('objetivo')->nullable();
            $table->timestamps();

            // Foreign Key
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_triagem');
    }
};
