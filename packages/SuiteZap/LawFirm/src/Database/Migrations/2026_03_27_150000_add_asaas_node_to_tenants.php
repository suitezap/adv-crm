<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mothership';

    public function up(): void
    {
        if (Schema::connection('mothership')->hasTable('tenants')) {
            if (!Schema::connection('mothership')->hasColumn('tenants', 'asaas_node_id')) {
                Schema::connection('mothership')->table('tenants', function (Blueprint $table) {
                    $table->unsignedBigInteger('asaas_node_id')->nullable()->after('minio_bucket_name');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::connection('mothership')->hasTable('tenants')) {
            if (Schema::connection('mothership')->hasColumn('tenants', 'asaas_node_id')) {
                Schema::connection('mothership')->table('tenants', function (Blueprint $table) {
                    $table->dropColumn('asaas_node_id');
                });
            }
        }
    }
};
