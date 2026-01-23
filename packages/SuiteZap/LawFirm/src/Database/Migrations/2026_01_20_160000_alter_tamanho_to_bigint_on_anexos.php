<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Alters 'tamanho' column from integer to bigInteger to support files > 2GB.
     */
    public function up(): void
    {
        Schema::table('law_processo_anexos', function (Blueprint $table) {
            $table->bigInteger('tamanho')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('law_processo_anexos', function (Blueprint $table) {
            $table->integer('tamanho')->nullable()->change();
        });
    }
};
