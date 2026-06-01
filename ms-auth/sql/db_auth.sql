-- ==========================================================================
-- Banco: db_auth  (Microsservico ms-auth - porta 8000)
-- Responsavel pela autenticacao e emissao/validacao de tokens JWT.
-- Princípio: Database per Service (isolamento de dados).
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS db_auth
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_auth;

-- --------------------------------------------------------------------------
-- Tabela de usuarios
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(120)        NOT NULL,
    email        VARCHAR(180)        NOT NULL,
    password_hash VARCHAR(255)       NOT NULL,
    role         ENUM('admin','locador','locatario') NOT NULL DEFAULT 'locatario',
    created_at   TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Seed: usuario administrador padrao
-- Senha em texto plano: "admin123"
-- Hash gerado via password_hash('admin123', PASSWORD_BCRYPT).
-- --------------------------------------------------------------------------
INSERT INTO users (name, email, password_hash, role)
VALUES (
    'Administrador',
    'admin@aluguel.dev',
    '$2y$12$urhuh3I3MmYcPnt8j5pnueJNcdXjp3KZQibiiRfYD4m1dXEpFFfva',
    'admin'
)
ON DUPLICATE KEY UPDATE email = email;
