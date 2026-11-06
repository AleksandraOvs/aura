<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Logger;

class Staging_Cleaner
{
    /**
     * Нормализует путь для сравнения.
     */
    private static function normalize_path($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Сравнивает пути с учётом регистра файловой системы.
     */
    private static function paths_equal($path_a, $path_b)
    {
        $path_a = self::normalize_path($path_a);
        $path_b = self::normalize_path($path_b);

        if (DIRECTORY_SEPARATOR === '\\') {
            return strtolower($path_a) === strtolower($path_b);
        }

        return $path_a === $path_b;
    }

    /**
     * Удаляет staging-файл и файл категорий завершённой сессии.
     *
     * Разрешены только UUID-файлы непосредственно
     * в uploads/supplier-importer.
     */
    public static function clean(Import_Session $session)
    {
        if (!$session->is_finished()) {
            return false;
        }

        $staging_file = trim($session->get_staging_file());

        if ($staging_file === '') {
            return true;
        }

        $upload_dir = wp_upload_dir();

        if (
            !empty($upload_dir['error'])
            || empty($upload_dir['basedir'])
        ) {
            Logger::warning(
                'Не удалось определить каталог staging-файлов.',
                [
                    'import_id' => $session->get_import_id(),
                    'error'     => $upload_dir['error'] ?? 'Неизвестная ошибка',
                ]
            );

            return false;
        }

        $allowed_dir = realpath(
            trailingslashit($upload_dir['basedir']) . 'supplier-importer'
        );

        $filename = basename(
            str_replace('\\', '/', $staging_file)
        );

        if (
            !$allowed_dir
            || !preg_match('/^[a-f0-9-]{36}\.jsonl$/i', $filename)
            || is_link($staging_file)
        ) {
            Logger::warning(
                'Пропущена очистка staging-файла: недопустимый путь.',
                [
                    'import_id' => $session->get_import_id(),
                    'file'      => $staging_file,
                ]
            );

            return false;
        }

        $real_staging_file = realpath($staging_file);

        if (
            !$real_staging_file
            || !is_file($real_staging_file)
            || !self::paths_equal(
                dirname($real_staging_file),
                $allowed_dir
            )
        ) {
            Logger::warning(
                'Пропущена очистка staging-файла: файл находится вне разрешённого каталога.',
                [
                    'import_id' => $session->get_import_id(),
                    'file'      => $staging_file,
                ]
            );

            return false;
        }

        $categories_file = $real_staging_file . '.categories.json';

        $files = [
            $real_staging_file,
            $categories_file,
        ];

        $success = true;

        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                continue;
            }

            if (is_link($file)) {
                $success = false;

                Logger::warning(
                    'Пропущено удаление временного файла: обнаружена символическая ссылка.',
                    [
                        'import_id' => $session->get_import_id(),
                        'file'      => $file,
                    ]
                );

                continue;
            }

            $real_file = realpath($file);

            if (
                !$real_file
                || !is_file($real_file)
                || !self::paths_equal(
                    dirname($real_file),
                    $allowed_dir
                )
            ) {
                $success = false;

                Logger::warning(
                    'Пропущено удаление временного файла: недопустимый путь.',
                    [
                        'import_id' => $session->get_import_id(),
                        'file'      => $file,
                    ]
                );

                continue;
            }

            if (!@unlink($real_file)) {
                $success = false;

                Logger::warning(
                    'Не удалось удалить временный файл импорта.',
                    [
                        'import_id' => $session->get_import_id(),
                        'file'      => $file,
                    ]
                );
            }
        }

        return $success;
    }
}
