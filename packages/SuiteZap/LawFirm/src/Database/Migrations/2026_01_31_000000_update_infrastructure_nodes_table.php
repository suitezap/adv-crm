<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adiciona colunas essenciais para configuração de nós de infraestrutura (N8N, Evolution, MinIO).
     */
    public function up(): void
    {
        Schema::connection('mothership')->table('infrastructure_nodes', function (Blueprint $table) {
            // Tipo do nó (n8n, evolution, minio, etc)
            if (!Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'type')) {
                $table->string('type')->index()->after('id');
            }

            // URL base do serviço
            if (!Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'base_url')) {
                $table->string('base_url')->nullable()->after('type');
            }

            // API Key para autenticação
            if (!Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'api_key')) {
                $table->string('api_key')->nullable()->after('base_url');
            }

            // Metadados adicionais (JSON)
            if (!Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('api_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mothership')->table('infrastructure_nodes', function (Blueprint $table) {
            if (Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'base_url')) {
                $table->dropColumn('base_url');
            }

            if (Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'api_key')) {
                $table->dropColumn('api_key');
            }

            if (Schema::connection('mothership')->hasColumn('infrastructure_nodes', 'meta_data')) {
                $table->dropColumn('meta_data');
            }
        });
    }
};
