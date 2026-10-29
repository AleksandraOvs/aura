<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

class Feed_Downloader
{
    /**
     * Максимальный размер фида — 200 МБ.
     */
    private const MAX_FILE_SIZE = 209715200;

    /**
     * Максимальное время ожидания ответа — 60 секунд.
     */
    private const TIMEOUT = 60;

    /**
     * Скачать фид во временный файл.
     *
     * @param string $url URL фида поставщика.
     *
     * @return string|\WP_Error Путь к файлу или ошибка.
     */
    public function download($url)
    {
        $url = esc_url_raw(trim($url));

        if (
            !$url
            || !wp_http_validate_url($url)
            || !in_array(
                strtolower((string) wp_parse_url($url, PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            return new \WP_Error(
                'invalid_feed_url',
                'Указан некорректный URL фида.'
            );
        }

        $file_path = wp_tempnam('supplier-feed');

        if (!$file_path) {
            return new \WP_Error(
                'feed_temp_file_error',
                'Не удалось создать временный файл для фида.'
            );
        }

        $response = wp_safe_remote_get(
            $url,
            [
                'timeout'             => self::TIMEOUT,
                'redirection'         => 3,
                'stream'              => true,
                'filename'            => $file_path,
                'limit_response_size' => self::MAX_FILE_SIZE,
                'reject_unsafe_urls'  => true,
                'headers'             => [
                    'Accept' => 'text/csv, text/xml, application/xml, '
                        . 'application/yaml, text/yaml, */*',
                ],
            ]
        );

        if (is_wp_error($response)) {
            $this->delete_temp_file($file_path);

            return new \WP_Error(
                'feed_download_error',
                'Не удалось скачать фид: ' . $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code < 200 || $status_code >= 300) {
            $this->delete_temp_file($file_path);

            return new \WP_Error(
                'feed_http_error',
                sprintf(
                    'Сервер поставщика вернул HTTP %d.',
                    $status_code
                )
            );
        }

        clearstatcache(true, $file_path);

        if (!is_file($file_path)) {
            $this->delete_temp_file($file_path);

            return new \WP_Error(
                'feed_file_missing',
                'Скачанный файл не найден.'
            );
        }

        $file_size = filesize($file_path);

        if ($file_size === false || $file_size === 0) {
            $this->delete_temp_file($file_path);

            return new \WP_Error(
                'feed_empty',
                'Поставщик вернул пустой файл.'
            );
        }

        if ($file_size > self::MAX_FILE_SIZE) {
            $this->delete_temp_file($file_path);

            return new \WP_Error(
                'feed_too_large',
                'Размер фида превышает допустимые 200 МБ.'
            );
        }

        return $file_path;
    }

    /**
     * Удалить временный файл.
     *
     * Вызывается после обработки фида.
     */
    public function delete_temp_file($file_path)
    {
        if (
            is_string($file_path)
            && $file_path !== ''
            && is_file($file_path)
        ) {
            wp_delete_file($file_path);
        }
    }
}
