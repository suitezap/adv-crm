<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_transactions', function (Blueprint $table) {
            // Identifica em qual tenant (instância LawFirm) a transação ocorreu
            $table->string('tenant_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('saas_transactions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
