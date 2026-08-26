#!/bin/bash
set -e

echo "🚀 Iniciando LawFirm SaaS v6.2 (LF v3.55.1)..."

# 1. Setup Inicial
cd /var/www/html

if [ ! -f ".env" ]; then
    echo "⚠️ .env não encontrado."
    if [ -f ".env.example" ]; then
        echo "📋 Copiando de .env.example..."
        cp .env.example .env
    else
        echo "❌ .env.example não encontrado. Criando vazio."
        touch .env
    fi
     # Força FILESYSTEM_DISK=public se não estiver definido
    if ! grep -q "FILESYSTEM_DISK" .env; then
        echo "FILESYSTEM_DISK=public" >> .env
    fi
    # IMPORTANTE: Ajustar permissão após criar
    chown www-data:www-data .env
fi

# CRÍTICO: Integridade de Storage
echo "🔧 Ajustando Storage..."
mkdir -p storage/app/public/processos storage/app/public/configuration storage/framework/cache/data storage/framework/views storage/framework/sessions storage/logs
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "🔗 Linking Storage..."
php artisan storage:link

# 3. Otimização
echo "🧹 Limpando caches..."
php artisan optimize:clear
php artisan config:clear

# 4. Assets
echo "🎨 Publicando Assets..."
php artisan vendor:publish --tag=public --force

# 5. Banco de Dados
echo "🗄️ Garantindo a existência do banco de dados..."
php -r '
try {
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $port = getenv("DB_PORT") ?: "3306";
    $user = getenv("DB_USERNAME") ?: "root";
    $pass = getenv("DB_PASSWORD") ?: "";
    $dbname = getenv("DB_DATABASE") ?: "forge";
    
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "✅ Banco de dados `{$dbname}` verificado/criado com sucesso.\n";
} catch (PDOException $e) {
    echo "⚠️ Aviso ao verificar/criar banco: " . $e->getMessage() . "\n";
}
'

if [ "$1" == "apache2-foreground" ] || [ -z "$1" ]; then
    echo "📦 Executing Robust Migration Strategy..."
php artisan migrate --force \
    --path=database/migrations \
    --path=packages/Webkul/Attribute/src/Database/Migrations \
    --path=packages/Webkul/Core/src/Database/Migrations \
    --path=packages/Webkul/User/src/Database/Migrations \
    --path=packages/Webkul/Tag/src/Database/Migrations \
    --path=packages/Webkul/Contact/src/Database/Migrations \
    --path=packages/Webkul/Warehouse/src/Database/Migrations \
    --path=packages/Webkul/Product/src/Database/Migrations \
    --path=packages/Webkul/EmailTemplate/src/Database/Migrations \
    --path=packages/Webkul/Email/src/Database/Migrations \
    --path=packages/Webkul/Lead/src/Database/Migrations \
    --path=packages/Webkul/Quote/src/Database/Migrations \
    --path=packages/Webkul/Activity/src/Database/Migrations \
    --path=packages/Webkul/WebForm/src/Database/Migrations \
    --path=packages/Webkul/Admin/src/Database/Migrations \
    --path=packages/Webkul/Automation/src/Database/Migrations \
    --path=packages/Webkul/Installer/src/Database/Migrations \
    --path=packages/Webkul/DataGrid/src/Database/Migrations \
    --path=packages/Webkul/Marketing/src/Database/Migrations \
    --path=packages/Webkul/DataTransfer/src/Database/Migrations \
    --path=packages/SuiteZap/LawFirm/src/Database/Migrations

# Seed apenas no PRIMEIRO deploy (quando não há dados essenciais)
# O PipelineSeeder é destrutivo (DELETE + INSERT), então não pode rodar em todo boot.
PIPELINE_COUNT=$(php artisan tinker --execute="echo \Illuminate\Support\Facades\DB::table('lead_pipelines')->count();" 2>/dev/null | tail -1)
if [ "$PIPELINE_COUNT" = "0" ] || [ -z "$PIPELINE_COUNT" ]; then
    echo "🌱 Primeiro deploy detectado. Executando seeders..."
    php artisan db:seed --force
fi

