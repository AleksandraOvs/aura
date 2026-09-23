<?php

use Supplier_Importer\Suppliers\Denkirs\Denkirs_Parser;
use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;
use Supplier_Importer\Import\Mapping_Repository;
use Supplier_Importer\Import\Product_Creator;

$csv = 'ПУТЬ_К_ТВОЕМУ_CSV';

$parser = new Denkirs_Parser();

$rows = $parser->parse($csv);

if (empty($rows)) {
    die("CSV пустой или не удалось прочитать.\n");
}

echo 'ROWS: ' . count($rows) . PHP_EOL;
echo PHP_EOL;

$row = $rows[0];

echo "=== CSV ROW ===" . PHP_EOL;
print_r($row);

echo PHP_EOL;
echo "=== MAPPED DATA ===" . PHP_EOL;

$mapper = new Denkirs_Mapper();

$product_data = $mapper->map($row);

print_r($product_data);

echo PHP_EOL;
echo "=== ATTRIBUTES ===" . PHP_EOL;

print_r(
    $product_data->get('attributes')
);
