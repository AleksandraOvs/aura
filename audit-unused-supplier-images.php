
<?php

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$sql = "
    SELECT
        a.ID AS attachment_id,
        a.post_parent,
        a.post_title,
        a.guid,
        parent.post_type AS parent_type,
        parent.post_status AS parent_status,
        parent.post_title AS parent_title,
        pm.meta_value AS source_url
    FROM {$wpdb->posts} a
    INNER JOIN {$wpdb->postmeta} pm
        ON pm.post_id = a.ID
        AND pm.meta_key = '_supplier_image_url'
    LEFT JOIN {$wpdb->posts} parent
        ON parent.ID = a.post_parent
    WHERE
        a.post_type = 'attachment'
        AND a.post_mime_type LIKE 'image/%'
        AND NOT EXISTS (
            SELECT 1
            FROM {$wpdb->postmeta} used
            WHERE
                (
                    used.meta_key = '_thumbnail_id'
                    AND used.meta_value = CAST(a.ID AS CHAR)
                )
                OR
                (
                    used.meta_key = '_product_image_gallery'
                    AND FIND_IN_SET(
                        a.ID,
                        used.meta_value
                    )
                )
        )
    ORDER BY a.ID
";

$rows = $wpdb->get_results($sql);

if ($wpdb->last_error) {
    exit("SQL ERROR: " . $wpdb->last_error . "\n");
}

$groups = [];

foreach ($rows as $row) {

    $type = $row->parent_type ?: 'no_parent';
    $status = $row->parent_status ?: 'no_parent';

    $key = $type . ' / ' . $status;

    if (!isset($groups[$key])) {
        $groups[$key] = 0;
    }

    $groups[$key]++;
}

echo "====================================\n";
echo "UNUSED SUPPLIER IMAGES: PARENTS\n";
echo "====================================\n";

echo "Total candidates: " . count($rows) . "\n\n";

echo "GROUPS BY PARENT:\n";

foreach ($groups as $key => $count) {
    echo $key . ': ' . $count . "\n";
}

echo "\nFIRST 40 CANDIDATES:\n\n";

foreach (array_slice($rows, 0, 40) as $row) {

    echo sprintf(
        "ID: %d | Parent: %d | Type: %s | Status: %s | Product: %s\n",
        $row->attachment_id,
        $row->post_parent,
        $row->parent_type ?: '-',
        $row->parent_status ?: '-',
        $row->parent_title ?: '-'
    );
}

echo "\nAUDIT COMPLETED. NO FILES DELETED.\n";
