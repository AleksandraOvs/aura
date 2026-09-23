<?php

use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;

$file = WP_CONTENT_DIR . '/uploads/supplier-importer/denkirs-21.csv';

if (!file_exists($file)) {
    die("Файл не найден: {$file}\n");
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
    array_slice($row, 0, count($headers))
);

$mapper = new Denkirs_Mapper();

$product_data = $mapper->map($row);

echo "=== BASIC DATA ===" . PHP_EOL;

echo 'SKU: ';
echo $product_data->get('sku');
echo PHP_EOL;

echo 'NAME: ';
echo $product_data->get('name');
echo PHP_EOL;

echo 'CATEGORY: ';
echo $product_data->get('category');
echo PHP_EOL;

echo PHP_EOL;
echo "=== ATTRIBUTES ===" . PHP_EOL;

print_r(
    $product_data->get('attributes')
);
