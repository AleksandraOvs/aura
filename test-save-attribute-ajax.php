<?php

use Supplier_Importer\Import\Mapping_Repository;

$repository = new Mapping_Repository();

$id = $repository->save(
    'denkirs',
    'attribute_value',
    'белый',
    112,
    'цвет арматуры'
);

echo "Mapping ID: {$id}\n";

$mapping = $repository->find(
    'denkirs',
    'attribute_value',
    'белый',
    'цвет арматуры'
);

print_r($mapping);

$term = get_term(
    112,
    'pa_armature-color'
);

echo "\nTerm:\n";
print_r([
    'id'   => $term->term_id,
    'name' => $term->name,
    'taxonomy' => $term->taxonomy,
]);
