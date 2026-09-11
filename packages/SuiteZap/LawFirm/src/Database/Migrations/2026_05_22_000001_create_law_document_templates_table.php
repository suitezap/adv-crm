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
        Schema::create('law_document_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('titulo');
            $table->string('tipo')->default('outro')->comment('contrato, peticao, procuracao, notificacao, outro');
            $table->string('area_direito')->nullable()->comment('Se nulo, disponível para todos');
            $table->longText('conteudo');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('user_id');
            $table->timestamps();

            // Foreign Keys
            // The LawFirm architecture dictates using unsignedInteger for FKs relating to Krayin's core users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('law_document_templates');
    }
};
