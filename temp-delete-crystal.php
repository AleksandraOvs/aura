<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('wc_get_products')) {
    exit("WooCommerce не загружен.\n");
}

$supplier = 'crystal_lux';

// Получаем товары поставщика.
$products = wc_get_products([
    'limit'      => -1,
    'status'     => ['publish', 'draft', 'pending', 'private'],
    'meta_key'   => '_supplier',
    'meta_value' => $supplier,
    'return'     => 'objects',
]);

echo "Найдено товаров: " . count($products) . PHP_EOL;

// Проверяем числовые атрибуты.
$attributes = wc_get_attribute_taxonomies();
$numeric_attributes = [];

foreach ($attributes as $attribute) {
    if (!preg_match('/^\d+$/', $attribute->attribute_name)) {
        continue;
    }

    $taxonomy = 'pa_' . $attribute->attribute_name;

    if (!taxonomy_exists($taxonomy)) {
        continue;
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        continue;
    }

    $object_ids = [];

    foreach ($terms as $term) {
        $ids = get_objects_in_term($term->term_id, $taxonomy);

        if (!is_wp_error($ids)) {
            $object_ids = array_merge($object_ids, $ids);
        }
    }

    $object_ids = array_unique(array_map('intval', $object_ids));

    foreach ($object_ids as $object_id) {
        if (get_post_meta($object_id, '_supplier', true) !== $supplier) {
            exit("ОТМЕНА: атрибут {$taxonomy} используется другим объектом ID {$object_id}\n");
        }
    }

    $numeric_attributes[] = [
        'id'       => (int) $attribute->attribute_id,
        'taxonomy' => $taxonomy,
    ];
}

echo "Числовых атрибутов для удаления: "
    . count($numeric_attributes) . PHP_EOL;

// Удаляем товары.
$deleted_products = 0;

foreach ($products as $product) {
    $id = $product->get_id();

    $result = $product->delete(true);

    if ($result) {
        $deleted_products++;
        echo "Удалён товар ID {$id}\n";
    }
}

echo "Удалено товаров: {$deleted_products}\n";

// Удаляем числовые глобальные атрибуты.
$deleted_attributes = 0;

foreach ($numeric_attributes as $attribute) {
    $result = wc_delete_attribute($attribute['id']);

    if (is_wp_error($result)) {
        echo "Ошибка удаления {$attribute['taxonomy']}: "
            . $result->get_error_message() . PHP_EOL;

        continue;
    }

    $deleted_attributes++;

    echo "Удалён атрибут: {$attribute['taxonomy']}\n";
}

delete_transient('wc_attribute_taxonomies');

echo PHP_EOL;
echo "Удалено товаров: {$deleted_products}\n";
echo "Удалено атрибутов: {$deleted_attributes}\n";
