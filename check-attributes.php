
<?php

$attributes = wc_get_attribute_taxonomies();

foreach ($attributes as $attribute) {
    $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'number'     => 0,
    ]);

    if (is_wp_error($terms)) {
        continue;
    }

    $numbers = [];
    $other = [];

    foreach ($terms as $term) {
        $name = trim($term->name);

        // Принимаем целые и дробные числа с точкой или запятой.
        if (preg_match('/^\d+(?:[.,]\d+)?$/', $name)) {
            $numbers[] = (float) str_replace(',', '.', $name);
        } else {
            $other[] = $name;
        }
    }

    echo "\n========================================\n";
    echo "Атрибут: {$attribute->attribute_label}\n";
    echo "Таксономия: {$taxonomy}\n";
    echo "Всего терминов: " . count($terms) . "\n";

    if ($numbers) {
        echo "Числовых: " . count($numbers) . "\n";
        echo "Минимум: " . min($numbers) . "\n";
        echo "Максимум: " . max($numbers) . "\n";
    } else {
        echo "Числовых значений нет\n";
    }

    echo "Примеры остальных значений: "
        . implode(', ', array_slice($other, 0, 12))
        . "\n";
}
