-- =============================================================================
-- Fix Pack Migration — 2026-08-20
-- Eseguire una sola volta sull'ambiente di produzione.
-- MySQL non supporta "ADD COLUMN IF NOT EXISTS" in modo nativo (prima di 8.0),
-- quindi ogni ALTER è preceduto da una verifica su INFORMATION_SCHEMA tramite
-- stored procedure temporanee per garantire idempotenza.
-- =============================================================================

-- Helper: stored procedure per aggiungere una colonna solo se non esiste già.
DROP PROCEDURE IF EXISTS add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE add_column_if_missing(
    IN tbl VARCHAR(64),
    IN col VARCHAR(64),
    IN col_def TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tbl
          AND COLUMN_NAME  = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', col_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Helper: stored procedure per aggiungere un index solo se non esiste già.
DROP PROCEDURE IF EXISTS add_index_if_missing;
DELIMITER $$
CREATE PROCEDURE add_index_if_missing(
    IN tbl       VARCHAR(64),
    IN idx_name  VARCHAR(64),
    IN idx_def   TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tbl
          AND INDEX_NAME   = idx_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD ', idx_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- =============================================================================
-- 1. Tabella `offers`: aggiunge click_count e discount_type
-- =============================================================================

-- 1a. click_count: contatore aggregato dei click sull'offerta (incrementato da AnalyticsService).
CALL add_column_if_missing(
    'offers',
    'click_count',
    '`click_count` INT NOT NULL DEFAULT 0 AFTER `is_featured`'
);

-- 1b. discount_type: distingue sconto percentuale (PERCENT) da importo fisso (AMOUNT).
--     Risolve il bug per cui uno sconto di 100€ veniva mostrato come "100%".
CALL add_column_if_missing(
    'offers',
    'discount_type',
    '`discount_type` ENUM(''PERCENT'',''AMOUNT'') NOT NULL DEFAULT ''PERCENT'' AFTER `badge`'
);

-- 1c. Index su click_count per ordinamento rapido per popolarità.
CALL add_index_if_missing(
    'offers',
    'idx_offers_click_count',
    'INDEX `idx_offers_click_count` (`click_count` DESC)'
);

-- =============================================================================
-- 2. Tabella `stores`: aggiunge click_count
-- =============================================================================

-- 2a. click_count: contatore aggregato dei click sullo store.
CALL add_column_if_missing(
    'stores',
    'click_count',
    '`click_count` INT NOT NULL DEFAULT 0 AFTER `is_featured`'
);

-- 2b. Index su click_count.
CALL add_index_if_missing(
    'stores',
    'idx_stores_click_count',
    'INDEX `idx_stores_click_count` (`click_count` DESC)'
);

-- =============================================================================
-- 3. Tabella `affiliate_mappings`: aggiunge raw_payload se mancante
--    (usato da TradeDoublerImportService per salvare il payload JSON originale)
-- =============================================================================
CALL add_column_if_missing(
    'affiliate_mappings',
    'raw_payload',
    '`raw_payload` JSON NULL AFTER `external_hash`'
);

-- =============================================================================
-- 4. Tabella `import_logs`: aggiunge network_name se mancante
-- =============================================================================
CALL add_column_if_missing(
    'import_logs',
    'network_name',
    '`network_name` VARCHAR(120) NULL AFTER `network_id`'
);

-- =============================================================================
-- 5. Backfill click_count su offers da dati esistenti nella tabella clicks
-- =============================================================================
UPDATE offers o
SET    o.click_count = (
           SELECT COUNT(*) FROM clicks c WHERE c.offer_id = o.id
       )
WHERE  o.click_count = 0;

-- 5b. Backfill click_count su stores
UPDATE stores s
SET    s.click_count = (
           SELECT COUNT(*) FROM clicks c WHERE c.store_id = s.id
       )
WHERE  s.click_count = 0;

-- =============================================================================
-- 6. NOTA SUL BUG discount_type (offerte esistenti):
--    NON correggiamo automaticamente i dati storici perché non è possibile
--    determinare con certezza il tipo originale solo dal valore numerico
--    (es. un badge "100" potrebbe essere 100% di sconto su beni digitali
--    oppure 100€ su beni fisici).
--
--    Euristico opzionale (COMMENTATO — valutare con il team prima di eseguire):
--    Le offerte con badge numerico > 90 che provengono da feed TradeDoubler
--    sono quasi certamente importi fissi (€), non percentuali.
--    Decommentare e adattare dopo verifica manuale di un campione:
-- =============================================================================
/*
UPDATE offers
SET    discount_type = 'AMOUNT'
WHERE  discount_type = 'PERCENT'
  AND  badge REGEXP '^[0-9]+(\.[0-9]+)?$'
  AND  CAST(badge AS DECIMAL(10,2)) > 90
  AND  external_id IS NOT NULL;  -- solo offerte importate da feed affiliazione
*/

-- =============================================================================
-- 7. Pulizia procedure temporanee
-- =============================================================================
DROP PROCEDURE IF EXISTS add_column_if_missing;
DROP PROCEDURE IF EXISTS add_index_if_missing;

-- Fine migration 2026_08_20_fix_pack.sql
