<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product;
use Supplier_Importer\Core\Logger;

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

        Logger::info(
            'IMAGE MEMORY START: '
                . round(memory_get_usage(true) / 1024 / 1024, 2)
                . ' MB / peak '
                . round(memory_get_peak_usage(true) / 1024 / 1024, 2)
                . ' MB / limit '
                . ini_get('memory_limit')
        );

        if (
            !is_array($image_urls)
            || empty($image_urls)
        ) {
            return [];
        }

        $image_urls = $this->normalize_urls(
            $image_urls
        );

        $image_urls = array_slice($image_urls, 0, 6);

        if (empty($image_urls)) {
            return [];
        }

        $image_ids = [];

        foreach ($image_urls as $index => $image_url) {

            Logger::info(
                'IMAGE DEBUG: PROCESS '
                    . ($index + 1)
                    . '/' . count($image_urls)
                    . ' url=' . $image_url
            );

            $image_start = microtime(true);

            $attachment_id = $this->get_or_download_image(
                $image_url,
                $product->get_id()
            );

            Logger::info(
                'IMAGE DEBUG: PROCESS END '
                    . ($index + 1)
                    . '/' . count($image_urls)
                    . ' time=' . round(microtime(true) - $image_start, 2)
                    . ' sec'
                    . ' attachment_id=' . (int) $attachment_id
            );

            Logger::info(
                'IMAGE MEMORY AFTER '
                    . ($index + 1)
                    . ': '
                    . round(memory_get_usage(true) / 1024 / 1024, 2)
                    . ' MB / peak '
                    . round(memory_get_peak_usage(true) / 1024 / 1024, 2)
                    . ' MB / limit '
                    . ini_get('memory_limit')
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

        $start = microtime(true);

        Logger::info(
            'IMAGE DEBUG: START'
                . ' url=' . $image_url
                . ' product_id=' . $product_id
        );

        /*
     * 1. Скачиваем оригинальное изображение.
     */
        $download_start = microtime(true);

        $tmp_file = download_url(
            $image_url,
            30
        );

        Logger::info(
            'IMAGE DEBUG: download_url END'
                . ' time='
                . round(
                    microtime(true) - $download_start,
                    2
                )
                . ' sec'
        );

        if (is_wp_error($tmp_file)) {

            Logger::info(
                'IMAGE DEBUG: download ERROR'
                    . ' url=' . $image_url
                    . ' error=' . $tmp_file->get_error_message()
            );

            return 0;
        }

        /*
     * 2. Определяем тип файла.
     */
        $mime_type = mime_content_type(
            $tmp_file
        );

        $original_size = filesize(
            $tmp_file
        );

        $file_name = $this->get_file_name(
            $image_url
        );

        Logger::info(
            'IMAGE DEBUG: ORIGINAL'
                . ' url=' . $image_url
                . ' type=' . $mime_type
                . ' size='
                . round(
                    $original_size / 1024 / 1024,
                    2
                )
                . ' MB'
        );

        /*
 * Оптимизируем JPEG и PNG.
 *
 * JPEG:
 * - сохраняем JPEG;
 * - quality 82.
 *
 * PNG:
 * - переводим в JPEG;
 * - прозрачность заменяем белым фоном;
 * - quality 82.
 */
        if (
            in_array(
                $mime_type,
                [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                ],
                true
            )
        ) {

            $optimize_start = microtime(true);

            $image_editor = wp_get_image_editor(
                $tmp_file
            );

            if (is_wp_error($image_editor)) {

                Logger::info(
                    'IMAGE DEBUG: OPTIMIZE SKIP'
                        . ' reason=image_editor_error'
                        . ' type=' . $mime_type
                        . ' error='
                        . $image_editor->get_error_message()
                );
            } else {

                /*
         * PNG превращаем в JPEG.
         */
                if ($mime_type === 'image/png') {

                    /*
             * Задаём белый фон.
             *
             * Это убирает прозрачность.
             */
                    $image_editor->set_background_color(
                        '#ffffff'
                    );

                    $image_editor->set_quality(82);

                    $optimized = $image_editor->save(
                        $tmp_file,
                        'image/jpeg'
                    );
                } else {

                    /*
             * JPEG просто сжимаем.
             */
                    $image_editor->set_quality(82);

                    $optimized = $image_editor->save(
                        $tmp_file,
                        'image/jpeg'
                    );
                }

                if (is_wp_error($optimized)) {

                    Logger::info(
                        'IMAGE DEBUG: OPTIMIZE ERROR'
                            . ' type=' . $mime_type
                            . ' error='
                            . $optimized->get_error_message()
                    );
                } else {

                    $optimized_size = filesize(
                        $tmp_file
                    );

                    $saved_bytes = $original_size
                        - $optimized_size;

                    $saved_percent = 0;

                    if ($original_size > 0) {
                        $saved_percent = round(
                            (
                                $saved_bytes
                                / $original_size
                            ) * 100,
                            1
                        );
                    }

                    Logger::info(
                        'IMAGE DEBUG: IMAGE OPTIMIZED'
                            . ' original_type=' . $mime_type
                            . ' final_type=image/jpeg'
                            . ' before='
                            . round(
                                $original_size / 1024 / 1024,
                                2
                            )
                            . ' MB'
                            . ' after='
                            . round(
                                $optimized_size / 1024 / 1024,
                                2
                            )
                            . ' MB'
                            . ' saved='
                            . $saved_percent
                            . '%'
                            . ' time='
                            . round(
                                microtime(true)
                                    - $optimize_start,
                                2
                            )
                            . ' sec'
                    );

                    /*
             * После конвертации PNG → JPEG
             * MIME должен соответствовать новому файлу.
             */
                    $mime_type = 'image/jpeg';

                    $file_name = pathinfo(
                        $file_name,
                        PATHINFO_FILENAME
                    ) . '.jpg';
                }
            }
        } else {

            Logger::info(
                'IMAGE DEBUG: OPTIMIZE SKIP'
                    . ' url=' . $image_url
                    . ' type=' . $mime_type
                    . ' reason=unsupported_type'
            );
        }

        /*
     * 4. Получаем имя файла.
     */
        $file_name = $this->get_file_name(
            $image_url
        );

        /*
     * 5. Формируем файл для WordPress.
     */
        $file = [
            'name'     => $file_name,
            'type'     => $mime_type,
            'tmp_name' => $tmp_file,
            'error'    => 0,
            'size'     => filesize($tmp_file),
        ];

        /*
     * 6. Создаём attachment.
     */
        $media_start = microtime(true);

        $attachment_id = @media_handle_sideload(
            $file,
            $product_id
        );

        Logger::info(
            'IMAGE DEBUG: media_handle_sideload END'
                . ' time='
                . round(
                    microtime(true) - $media_start,
                    2
                )
                . ' sec'
        );

        if (is_wp_error($attachment_id)) {

            Logger::info(
                'IMAGE DEBUG: media ERROR'
                    . ' error='
                    . $attachment_id->get_error_message()
            );

            @unlink($tmp_file);

            return 0;
        }

        /*
     * 7. Сохраняем исходный URL поставщика.
     */
        update_post_meta(
            $attachment_id,
            self::SOURCE_URL_META,
            $image_url
        );

        /*
     * 8. Финальный лог.
     */
        Logger::info(
            'IMAGE DEBUG: END'
                . ' attachment_id='
                . $attachment_id
                . ' total='
                . round(
                    microtime(true) - $start,
                    2
                )
                . ' sec'
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
