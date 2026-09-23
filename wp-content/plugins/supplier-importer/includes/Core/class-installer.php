<?php

namespace Supplier_Importer\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Installer
{
    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        /*
         * Таблица импортов.
         */
        $imports_table =
            $wpdb->prefix . 'supplier_imports';

        $sql_imports = "CREATE TABLE {$imports_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            supplier VARCHAR(100) NOT NULL,
            file TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total INT UNSIGNED NOT NULL DEFAULT 0,
            processed INT UNSIGNED NOT NULL DEFAULT 0,
            created INT UNSIGNED NOT NULL DEFAULT 0,
            updated INT UNSIGNED NOT NULL DEFAULT 0,
            skipped INT UNSIGNED NOT NULL DEFAULT 0,
            errors INT UNSIGNED NOT NULL DEFAULT 0,
            offset INT UNSIGNED NOT NULL DEFAULT 0,
            started_at DATETIME NULL,
            finished_at DATETIME NULL,
            created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY supplier (supplier),
            KEY status (status),
            KEY created_by (created_by)
        ) {$charset_collate};";

        dbDelta($sql_imports);

        /*
         * Таблица ошибок импорта.
         */
        $errors_table =
            $wpdb->prefix . 'supplier_import_errors';

        $sql_errors = "CREATE TABLE {$errors_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            import_id BIGINT UNSIGNED NOT NULL,
            csv_row INT UNSIGNED NOT NULL DEFAULT 0,
            sku VARCHAR(255) NOT NULL DEFAULT '',
            error_type VARCHAR(100) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            raw_data LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY import_id (import_id),
            KEY csv_row (csv_row),
            KEY sku (sku),
            KEY error_type (error_type)
        ) {$charset_collate};";

        dbDelta($sql_errors);

        /*
         * Таблица сопоставлений.
         */

        $mappings_table =
            $wpdb->prefix . 'supplier_mappings';

        $sql_mappings = "CREATE TABLE {$mappings_table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    supplier VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    source_value VARCHAR(255) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY supplier_type_source (
        supplier,
        type,
        source_value
    ),

    KEY supplier (supplier),
    KEY type (type),
    KEY target_id (target_id)
) {$charset_collate};";

        dbDelta($sql_mappings);
    }
}
