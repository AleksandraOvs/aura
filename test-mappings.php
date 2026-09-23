<?php

use Supplier_Importer\Import\Mapping_Repository;

$repository = new Mapping_Repository();

$mappings = $repository->get_by_supplier(
    'denkirs',
    'category'
);

print_r($mappings);
