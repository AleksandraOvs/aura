<?php

$mapper = new \Supplier_Importer\Suppliers\Maytoni\Maytoni_Mapper();

$config = $mapper->get_default_attribute_config();

echo 'Количество полей: ' . count($config) . PHP_EOL;

echo 'brand_name: ';
var_export($config['brand_name'] ?? null);
echo PHP_EOL;

$saved = \Supplier_Importer\Import\Attribute_Config_Repository::get(
    'test_supplier'
);

echo 'Тестовая конфигурация: ';
var_export($saved);
echo PHP_EOL;
