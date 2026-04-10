<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona as colunas `type` e `group` na tabela `app_config` do banco Mothership.
 * Necessárias para categorizar e tipar as entradas de configuração dos serviços Escavador.
 */
return new class extends Migration {
    protected $connection = 'mothership';

    public function up(): void
    {
        if (!Schema::connection('mothership')->hasColumn('app_config', 'type')) {
            Schema::connection('mothership')->table('app_config', function (Blueprint $table) {
                $table->string('type', 50)->default('string')->after('value');
                $table->string('group', 100)->default('general')->after('type');
                $table->timestamp('created_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mothership')->table('app_config', function (Blueprint $table) {
            $table->dropColumn(['type', 'group', 'created_at']);
        });
    }
};
