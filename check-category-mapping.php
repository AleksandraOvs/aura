<?php

global $wpdb;

$table = $wpdb->prefix . 'supplier_mappings';

$rows = $wpdb->get_results(
    "SELECT *
     FROM {$table}
     WHERE supplier = 'denkirs'
     AND type = 'category'
     ORDER BY id DESC",
    ARRAY_A
);

foreach ($rows as $row) {
    echo PHP_EOL;
    echo 'ID: ' . $row['id'] . PHP_EOL;
    echo 'SOURCE: ' . $row['source_value'] . PHP_EOL;
    echo 'TARGET ID: ' . $row['target_id'] . PHP_EOL;

    $term = get_term(
        (int) $row['target_id'],
        'product_cat'
    );

    if ($term && !is_wp_error($term)) {
        echo 'TARGET NAME: ' . $term->name . PHP_EOL;
    } else {
        echo 'TARGET TERM: NOT FOUND' . PHP_EOL;
    }
}
