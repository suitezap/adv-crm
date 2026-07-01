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
        if (! Schema::hasTable('lawfirm_case_checklists')) {
            Schema::create('lawfirm_case_checklists', function (Blueprint $table) {
                $table->id();

                // Relacionamento com o LEAD (Caso do Krayin)
                $table->integer('lead_id')->unsigned();
                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');

                // Tipo de Checklist (ex: 'labor_claimant', 'labor_defendant')
                $table->string('type')->default('labor_claimant');

                // Controle de Estado (Onde parou - Etapa 1 a 8)
                $table->integer('current_step')->default(1);

                // Dados Preenchidos (JSON estruturado com as respostas dos inputs)
                $table->json('step_data')->nullable();

                // Último Feedback da IA (Para auditoria e exibição)
                $table->json('ai_last_feedback')->nullable();

                // Status Geral
                $table->enum('status', ['draft', 'in_progress', 'completed', 'blocked'])->default('draft');

                // Auditoria simples
                $table->integer('created_by')->unsigned()->nullable();
                $table->integer('updated_by')->unsigned()->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawfirm_case_checklists');
    }
};
