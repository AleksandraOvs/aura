<?php

$sku = 'DK5010-BK';

$product_id = wc_get_product_id_by_sku($sku);

if (!$product_id) {
    echo "PRODUCT NOT FOUND\n";
    exit;
}

$product = wc_get_product($product_id);

echo "PRODUCT ID: {$product_id}\n";
echo "SKU: {$product->get_sku()}\n";
echo "NAME: {$product->get_name()}\n\n";

$categories = $product->get_category_ids();

echo "CATEGORY IDS:\n";

foreach ($categories as $category_id) {
    $term = get_term($category_id, 'product_cat');

    if (!$term || is_wp_error($term)) {
        echo "- {$category_id}: NOT FOUND\n";
        continue;
    }

    echo "- {$category_id}: {$term->name}\n";
}
