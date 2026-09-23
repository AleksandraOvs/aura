<?php

use Supplier_Importer\Product\Product_Attributes;

$product = new WC_Product_Simple();

$attributes = [
    'style' => 'современный',
    'armature-color' => 'черный',
    'shade-color' => 'черный',
];

$handler = new Product_Attributes();

$handler->assign(
    $product,
    $attributes
);

$product->save();

echo 'PRODUCT ID: ' . $product->get_id() . PHP_EOL;
echo PHP_EOL;

foreach ($product->get_attributes() as $taxonomy => $attribute) {

    echo 'TAXONOMY: ' . $taxonomy . PHP_EOL;

    echo 'TERM IDS: ' . PHP_EOL;

    print_r(
        $attribute->get_options()
    );

    echo PHP_EOL;
}
