<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lawfirm_assistant_templates', function (Blueprint $table) {
            $table->string('required_module')->nullable()->index()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lawfirm_assistant_templates', function (Blueprint $table) {
            $table->dropColumn('required_module');
        });
    }
};
