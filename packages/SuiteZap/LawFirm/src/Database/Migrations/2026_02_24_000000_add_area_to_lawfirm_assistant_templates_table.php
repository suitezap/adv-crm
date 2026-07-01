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
        if (! Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'area')) {
            Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
                $table->string('area')->nullable()->after('category')->comment('Área Jurídica principal do assistente');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
