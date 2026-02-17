<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lawfirm_case_checklists', function (Blueprint $table) {
            // Adiciona fk para processos (pode ser nulo para leads que ainda não converteram)
            $table->unsignedBigInteger('processo_id')->nullable()->after('lead_id');
            $table->foreign('processo_id')->references('id')->on('processos')->onDelete('cascade');

            // Allow lead_id to be nullable now? 
            // The prompt says "checklist vinculado apenas a Leads. Precisamos permettre que ele pertença a um Processo".
            // It implies a checklist can belong to Lead OR Processo (or both during transition).
            $table->integer('lead_id')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lawfirm_case_checklists', function (Blueprint $table) {
            $table->dropForeign(['processo_id']);
            $table->dropColumn('processo_id');
            $table->integer('lead_id')->unsigned()->nullable(false)->change();
        });
    }
};
