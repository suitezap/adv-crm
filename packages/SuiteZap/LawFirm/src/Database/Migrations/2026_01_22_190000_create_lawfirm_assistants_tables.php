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
        Schema::create('lawfirm_assistant_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title'); // Ex: Ação de Alimentos
            $table->string('slug')->unique();
            $table->string('category'); // Ex: processual, contracts, pieces
            $table->text('description')->nullable();
            $table->json('form_schema'); // Definição dos campos (label, type, variable_name)
            $table->longText('prompt_structure'); // Texto com placeholders {{variavel}}
            $table->string('n8n_webhook_url')->nullable(); // URL para execução remota
            $table->integer('token_cost')->default(0); // Custo em tokens
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lawfirm_assistant_history', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned(); // Quem executou
            $table->integer('template_id')->unsigned();
            $table->json('input_data'); // O que foi preenchido
            $table->longText('generated_content')->nullable(); // O prompt gerado ou resposta da IA
            $table->string('execution_mode'); // 'prompt_only' ou 'agent_exec'
            $table->string('status')->default('completed'); // completed, pending (para n8n async), failed
            $table->timestamps();

            // Foreign Keys
            $table->foreign('template_id')->references('id')->on('lawfirm_assistant_templates')->onDelete('cascade');
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
        Schema::dropIfExists('lawfirm_assistant_history');
        Schema::dropIfExists('lawfirm_assistant_templates');
    }
};
