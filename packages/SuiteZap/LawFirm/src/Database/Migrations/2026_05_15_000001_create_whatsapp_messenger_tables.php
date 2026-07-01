<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Contacts (WhatsApp callers mapped to Krayin Persons) ---
        if (! Schema::hasTable('law_whatsapp_contacts')) {
            Schema::create('law_whatsapp_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('tenant_id')->index();
                $table->string('phone', 30)->index();           // e.g. 5511999999999
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->unsignedInteger('person_id')->nullable()->index(); // FK → Krayin persons.id
                $table->timestamps();

                $table->unique(['tenant_id', 'phone']);
            });
        }

        // --- Tickets (a conversation thread per contact) ---
        if (! Schema::hasTable('law_whatsapp_tickets')) {
            Schema::create('law_whatsapp_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('tenant_id')->index();
                $table->unsignedBigInteger('contact_id');
                $table->unsignedInteger('user_id')->nullable()->index();  // assigned agent (FK → users.id)
                $table->enum('status', ['pending', 'open', 'closed'])->default('pending')->index();
                $table->unsignedBigInteger('last_message_id')->nullable();
                $table->timestamps();

                $table->foreign('contact_id')->references('id')->on('law_whatsapp_contacts')->onDelete('cascade');
            });
        }

        // --- Messages  ---
        if (! Schema::hasTable('law_whatsapp_messages')) {
            Schema::create('law_whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('tenant_id')->index();
                $table->unsignedBigInteger('ticket_id');
                $table->string('evolution_message_id')->nullable()->unique(); // dedup key from Evolution
                $table->boolean('from_me')->default(false);
                $table->string('type')->default('text');  // text, image, audio, video, document
                $table->json('body');                    // { text: "...", mediaUrl: "...", ... }
                $table->tinyInteger('ack')->default(0);  // 0=pending,1=sent,2=delivered,3=read,4=played
                $table->timestamps();

                $table->foreign('ticket_id')->references('id')->on('law_whatsapp_tickets')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('law_whatsapp_messages');
        Schema::dropIfExists('law_whatsapp_tickets');
        Schema::dropIfExists('law_whatsapp_contacts');
    }
};
