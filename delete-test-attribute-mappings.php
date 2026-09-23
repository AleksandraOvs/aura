<?php

use Supplier_Importer\Import\Mapping_Repository;

$repository = new Mapping_Repository();

$repository->delete(
    'denkirs',
    'attribute_value',
    'черный',
    'цвет арматуры'
);

$repository->delete(
    'denkirs',
    'attribute_value',
    'черный',
    'цвет плафона'
);

echo "Тестовые attribute mappings удалены.\n";
