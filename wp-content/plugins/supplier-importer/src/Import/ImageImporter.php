<?php

namespace SupplierImporter\Import;

if (!defined('ABSPATH')) {
    exit;
}

class ImageImporter
{
    /**
     * Импорт изображений товара.
     *
     * @param int   $product_id
     * @param array $images
     *
     * @return array
     */
    public function import(
        int $product_id,
        array $images
    ): array {

        if (empty($images)) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'failed'   => 0,
            ];
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $imported = 0;
        $skipped  = 0;
        $failed   = 0;

        $attachment_ids = [];

        foreach ($images as $image_url) {

            $image_url = trim((string) $image_url);

            if ($image_url === '') {
                continue;
            }

            /*
             * Проверяем, не импортировали ли уже
             * это изображение ранее.
             */
            $existing_attachment = $this->findExistingImage(
                $product_id,
                $image_url
            );

            if ($existing_attachment) {
                $attachment_ids[] = $existing_attachment;
                $skipped++;

                continue;
            }

            $attachment_id = $this->downloadImage(
                $product_id,
                $image_url
            );

            if (!$attachment_id) {
                $failed++;
                continue;
            }

            $attachment_ids[] = $attachment_id;
            $imported++;
        }

        /*
         * Если изображения успешно найдены,
         * устанавливаем первое как главное.
         */
        if (!empty($attachment_ids)) {

            $product = wc_get_product($product_id);

            if ($product) {

                $current_image_id =
                    $product->get_image_id();

                if (!$current_image_id) {

                    $product->set_image_id(
                        $attachment_ids[0]
                    );

                    $product->set_gallery_image_ids(
                        array_slice(
                            $attachment_ids,
                            1
                        )
                    );

                    $product->save();
                } else {

                    /*
                     * Если главное изображение уже есть,
                     * просто добавляем новые изображения
                     * в галерею, не заменяя главное.
                     */
                    $gallery_ids =
                        $product->get_gallery_image_ids();

                    $gallery_ids = array_unique(
                        array_merge(
                            $gallery_ids,
                            $attachment_ids
                        )
                    );

                    $gallery_ids = array_values(
                        array_diff(
                            $gallery_ids,
                            [$current_image_id]
                        )
                    );

                    $product->set_gallery_image_ids(
                        $gallery_ids
                    );

                    $product->save();
                }
            }
        }

        return [
            'imported' => $imported,
            'skipped'  => $skipped,
            'failed'   => $failed,
        ];
    }

    /**
     * Ищем уже импортированное изображение
     * по исходному URL.
     */
    private function findExistingImage(
        int $product_id,
        string $image_url
    ): int {

        $query = new \WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',

            'post_parent' => $product_id,

            'meta_query' => [
                [
                    'key'   => '_supplier_image_url',
                    'value' => $image_url,
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return 0;
        }

        return (int) $query->posts[0];
    }

    /**
     * Скачать изображение и создать attachment.
     */
    private function downloadImage(
        int $product_id,
        string $image_url
    ): int {

        $tmp = download_url(
            $image_url,
            60
        );

        if (is_wp_error($tmp)) {
            return 0;
        }

        $filename = basename(
            parse_url(
                $image_url,
                PHP_URL_PATH
            )
        );

        $filename = sanitize_file_name(
            $filename
        );

        if ($filename === '') {
            $filename = 'supplier-image.jpg';
        }

        $file_array = [
            'name'     => $filename,
            'tmp_name' => $tmp,
        ];

        /*
     * WordPress при обработке изображения может вызвать
     * exif_read_data() и вывести PHP Warning, если у JPEG
     * повреждён или некорректен EXIF.
     *
     * Такой Warning ломает JSON-ответ AJAX.
     *
     * Подавляем только предупреждения, связанные
     * непосредственно с EXIF.
     */
        $previous_handler = set_error_handler(
            function (
                int $severity,
                string $message,
                string $file,
                int $line
            ) {

                if (
                    $severity === E_WARNING &&
                    (
                        strpos($message, 'exif_read_data') !== false ||
                        strpos($message, 'Incorrect APP1 Exif Identifier Code') !== false
                    )
                ) {
                    return true;
                }

                return false;
            }
        );

        try {

            $attachment_id = media_handle_sideload(
                $file_array,
                $product_id
            );
        } finally {

            restore_error_handler();
        }

        if (is_wp_error($attachment_id)) {

            @unlink($tmp);

            return 0;
        }

        /*
     * Сохраняем исходный URL.
     * По нему будем понимать, что изображение
     * уже было импортировано.
     */
        update_post_meta(
            $attachment_id,
            '_supplier_image_url',
            $image_url
        );

        return (int) $attachment_id;
    }
}
