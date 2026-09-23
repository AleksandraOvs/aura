<?php

global $wpdb;

$table = $wpdb->prefix . 'supplier_import_errors';

$charset = $wpdb->get_charset_collate();

$sql = "CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    import_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL DEFAULT 0,
    sku VARCHAR(255) NOT NULL DEFAULT '',
    error_type VARCHAR(100) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    raw_data LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY import_id (import_id),
    KEY row_number (row_number),
    KEY sku (sku),
    KEY error_type (error_type)
) {$charset};";

$result = $wpdb->query($sql);

echo 'RESULT: ';
var_dump($result);

echo 'ERROR: ';
var_dump($wpdb->last_error);

echo 'TABLE: ';
var_dump(
    $wpdb->get_var(
        "SHOW TABLES LIKE '{$table}'"
    )
);
