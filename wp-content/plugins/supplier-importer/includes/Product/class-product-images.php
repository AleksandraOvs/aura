<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product;

class Product_Images
{
    private const SOURCE_URL_META = '_supplier_image_url';

    /**
     * Обрабатывает изображения товара.
     *
     * @param WC_Product $product
     * @param array      $image_urls
     *
     * @return array ID загруженных изображений.
     */
    public function assign(
        WC_Product $product,
        $image_urls
    ) {
        if (!$product instanceof WC_Product) {
            throw new InvalidArgumentException(
                'Product_Images ожидает объект WC_Product.'
            );
        }

        if (
            !is_array($image_urls)
            || empty($image_urls)
        ) {
            return [];
        }

        $image_urls = $this->normalize_urls(
            $image_urls
        );

        if (empty($image_urls)) {
            return [];
        }

        $image_ids = [];

        foreach ($image_urls as $image_url) {
            $attachment_id = $this->get_or_download_image(
                $image_url,
                $product->get_id()
            );

            if (!$attachment_id) {
                continue;
            }

            $image_ids[] = $attachment_id;
        }

        $image_ids = array_values(
            array_unique($image_ids)
        );

        if (empty($image_ids)) {
            return [];
        }

        $this->set_product_images(
            $product,
            $image_ids
        );

        return $image_ids;
    }

    /**
     * Нормализует список URL.
     */
    private function normalize_urls($image_urls)
    {
        $result = [];

        foreach ($image_urls as $image_url) {
            $image_url = trim(
                (string) $image_url
            );

            if ($image_url === '') {
                continue;
            }

            if (
                !filter_var(
                    $image_url,
                    FILTER_VALIDATE_URL
                )
            ) {
                continue;
            }

            $result[] = $image_url;
        }

        return array_values(
            array_unique($result)
        );
    }

    /**
     * Ищет уже загруженное изображение
     * или скачивает новое.
     */
    private function get_or_download_image(
        $image_url,
        $product_id
    ) {
        $existing_id = $this->find_by_source_url(
            $image_url
        );

        if ($existing_id) {
            return $existing_id;
        }

        return $this->download_image(
            $image_url,
            $product_id
        );
    }

    /**
     * Ищет attachment по исходному URL.
     */
    private function find_by_source_url($image_url)
    {
        $query = new \WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::SOURCE_URL_META,
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
     * Скачивает изображение и создаёт attachment.
     */
    private function download_image(
        $image_url,
        $product_id
    ) {
        require_once ABSPATH
            . 'wp-admin/includes/media.php';

        require_once ABSPATH
            . 'wp-admin/includes/file.php';

        require_once ABSPATH
            . 'wp-admin/includes/image.php';

        $tmp_file = download_url(
            $image_url,
            30
        );

        if (is_wp_error($tmp_file)) {
            return 0;
        }

        $file_name = $this->get_file_name(
            $image_url
        );

        $file = [
            'name'     => $file_name,
            'type'     => mime_content_type($tmp_file),
            'tmp_name' => $tmp_file,
            'error'    => 0,
            'size'     => filesize($tmp_file),
        ];

        $attachment_id = @media_handle_sideload(
            $file,
            $product_id
        );

        // $attachment_id = media_handle_sideload(
        //     $file,
        //     $product_id
        // );

        if (is_wp_error($attachment_id)) {
            @unlink($tmp_file);

            return 0;
        }

        update_post_meta(
            $attachment_id,
            self::SOURCE_URL_META,
            $image_url
        );

        return (int) $attachment_id;
    }

    /**
     * Получает имя файла из URL.
     */
    private function get_file_name($image_url)
    {
        $path = wp_parse_url(
            $image_url,
            PHP_URL_PATH
        );

        $file_name = basename(
            (string) $path
        );

        if ($file_name === '' || $file_name === '/') {
            $file_name = 'supplier-image.jpg';
        }

        return sanitize_file_name(
            $file_name
        );
    }

    /**
     * Назначает изображения товару.
     */
    private function set_product_images(
        WC_Product $product,
        $image_ids
    ) {
        if (empty($image_ids)) {
            return;
        }

        $product->set_image_id(
            $image_ids[0]
        );

        $gallery_ids = array_slice(
            $image_ids,
            1
        );

        $product->set_gallery_image_ids(
            $gallery_ids
        );
    }
}
