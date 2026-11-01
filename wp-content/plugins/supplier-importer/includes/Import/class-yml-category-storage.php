<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Yml_Category_Storage
{
    /**
     * Сохранить категории в JSON.
     */
    public function save($file_path, array $categories)
    {
        $json = wp_json_encode(
            $categories,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new \RuntimeException(
                'Не удалось сериализовать категории YML.'
            );
        }

        if (
            file_put_contents($file_path, $json, LOCK_EX)
            !== strlen($json)
        ) {
            throw new \RuntimeException(
                'Не удалось сохранить категории YML.'
            );
        }

        return $file_path;
    }

    /**
     * Загрузить категории из JSON.
     */
    public function load($file_path)
    {
        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new \RuntimeException(
                'Файл категорий не найден или недоступен для чтения.'
            );
        }

        $json = file_get_contents($file_path);

        if ($json === false) {
            throw new \RuntimeException(
                'Не удалось прочитать файл категорий.'
            );
        }

        $categories = json_decode($json, true);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || !is_array($categories)
            || empty($categories)
        ) {
            throw new \UnexpectedValueException(
                'Файл категорий содержит некорректные данные.'
            );
        }

        return $categories;
    }
}
