<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Limpeza de Órfãos: Remove execuções cujo lead_id não existe em leads
        // Isso evita erro de Foreign Constraint Violation ao aplicar a chave
        DB::table('lawfirm_ai_executions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('leads')
                    ->whereColumn('leads.id', 'lawfirm_ai_executions.lead_id');
            })
            ->delete();

        // 2. Ajuste e Criação da Foreign Key
        Schema::table('lawfirm_ai_executions', function (Blueprint $table) {
            // Garante que a coluna seja do tipo compatível (unsigned integer)
            // Assumindo que leads.id é integer unsigned (padrão Krayin)
            $table->integer('lead_id')->unsigned()->change();

            // Adiciona a FK com Cascade Delete
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lawfirm_ai_executions', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            // Reverter para integer simples (signed) caso fosse o original, mas manter unsigned é seguro
            // $table->integer('lead_id')->change();
        });
    }
};
