<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('lawfirm_assistant_history') && !Schema::hasColumn('lawfirm_assistant_history', 'lead_id')) {
            Schema::table('lawfirm_assistant_history', function (Blueprint $table) {
                $table->unsignedInteger('lead_id')->nullable()->after('user_id');
                $table->foreign('lead_id')
                    ->references('id')
                    ->on('leads')
                    ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('lawfirm_assistant_history') && Schema::hasColumn('lawfirm_assistant_history', 'lead_id')) {
            Schema::table('lawfirm_assistant_history', function (Blueprint $table) {
                $table->dropForeign(['lead_id']);
                $table->dropColumn('lead_id');
            });
        }
    }
};
