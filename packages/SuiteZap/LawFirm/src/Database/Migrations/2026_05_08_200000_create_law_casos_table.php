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
        if (Schema::hasTable('law_casos')) {
            return;
        }

        Schema::create('law_casos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('area', 100)->nullable();
            $table->string('status', 50)->default('aberto');
            $table->string('prioridade', 20)->nullable();
            $table->text('descricao')->nullable();

            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('organization_id')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('person_id')
                ->references('id')->on('persons')
                ->onDelete('set null');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_casos');
    }
};
