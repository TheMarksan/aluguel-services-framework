-- ==========================================================================
-- Banco: db_reservas  (Microsservico ms-reservas - porta 8002)
-- Engine do framework: persiste reservas geradas pelo Template Method.
-- Princípio: Database per Service (isolamento de dados).
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS db_reservas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_reservas;

-- --------------------------------------------------------------------------
-- Tabela de reservas
-- property_id e user_id referenciam logicamente outros bancos (sem FK fisica).
-- modality registra qual modalidade do framework gerou a reserva
-- (ex.: 'vacation', 'long_term'), evidenciando o reuso da RentEngine.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id  BIGINT UNSIGNED NOT NULL,
    user_id      BIGINT UNSIGNED NULL,
    modality     VARCHAR(40)     NOT NULL,
    check_in     DATE            NOT NULL,
    check_out    DATE            NOT NULL,
    nights       INT UNSIGNED    NOT NULL DEFAULT 0,
    total_price  DECIMAL(10,2)   NOT NULL DEFAULT 0,
    status       ENUM('pending','confirmed','rejected','cancelled') NOT NULL DEFAULT 'pending',
    notes        VARCHAR(255)    NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reservations_property (property_id),
    KEY idx_reservations_modality (modality),
    KEY idx_reservations_dates (check_in, check_out)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
