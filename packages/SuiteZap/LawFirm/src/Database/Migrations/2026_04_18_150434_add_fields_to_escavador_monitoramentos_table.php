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
        Schema::table('escavador_monitoramentos', function (Blueprint $table) {
            if (! Schema::hasColumn('escavador_monitoramentos', 'status')) {
                $table->string('status', 20)->default('ativo');          // ativo|pausado|expirado
            }
            if (! Schema::hasColumn('escavador_monitoramentos', 'processo_id')) {
                $table->unsignedBigInteger('processo_id')->nullable();   // FK → processos (opcional)
            }
            if (! Schema::hasColumn('escavador_monitoramentos', 'custo_mensal')) {
                $table->decimal('custo_mensal', 8, 2)->nullable();       // Custo R$/mês do Escavador
            }
            if (! Schema::hasColumn('escavador_monitoramentos', 'nome_alvo')) {
                $table->string('nome_alvo', 200)->nullable();            // Nome do monitorado legível
            }
            if (! Schema::hasColumn('escavador_monitoramentos', 'ultima_notificacao_at')) {
                $table->timestamp('ultima_notificacao_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('escavador_monitoramentos', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'processo_id',
                'custo_mensal',
                'nome_alvo',
                'ultima_notificacao_at',
            ]);
        });
    }
};
