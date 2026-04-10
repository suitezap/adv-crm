<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona coluna `version` na tabela de templates do Mothership.
 * Esta coluna permite rastrear versões de templates e facilitar invalidação de cache.
 * O campo é incrementado automaticamente a cada `updateOrCreate` via API.
 */
return new class extends Migration {
    /**
     * Conexão: banco de dados Mothership (compartilhado entre todos os tenants).
     */
    protected $connection = 'mothership';

    public function up(): void
    {
        if (!Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'version')) {
            Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('is_active')
                    ->comment('Versão do template. Incrementada a cada atualização via API.');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
