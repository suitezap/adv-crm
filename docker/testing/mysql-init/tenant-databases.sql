-- ============================================================
-- tenant-databases.sql — Inicialização dos bancos de teste
-- LawFirm CRM / SuiteZap — Etapa 2
--
-- Executado automaticamente pelo MySQL na primeira inicialização.
-- Cria e permissiona tenant_a_test e tenant_b_test para test_user.
-- mothership_test é criado pelo próprio serviço mothership-db-test
-- via MYSQL_DATABASE.
-- ============================================================

CREATE DATABASE IF NOT EXISTS tenant_a_test
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS tenant_b_test
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Permissões do test_user (criado via MYSQL_USER/MYSQL_PASSWORD)
GRANT ALL PRIVILEGES ON tenant_a_test.* TO 'test_user'@'%';
GRANT ALL PRIVILEGES ON tenant_b_test.* TO 'test_user'@'%';

FLUSH PRIVILEGES;
