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
        if (!Schema::hasTable('escavador_envolvidos')) {
            Schema::create('escavador_envolvidos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('escavador_processo_id')->index();
                $table->string('nome', 200);
                $table->string('cpf_cnpj', 30)->nullable();
                $table->string('tipo_participacao', 50)->nullable();   // Autor, Réu, Advogado, Terceiro
                $table->string('oab', 30)->nullable();
                $table->string('escavador_id', 50)->nullable();
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
        Schema::dropIfExists('escavador_envolvidos');
    }
};
