<?php

use Supplier_Importer\CSV\Csv_Reader;
use Supplier_Importer\CSV\Csv_Analyzer;
use Supplier_Importer\Suppliers\Supplier_Manager;
use Supplier_Importer\Suppliers\Denkirs\Denkirs;

$csv_file = WP_CONTENT_DIR
    . '/uploads/supplier-importer/denkirs.csv';

/*
 * Регистрируем поставщика.
 */
$manager = new Supplier_Manager();

$manager->register(
    new Denkirs()
);

/*
 * Читаем CSV.
 */
$reader = new Csv_Reader(
    $csv_file
);

$reader->open();

$headers = $reader->get_headers();

echo "=== HEADERS ===\n";

print_r($headers);

/*
 * Определяем поставщика.
 */
$supplier = $manager->detect($headers);

if (!$supplier) {
    throw new RuntimeException(
        'Поставщик не определён.'
    );
}

echo "\n=== SUPPLIER ===\n";

echo $supplier->get_id() . "\n";
echo $supplier->get_name() . "\n";

/*
 * Получаем конфигурацию анализа
 * непосредственно от поставщика.
 */
$config = $supplier->get_csv_analysis_config();

echo "\n=== CONFIG ===\n";

print_r($config);

/*
 * Анализируем CSV.
 */
$analyzer = new Csv_Analyzer();

$result = $analyzer->analyze(
    $reader,
    $config
);

$reader->close();

echo "\n=== ANALYSIS ===\n";

print_r($result);
