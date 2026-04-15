<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('law_processo_prazos', function (Blueprint $table) {
            if (!Schema::hasColumn('law_processo_prazos', 'notificar_whatsapp')) {
                $table->boolean('notificar_whatsapp')->default(false)->after('activity_id')
                    ->comment('Ativa o robô agendador de notificações WhatsApp para este prazo');
            }
            if (!Schema::hasColumn('law_processo_prazos', 'ultima_notificacao_5d')) {
                $table->timestamp('ultima_notificacao_5d')->nullable()->after('notificar_whatsapp')
                    ->comment('Timestamp do último envio da notificação com 5 dias de antecedência');
            }
            if (!Schema::hasColumn('law_processo_prazos', 'ultima_notificacao_1d')) {
                $table->timestamp('ultima_notificacao_1d')->nullable()->after('ultima_notificacao_5d')
                    ->comment('Timestamp do último envio da notificação com 1 dia de antecedência (véspera)');
            }
            if (!Schema::hasColumn('law_processo_prazos', 'ultima_notificacao_0d')) {
                $table->timestamp('ultima_notificacao_0d')->nullable()->after('ultima_notificacao_1d')
                    ->comment('Timestamp do último envio da notificação no dia do vencimento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('law_processo_prazos', function (Blueprint $table) {
            $cols = ['notificar_whatsapp', 'ultima_notificacao_5d', 'ultima_notificacao_1d', 'ultima_notificacao_0d'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('law_processo_prazos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
