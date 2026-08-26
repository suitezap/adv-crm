# 🐳 Runbook: Execução da Suíte Completa via Docker (run-tests-docker.md)

Este guia orienta a execução da infraestrutura completa de testes via Docker Compose em rede interna isolada.

---

## 1. Subir Ambiente de Testes

```bash
# 1. Construir imagem candidata da aplicação
docker build -t suitezap/lawfirm:candidate-local .

# 2. Subir os 8 serviços da suíte em rede interna isolada
docker compose -f docker-compose.test.yml up -d mysql-test mothership-db-test redis-test mock-server app-tenant-a app-tenant-b worker-tenant-a worker-tenant-b

# 3. Verificar prontidão e logs
docker compose -f docker-compose.test.yml ps
```

---

## 2. Executar Suíte E2E Playwright

```bash
# Executar o contêiner Playwright Python
docker compose -f docker-compose.test.yml run --rm playwright

# Desligar o ambiente após a execução
docker compose -f docker-compose.test.yml down -v
```
