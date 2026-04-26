<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('escavador_movimentacoes')) {
            Schema::create('escavador_movimentacoes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('escavador_processo_id')->index();
                $table->date('data_movimentacao');
                $table->text('texto_movimentacao');
                $table->string('escavador_id', 50)->nullable();   // ID na API do Escavador
                $table->string('tipo', 80)->nullable();            // Sentença, Despacho, etc.
                $table->json('raw_json')->nullable();
                $table->timestamps();

                $table->foreign('escavador_processo_id')->references('id')->on('escavador_processos')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('escavador_movimentacoes');
    }
};
