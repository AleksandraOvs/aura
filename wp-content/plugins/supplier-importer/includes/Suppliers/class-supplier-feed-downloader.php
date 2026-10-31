<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use WP_Error;

class Supplier_Feed_Downloader
{
    /**
     * Максимальный размер фида: 256 МБ.
     */
    private const MAX_FILE_SIZE = 268435456;

    /**
     * Максимальное время ожидания ответа.
     */
    private const TIMEOUT = 60;

    /**
     * Скачать фид во временный файл.
     *
     * @param string $url URL фида.
     * @return string|WP_Error Путь к файлу или ошибка.
     */
    public function download($url)
    {
        $url = esc_url_raw(trim((string) $url));

        if (!$url || !wp_http_validate_url($url)) {
            return new WP_Error(
                'invalid_feed_url',
                'Некорректный URL фида.'
            );
        }

        $scheme = strtolower(
            (string) wp_parse_url($url, PHP_URL_SCHEME)
        );

        if (!in_array($scheme, ['http', 'https'], true)) {
            return new WP_Error(
                'invalid_feed_scheme',
                'Разрешены только HTTP и HTTPS.'
            );
        }

        $file = wp_tempnam('supplier-feed');

        if (!$file) {
            return new WP_Error(
                'feed_temp_file_error',
                'Не удалось создать временный файл.'
            );
        }

        $response = wp_safe_remote_get(
            $url,
            [
                'timeout'             => self::TIMEOUT,
                'redirection'         => 3,
                'stream'              => true,
                'filename'            => $file,
                'limit_response_size' => self::MAX_FILE_SIZE,
                'reject_unsafe_urls'  => true,
                'headers'             => [
                    'Accept' => 'text/csv, application/xml, text/xml, '
                        . 'application/yaml, text/yaml, */*',
                ],
            ]
        );

        if (is_wp_error($response)) {
            $this->delete_temp_file($file);

            return new WP_Error(
                'feed_download_error',
                'Не удалось скачать фид: ' . $response->get_error_message()
            );
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            $this->delete_temp_file($file);

            return new WP_Error(
                'feed_http_error',
                sprintf(
                    'Сервер фида вернул HTTP %d.',
                    $status
                )
            );
        }

        clearstatcache(true, $file);

        $size = is_file($file) ? filesize($file) : false;

        if ($size === false || $size === 0) {
            $this->delete_temp_file($file);

            return new WP_Error(
                'feed_empty_file',
                'Скачанный фид пуст или не удалось прочитать файл.'
            );
        }

        if ($size >= self::MAX_FILE_SIZE) {
            $this->delete_temp_file($file);

            return new WP_Error(
                'feed_too_large',
                'Размер фида достиг ограничения 256 МБ. '
                    . 'Импорт отменён.'
            );
        }

        return $file;
    }

    /**
     * Удалить временный файл.
     */
    public function delete_temp_file($file)
    {
        if (is_string($file) && is_file($file)) {
            wp_delete_file($file);
        }
    }
}
