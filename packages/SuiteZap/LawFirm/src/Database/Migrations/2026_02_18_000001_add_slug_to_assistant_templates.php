<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::connection('mothership')->hasTable('lawfirm_assistant_templates') && !Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'slug')) {
            Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
                $table->string('slug')->after('id')->nullable()->index();
            });
        }
    }

    public function down()
    {
        if (Schema::connection('mothership')->hasTable('lawfirm_assistant_templates') && Schema::connection('mothership')->hasColumn('lawfirm_assistant_templates', 'slug')) {
            Schema::connection('mothership')->table('lawfirm_assistant_templates', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
