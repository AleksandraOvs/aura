<?php

/**
 * Очистка изображений Supplier Importer.
 *
 * По умолчанию — только аудит.
 * Удаление: php cleanup-supplier-attachments.php --delete
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require_once __DIR__ . '/wp-load.php';

global $wpdb;

@set_time_limit(0);
@ini_set('memory_limit', '512M');

$delete_mode = in_array('--delete', $argv, true);

$limit = null;

foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $matches)) {
        $limit = max(1, (int) $matches[1]);
    }
}

$batch_size = 50;

$log_path = __DIR__ . '/supplier-attachments-cleanup.log';
$lock_path = __DIR__ . '/supplier-attachments-cleanup.lock';

$lock = fopen($lock_path, 'c');

if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Another cleanup process is running.\n");
}

$log = fopen($log_path, 'a');

if (!$log) {
    exit("Cannot open log file.\n");
}

function cleanup_log($message)
{
    global $log;

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    echo $line . PHP_EOL;
    fwrite($log, $line . PHP_EOL);
    fflush($log);
}

function cleanup_remove_product_references($attachment_id)
{
    global $wpdb;

    $attachment_id = (int) $attachment_id;

    $touched_posts = [];

    // Удаляем ссылки на главное изображение.
    $thumbnail_posts = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_thumbnail_id'
              AND meta_value = %s
            ",
            (string) $attachment_id
        )
    );

    foreach ($thumbnail_posts as $post_id) {
        $touched_posts[] = (int) $post_id;
    }

    $wpdb->delete(
        $wpdb->postmeta,
        [
            'meta_key'   => '_thumbnail_id',
            'meta_value' => (string) $attachment_id,
        ],
        ['%s', '%s']
    );

    // Удаляем ID из галерей товаров.
    $gallery_rows = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT meta_id, post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_product_image_gallery'
              AND FIND_IN_SET(%s, meta_value)
            ",
            (string) $attachment_id
        )
    );

    foreach ($gallery_rows as $row) {
        $ids = array_filter(
            array_map(
                'absint',
                explode(',', $row->meta_value)
            )
        );

        $ids = array_values(
            array_diff($ids, [$attachment_id])
        );

        $wpdb->update(
            $wpdb->postmeta,
            [
                'meta_value' => implode(',', $ids),
            ],
            [
                'meta_id' => (int) $row->meta_id,
            ],
            ['%s'],
            ['%d']
        );

        $touched_posts[] = (int) $row->post_id;
    }

    foreach (array_unique($touched_posts) as $post_id) {
        clean_post_cache($post_id);
    }

    return count(array_unique($touched_posts));
}

cleanup_log('========================================');
cleanup_log(
    $delete_mode
        ? 'DELETE MODE'
        : 'DRY RUN MODE'
);
cleanup_log('Batch size: ' . $batch_size);
cleanup_log('Limit: ' . ($limit ?: 'unlimited'));

$last_id = 0;
$processed = 0;
$deleted = 0;
$errors = 0;
$references_cleaned = 0;
$sample = [];

do {
    $sql = $wpdb->prepare(
        "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm
            ON pm.post_id = p.ID
            AND pm.meta_key = '_supplier_image_url'
        WHERE p.post_type = 'attachment'
          AND p.post_mime_type LIKE 'image/%%'
          AND p.ID > %d
        ORDER BY p.ID ASC
        LIMIT %d
        ",
        $last_id,
        $batch_size
    );

    $ids = $wpdb->get_col($sql);

    if ($wpdb->last_error) {
        cleanup_log('SQL ERROR: ' . $wpdb->last_error);
        $errors++;
        break;
    }

    if (!$ids) {
        break;
    }

    foreach ($ids as $id) {
        $id = (int) $id;
        $last_id = $id;

        if ($limit !== null && $processed >= $limit) {
            break 2;
        }

        $processed++;

        if (!$delete_mode) {
            if (count($sample) < 20) {
                $sample[] = $id;
            }

            continue;
        }

        $result = wp_delete_attachment($id, true);

        if (!$result) {
            $errors++;
            cleanup_log("ERROR: attachment {$id} was not deleted.");
            continue;
        }

        $deleted++;

        $cleaned = cleanup_remove_product_references($id);
        $references_cleaned += $cleaned;

        if ($deleted % 100 === 0) {
            cleanup_log(
                "Progress: deleted={$deleted}, errors={$errors}, last_id={$id}"
            );
        }
    }
} while (count($ids) === $batch_size);

if (!$delete_mode) {
    cleanup_log('AUDIT RESULT');
    cleanup_log('Candidates: ' . $processed);
    cleanup_log('First IDs: ' . implode(', ', $sample));
    cleanup_log('No files were deleted.');
} else {
    cleanup_log('DELETE RESULT');
    cleanup_log('Processed: ' . $processed);
    cleanup_log('Deleted: ' . $deleted);
    cleanup_log('Errors: ' . $errors);
    cleanup_log('Posts with cleaned references: ' . $references_cleaned);
}

cleanup_log('FINISHED');
cleanup_log('========================================');

fclose($log);
flock($lock, LOCK_UN);
fclose($lock);
