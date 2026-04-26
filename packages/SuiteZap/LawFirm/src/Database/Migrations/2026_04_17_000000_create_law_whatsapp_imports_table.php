<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('law_whatsapp_imports')) {
            return;
        }

        Schema::create('law_whatsapp_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('processo_id');
            $table->string('remote_jid');
            $table->string('contact_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->unsignedInteger('imported_by')->nullable();
            $table->timestamps();

            $table->foreign('processo_id')
                  ->references('id')
                  ->on('processos')
                  ->onDelete('cascade');

            $table->foreign('imported_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('law_whatsapp_imports');
    }
};
