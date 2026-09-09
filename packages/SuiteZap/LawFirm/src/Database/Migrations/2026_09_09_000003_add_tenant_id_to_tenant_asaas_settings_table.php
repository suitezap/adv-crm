<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->table('tenant_asaas_settings', function (Blueprint $table) {
            if (! Schema::connection('mysql')->hasColumn('tenant_asaas_settings', 'tenant_id')) {
                $table->string('tenant_id')->nullable()->index()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('tenant_asaas_settings', function (Blueprint $table) {
            if (Schema::connection('mysql')->hasColumn('tenant_asaas_settings', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
