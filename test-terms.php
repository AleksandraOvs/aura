<?php

$ids = [101, 202];

foreach ($ids as $id) {

    $term = get_term($id);

    echo "\n";
    echo "ID: " . $id . "\n";

    if (!$term || is_wp_error($term)) {
        echo "TERM NOT FOUND\n";
        continue;
    }

    echo "Name: " . $term->name . "\n";
    echo "Taxonomy: " . $term->taxonomy . "\n";
    echo "Parent: " . $term->parent . "\n";
    echo "Slug: " . $term->slug . "\n";
}
