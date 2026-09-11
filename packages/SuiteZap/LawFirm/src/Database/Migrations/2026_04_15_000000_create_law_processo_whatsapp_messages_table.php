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
        Schema::create('law_processo_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('processo_id');
            $table->string('remote_jid')->index();
            $table->string('sender_name')->nullable();
            $table->text('message_text')->nullable();
            $table->string('message_id')->nullable()->unique();
            $table->timestamp('message_timestamp')->nullable();
            $table->boolean('is_from_me')->default(false);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('processo_id')
                ->references('id')
                ->on('processos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('law_processo_whatsapp_messages');
    }
};
