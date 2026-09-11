#!/usr/bin/env bash
# ============================================================
# run-backend-tests.sh — Script seguro de execução Pest
# LawFirm CRM / SuiteZap — Etapa 2
#
# USO:
#   bash quality/scripts/run-backend-tests.sh [args do pest]
#
# COMPORTAMENTO:
#   1. Sobe mysql-test, mothership-db-test, redis-test com
#      espera bloqueante por healthchecks (--wait --wait-timeout 120)
#   2. Executa composer install dentro do contêiner php-tests
#      (respeita composer.lock, nunca usa vendor do Windows)
#   3. Executa composer check-platform-reqs
#   4. Executa a suíte Pest
#   5. cleanup() via trap EXIT:
#      - captura o exit code real
#      - em falha: coleta logs para reports/backend/
#      - executa docker compose down -v
#      - retorna o exit code original
# ============================================================
set -Eeuo pipefail

COMPOSE_FILE="docker-compose.test.yml"
REPORT_DIR="reports/backend"

cleanup() {
    local status=$?
    echo ""
    echo "🧹 Finalizando contêineres e limpando volumes de teste..."
    if [ "$status" -ne 0 ]; then
        echo "❌ Falha detectada (Exit Code: $status). Coletando logs de diagnóstico..."
        mkdir -p "${REPORT_DIR}"
        docker compose -f "${COMPOSE_FILE}" logs --no-color \
            > "${REPORT_DIR}/docker-compose-failure.log" 2>&1 || true
        echo "📁 Logs salvos em: ${REPORT_DIR}/docker-compose-failure.log"
    fi
    docker compose -f "${COMPOSE_FILE}" down -v || true
    exit $status
}
trap cleanup EXIT

echo "============================================================"
echo "🚀 LawFirm CRM — Backend Test Suite (Etapa 2)"
echo "============================================================"

# ─── Pré-verificação de segurança ───────────────────────────
if [ "${APP_ENV:-}" = "production" ]; then
    echo "🚨 ABORTANDO: APP_ENV=production detectado. Testes isolados não rodam em produção."
    exit 1
fi

echo ""
echo "📦 Subindo serviços de infraestrutura com healthchecks..."
docker compose -f "${COMPOSE_FILE}" up -d --wait --wait-timeout 120 \
    mysql-test mothership-db-test redis-test

echo ""
echo "🔧 Instalando dependências PHP dentro do contêiner Linux..."
docker compose -f "${COMPOSE_FILE}" run --rm \
    -e COMPOSER_CACHE_DIR=/tmp/.composer-cache \
    php-tests \
    composer install --no-interaction --prefer-dist

echo ""
echo "🔍 Verificando paridade de plataforma PHP (composer check-platform-reqs)..."
docker compose -f "${COMPOSE_FILE}" run --rm php-tests \
    composer check-platform-reqs

echo ""
echo "⚙️  Preparando banco de dados de testes (migrate & seed)..."
docker compose -f "${COMPOSE_FILE}" run --rm php-tests \
    php artisan migrate:fresh --seed --env=testing

echo ""
echo "🧪 Executando suíte Pest no contêiner php-tests..."
mkdir -p "${REPORT_DIR}"
docker compose -f "${COMPOSE_FILE}" run --rm php-tests \
    vendor/bin/pest "$@"

echo ""
echo "✅ Suíte Pest executada com sucesso!"
