<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela `app_config` no banco Mothership.
 *
 * Propósito: armazenar configurações globais do ecossistema SaaS sem depender
 * de variáveis de ambiente (.env) em cada stack de cliente.
 *
 * Uso principal: `api_secret` — chave compartilhada entre o Mothership Panel
 * e o LawFirm CRM para autenticar webhooks de invalidação de cache.
 *
 * Ambas as plataformas leem desta tabela, eliminando sincronização manual de .env.
 */
return new class extends Migration
{
    protected $connection = 'mothership';

    public function up(): void
    {
        if (! Schema::connection('mothership')->hasTable('app_config')) {
            Schema::connection('mothership')->create('app_config', function (Blueprint $table) {
                $table->string('key', 100)->primary();
                $table->text('value')->nullable();
                $table->string('description', 255)->nullable()
                    ->comment('Descrição legível da configuração');
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });

            // Seed inicial: gerar um api_secret único e seguro
            DB::connection('mothership')->table('app_config')->insert([
                [
                    'key'         => 'api_secret',
                    'value'       => bin2hex(random_bytes(32)), // 64-char hex aleatório
                    'description' => 'Chave secreta compartilhada entre Mothership Panel e LawFirm CRM para autenticação de webhooks. Lida pelo MothershipTemplateController e api/templates.php.',
                    'updated_at'  => now(),
                ],
                [
                    'key'         => 'crm_webhook_url',
                    'value'       => null, // Preencher com a URL do LawFirm: https://seu-crm.com/admin/juridico/mothership/cache/invalidate
                    'description' => 'URL do endpoint de invalidação de cache no LawFirm CRM. Chamada após qualquer mutação de template no Mothership Panel.',
                    'updated_at'  => now(),
                ],
                [
                    'key'         => 'cache_version',
                    'value'       => '1',
                    'description' => 'Versão global de cache de templates. Incrementada a cada publicação para invalidar caches em todos os tenants.',
                    'updated_at'  => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection('mothership')->dropIfExists('app_config');
    }
};
