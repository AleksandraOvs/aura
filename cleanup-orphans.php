<?php
global $wpdb;

$ids = $wpdb->get_col("
    SELECT a.ID
    FROM {$wpdb->posts} a
    LEFT JOIN {$wpdb->posts} p ON p.ID = a.post_parent
    WHERE a.post_type = 'attachment'
      AND a.post_mime_type LIKE 'image/%'
      AND a.post_parent > 0
      AND p.ID IS NULL
");

echo "Найдено осиротевших изображений: " . count($ids) . PHP_EOL;

foreach ($ids as $id) {
    wp_delete_attachment($id, true);
}

echo "Удалено: " . count($ids) . PHP_EOL;
