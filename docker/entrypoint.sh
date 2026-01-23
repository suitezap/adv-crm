#!/bin/bash
set -e

echo "🚀 Iniciando LawFirm SaaS v6.1 (Storage Fix)..."

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
mkdir -p storage/app/public/processos storage/app/public/configuration
chown -R www-data:www-data storage/app/public bootstrap/cache
chmod -R 775 storage/app/public

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
    --path=packages/Webkul/Lead/src/Database/Migrations \
    --path=packages/Webkul/Quote/src/Database/Migrations \
    --path=packages/Webkul/Activity/src/Database/Migrations \
    --path=packages/Webkul/Mail/src/Database/Migrations \
    --path=packages/Webkul/WebForm/src/Database/Migrations \
    --path=packages/Webkul/Admin/src/Database/Migrations \
    --path=packages/Webkul/Automation/src/Database/Migrations \
    --path=packages/Webkul/Installer/src/Database/Migrations \
    --path=packages/SuiteZap/LawFirm/src/Database/Migrations

# Executa o seed apenas se necessário (lógica de idempotência recomendada)
php artisan db:seed --force

# 6. Start Apache
echo "🔥 Subindo Apache..."
exec apache2-foreground
