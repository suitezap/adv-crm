#!/usr/bin/env bash
set -Eeuo pipefail

# Capturar o SHA do commit atual (ou usar um genérico se não tiver git)
COMMIT_SHA=$(git rev-parse --short HEAD 2>/dev/null || echo "local")
IMAGE_TAG="suitezap/lawfirm:candidate-${COMMIT_SHA}"

cleanup() {
    local status=$?
    echo "🧹 Finalizando contêineres e limpando volumes de teste E2E..."
    if [ "$status" -ne 0 ]; then
        echo "❌ Falha detectada (Exit Code: $status). Coletando logs de diagnóstico..."
        mkdir -p reports/e2e
        docker compose -f docker-compose.test.yml logs --no-color > reports/e2e/docker-compose-failure.log || true
    fi
    docker compose -f docker-compose.test.yml down -v || true
    exit $status
}
trap cleanup EXIT

echo "🚀 Construindo a imagem candidata: ${IMAGE_TAG} (sendo taggeada como candidate-local para o compose)"
docker build -t suitezap/lawfirm:candidate-local -t ${IMAGE_TAG} .

echo "🚀 Subindo serviços de infraestrutura e aplicação com espera bloqueante..."
# Perfil E2E requer todos os serviços. O mock-server já subirá por dependência.
# O compose cuidará da ordem. Subiremos até worker-tenant-b no modo daemon (--wait).
docker compose -f docker-compose.test.yml --profile e2e up -d --wait --wait-timeout 120 app-tenant-a app-tenant-b worker-tenant-a worker-tenant-b

echo "🧪 Executando suíte Playwright Python no contêiner playwright-test..."
docker compose -f docker-compose.test.yml --profile e2e run --rm playwright-test pytest "$@"
