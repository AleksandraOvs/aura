<?php

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$rows = $wpdb->get_results(
    "SELECT id, supplier, total, processed, created, updated, skipped, errors, status
     FROM {$wpdb->prefix}supplier_imports
     ORDER BY id DESC
     LIMIT 5",
    ARRAY_A
);

print_r($rows);