# SEMPRE aplica correção pt_BR em TODOS os dados que seeders inserem via trans()
# (sobrescreve qualquer dado inserido pelo seeder com locale incorreto)
echo "🇧🇷 Aplicando tradução pt_BR em todos os dados de seeders..."
php artisan tinker --execute="
    // === PIPELINE & STAGES ===
    \DB::table('lead_pipeline_stages')->where('code','new')->update(['name'=>'Novo']);
    \DB::table('lead_pipeline_stages')->where('code','follow-up')->update(['name'=>'Acompanhamento']);
    \DB::table('lead_pipeline_stages')->where('code','prospect')->update(['name'=>'Qualificado']);
    \DB::table('lead_pipeline_stages')->where('code','negotiation')->update(['name'=>'Negociação']);
    \DB::table('lead_pipeline_stages')->where('code','won')->update(['name'=>'Ganho']);
    \DB::table('lead_pipeline_stages')->where('code','lost')->update(['name'=>'Perdido']);
    \DB::table('lead_pipelines')->where('is_default',1)->update(['name'=>'Funil Padrão']);

    // === ROLES ===
    \DB::table('roles')->where('id',1)->update(['name'=>'Administrador','description'=>'Função de Administrador']);

    // === LEAD SOURCES ===
    \DB::table('lead_sources')->where('id',1)->update(['name'=>'E-mail']);
    \DB::table('lead_sources')->where('id',2)->update(['name'=>'Web']);
    \DB::table('lead_sources')->where('id',3)->update(['name'=>'Formulário Web']);
    \DB::table('lead_sources')->where('id',4)->update(['name'=>'WhatsApp']);
    \DB::table('lead_sources')->where('id',5)->update(['name'=>'Direto']);

    // === LEAD TYPES ===
    \DB::table('lead_types')->where('id',1)->update(['name'=>'Novo Negócio']);
    \DB::table('lead_types')->where('id',2)->update(['name'=>'Negócio Existente']);

    // === WORKFLOWS ===
    \DB::table('workflows')->where('id',1)->update(['name'=>'E-mails para participantes após adicionar atividade','description'=>'E-mails para participantes após adicionar atividade']);
    \DB::table('workflows')->where('id',2)->update(['name'=>'E-mails para participantes após atualização de atividade','description'=>'E-mails para participantes após atualização de atividade']);

    // === EMAIL TEMPLATES ===
    \DB::table('email_templates')->where('id',1)->update(['name'=>'Atividade Adicionada','subject'=>'Atividade Adicionada: {%activities.title%}']);
    \DB::table('email_templates')->where('id',2)->update(['name'=>'Atividade modificada','subject'=>'Atividade modificada: {%activities.title%}']);

    // === ATTRIBUTES (by code + entity_type) ===
    // Leads
    \DB::table('attributes')->where('code','title')->where('entity_type','leads')->update(['name'=>'Título']);
    \DB::table('attributes')->where('code','description')->where('entity_type','leads')->update(['name'=>'Descrição']);
    \DB::table('attributes')->where('code','lead_value')->where('entity_type','leads')->update(['name'=>'Valor da Oportunidade']);
    \DB::table('attributes')->where('code','lead_source_id')->where('entity_type','leads')->update(['name'=>'Origem']);
    \DB::table('attributes')->where('code','lead_type_id')->where('entity_type','leads')->update(['name'=>'Tipo']);
    \DB::table('attributes')->where('code','user_id')->where('entity_type','leads')->update(['name'=>'Responsável pela Venda']);
    \DB::table('attributes')->where('code','expected_close_date')->where('entity_type','leads')->update(['name'=>'Data de Fechamento Esperada']);
    \DB::table('attributes')->where('code','lead_pipeline_id')->where('entity_type','leads')->update(['name'=>'Funil']);
    \DB::table('attributes')->where('code','lead_pipeline_stage_id')->where('entity_type','leads')->update(['name'=>'Estágio']);
    // Persons
    \DB::table('attributes')->where('code','name')->where('entity_type','persons')->update(['name'=>'Nome']);
    \DB::table('attributes')->where('code','emails')->where('entity_type','persons')->update(['name'=>'E-mails']);
    \DB::table('attributes')->where('code','contact_numbers')->where('entity_type','persons')->update(['name'=>'Números de Contato']);
    \DB::table('attributes')->where('code','job_title')->where('entity_type','persons')->update(['name'=>'Cargo']);
    \DB::table('attributes')->where('code','user_id')->where('entity_type','persons')->update(['name'=>'Responsável pela Venda']);
    \DB::table('attributes')->where('code','organization_id')->where('entity_type','persons')->update(['name'=>'Empresa']);
    // Organizations
    \DB::table('attributes')->where('code','name')->where('entity_type','organizations')->update(['name'=>'Nome']);
    \DB::table('attributes')->where('code','address')->where('entity_type','organizations')->update(['name'=>'Endereço']);
    \DB::table('attributes')->where('code','user_id')->where('entity_type','organizations')->update(['name'=>'Responsável pela Venda']);
    // Products
    \DB::table('attributes')->where('code','name')->where('entity_type','products')->update(['name'=>'Nome']);
    \DB::table('attributes')->where('code','description')->where('entity_type','products')->update(['name'=>'Descrição']);
    \DB::table('attributes')->where('code','sku')->where('entity_type','products')->update(['name'=>'Código']);
    \DB::table('attributes')->where('code','quantity')->where('entity_type','products')->update(['name'=>'Quantidade']);
    \DB::table('attributes')->where('code','price')->where('entity_type','products')->update(['name'=>'Preço']);
    // Quotes
    \DB::table('attributes')->where('code','user_id')->where('entity_type','quotes')->update(['name'=>'Responsável pela Venda']);
    \DB::table('attributes')->where('code','subject')->where('entity_type','quotes')->update(['name'=>'Assunto']);
    \DB::table('attributes')->where('code','description')->where('entity_type','quotes')->update(['name'=>'Descrição']);
    \DB::table('attributes')->where('code','billing_address')->where('entity_type','quotes')->update(['name'=>'Endereço de Cobrança']);
    \DB::table('attributes')->where('code','shipping_address')->where('entity_type','quotes')->update(['name'=>'Endereço de Entrega']);
    \DB::table('attributes')->where('code','discount_percent')->where('entity_type','quotes')->update(['name'=>'Percentual de Desconto']);
    \DB::table('attributes')->where('code','discount_amount')->where('entity_type','quotes')->update(['name'=>'Valor do Desconto']);
    \DB::table('attributes')->where('code','tax_amount')->where('entity_type','quotes')->update(['name'=>'Valor do Imposto']);
    \DB::table('attributes')->where('code','adjustment_amount')->where('entity_type','quotes')->update(['name'=>'Valor de Ajuste']);
    \DB::table('attributes')->where('code','sub_total')->where('entity_type','quotes')->update(['name'=>'Subtotal']);
    \DB::table('attributes')->where('code','grand_total')->where('entity_type','quotes')->update(['name'=>'Total Geral']);
    \DB::table('attributes')->where('code','expired_at')->where('entity_type','quotes')->update(['name'=>'Expira em']);
    \DB::table('attributes')->where('code','person_id')->where('entity_type','quotes')->update(['name'=>'Pessoa']);
    // Warehouses
    \DB::table('attributes')->where('code','name')->where('entity_type','warehouses')->update(['name'=>'Nome']);
    \DB::table('attributes')->where('code','description')->where('entity_type','warehouses')->update(['name'=>'Descrição']);
    \DB::table('attributes')->where('code','contact_name')->where('entity_type','warehouses')->update(['name'=>'Nome do Contato']);
    \DB::table('attributes')->where('code','contact_emails')->where('entity_type','warehouses')->update(['name'=>'Emails de Contato']);
    \DB::table('attributes')->where('code','contact_numbers')->where('entity_type','warehouses')->update(['name'=>'Números de Contato']);
    \DB::table('attributes')->where('code','contact_address')->where('entity_type','warehouses')->update(['name'=>'Endereço de Contato']);

    echo 'pt_BR OK';
" 2>/dev/null
fi

# 6. Start Process
# Garante que qualquer log ou arquivo de cache criado pelas migrações/comandos acima (rodados como root)
# pertença ao www-data antes de iniciarmos o processo final.
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ $# -gt 0 ]; then
    if [ "$1" != "apache2-foreground" ]; then
        echo "⚙️ Executando comando em background: $@"
        # Preserva eventuais aspas e escapes de strings complexas (ex: sh -c "while true...")
        CMD_STR=$(printf "%q " "$@")
        # Roda workers e schedulers como www-data para não estourar permissões de root
        exec su -s /bin/sh www-data -c "$CMD_STR"
    else
        echo "🔥 Subindo Apache..."
        exec "$@"
    fi
else
    echo "🔥 Subindo Apache..."
    exec apache2-foreground
fi
