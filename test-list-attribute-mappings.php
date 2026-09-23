<?php

global $wpdb;

$table = $wpdb->prefix . 'supplier_mappings';

$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT
            id,
            supplier,
            type,
            source_parent,
            source_value,
            target_id
        FROM {$table}
        WHERE supplier = %s
        AND type = %s
        ORDER BY id DESC
        LIMIT 10",
        'denkirs',
        'attribute_value'
    ),
    ARRAY_A
);

print_r($rows);
