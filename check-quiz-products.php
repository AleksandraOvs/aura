<?php

require_once __DIR__ . '/wp-load.php';

echo '<pre>';

$ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'tax_query'      => [
        [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => [5199],
        ],
    ],
]);

echo 'Всего товаров: ' . count($ids) . PHP_EOL . PHP_EOL;

foreach ($ids as $id) {
    $terms = wp_get_object_terms($id, 'pa_тип-лампы', [
        'fields' => 'names',
    ]);

    echo $id . ' | ' . get_the_title($id) . ' | ';

    echo is_wp_error($terms)
        ? $terms->get_error_message()
        : implode(', ', $terms);

    echo PHP_EOL;
}

echo '</pre>';
