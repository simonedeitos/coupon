-- =============================================================================
-- Migration: 2026_08_21_fixes.sql
-- Descrizione: Aggiornamenti schema per tutte le correzioni del 21/08/2026.
-- Sicuro da rieseguire (idempotente) grazie a IF NOT EXISTS / IF EXISTS.
-- =============================================================================

-- 1. Contatore di popolarità aggregato su offers
-- MySQL non supporta ADD COLUMN IF NOT EXISTS prima di 8.0; usiamo il blocco
-- procedurale per gestire ambienti pre-8.0 in modo sicuro.
SET @col_offers_click_count = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offers' AND COLUMN_NAME = 'click_count'
);
SET @sql = IF(@col_offers_click_count = 0,
    'ALTER TABLE offers ADD COLUMN click_count INT NOT NULL DEFAULT 0 AFTER is_featured',
    'SELECT 1 -- click_count già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indice su click_count per offers
SET @idx_offers = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offers' AND INDEX_NAME = 'idx_offers_click_count'
);
SET @sql = IF(@idx_offers = 0,
    'ALTER TABLE offers ADD INDEX idx_offers_click_count (click_count)',
    'SELECT 2 -- indice già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Contatore di popolarità aggregato su stores
SET @col_stores_click_count = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND COLUMN_NAME = 'click_count'
);
SET @sql = IF(@col_stores_click_count = 0,
    'ALTER TABLE stores ADD COLUMN click_count INT NOT NULL DEFAULT 0 AFTER is_featured',
    'SELECT 3 -- click_count già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_stores = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND INDEX_NAME = 'idx_stores_click_count'
);
SET @sql = IF(@idx_stores = 0,
    'ALTER TABLE stores ADD INDEX idx_stores_click_count (click_count)',
    'SELECT 4 -- indice già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Tipo di sconto esplicito su offers (PERCENT / AMOUNT)
SET @col_discount_type = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'offers' AND COLUMN_NAME = 'discount_type'
);
SET @sql = IF(@col_discount_type = 0,
    "ALTER TABLE offers ADD COLUMN discount_type ENUM('PERCENT','AMOUNT') NULL AFTER badge",
    'SELECT 5 -- discount_type già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Campi negozio da import TradeDoubler (potrebbero già esistere nello schema)
-- description è già presente; logo_path e category_id sono già presenti per schema.sql
-- (inclusi a titolo di sicurezza per ambienti con schema vecchio)
SET @col_stores_logo = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND COLUMN_NAME = 'logo_path'
);
SET @sql = IF(@col_stores_logo = 0,
    'ALTER TABLE stores ADD COLUMN logo_path VARCHAR(255) NULL',
    'SELECT 6 -- logo_path già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_stores_desc = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND COLUMN_NAME = 'description'
);
SET @sql = IF(@col_stores_desc = 0,
    'ALTER TABLE stores ADD COLUMN description TEXT NULL',
    'SELECT 7 -- description già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_stores_cat = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stores' AND COLUMN_NAME = 'category_id'
);
SET @sql = IF(@col_stores_cat = 0,
    'ALTER TABLE stores ADD COLUMN category_id BIGINT UNSIGNED NULL',
    'SELECT 8 -- category_id già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Tabella per tracciare le visite del sito
CREATE TABLE IF NOT EXISTS `page_views` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `url` VARCHAR(500) NOT NULL,
    `referrer` VARCHAR(500) NULL,
    `ip_hash` VARCHAR(64) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_page_views_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Backfill contatori aggregati dai click esistenti
UPDATE offers o
SET click_count = (SELECT COUNT(*) FROM clicks c WHERE c.offer_id = o.id)
WHERE click_count = 0;

UPDATE stores s
SET click_count = (
    SELECT COUNT(*) FROM clicks c
    INNER JOIN offers o ON o.id = c.offer_id
    WHERE o.store_id = s.id
)
WHERE click_count = 0;

-- 7. Valorizzazione discount_type in base ai dati badge già presenti
-- Offerte con valore badge che contiene % -> PERCENT
UPDATE offers
SET discount_type = 'PERCENT'
WHERE discount_type IS NULL AND badge IS NOT NULL AND badge LIKE '%\\%%';

-- Offerte con valore badge che contiene € o EUR -> AMOUNT
UPDATE offers
SET discount_type = 'AMOUNT'
WHERE discount_type IS NULL AND badge IS NOT NULL AND (badge LIKE '%€%' OR badge LIKE '%EUR%' OR badge LIKE '%euro%');

-- 8. Indice su audit_logs per action (potrebbe già esistere)
SET @idx_audit_action = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_logs_action'
);
SET @sql = IF(@idx_audit_action = 0,
    'ALTER TABLE audit_logs ADD INDEX idx_audit_logs_action (action)',
    'SELECT 9 -- indice già presente');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
