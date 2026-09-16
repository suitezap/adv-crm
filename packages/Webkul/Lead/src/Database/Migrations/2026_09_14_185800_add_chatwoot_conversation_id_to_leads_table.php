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
        // Idempotente (SKILL.md §10.2): artisan migrate --path roda esta migration
        // na conexão default do tenant, igual às 18 migrations irmãs de Lead
        // (todas usam Schema:: sem connection explícita). Trocar para
        // Schema::connection('tenant') aqui quebraria o provisionamento, pois
        // essa conexão nomeada não existe em todos os contextos de boot.
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'chatwoot_conversation_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->integer('chatwoot_conversation_id')->nullable()->after('lead_pipeline_stage_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'chatwoot_conversation_id')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('chatwoot_conversation_id');
        });
    }
};
