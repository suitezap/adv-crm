<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Remove a FK constraint que apontava para tabela local - templates agora estão no MotherShip.
     */
    public function up(): void
    {
        Schema::table('lawfirm_assistant_history', function (Blueprint $table) {
            // Remove a FK constraint (o nome pode variar, vamos usar o padrão Laravel)
            $table->dropForeign(['template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lawfirm_assistant_history', function (Blueprint $table) {
            // Restaurar FK (se necessário rollback)
            $table->foreign('template_id')
                ->references('id')
                ->on('lawfirm_assistant_templates')
                ->onDelete('cascade');
        });
    }
};
