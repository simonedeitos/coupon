-- Migration one-shot: eseguire una sola volta su schema non ancora aggiornato.
-- Se alcune colonne esistono già, applicare manualmente solo le istruzioni mancanti.

ALTER TABLE offers 
    ADD COLUMN discount_type ENUM('PERCENT','AMOUNT','NONE') NOT NULL DEFAULT 'NONE' AFTER coupon_code,
    ADD COLUMN discount_value DECIMAL(10,2) NULL AFTER discount_type,
    ADD COLUMN click_count INT NOT NULL DEFAULT 0 AFTER is_featured,
    ADD INDEX idx_offers_click_count (click_count);

ALTER TABLE stores
    ADD COLUMN click_count INT NOT NULL DEFAULT 0 AFTER is_featured,
    ADD INDEX idx_stores_click_count (click_count);

ALTER TABLE clicks
    ADD COLUMN session_id CHAR(64) NULL AFTER offer_id,
    ADD COLUMN ip_address VARBINARY(16) NULL AFTER referer,
    ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address;

CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(255) NOT NULL,
    referer VARCHAR(255) NULL,
    session_id CHAR(64) NULL,
    ip_address VARBINARY(16) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_views_created (created_at),
    INDEX idx_page_views_path (path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
