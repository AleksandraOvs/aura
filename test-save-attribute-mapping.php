<?php

use Supplier_Importer\Import\Mapping_Repository;

$repository = new Mapping_Repository();

$id = $repository->save(
    'denkirs',
    'attribute_value',
    'черный',
    133,
    'цвет арматуры'
);

echo "Mapping ID: {$id}\n";

$mapping = $repository->find(
    'denkirs',
    'attribute_value',
    'черный',
    'цвет арматуры'
);

print_r($mapping);
