<?php

namespace Supplier_Importer\CSV;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Core\Logger;
use Supplier_Importer\Core\Plugin;

class Csv_Pipeline_Test
{
    /**
     * Запустить тест CSV pipeline.
     *
     * @param string $file
     *
     * @return array
     */
    public static function run($file)
    {
        Logger::info(
            '=== CSV PIPELINE TEST START ==='
        );

        /*
         * ---------------------------------------------------------
         * 1. Проверяем CSV
         * ---------------------------------------------------------
         */

        $validator = new Csv_Validator();

        $validation = $validator->validate($file);

        Logger::info(
            'Результат проверки CSV.',
            [
                'valid' => $validation['valid'],
                'encoding' => $validation['encoding'],
                'delimiter' => $validation['delimiter'],
                'columns_count' => $validation['columns_count'],
                'data_rows' => $validation['data_rows'],
            ]
        );

        if (!$validation['valid']) {
            Logger::error(
                'CSV не прошёл валидацию.',
                [
                    'errors' => $validation['errors'],
                ]
            );

            return [
                'success' => false,
                'stage' => 'validation',
                'result' => $validation,
            ];
        }

        /*
         * ---------------------------------------------------------
         * 2. Определяем поставщика
         * ---------------------------------------------------------
         */

        $supplier_manager = Plugin::get_supplier_manager();

        if (!$supplier_manager) {
            Logger::error(
                'Supplier Manager не инициализирован.'
            );

            return [
                'success' => false,
                'stage' => 'supplier_manager',
            ];
        }

        $supplier = $supplier_manager->detect(
            $validation['headers']
        );

        if (!$supplier) {
            Logger::error(
                'Не удалось определить поставщика.',
                [
                    'headers' => $validation['headers'],
                ]
            );

            return [
                'success' => false,
                'stage' => 'supplier_detection',
                'headers' => $validation['headers'],
            ];
        }

        Logger::info(
            'Поставщик определён.',
            [
                'id' => $supplier->get_id(),
                'name' => $supplier->get_name(),
            ]
        );

        /*
         * ---------------------------------------------------------
         * 3. Открываем CSV Reader
         * ---------------------------------------------------------
         */

        $reader = new Csv_Reader(
            $file,
            $validation['delimiter']
        );

        try {
            $reader->open();
        } catch (\Throwable $e) {
            Logger::error(
                'Не удалось открыть CSV Reader.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return [
                'success' => false,
                'stage' => 'reader',
                'error' => $e->getMessage(),
            ];
        }

        /*
         * ---------------------------------------------------------
         * 4. Читаем первую запись
         * ---------------------------------------------------------
         */

        $row = $reader->read();

        if (!$row) {
            $reader->close();

            Logger::error(
                'CSV не содержит записей.'
            );

            return [
                'success' => false,
                'stage' => 'reader',
            ];
        }

        Logger::info(
            'Первая запись CSV прочитана.',
            [
                'row_number' => $row['row_number'],
            ]
        );

        Logger::info(
            '=== ПОЛЯ ПЕРВОЙ ЗАПИСИ ===',
            $row['data']
        );

        Logger::info(
            '=== КОНЕЦ ПОЛЕЙ ПЕРВОЙ ЗАПИСИ ==='
        );

        /*
         * ---------------------------------------------------------
         * 5. Получаем mapper
         * ---------------------------------------------------------
         */

        $mapper = $supplier->get_mapper();

        if (!$mapper) {
            $reader->close();

            Logger::error(
                'У поставщика отсутствует mapper.'
            );

            return [
                'success' => false,
                'stage' => 'mapper',
            ];
        }

        /*
         * ---------------------------------------------------------
         * 6. Преобразуем запись
         * ---------------------------------------------------------
         */

        try {
            $product = $mapper->map(
                $row['data']
            );
        } catch (\Throwable $e) {
            $reader->close();

            Logger::error(
                'Ошибка mapper.',
                [
                    'message' => $e->getMessage(),
                    'row_number' => $row['row_number'],
                ]
            );

            return [
                'success' => false,
                'stage' => 'mapping',
                'error' => $e->getMessage(),
            ];
        }

        /*
         * ---------------------------------------------------------
         * 7. Закрываем CSV
         * ---------------------------------------------------------
         */

        $reader->close();

        /*
         * ---------------------------------------------------------
         * 8. Выводим нормализованный товар
         * ---------------------------------------------------------
         */

        Logger::info(
            'Нормализованный товар.',
            $product->to_array()
        );

        Logger::info(
            '=== CSV PIPELINE TEST END ==='
        );

        return [
            'success' => true,
            'supplier' => $supplier->get_id(),
            'row_number' => $row['row_number'],
            'product' => $product,
        ];
    }
}
