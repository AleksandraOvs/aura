<?php

$term_id = 27;

$term = get_term($term_id, 'product_cat');

echo "CATEGORY: {$term->name}\n";
echo "TERM ID: {$term_id}\n";
echo "COUNT FROM TERM: {$term->count}\n\n";

$product_ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'tax_query'      => [
        [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => [$term_id],
        ],
    ],
]);

echo "PRODUCTS FOUND: " . count($product_ids) . "\n\n";

foreach ($product_ids as $product_id) {
    $product = wc_get_product($product_id);

    if (!$product) {
        continue;
    }

    echo $product_id
        . ' | '
        . $product->get_sku()
        . ' | '
        . $product->get_name()
        . PHP_EOL;
}
