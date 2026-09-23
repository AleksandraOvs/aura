<?php

use Supplier_Importer\Product\Product_Attributes;

$product = new WC_Product_Simple();

$attributes = [
    'style' => 'современный',
    'armature-color' => 'черный',
    'shade-color' => 'черный',
];

$attribute_manager = new Product_Attributes();

$attribute_manager->assign(
    $product,
    $attributes
);

$product_attributes = $product->get_attributes();

foreach ($product_attributes as $taxonomy => $attribute) {
    echo "\n";
    echo "TAXONOMY: {$taxonomy}\n";
    echo "ID: " . $attribute->get_id() . "\n";
    echo "OPTIONS:\n";

    print_r(
        $attribute->get_options()
    );
}
