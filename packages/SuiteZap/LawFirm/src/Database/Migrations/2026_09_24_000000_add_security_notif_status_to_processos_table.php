<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processos', function (Blueprint $table) {
            if (! Schema::hasColumn('processos', 'security_notif_status')) {
                $table->string('security_notif_status')->nullable()->after('sercreta');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processos', function (Blueprint $table) {
            if (Schema::hasColumn('processos', 'security_notif_status')) {
                $table->dropColumn('security_notif_status');
            }
        });
    }
};
