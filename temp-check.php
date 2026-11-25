<?php

$attributes = wc_get_attribute_taxonomies();

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

    $crystal_ids = [];
    $other_ids = [];

    foreach ($object_ids as $object_id) {
        $supplier = get_post_meta($object_id, '_supplier', true);

        if ($supplier === 'crystal_lux') {
            $crystal_ids[] = $object_id;
        } else {
            $other_ids[] = $object_id;
        }
    }

    printf(
        "Атрибут: pa_%s | Всего объектов: %d | Crystal Lux: %d | Другие: %d\n",
        $attribute->attribute_name,
        count($object_ids),
        count($crystal_ids),
        count($other_ids)
    );

    if (!empty($other_ids)) {
        echo "  ID других объектов: " . implode(', ', $other_ids) . PHP_EOL;
    }
}
