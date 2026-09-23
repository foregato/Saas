-- =====================================================================
-- MEI SaaS — Painel de Controle da Empresa
-- Schema MySQL 8+ / compatível com phpMyAdmin (Hostinger)
-- Charset: utf8mb4
-- Fase 1: estrutura completa (algumas tabelas serão preenchidas nas
-- fases seguintes, mas o schema já é criado por completo aqui).
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE DATABASE IF NOT EXISTS mei_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mei_saas;

-- ---------------------------------------------------------------------
-- TENANTS / EMPRESAS
-- ---------------------------------------------------------------------
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cnpj VARCHAR(18) NOT NULL,
    razao_social VARCHAR(255) NOT NULL,
    nome_fantasia VARCHAR(255) DEFAULT NULL,
    cnae VARCHAR(20) DEFAULT NULL,
    atividade_principal VARCHAR(255) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    cidade VARCHAR(120) DEFAULT NULL,
    estado CHAR(2) DEFAULT NULL,
    cep VARCHAR(10) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    onboarding_completo TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_companies_cnpj (cnpj)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner','admin','member') NOT NULL DEFAULT 'owner',
    is_platform_admin TINYINT(1) NOT NULL DEFAULT 0,
    email_verified_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_tenant (tenant_id),
    CONSTRAINT fk_users_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tokens de acesso da API (bearer tokens), controle de sessões
CREATE TABLE auth_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_auth_tokens_hash (token_hash),
    KEY idx_auth_tokens_user (user_id),
    CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Recuperação de senha
CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pwreset_user (user_id),
    CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Rate limiting simples de login
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_lookup (email, ip_address, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLIENTES / FORNECEDORES
-- ---------------------------------------------------------------------
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    documento VARCHAR(20) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customers_tenant (tenant_id),
    CONSTRAINT fk_customers_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    documento VARCHAR(20) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_suppliers_tenant (tenant_id),
    CONSTRAINT fk_suppliers_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUTOS / SERVIÇOS
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    sku VARCHAR(60) DEFAULT NULL,
    categoria VARCHAR(100) DEFAULT NULL,
    custo DECIMAL(12,2) NOT NULL DEFAULT 0,
    preco DECIMAL(12,2) NOT NULL DEFAULT 0,
    estoque INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 0,
    unidade VARCHAR(20) DEFAULT 'un',
    descricao TEXT DEFAULT NULL,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_products_tenant (tenant_id),
    CONSTRAINT fk_products_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    preco DECIMAL(12,2) NOT NULL DEFAULT 0,
    duracao_minutos INT DEFAULT NULL,
    categoria VARCHAR(100) DEFAULT NULL,
    descricao TEXT DEFAULT NULL,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_services_tenant (tenant_id),
    CONSTRAINT fk_services_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VENDAS
-- ---------------------------------------------------------------------
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED DEFAULT NULL,
    data DATE NOT NULL,
    valor_bruto DECIMAL(12,2) NOT NULL DEFAULT 0,
    desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
    valor_final DECIMAL(12,2) NOT NULL DEFAULT 0,
    forma_pagamento VARCHAR(60) DEFAULT NULL,
    status ENUM('concluida','pendente','cancelada') NOT NULL DEFAULT 'concluida',
    observacao TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sales_tenant (tenant_id),
    KEY idx_sales_customer (customer_id),
    CONSTRAINT fk_sales_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED DEFAULT NULL,
    service_id BIGINT UNSIGNED DEFAULT NULL,
    descricao VARCHAR(190) NOT NULL,
    quantidade DECIMAL(12,2) NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sale_items_tenant (tenant_id),
    KEY idx_sale_items_sale (sale_id),
    CONSTRAINT fk_saleitems_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_saleitems_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_saleitems_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FINANCEIRO
-- ---------------------------------------------------------------------
CREATE TABLE financial_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('entrada','saida') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_fincat_tenant (tenant_id),
    CONSTRAINT fk_fincat_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE financial_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('entrada','saida') NOT NULL,
    descricao VARCHAR(190) NOT NULL,
    valor DECIMAL(12,2) NOT NULL,
    data DATE NOT NULL,
    category_id BIGINT UNSIGNED DEFAULT NULL,
    customer_id BIGINT UNSIGNED DEFAULT NULL,
    supplier_id BIGINT UNSIGNED DEFAULT NULL,
    forma_pagamento VARCHAR(60) DEFAULT NULL,
    status ENUM('recebido','pago','pendente','atrasado','cancelado') NOT NULL DEFAULT 'pendente',
    recorrencia ENUM('nenhuma','mensal','semanal','anual') NOT NULL DEFAULT 'nenhuma',
    observacao TEXT DEFAULT NULL,
    sale_id BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_finentries_tenant (tenant_id),
    KEY idx_finentries_data (data),
    KEY idx_finentries_status (status),
    CONSTRAINT fk_finentries_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_finentries_category FOREIGN KEY (category_id) REFERENCES financial_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_finentries_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_finentries_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    CONSTRAINT fk_finentries_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Visão dedicada de contas a receber/pagar (referenciam financial_entries)
CREATE TABLE accounts_receivable (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    financial_entry_id BIGINT UNSIGNED NOT NULL,
    vencimento DATE NOT NULL,
    pago_em DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ar_tenant (tenant_id),
    CONSTRAINT fk_ar_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_ar_entry FOREIGN KEY (financial_entry_id) REFERENCES financial_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE accounts_payable (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    financial_entry_id BIGINT UNSIGNED NOT NULL,
    vencimento DATE NOT NULL,
    pago_em DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ap_tenant (tenant_id),
    CONSTRAINT fk_ap_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_ap_entry FOREIGN KEY (financial_entry_id) REFERENCES financial_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DOCUMENTOS
-- ---------------------------------------------------------------------
CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    categoria ENUM('cnpj','ccmei','fiscal','contrato','comprovante','certificado','outro') NOT NULL DEFAULT 'outro',
    arquivo_path VARCHAR(500) NOT NULL,
    arquivo_mime VARCHAR(120) NOT NULL,
    arquivo_tamanho INT UNSIGNED NOT NULL,
    data_emissao DATE DEFAULT NULL,
    validade DATE DEFAULT NULL,
    observacao TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_documents_tenant (tenant_id),
    KEY idx_documents_validade (validade),
    CONSTRAINT fk_documents_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- OBRIGAÇÕES DO MEI
-- ---------------------------------------------------------------------
CREATE TABLE obligations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    tipo VARCHAR(60) DEFAULT NULL,
    vencimento DATE NOT NULL,
    paga TINYINT(1) NOT NULL DEFAULT 0,
    pago_em DATE DEFAULT NULL,
    comprovante_document_id BIGINT UNSIGNED DEFAULT NULL,
    observacao TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_obligations_tenant (tenant_id),
    KEY idx_obligations_vencimento (vencimento),
    CONSTRAINT fk_obligations_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_obligations_document FOREIGN KEY (comprovante_document_id) REFERENCES documents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de configuração de limites/regras oficiais (não fixado no código)
CREATE TABLE tax_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(80) NOT NULL,
    valor DECIMAL(14,2) NOT NULL,
    periodo_referencia VARCHAR(20) NOT NULL,
    fonte VARCHAR(255) DEFAULT NULL,
    atualizado_em DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tax_rules_chave_periodo (chave, periodo_referencia)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- AGENDA
-- ---------------------------------------------------------------------
CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED DEFAULT NULL,
    titulo VARCHAR(190) NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL,
    duracao_minutos INT DEFAULT 30,
    observacao TEXT DEFAULT NULL,
    status ENUM('agendado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_appointments_tenant (tenant_id),
    KEY idx_appointments_data (data),
    CONSTRAINT fk_appointments_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- NOTIFICAÇÕES
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    tipo VARCHAR(60) NOT NULL,
    titulo VARCHAR(190) NOT NULL,
    mensagem TEXT NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_tenant (tenant_id),
    KEY idx_notifications_lida (lida),
    CONSTRAINT fk_notifications_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONFIGURAÇÕES
-- ---------------------------------------------------------------------
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    chave VARCHAR(80) NOT NULL,
    valor TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_tenant_chave (tenant_id, chave),
    CONSTRAINT fk_settings_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ASSINATURAS
-- ---------------------------------------------------------------------
CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo ENUM('free','pro','premium') NOT NULL,
    nome VARCHAR(80) NOT NULL,
    limite_produtos INT DEFAULT NULL,
    limite_usuarios INT DEFAULT NULL,
    limite_documentos INT DEFAULT NULL,
    preco_mensal DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plans_codigo (codigo)
) ENGINE=InnoDB;

CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('trial','ativa','cancelada','expirada','inadimplente') NOT NULL DEFAULT 'trial',
    inicio DATE NOT NULL,
    renovacao DATE DEFAULT NULL,
    cancelamento DATE DEFAULT NULL,
    gateway VARCHAR(60) DEFAULT NULL,
    gateway_reference VARCHAR(190) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_subscriptions_tenant (tenant_id),
    CONSTRAINT fk_subscriptions_company FOREIGN KEY (tenant_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- LOGS ADMINISTRATIVOS
-- ---------------------------------------------------------------------
CREATE TABLE admin_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(190) NOT NULL,
    alvo_tipo VARCHAR(60) DEFAULT NULL,
    alvo_id BIGINT UNSIGNED DEFAULT NULL,
    detalhes TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_adminlogs_admin (admin_user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SEED: planos padrão
-- ---------------------------------------------------------------------
INSERT INTO plans (codigo, nome, limite_produtos, limite_usuarios, limite_documentos, preco_mensal) VALUES
('free', 'Gratuito', 20, 1, 10, 0.00),
('pro', 'Pro', 200, 3, 100, 0.00),
('premium', 'Premium', NULL, 10, NULL, 0.00);
