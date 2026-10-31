<?php

namespace Supplier_Importer\AJAX;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Suppliers\Feed_Sync;
use Supplier_Importer\Suppliers\Supplier_Feed_Settings;

class Ajax_Yml_Upload
{
    /**
     * Максимальный размер файла: 256 МБ.
     */
    private const MAX_FILE_SIZE = 268435456;

    public function __construct()
    {
        add_action(
            'wp_ajax_supplier_import_yml_upload_start',
            [$this, 'start_import']
        );
    }

    /**
     * Обработать ручную загрузку YML-файла.
     */
    public function start_import()
    {
        // Проверка прав.
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => 'Недостаточно прав для импорта.',
            ], 403);
        }

        // Проверка nonce.
        $nonce = isset($_POST['nonce'])
            ? sanitize_text_field(wp_unslash($_POST['nonce']))
            : '';

        if (!wp_verify_nonce($nonce, 'supplier_import')) {
            wp_send_json_error([
                'message' => 'Ошибка проверки безопасности.',
            ], 403);
        }

        // Пока поддерживается только Maytoni.
        $supplier_id = 'maytoni';

        // Проверка наличия файла.
        if (
            !isset($_FILES['file'])
            || !is_array($_FILES['file'])
        ) {
            wp_send_json_error([
                'message' => 'Файл не передан.',
            ], 400);
        }

        $upload = $_FILES['file'];
        if (
            !isset($upload['error'], $upload['tmp_name'], $upload['name'], $upload['size'])
            || !is_string($upload['tmp_name'])
            || !is_string($upload['name'])
            || !is_numeric($upload['size'])
        ) {
            wp_send_json_error([
                'message' => 'Некорректные данные загруженного файла.',
            ], 400);
        }

        // Проверка ошибки загрузки.
        if (
            !isset($upload['error'])
            || $upload['error'] !== UPLOAD_ERR_OK
        ) {
            wp_send_json_error([
                'message' => $this->get_upload_error(
                    $upload['error'] ?? UPLOAD_ERR_NO_FILE
                ),
            ], 400);
        }

        // Проверка временного файла.
        if (
            empty($upload['tmp_name'])
            || !is_uploaded_file($upload['tmp_name'])
        ) {
            wp_send_json_error([
                'message' => 'Некорректный загруженный файл.',
            ], 400);
        }

        // Проверка расширения.
        $file_name = sanitize_file_name(
            wp_unslash($upload['name'] ?? '')
        );

        if (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) !== 'yml') {
            wp_send_json_error([
                'message' => 'Допускаются только файлы с расширением .yml.',
            ], 400);
        }

        // Проверка размера.
        $file_size = (int) ($upload['size'] ?? 0);

        if (
            $file_size <= 0
            || $file_size >= self::MAX_FILE_SIZE
        ) {
            wp_send_json_error([
                'message' => 'Размер файла должен быть меньше 256 МБ.',
            ], 400);
        }

        // Создание временного файла.
        $file_path = wp_tempnam('supplier-yml-upload');

        if (!$file_path) {
            wp_send_json_error([
                'message' => 'Не удалось создать временный файл.',
            ], 500);
        }

        // Перемещение загруженного файла.
        if (!move_uploaded_file($upload['tmp_name'], $file_path)) {
            wp_delete_file($file_path);

            wp_send_json_error([
                'message' => 'Не удалось сохранить загруженный файл.',
            ], 500);
        }

        $result = null;
        $error = null;

        try {
            clearstatcache(true, $file_path);

            $actual_size = filesize($file_path);

            if (
                $actual_size === false
                || $actual_size <= 0
                || $actual_size >= self::MAX_FILE_SIZE
            ) {
                throw new \RuntimeException(
                    'Файл пуст или превышает ограничение 256 МБ.'
                );
            }

            // Настройки импорта Maytoni.
            $settings = Supplier_Feed_Settings::get($supplier_id);
            $settings['format'] = 'yml';

            // Полноценный импорт и синхронизация.
            $feed_sync = new Feed_Sync();

            $result = $feed_sync->process_feed_file(
                $supplier_id,
                $file_path,
                $settings,
                'manual',
                $file_name
            );
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
        } finally {
            // Удаляем временный файл независимо от результата.
            wp_delete_file($file_path);
        }

        if ($error !== null) {
            wp_send_json_error([
                'message' => $error,
            ], 500);
        }

        wp_send_json_success([
            'import_id' => $result['import_id'],
            'status'    => $result['status'],
            'result'    => [
                'total'   => (int) $result['result']['total'],
                'created' => (int) $result['result']['created'],
                'updated' => (int) $result['result']['updated'],
                'skipped' => (int) $result['result']['skipped'],
                'errors'  => (int) $result['result']['errors'],
            ],
        ]);
    }

    /**
     * Получить сообщение об ошибке загрузки.
     */
    private function get_upload_error($error)
    {
        switch ((int) $error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Размер файла превышает допустимый лимит сервера.';

            case UPLOAD_ERR_PARTIAL:
                return 'Файл загружен не полностью.';

            case UPLOAD_ERR_NO_FILE:
                return 'Файл не выбран.';

            case UPLOAD_ERR_NO_TMP_DIR:
                return 'На сервере отсутствует временная директория.';

            case UPLOAD_ERR_CANT_WRITE:
                return 'Не удалось записать файл на сервер.';

            case UPLOAD_ERR_EXTENSION:
                return 'Загрузка файла остановлена расширением PHP.';

            default:
                return 'Неизвестная ошибка загрузки файла.';
        }
    }
}
