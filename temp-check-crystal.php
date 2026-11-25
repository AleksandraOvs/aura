<?php

$products = wc_get_products([
    'limit'      => -1,
    'status'     => ['publish', 'draft', 'pending', 'private'],
    'meta_key'   => '_supplier',
    'meta_value' => 'crystal_lux',
    'return'     => 'ids',
]);

$numeric_attributes = [];

foreach (wc_get_attribute_taxonomies() as $attribute) {
    if (preg_match('/^\d+$/', $attribute->attribute_name)) {
        $numeric_attributes[] = $attribute->attribute_name;
    }
}

echo 'Товаров crystal_lux: ' . count($products) . PHP_EOL;
echo 'Числовых атрибутов: ' . count($numeric_attributes) . PHP_EOL;
