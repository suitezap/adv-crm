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
        if (! Schema::hasTable('escavador_documentos')) {
            Schema::create('escavador_documentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('escavador_processo_id')->index();
                $table->string('tipo', 80)->nullable();               // Petição, Sentença, Acórdão
                $table->string('escavador_id', 50)->nullable();       // ID na API
                $table->text('url_pdf')->nullable();                  // Link interno (S3) ou externo
                $table->string('fonte', 30)->default('publicos');      // publicos|autos
                $table->timestamp('data_extracao')->nullable();
                $table->json('raw_json')->nullable();
                $table->timestamps();

                $table->foreign('escavador_processo_id')->references('id')->on('escavador_processos')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('escavador_documentos');
    }
};
