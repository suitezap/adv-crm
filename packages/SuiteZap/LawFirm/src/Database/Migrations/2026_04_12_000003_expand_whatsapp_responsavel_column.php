<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            // Expand to accept formatted phone numbers like "(11) 99999-9999" or "+55 (11) 99999-9999"
            $table->string('whatsapp_responsavel', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->string('whatsapp_responsavel', 20)->nullable()->change();
        });
    }
};
