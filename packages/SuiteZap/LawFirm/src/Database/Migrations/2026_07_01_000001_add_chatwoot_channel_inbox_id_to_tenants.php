<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Separar Account ID do real Inbox ID no Chatwoot
 *
 * PROBLEMA CORRIGIDO:
 *   A coluna `chatwoot_inbox_id` era usada de forma ambígua — ora guardava o
 *   account_id (ID da conta Chatwoot), ora o inbox_id (ID da caixa de entrada).
 *   Isso causava bugs no ChatwootWebhookController (inbox mismatch) e no
 *   ChatwootService::createContact() (inbox_id errado passado na criação).
 *
 * SOLUÇÃO:
 *   - `chatwoot_inbox_id`         → mantida (legado), agora usada SOMENTE para account_id
 *   - `chatwoot_channel_inbox_id` → NOVA coluna para o ID real da caixa de entrada (inbox)
 *
 * Referência: ARCHITECTURE_LawFirm_orient.md §14.4 — Token Distinction
 * Referência: mothership/migrations/2026_07_01_add_chatwoot_channel_inbox_id.sql
 */
return new class extends Migration
{
    /**
     * Conexão usada por esta migration — aponta para o banco do Mothership.
     */
    protected $connection = 'mothership';

    public function up(): void
    {
        Schema::connection('mothership')->table('tenants', function (Blueprint $table) {
            // Adiciona apenas se não existir (segurança para re-runs)
            if (! Schema::connection('mothership')->hasColumn('tenants', 'chatwoot_channel_inbox_id')) {
                $table->unsignedInteger('chatwoot_channel_inbox_id')
                    ->nullable()
                    ->after('chatwoot_inbox_id')
                    ->comment('ID da Inbox (caixa de entrada) do Chatwoot para este tenant — distinto do account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mothership')->table('tenants', function (Blueprint $table) {
            $table->dropColumn('chatwoot_channel_inbox_id');
        });
    }
};
