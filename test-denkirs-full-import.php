<?php

use Supplier_Importer\CSV\Csv_Reader;
use Supplier_Importer\Suppliers\Denkirs\Denkirs;
use Supplier_Importer\Import\Import_Manager;
use Supplier_Importer\Product\Product_Data;

$file = WP_CONTENT_DIR . '/uploads/supplier-importer/denkirs-21.csv';

if (!file_exists($file)) {
    die("CSV не найден: {$file}\n");
}

/*
 * Читаем первую строку CSV.
 */
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

echo "=== CSV ===" . PHP_EOL;

echo 'SKU: ' . ($row['Артикул'] ?? '') . PHP_EOL;
echo 'NAME: ' . ($row['Название товара'] ?? '') . PHP_EOL;
echo 'CATEGORY: ' . ($row['Категория'] ?? '') . PHP_EOL;

echo PHP_EOL;

/*
 * Получаем штатный Denkirs mapper.
 */
$supplier = new Denkirs();

$mapper = $supplier->get_mapper();

$product_data = $mapper->map(
    $row
);

echo "=== PRODUCT DATA ===" . PHP_EOL;

echo 'SKU: ' . $product_data->get_sku() . PHP_EOL;
echo 'NAME: ' . $product_data->get_name() . PHP_EOL;
echo 'CATEGORY: ' . $product_data->get('category', '') . PHP_EOL;

echo PHP_EOL;

/*
 * Полный штатный импорт.
 */
$manager = new Import_Manager();

$result = $manager->import([
    $product_data,
]);

echo "=== IMPORT RESULT ===" . PHP_EOL;

print_r($result);

echo PHP_EOL;

/*
 * Проверяем итоговый товар.
 */
if (
    !empty($result['items'][0]['product_id'])
) {
    $product_id = (int) $result['items'][0]['product_id'];

    $product = wc_get_product(
        $product_id
    );

    if ($product) {

        echo "=== FINAL PRODUCT ===" . PHP_EOL;

        echo 'ID: ' . $product->get_id() . PHP_EOL;
        echo 'SKU: ' . $product->get_sku() . PHP_EOL;
        echo 'NAME: ' . $product->get_name() . PHP_EOL;

        echo PHP_EOL;
        echo "CATEGORIES:" . PHP_EOL;

        print_r(
            $product->get_category_ids()
        );

        echo PHP_EOL;
        echo "ATTRIBUTES:" . PHP_EOL;

        foreach (
            $product->get_attributes()
            as $taxonomy => $attribute
        ) {
            echo PHP_EOL;
            echo 'TAXONOMY: ' . $taxonomy . PHP_EOL;
            echo 'TERM IDS: ' . PHP_EOL;

            print_r(
                $attribute->get_options()
            );
        }
    }
}
