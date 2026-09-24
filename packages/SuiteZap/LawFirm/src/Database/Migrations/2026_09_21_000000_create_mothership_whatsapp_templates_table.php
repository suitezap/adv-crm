<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WA-TPL-001 — Cria tabela lawfirm_whatsapp_templates no banco mothership.
 *
 * Esta tabela armazena os templates globais de mensagens WhatsApp gerenciados
 * pelo MotherShip. Os tenants podem sobrescrever individualmente via core_config.
 */
return new class extends Migration
{
    protected $connection = 'mothership';

    public function up(): void
    {
        if (Schema::connection('mothership')->hasTable('lawfirm_whatsapp_templates')) {
            return; // Idempotente
        }

        Schema::connection('mothership')->create('lawfirm_whatsapp_templates', function (Blueprint $table) {
            $table->id();

            // Identificador único da chave do template (ex: new_prazo_client)
            $table->string('name')->unique()->comment('Chave única do template, ex: new_prazo_client');

            // Metadados de exibição
            $table->string('title')->comment('Título legível exibido no painel');
            $table->string('group')->default('outros')->comment('Grupo de agrupamento: prazos, agendador_adv, financeiro, ged, juridico');
            $table->text('info')->nullable()->comment('Texto de ajuda / variáveis disponíveis');
            $table->unsignedTinyInteger('rows')->default(4)->comment('Altura do textarea na UI');

            // Conteúdo padrão global
            $table->text('default_text')->comment('Texto padrão do template, editável pelo admin do MotherShip');

            // Controle
            $table->boolean('is_active')->default(true)->comment('Se false, o template não aparece no CRM');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Ordem de exibição dentro do grupo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mothership')->dropIfExists('lawfirm_whatsapp_templates');
    }
};
