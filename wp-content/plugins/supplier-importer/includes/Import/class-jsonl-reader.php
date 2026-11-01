<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Jsonl_Reader
{
    /**
     * Прочитать порцию товаров из JSONL.
     *
     * @param string $file_path Путь к staging-файлу.
     * @param int    $cursor    Байтовая позиция начала чтения.
     * @param int    $limit     Максимальное количество товаров.
     *
     * @return array
     */
    public function read_batch($file_path, $cursor = 0, $limit = 50)
    {
        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new \RuntimeException(
                'Staging-файл не найден или недоступен для чтения.'
            );
        }

        $file_size = filesize($file_path);

        if ($file_size === false) {
            throw new \RuntimeException(
                'Не удалось определить размер staging-файла.'
            );
        }

        $cursor = max(0, (int) $cursor);
        $limit = max(1, (int) $limit);

        if ($cursor > $file_size) {
            throw new \RuntimeException(
                'Позиция чтения выходит за пределы staging-файла.'
            );
        }

        $handle = fopen($file_path, 'rb');

        if (!$handle) {
            throw new \RuntimeException(
                'Не удалось открыть staging-файл.'
            );
        }

        try {
            if (fseek($handle, $cursor) !== 0) {
                throw new \RuntimeException(
                    'Не удалось перейти к позиции чтения staging-файла.'
                );
            }

            $items = [];

            while (
                count($items) < $limit
                && ftell($handle) < $file_size
            ) {
                $line = fgets($handle);

                if ($line === false) {
                    break;
                }

                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $item = json_decode($line, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException(
                        'Ошибка чтения staging-файла: '
                            . json_last_error_msg()
                    );
                }

                if (!is_array($item)) {
                    throw new \RuntimeException(
                        'Некорректная запись в staging-файле.'
                    );
                }

                $items[] = $item;
            }

            $new_cursor = ftell($handle);

            return [
                'items' => $items,
                'cursor' => $new_cursor,
                'finished' => $new_cursor >= $file_size,
            ];
        } finally {
            fclose($handle);
        }
    }
}
