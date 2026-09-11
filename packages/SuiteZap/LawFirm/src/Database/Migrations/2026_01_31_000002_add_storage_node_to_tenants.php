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
        // Verificamos se a culuna já existe para evitar erro de duplicação
        if (! Schema::connection('mothership')->hasColumn('tenants', 'storage_node_id')) {
            Schema::connection('mothership')->table('tenants', function (Blueprint $table) {
                $table->unsignedBigInteger('storage_node_id')->nullable()->after('n8n_node_id');

                // Adicionando a Foreign Key
                $table->foreign('storage_node_id')->references('id')->on('infrastructure_nodes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mothership')->table('tenants', function (Blueprint $table) {
            // Removendo a Foreign Key antes de remover a coluna
            // Nota: Se a migration rodar o down e a FK não existir, pode dar erro,
            // mas o padrão é assumir que o up rodou completo.
            $table->dropForeign(['storage_node_id']);
            $table->dropColumn('storage_node_id');
        });
    }
};
