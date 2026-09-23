<?php

use Supplier_Importer\Import\Mapping_Repository;

if (!defined('ABSPATH')) {
    exit;
}

$repository = new Mapping_Repository();

$supplier = 'denkirs';

/*
 * Удаляем тестовые записи,
 * чтобы тест можно было запускать повторно.
 */
$repository->delete(
    $supplier,
    'attribute_value',
    'черный',
    'цвет арматуры'
);

$repository->delete(
    $supplier,
    'attribute_value',
    'черный',
    'цвет плафона'
);

$repository->delete(
    $supplier,
    'attribute',
    'цвет арматуры'
);

/*
 * 1. Сохраняем "черный" для цвета арматуры.
 */
$id1 = $repository->save(
    $supplier,
    'attribute_value',
    'черный',
    101,
    'цвет арматуры'
);

echo "ID 1: {$id1}\n";

/*
 * 2. Сохраняем "черный" для цвета плафона.
 */
$id2 = $repository->save(
    $supplier,
    'attribute_value',
    'черный',
    202,
    'цвет плафона'
);

echo "ID 2: {$id2}\n";

/*
 * 3. Сохраняем обычный атрибут.
 */
$id3 = $repository->save(
    $supplier,
    'attribute',
    'цвет арматуры',
    303
);

echo "ID 3: {$id3}\n";

if ($id3 === 0) {
    global $wpdb;

    echo "\nDB ERROR:\n";
    echo $wpdb->last_error . "\n";
}

/*
 * Проверяем первое значение.
 */
$mapping1 = $repository->find(
    $supplier,
    'attribute_value',
    'черный',
    'цвет арматуры'
);

echo "\nMapping 1:\n";
print_r($mapping1);

/*
 * Проверяем второе значение.
 */
$mapping2 = $repository->find(
    $supplier,
    'attribute_value',
    'черный',
    'цвет плафона'
);

echo "\nMapping 2:\n";
print_r($mapping2);

/*
 * Получаем все mappings поставщика.
 */
$all = $repository->get_by_supplier(
    $supplier
);

echo "\nAll mappings:\n";
print_r($all);

/*
 * Проверяем обновление существующего mapping.
 */
echo "\nUpdate test:\n";

$updated_id = $repository->save(
    $supplier,
    'attribute_value',
    'черный',
    999,
    'цвет арматуры'
);

echo "Updated ID: {$updated_id}\n";

$updated_mapping = $repository->find(
    $supplier,
    'attribute_value',
    'черный',
    'цвет арматуры'
);

print_r($updated_mapping);
