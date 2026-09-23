<?php

$sku = 'DK5010-BK';

$product_id = wc_get_product_id_by_sku($sku);

if ($product_id) {
    echo "PRODUCT EXISTS" . PHP_EOL;
    echo "ID: " . $product_id . PHP_EOL;

    $product = wc_get_product($product_id);

    if ($product) {
        echo "NAME: " . $product->get_name() . PHP_EOL;
        echo "SKU: " . $product->get_sku() . PHP_EOL;
    }

    exit;
}

echo "PRODUCT NOT FOUND" . PHP_EOL;
