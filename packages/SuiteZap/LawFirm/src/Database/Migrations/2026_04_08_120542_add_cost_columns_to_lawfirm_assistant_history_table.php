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
        Schema::table('lawfirm_assistant_history', function (Blueprint $table) {
            $table->string('execution_id')->nullable()->after('status');
            $table->string('node_name')->nullable()->after('execution_id');
            $table->string('model')->nullable()->after('node_name');
            $table->decimal('total_cost', 10, 4)->nullable()->after('model');
            $table->decimal('real_cost', 10, 4)->nullable()->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lawfirm_assistant_history', function (Blueprint $table) {
            $table->dropColumn(['execution_id', 'node_name', 'model', 'total_cost', 'real_cost']);
        });
    }
};
