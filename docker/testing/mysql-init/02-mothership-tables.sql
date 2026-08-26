-- MOCK SCHEMA FOR MOTHERSHIP DB (Used in isolated testing)

CREATE DATABASE IF NOT EXISTS mothership_test
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mothership_test;

CREATE TABLE IF NOT EXISTS tenants (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    n8n_node_id BIGINT UNSIGNED NULL,
    asaas_node_id VARCHAR(255) NULL,
    chatwoot_channel_inbox_id VARCHAR(255) NULL,
    storage_node_id VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(255) NOT NULL,
    plan_name VARCHAR(255) NOT NULL DEFAULT 'default',
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    current_usage_bytes BIGINT DEFAULT 0,
    suitecoin_balance DECIMAL(20,4) NOT NULL DEFAULT 0.0000,
    active_modules JSON NULL,
    expires_at DATE NULL,
    max_users INT NULL,
    storage_limit_gb INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS app_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `type` VARCHAR(50) NULL,
    `group` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS lawfirm_assistant_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    category VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    prompt_structure LONGTEXT NOT NULL,
    n8n_webhook_url VARCHAR(255) NULL,
    required_module VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS tenant_billing_infos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS infrastructure_nodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'n8n',
    base_url VARCHAR(255) NULL,
    api_key VARCHAR(255) NULL,
    capacity_limit INT NULL,
    current_load INT DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    meta_data JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

GRANT ALL PRIVILEGES ON mothership_test.* TO 'test_user'@'%';
FLUSH PRIVILEGES;
