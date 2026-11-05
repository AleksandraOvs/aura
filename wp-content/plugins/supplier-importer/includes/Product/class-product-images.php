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
     * Размеры изображений, создаваемые при импорте товаров.
     */
    private const IMPORT_IMAGE_SIZES = [
        'woocommerce_thumbnail',
        'woocommerce_single',
        'woocommerce_gallery_thumbnail',
        'thumbnail',
    ];

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

        $image_urls = array_slice($image_urls, 0, 6);

        if (empty($image_urls)) {
            return [];
        }

        $image_ids = [];

        foreach ($image_urls as $index => $image_url) {

            $image_start = microtime(true);

            $attachment_id = $this->get_or_download_image(
                $image_url,
                $product->get_id()
            );

            if (is_wp_error($attachment_id)) {
                Logger::info(
                    'IMAGE DEBUG: IMAGE ERROR'
                        . ' url=' . $image_url
                        . ' error=' . $attachment_id->get_error_message()
                );

                continue;
            }

            if (!$attachment_id || !is_numeric($attachment_id)) {
                Logger::info(
                    'IMAGE DEBUG: INVALID ATTACHMENT ID'
                );

                return 0;
            }

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

        /*
     * 1. Скачиваем оригинальное изображение.
     */
        $original_tmp_file = download_url(
            $image_url,
            30
        );

        if (is_wp_error($original_tmp_file)) {
            Logger::info(
                'IMAGE DEBUG: DOWNLOAD ERROR'
                    . ' url=' . $image_url
                    . ' error=' . $original_tmp_file->get_error_message()
            );

            return 0;
        }

        // Файл, который в итоге передадим WordPress.
        $tmp_file = $original_tmp_file;

        try {

            /*
         * 2. Проверяем исходный файл.
         */
            if (
                !is_file($original_tmp_file)
                || !is_readable($original_tmp_file)
            ) {
                Logger::info(
                    'IMAGE DEBUG: ORIGINAL FILE INVALID'
                        . ' url=' . $image_url
                );

                return 0;
            }

            $original_size = filesize($original_tmp_file);

            if ($original_size === false || $original_size <= 0) {
                Logger::info(
                    'IMAGE DEBUG: INVALID ORIGINAL SIZE'
                        . ' url=' . $image_url
                );

                return 0;
            }

            $image_info = @getimagesize($original_tmp_file);

            if (!$image_info || empty($image_info['mime'])) {
                Logger::info(
                    'IMAGE DEBUG: INVALID IMAGE'
                        . ' url=' . $image_url
                );

                return 0;
            }

            $mime_type = $image_info['mime'];

            $file_name = $this->get_file_name(
                $image_url
            );

            /*
         * 3. Оптимизируем JPEG и PNG.
         */
            if (
                in_array(
                    $mime_type,
                    [
                        'image/jpeg',
                        'image/png',
                    ],
                    true
                )
            ) {
                $optimization = $this->optimize_image(
                    $tmp_file,
                    $mime_type,
                    $file_name
                );

                if (is_wp_error($optimization)) {
                    Logger::info(
                        'IMAGE DEBUG: OPTIMIZATION FAILED'
                            . ' url=' . $image_url
                            . ' error=' . $optimization->get_error_message()
                            . ' upload=skipped'
                    );

                    return 0;
                }
            }

            /*
         * 4. Проверяем файл перед передачей WordPress.
         */
            if (
                !is_file($tmp_file)
                || !is_readable($tmp_file)
            ) {
                Logger::info(
                    'IMAGE DEBUG: FINAL FILE MISSING'
                        . ' url=' . $image_url
                );

                return 0;
            }

            $final_size = filesize($tmp_file);

            if ($final_size === false || $final_size <= 0) {
                Logger::info(
                    'IMAGE DEBUG: FINAL FILE EMPTY'
                        . ' url=' . $image_url
                );

                return 0;
            }

            $file = [
                'name'     => $file_name,
                'type'     => $mime_type,
                'tmp_name' => $tmp_file,
                'error'    => 0,
                'size'     => $final_size,
            ];

            /*
         * 5. Создаём attachment.
         */
            $media_start = microtime(true);

            Logger::info(
                'IMAGE DEBUG: MEDIA START'
                    . ' size=' . size_format($final_size)
                    . ' memory='
                    . round(
                        memory_get_usage(true) / 1024 / 1024,
                        2
                    )
                    . ' MB'
            );

            /*
 * Ограничиваем генерацию размеров только
 * для текущего изображения поставщика.
 */
            $image_sizes_filter = static function (
                $new_sizes,
                $image_meta,
                $attachment_id
            ) {
                return array_intersect_key(
                    $new_sizes,
                    array_flip(self::IMPORT_IMAGE_SIZES)
                );
            };

            add_filter(
                'intermediate_image_sizes_advanced',
                $image_sizes_filter,
                PHP_INT_MAX,
                3
            );

            try {
                $attachment_id = @media_handle_sideload(
                    $file,
                    $product_id
                );
            } finally {
                remove_filter(
                    'intermediate_image_sizes_advanced',
                    $image_sizes_filter,
                    PHP_INT_MAX
                );
            }

            if (is_wp_error($attachment_id)) {
                Logger::info(
                    'IMAGE DEBUG: MEDIA ERROR'
                        . ' time='
                        . round(
                            microtime(true) - $media_start,
                            2
                        )
                        . ' sec'
                        . ' error='
                        . $attachment_id->get_error_message()
                );

                return 0;
            }

            Logger::info(
                'IMAGE DEBUG: MEDIA END'
                    . ' time='
                    . round(
                        microtime(true) - $media_start,
                        2
                    )
                    . ' sec'
                    . ' attachment_id=' . (int) $attachment_id
            );

            /*
         * 6. Сохраняем исходный URL поставщика.
         */
            update_post_meta(
                $attachment_id,
                self::SOURCE_URL_META,
                $image_url
            );

            /*
         * 7. Финальный лог.
         */
            Logger::info(
                'IMAGE DEBUG: END'
                    . ' attachment_id=' . $attachment_id
                    . ' size=' . size_format($final_size)
                    . ' total='
                    . round(
                        microtime(true) - $start,
                        2
                    )
                    . ' sec'
            );

            return (int) $attachment_id;
        } finally {

            /*
         * Удаляем оба временных файла.
         */
            $files_to_delete = array_unique([
                $original_tmp_file,
                $tmp_file,
            ]);

            foreach ($files_to_delete as $temporary_file) {
                if (
                    is_string($temporary_file)
                    && $temporary_file !== ''
                    && is_file($temporary_file)
                ) {
                    @unlink($temporary_file);
                }
            }
        }
    }


    /**
     * Оптимизирует JPEG и PNG через GD.
     *
     * JPEG:
     * - преобразование в sRGB;
     * - удаление метаданных и ICC-профиля;
     * - качество 82.
     *
     * PNG:
     * - сохраняет прозрачность;
     * - удаляет метаданные;
     * - сохраняет формат PNG.
     *
     * @param string $file_path Путь к файлу (передаётся по ссылке).
     * @param string $mime_type MIME-тип (передаётся по ссылке).
     * @param string $file_name Имя файла (передаётся по ссылке).
     *
     * @return true|\WP_Error
     */
    private function optimize_image(
        &$file_path,
        &$mime_type,
        &$file_name
    ) {
        $original_size = filesize($file_path);

        if ($original_size === false || $original_size <= 0) {
            return new \WP_Error(
                'image_empty',
                'Исходное изображение пустое.'
            );
        }

        $original_info = @getimagesize($file_path);

        if (!$original_info) {
            return new \WP_Error(
                'image_invalid',
                'Не удалось определить параметры изображения.'
            );
        }

        /*
     * Не перекодируем небольшие файлы.
     *
     * В них обычно нет существенной экономии,
     * а повторное JPEG-кодирование может увеличить размер.
     */
        $min_size = 100 * 1024;

        if ($original_size < $min_size) {
            Logger::info(
                'IMAGE DEBUG: OPTIMIZATION SKIPPED'
                    . ' original=' . size_format($original_size)
                    . ' reason=below_min_size'
                    . ' mime=' . $mime_type
            );

            return true;
        }

        /*
     * Определяем формат результата.
     */
        if ($mime_type === 'image/jpeg') {
            $optimized_extension = 'jpg';
            $quality = 82;
        } elseif ($mime_type === 'image/png') {
            $optimized_extension = 'png';
            $quality = 82;
        } else {
            return new \WP_Error(
                'unsupported_image_type',
                'Неподдерживаемый тип изображения.'
            );
        }

        /*
     * Создаём отдельный временный файл.
     */
        $optimized_file = $file_path
            . '.optimized.'
            . $optimized_extension;

        try {

            /*
         * 1. Открываем изображение через WordPress.
         */
            $image_editor = wp_get_image_editor($file_path);

            if (is_wp_error($image_editor)) {
                return new \WP_Error(
                    'image_editor_error',
                    $image_editor->get_error_message()
                );
            }

            /*
         * 2. Настраиваем качество.
         */
            $image_editor->set_quality($quality);

            /*
         * 3. Сохраняем в исходном формате.
         */
            $result = $image_editor->save(
                $optimized_file,
                $mime_type
            );

            if (is_wp_error($result)) {
                return new \WP_Error(
                    'image_save_error',
                    $result->get_error_message()
                );
            }

            /*
         * 4. Проверяем созданный файл.
         */
            if (
                !is_file($optimized_file)
                || !is_readable($optimized_file)
            ) {
                return new \WP_Error(
                    'optimized_file_missing',
                    'Оптимизированный файл не создан.'
                );
            }

            $optimized_size = filesize($optimized_file);

            if ($optimized_size === false || $optimized_size <= 0) {
                return new \WP_Error(
                    'optimized_file_empty',
                    'Оптимизированный файл пустой.'
                );
            }

            /*
         * 5. Проверяем формат и размеры результата.
         */
            $optimized_info = @getimagesize($optimized_file);

            if (
                !$optimized_info
                || empty($optimized_info['mime'])
                || $optimized_info['mime'] !== $mime_type
            ) {
                return new \WP_Error(
                    'optimized_file_invalid',
                    'Оптимизированный файл имеет неверный формат.'
                );
            }

            if (
                (int) $optimized_info[0] !== (int) $original_info[0]
                || (int) $optimized_info[1] !== (int) $original_info[1]
            ) {
                return new \WP_Error(
                    'optimized_dimensions_changed',
                    'Размеры изображения изменились после оптимизации.'
                );
            }

            /*
         * 6. Если результат не меньше оригинала,
         * оставляем исходный файл.
         */
            if ($optimized_size >= $original_size) {
                Logger::info(
                    'IMAGE DEBUG: OPTIMIZATION SKIPPED'
                        . ' original=' . size_format($original_size)
                        . ' optimized=' . size_format($optimized_size)
                        . ' reason=not_smaller'
                        . ' mime=' . $mime_type
                );

                return true;
            }

            /*
         * 7. Используем оптимизированный файл.
         *
         * Исходник не перезаписываем.
         */
            $file_path = $optimized_file;

            /*
         * JPEG может получить расширение .jpg.
         * PNG остаётся PNG.
         */
            $file_name = pathinfo(
                $file_name,
                PATHINFO_FILENAME
            ) . '.' . $optimized_extension;

            /*
         * 8. Логируем экономию.
         */
            $saved_percent = round(
                (
                    ($original_size - $optimized_size)
                    / $original_size
                ) * 100,
                1
            );

            Logger::info(
                'IMAGE DEBUG: OPTIMIZED'
                    . ' original=' . size_format($original_size)
                    . ' optimized=' . size_format($optimized_size)
                    . ' saved=' . $saved_percent . '%'
                    . ' mime=' . $mime_type
            );

            return true;
        } catch (\Throwable $e) {

            return new \WP_Error(
                'image_optimization_exception',
                $e->getMessage()
            );
        } finally {

            /*
         * Если файл не был выбран для загрузки,
         * удаляем его.
         */
            if (
                is_file($optimized_file)
                && $file_path !== $optimized_file
            ) {
                @unlink($optimized_file);
            }
        }
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
