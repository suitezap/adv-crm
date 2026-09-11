$ErrorActionPreference = "Stop"

# Garantir que a rede externa existe (idempotente)
# Necessário para evitar bug de ID stale de rede no Docker Desktop Windows.
# A rede é criada uma única vez e nunca recriada pelo compose down/up.
$netExists = docker network ls --filter "name=^quality_internal$" --format "{{.Name}}" 2>$null
if ($netExists -ne "quality_internal") {
    Write-Host "🔧 Criando rede externa quality_internal..."
    docker network create --driver bridge --internal quality_internal
} else {
    Write-Host "✅ Rede quality_internal já existe."
}

# Get a short commit sha or default to 'local'
try {
    $commitSha = git rev-parse --short HEAD 2>$null
    if (-not $commitSha) { $commitSha = "local" }
} catch {
    $commitSha = "local"
}
$imageTag = "suitezap/lawfirm:candidate-$commitSha"

Write-Host "🚀 Construindo a imagem candidata: $imageTag (sendo taggeada como candidate-local para o compose)"
docker build -t suitezap/lawfirm:candidate-local -t $imageTag .

Write-Host "🚀 Subindo serviços de infraestrutura e aplicação com espera bloqueante..."
docker compose -f docker-compose.test.yml --profile e2e up -d --wait --wait-timeout 120 app-tenant-a app-tenant-b worker-tenant-a worker-tenant-b

Write-Host "🧪 Executando suíte Playwright Python no contêiner playwright-test..."
try {
    docker compose -f docker-compose.test.yml --profile e2e run --rm --build playwright-test pytest $args
    $exitCode = $LASTEXITCODE
} catch {
    $exitCode = 1
}

Write-Host "🧹 Finalizando contêineres e limpando volumes de teste E2E..."
if ($exitCode -ne 0) {
    Write-Host "❌ Falha detectada (Exit Code: $exitCode). Coletando logs de diagnóstico..." -ForegroundColor Red
    New-Item -ItemType Directory -Force -Path reports/e2e | Out-Null
    docker compose -f docker-compose.test.yml --profile e2e logs --no-color > reports/e2e/docker-compose-failure.log
}

docker compose -f docker-compose.test.yml down -v

exit $exitCode
