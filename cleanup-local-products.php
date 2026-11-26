<?php

require_once __DIR__ . '/wp-load.php';

if (wp_parse_url(home_url(), PHP_URL_HOST) !== 'aura.local') {
    exit("ОШИБКА: это не aura.local. Удаление отменено.\n");
}

if (!class_exists('WooCommerce')) {
    exit("ОШИБКА: WooCommerce не активен.\n");
}

$product_ids = get_posts([
    'post_type' => ['product', 'product_variation'],
    'post_status' => array_keys(get_post_stati()),
    'numberposts' => -1,
    'fields' => 'ids',
    'orderby' => 'ID',
    'order' => 'ASC',
]);

$image_ids = [];

foreach ($product_ids as $product_id) {
    $thumbnail = absint(get_post_meta($product_id, '_thumbnail_id', true));

    if ($thumbnail) {
        $image_ids[] = $thumbnail;
    }

    $gallery = get_post_meta($product_id, '_product_image_gallery', true);

    if ($gallery) {
        foreach (explode(',', $gallery) as $image_id) {
            $image_id = absint($image_id);

            if ($image_id) {
                $image_ids[] = $image_id;
            }
        }
    }
}

$image_ids = array_values(array_unique($image_ids));

$attributes = wc_get_attribute_taxonomies();

echo "Сайт: " . home_url() . "\n";
echo "Товаров и вариаций: " . count($product_ids) . "\n";
echo "Изображений товаров: " . count($image_ids) . "\n";
echo "Глобальных атрибутов: " . count($attributes) . "\n";
echo "\n";

if (!isset($argv[1]) || $argv[1] !== '--execute') {
    echo "РЕЖИМ ПРОВЕРКИ. Ничего не удалено.\n";
    echo "Для запуска удаления используй параметр --execute.\n";
    exit;
}

// Сначала удаляем вариации, затем родительские товары.
usort($product_ids, function ($a, $b) {
    $a_is_variation = get_post_type($a) === 'product_variation';
    $b_is_variation = get_post_type($b) === 'product_variation';

    return $b_is_variation <=> $a_is_variation;
});

$deleted_products = 0;

foreach ($product_ids as $product_id) {
    if (get_post($product_id) && wp_delete_post($product_id, true)) {
        $deleted_products++;
    }
}

$deleted_images = 0;

foreach ($image_ids as $image_id) {
    if (get_post($image_id) && wp_delete_attachment($image_id, true)) {
        $deleted_images++;
    }
}

$deleted_attributes = 0;

foreach ($attributes as $attribute) {
    $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

    if (taxonomy_exists($taxonomy)) {
        $term_ids = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (!is_wp_error($term_ids)) {
            foreach ($term_ids as $term_id) {
                wp_delete_term($term_id, $taxonomy);
            }
        }
    }

    if (wc_delete_attribute($attribute->attribute_id)) {
        $deleted_attributes++;
    }
}

echo "ОЧИСТКА ЗАВЕРШЕНА.\n";
echo "Удалено товаров и вариаций: {$deleted_products}\n";
echo "Удалено изображений: {$deleted_images}\n";
echo "Удалено глобальных атрибутов: {$deleted_attributes}\n";