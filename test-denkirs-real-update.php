<?php

use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;
use Supplier_Importer\Import\Product_Updater;

$product_id = 2670;

$product = wc_get_product($product_id);

if (!$product) {
    die("Товар {$product_id} не найден.\n");
}

$file = WP_CONTENT_DIR . '/uploads/supplier-importer/denkirs-21.csv';

if (!file_exists($file)) {
    die("CSV не найден: {$file}\n");
}

$handle = fopen($file, 'r');

if (!$handle) {
    die("Не удалось открыть CSV.\n");
}

$headers = fgetcsv(
    $handle,
    0,
    ';'
);

$row = fgetcsv(
    $handle,
    0,
    ';'
);

fclose($handle);

if (!$headers || !$row) {
    die("Не удалось прочитать CSV.\n");
}

$headers = array_map(
    'trim',
    $headers
);

$row = array_pad(
    $row,
    count($headers),
    ''
);

$row = array_combine(
    $headers,
    array_slice(
        $row,
        0,
        count($headers)
    )
);

$mapper = new Denkirs_Mapper();

$product_data = $mapper->map($row);

echo "=== BEFORE ===" . PHP_EOL;

echo 'ID: ' . $product->get_id() . PHP_EOL;
echo 'SKU: ' . $product->get_sku() . PHP_EOL;
echo 'NAME: ' . $product->get_name() . PHP_EOL;

echo PHP_EOL;
echo "=== UPDATE ===" . PHP_EOL;

$updater = new Product_Updater();

$updated_product = $updater->update(
    $product,
    $product_data
);

echo 'UPDATED ID: ';
echo $updated_product->get_id();
echo PHP_EOL;

echo PHP_EOL;
echo "=== AFTER ===" . PHP_EOL;

echo 'SKU: ';
echo $updated_product->get_sku();
echo PHP_EOL;

echo 'NAME: ';
echo $updated_product->get_name();
echo PHP_EOL;

echo PHP_EOL;
echo "=== CATEGORIES ===" . PHP_EOL;

$category_ids = $updated_product->get_category_ids();

print_r($category_ids);

echo PHP_EOL;
echo "=== ATTRIBUTES ===" . PHP_EOL;

foreach (
    $updated_product->get_attributes()
    as $taxonomy => $attribute
) {
    echo PHP_EOL;
    echo 'TAXONOMY: ' . $taxonomy . PHP_EOL;
    echo 'TERM IDS: ' . PHP_EOL;

    print_r(
        $attribute->get_options()
    );
}
