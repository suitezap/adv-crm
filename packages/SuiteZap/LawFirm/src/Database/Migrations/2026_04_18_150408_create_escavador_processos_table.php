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
        if (! Schema::hasTable('escavador_processos')) {
            Schema::create('escavador_processos', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id', 50)->index();
                $table->unsignedBigInteger('processo_id')->nullable()->index();  // FK → processos
                $table->string('numero_cnj', 30)->index();
                $table->string('tribunal', 150)->nullable();
                $table->string('vara', 200)->nullable();
                $table->boolean('segredo_justica')->default(false);
                $table->text('resumo_ia')->nullable();                           // Cache do Resumo IA V2
                $table->string('status_atualizacao', 30)->default('pendente');   // pendente|atualizado|erro
                $table->string('escavador_id', 50)->nullable();                  // ID externo no Escavador
                $table->json('capa_json')->nullable();                           // JSON completo da capa V2
                $table->timestamp('data_ultima_verificacao')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'numero_cnj']);
                $table->foreign('processo_id')->references('id')->on('processos')->nullOnDelete();
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
        Schema::dropIfExists('escavador_processos');
    }
};
