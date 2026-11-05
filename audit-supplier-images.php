
<?php

/**
 * Диагностика изображений товаров.
 *
 * Ничего не удаляет и не изменяет.
 * Запускать через PHP CLI.
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$source_meta = '_supplier_image_url';

echo "====================================\n";
echo "SUPPLIER IMAGE AUDIT\n";
echo "====================================\n\n";

/*
 * 1. Собираем изображения,
 * используемые товарами.
 */

$product_usage = [];

$product_meta = $wpdb->get_results(
    "
    SELECT
        p.ID AS product_id,
        p.post_type,
        pm.meta_key,
        pm.meta_value
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm
        ON pm.post_id = p.ID
    WHERE
        p.post_type IN ('product', 'product_variation')
        AND pm.meta_key IN (
            '_thumbnail_id',
            '_product_image_gallery'
        )
    "
);

foreach ($product_meta as $row) {

    if ($row->meta_key === '_thumbnail_id') {

        $image_id = (int) $row->meta_value;

        if ($image_id > 0) {
            $product_usage[$image_id][] =
                $row->product_id . ':featured';
        }

        continue;
    }

    $gallery_ids = array_filter(
        array_map(
            'absint',
            explode(',', (string) $row->meta_value)
        )
    );

    foreach ($gallery_ids as $image_id) {
        $product_usage[$image_id][] =
            $row->product_id . ':gallery';
    }
}

$product_image_ids = array_keys($product_usage);

echo "PRODUCT REFERENCES\n";
echo "Products image references: "
    . count($product_image_ids)
    . "\n\n";

/*
 * 2. Находим все изображения
 * с метаполем Supplier Importer.
 */

$supplier_ids = $wpdb->get_col(
    $wpdb->prepare(
        "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm
            ON pm.post_id = p.ID
        WHERE
            p.post_type = 'attachment'
            AND p.post_mime_type LIKE %s
            AND pm.meta_key = %s
        ",
        'image/%',
        $source_meta
    )
);

$supplier_ids = array_map('intval', $supplier_ids);

echo "SUPPLIER IMPORTER\n";
echo "Supplier attachments: "
    . count($supplier_ids)
    . "\n";

/*
 * 3. Функция подсчёта файлов
 * одного attachment.
 */

$inspect_attachment = function ($attachment_id) {

    $attached_file = get_attached_file(
        $attachment_id
    );

    $metadata = wp_get_attachment_metadata(
        $attachment_id
    );

    $files = [];

    if ($attached_file) {
        $files[] = $attached_file;
    }

    if (
        is_array($metadata)
        && !empty($metadata['sizes'])
        && $attached_file
    ) {
        $directory = dirname($attached_file);

        foreach ($metadata['sizes'] as $size) {
            if (!empty($size['file'])) {
                $files[] = $directory
                    . '/'
                    . $size['file'];
            }
        }
    }

    if (
        is_array($metadata)
        && !empty($metadata['original_image'])
        && $attached_file
    ) {
        $files[] = dirname($attached_file)
            . '/'
            . $metadata['original_image'];
    }

    $files = array_unique($files);

    $total_size = 0;
    $existing_count = 0;
    $missing_count = 0;

    foreach ($files as $file) {

        if (!is_file($file)) {
            $missing_count++;
            continue;
        }

        $size = filesize($file);

        if ($size !== false) {
            $total_size += $size;
            $existing_count++;
        }
    }

    return [
        'file'          => $attached_file ?: '',
        'files_count'   => $existing_count,
        'missing_count' => $missing_count,
        'total_size'    => $total_size,
    ];
};

/*
 * 4. Формируем CSV.
 */

$upload_dir = wp_upload_dir();

$report_path = trailingslashit(
    $upload_dir['basedir']
) . 'supplier-image-audit-'
    . date('Y-m-d-His')
    . '.csv';

$csv = fopen($report_path, 'w');

if (!$csv) {
    exit("Cannot create CSV report.\n");
}

fputcsv($csv, [
    'attachment_id',
    'title',
    'parent_id',
    'source_url',
    'used_in_product',
    'product_references',
    'original_file',
    'files_count',
    'missing_count',
    'total_size_bytes',
    'total_size_mb',
]);

$total_bytes = 0;
$used_count = 0;
$unused_count = 0;
$missing_files = 0;
$total_files = 0;

foreach ($supplier_ids as $attachment_id) {

    $post = get_post($attachment_id);

    if (!$post) {
        continue;
    }

    $info = $inspect_attachment(
        $attachment_id
    );

    $is_used = isset(
        $product_usage[$attachment_id]
    );

    if ($is_used) {
        $used_count++;
    } else {
        $unused_count++;
    }

    $total_bytes += $info['total_size'];
    $total_files += $info['files_count'];
    $missing_files += $info['missing_count'];

    $source_url = get_post_meta(
        $attachment_id,
        $source_meta,
        true
    );

    fputcsv($csv, [
        $attachment_id,
        $post->post_title,
        $post->post_parent,
        $source_url,
        $is_used ? 'YES' : 'NO',
        isset($product_usage[$attachment_id])
            ? implode('; ', $product_usage[$attachment_id])
            : '',
        $info['file'],
        $info['files_count'],
        $info['missing_count'],
        $info['total_size'],
        round($info['total_size'] / 1024 / 1024, 2),
    ]);
}

fclose($csv);

/*
 * 5. Проверяем ссылки товаров
 * на изображения без метаполя импортёра.
 */

$legacy_count = 0;

foreach ($product_image_ids as $image_id) {

    if (
        !in_array(
            $image_id,
            $supplier_ids,
            true
        )
        && get_post_type($image_id) === 'attachment'
    ) {
        $legacy_count++;
    }
}

/*
 * 6. Итог.
 */

echo "\n====================================\n";
echo "AUDIT RESULT\n";
echo "====================================\n";

echo "Supplier attachments: "
    . count($supplier_ids)
    . "\n";

echo "Used in products: "
    . $used_count
    . "\n";

echo "Not referenced by products: "
    . $unused_count
    . "\n";

echo "Product images without supplier meta: "
    . $legacy_count
    . "\n";

echo "Existing files: "
    . $total_files
    . "\n";

echo "Missing files: "
    . $missing_files
    . "\n";

echo "Supplier images total: "
    . size_format($total_bytes)
    . "\n";

echo "\nCSV REPORT:\n";
echo $report_path . "\n";

echo "\nAUDIT COMPLETED. NO FILES DELETED.\n";
