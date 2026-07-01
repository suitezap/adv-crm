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
        Schema::create('lawfirm_ai_executions', function (Blueprint $table) {
            $table->id();
            $table->integer('lead_id')->index(); // FK to leads table
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('prompt_version');
            $table->json('input_data');
            $table->json('output_data');
            $table->string('risk_level')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('lawfirm_human_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('lawfirm_ai_executions')->onDelete('cascade');
            $table->integer('user_id'); // FK to users
            $table->string('decision');
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('lawfirm_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->integer('entity_id');
            $table->string('action');
            $table->integer('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawfirm_audit_logs');
        Schema::dropIfExists('lawfirm_human_decisions');
        Schema::dropIfExists('lawfirm_ai_executions');
    }
};
