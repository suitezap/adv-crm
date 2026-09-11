<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Muda a semântica do campo "tipo" de classificação de gravidade (fatal/comum)
     * para classificação de categoria (prazo/tarefa).
     */
    public function up(): void
    {
        // 1. Migrar dados existentes: todos os registros tornam-se 'prazo'
        //    (fatal e comum são variações de prazo jurídico, não tarefas)
        DB::table('law_processo_prazos')
            ->whereIn('tipo', ['fatal', 'comum'])
            ->update(['tipo' => 'prazo']);

        // 2. Alterar o default da coluna
        Schema::table('law_processo_prazos', function (Blueprint $table) {
            $table->string('tipo')->default('prazo')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte o default e migra dados de volta (best effort)
        Schema::table('law_processo_prazos', function (Blueprint $table) {
            $table->string('tipo')->default('comum')->change();
        });

        DB::table('law_processo_prazos')
            ->where('tipo', 'prazo')
            ->update(['tipo' => 'comum']);
    }
};
