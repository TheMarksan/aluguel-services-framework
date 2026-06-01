-- ==========================================================================
-- Banco: db_catalogo  (Microsservico ms-catalogo - porta 8001)
-- Cadastro e consulta de imoveis (casas e apartamentos).
-- Princípio: Database per Service (isolamento de dados).
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS db_catalogo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_catalogo;

-- --------------------------------------------------------------------------
-- Tabela de imoveis
-- owner_id referencia logicamente users.id do db_auth (sem FK fisica,
-- pois cada microsservico possui banco isolado).
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS properties (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_id      BIGINT UNSIGNED NULL,
    title         VARCHAR(160)    NOT NULL,
    description   TEXT            NULL,
    type          ENUM('casa','apartamento') NOT NULL DEFAULT 'apartamento',
    city          VARCHAR(120)    NOT NULL,
    address       VARCHAR(255)    NOT NULL,
    bedrooms      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    bathrooms     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    area_m2       DECIMAL(8,2)    NOT NULL DEFAULT 0,
    daily_price   DECIMAL(10,2)   NOT NULL DEFAULT 0,
    monthly_price DECIMAL(10,2)   NOT NULL DEFAULT 0,
    available     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_properties_city (city),
    KEY idx_properties_type (type),
    KEY idx_properties_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Seed: imoveis de exemplo
-- --------------------------------------------------------------------------
INSERT INTO properties
    (owner_id, title, description, type, city, address, bedrooms, bathrooms, area_m2, daily_price, monthly_price, available)
VALUES
    (1, 'Apartamento Beira-Mar', 'Vista para o mar, mobiliado.', 'apartamento', 'Maceio', 'Av. Alvaro Otacilio, 1000', 2, 2, 78.50, 350.00, 4500.00, 1),
    (1, 'Casa na Praia do Frances', 'Casa ampla com piscina.', 'casa', 'Marechal Deodoro', 'Rua das Conchas, 45', 4, 3, 220.00, 800.00, 9000.00, 1),
    (1, 'Studio Centro', 'Compacto e bem localizado.', 'apartamento', 'Maceio', 'Rua do Comercio, 200', 1, 1, 35.00, 180.00, 2200.00, 1);
