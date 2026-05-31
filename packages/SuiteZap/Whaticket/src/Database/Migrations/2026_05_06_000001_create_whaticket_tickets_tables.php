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
        // Contacts
        Schema::create('whaticket_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index()->comment('SaaS Tenant Isolation');
            $table->string('name');
            $table->string('number')->index();
            $table->string('email')->nullable();
            $table->string('profile_pic_url')->nullable();
            $table->boolean('is_group')->default(false);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'number']);
        });

        // Queues (Sectores)
        Schema::create('whaticket_queues', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('color');
            $table->text('greeting_message')->nullable();
            $table->timestamps();
        });

        // WhatsApp Connections
        Schema::create('whaticket_whatsapps', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('status')->default('DISCONNECTED');
            $table->boolean('is_default')->default(false);
            $table->string('number')->nullable();
            $table->string('provider')->default('evolution');
            $table->timestamps();
        });

        // Labels / Tags
        Schema::create('whaticket_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('color');
            $table->timestamps();
        });

        // Tickets
        Schema::create('whaticket_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('status')->default('pending')->comment('pending, open, closed');
            $table->unsignedInteger('unread_messages')->default(0);
            $table->boolean('is_group')->default(false);
            
            // Standard user ownership via user_id
            $table->unsignedBigInteger('user_id')->nullable(); 
            // Foreign Keys mapping
            $table->foreignId('contact_id')->constrained('whaticket_contacts')->onDelete('cascade');
            $table->foreignId('whatsapp_id')->nullable()->constrained('whaticket_whatsapps')->onDelete('set null');
            $table->foreignId('queue_id')->nullable()->constrained('whaticket_queues')->onDelete('set null');
            
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // Messages
        Schema::create('whaticket_messages', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Message ID from Provider');
            $table->string('tenant_id')->index();
            $table->foreignId('ticket_id')->constrained('whaticket_tickets')->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained('whaticket_contacts')->onDelete('cascade');
            
            $table->text('body');
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            $table->boolean('from_me')->default(false);
            $table->boolean('read')->default(false);
            $table->integer('ack')->default(0)->comment('0: pending, 1: sent, 2: received, 3: read, 4: played');
            
            $table->timestamps();
        });

        // Ticket Tags Pivot
        Schema::create('whaticket_ticket_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('ticket_id')->constrained('whaticket_tickets')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('whaticket_tags')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['ticket_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whaticket_ticket_tags');
        Schema::dropIfExists('whaticket_messages');
        Schema::dropIfExists('whaticket_tickets');
        Schema::dropIfExists('whaticket_tags');
        Schema::dropIfExists('whaticket_whatsapps');
        Schema::dropIfExists('whaticket_queues');
        Schema::dropIfExists('whaticket_contacts');
    }
};
