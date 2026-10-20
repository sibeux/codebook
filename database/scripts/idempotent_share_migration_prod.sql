-- =============================================================================
-- IDEMPOTENT SQL SCRIPT FOR PRODUCTION DATABASE (MySQL / MariaDB)
-- Migration: 2026_10_20_160933_add_is_public_and_share_token_to_notes_table
-- Feature: Fitur Share & Visibilitas Catatan (Public / Private)
-- =============================================================================

-- 1. Helper Stored Procedure: Add Column If Not Exists
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DELIMITER $$
CREATE PROCEDURE add_column_if_not_exists(
    IN target_table VARCHAR(64),
    IN target_column VARCHAR(64),
    IN column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = target_table
          AND column_name = target_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', target_table, '` ADD COLUMN `', target_column, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

-- 2. Helper Stored Procedure: Add Unique Key If Not Exists
DROP PROCEDURE IF EXISTS add_unique_key_if_not_exists;
DELIMITER $$
CREATE PROCEDURE add_unique_key_if_not_exists(
    IN target_table VARCHAR(64),
    IN index_name VARCHAR(64),
    IN index_column VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = target_table
          AND index_name = index_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', target_table, '` ADD UNIQUE KEY `', index_name, '` (`', index_column, '`)');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$
DELIMITER ;

-- 3. Tambahkan kolom `is_public` (default 0 / false) jika belum ada
CALL add_column_if_not_exists('notes', 'is_public', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_pinned`');

-- 4. Tambahkan kolom `share_token` jika belum ada
CALL add_column_if_not_exists('notes', 'share_token', 'VARCHAR(64) NULL AFTER `is_public`');

-- 5. Backfill token acak 32-karakter unik untuk catatan yang belum memiliki share_token
UPDATE `notes`
SET `share_token` = MD5(CONCAT(id, UUID(), RAND(), NOW()))
WHERE `share_token` IS NULL OR `share_token` = '';

-- 6. Tambahkan Unique Index pada kolom `share_token` jika belum ada
CALL add_unique_key_if_not_exists('notes', 'notes_share_token_unique', 'share_token');

-- 7. Sinkronkan dengan tabel `migrations` Laravel agar tidak bentrok jika artisan migrate dijalankan
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_20_160933_add_is_public_and_share_token_to_notes_table', (COALESCE(MAX(`batch`), 0) + 1)
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1 
    FROM `migrations` 
    WHERE `migration` = '2026_10_20_160933_add_is_public_and_share_token_to_notes_table'
);

-- 8. Bersihkan Stored Procedure Sementara
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_unique_key_if_not_exists;

-- =============================================================================
-- SELESAI: Script dapat dijalankan berulang kali secara aman (idempotent).
-- =============================================================================
