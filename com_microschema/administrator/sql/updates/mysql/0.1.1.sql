CREATE TABLE IF NOT EXISTS `#__microschema_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `context` VARCHAR(100) NOT NULL,
    `item_id` BIGINT UNSIGNED NOT NULL,
    `params` MEDIUMTEXT NOT NULL,
    `state` TINYINT NOT NULL DEFAULT 1,
    `created` DATETIME NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NULL DEFAULT NULL,
    `modified` DATETIME NULL DEFAULT NULL,
    `modified_by` INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_context_item_id` (`context`, `item_id`),
    KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
