<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use Supplier_Importer\Product\Product_Data;

class Import_Manager
{
    private $product_importer;

    public function __construct()
    {
        $this->product_importer = new Product_Importer();
    }

    /**
     * Импортировать товары из массива.
     *
     * @param Product_Data[] $products
     *
     * @return array
     */
    public function import($products)
    {
        if (!is_array($products)) {
            throw new InvalidArgumentException(
                'Import_Manager ожидает массив товаров.'
            );
        }

        $result = [
            'total'   => count($products),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors'  => 0,
            'items'   => [],
        ];

        foreach ($products as $index => $product_data) {
            if (!$product_data instanceof Product_Data) {
                $result['errors']++;

                $result['items'][] = [
                    'index'   => $index,
                    'action'  => 'error',
                    'sku'     => '',
                    'message' => 'Некорректный объект Product_Data.',
                ];

                continue;
            }

            try {
                $import_result = $this->product_importer->import(
                    $product_data
                );

                $action = $import_result['action'];

                if ($action === 'created') {
                    $result['created']++;
                } elseif ($action === 'updated') {
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }

                $result['items'][] = [
                    'index'      => $index,
                    'action'     => $action,
                    'product_id' => $import_result['product']->get_id(),
                    'sku'        => $product_data->get_sku(),
                ];
            } catch (\Throwable $e) {
                $result['errors']++;

                $result['items'][] = [
                    'index'   => $index,
                    'action'  => 'error',
                    'sku'     => $product_data->get_sku(),
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * Потоковый импорт товаров из Row_Reader.
     *
     * При критической ошибке чтения или закрытия возвращает
     * накопленные счётчики и описание ошибки в fatal_error.
     *
     * Ошибки отдельных товаров остаются в items.
     *
     * @param Row_Reader    $reader      Читатель записей.
     * @param callable      $mapper      Преобразует запись в Product_Data.
     * @param callable|null $on_success  Дополнительные действия после импорта.
     *
     * @return array
     */
    public function import_stream(
        Row_Reader $reader,
        callable $mapper,
        ?callable $on_success = null
    ) {
        $result = [
            'total'       => 0,
            'created'     => 0,
            'updated'     => 0,
            'skipped'     => 0,
            'errors'      => 0,
            'items'       => [],
            'fatal_error' => '',
        ];

        $reader_opened = false;

        try {
            $reader->open();
            $reader_opened = true;

            while (true) {
                $record = $reader->read();

                if ($record === null) {
                    break;
                }

                $index = $result['total'];
                $result['total']++;

                $sku = '';

                try {
                    $product_data = $mapper($record);

                    if (!$product_data instanceof Product_Data) {
                        throw new \UnexpectedValueException(
                            'Маппер не вернул объект Product_Data.'
                        );
                    }

                    $sku = $product_data->get_sku();

                    $import_result = $this->product_importer->import(
                        $product_data
                    );

                    if (
                        !is_array($import_result)
                        || !isset($import_result['action'])
                        || !isset($import_result['product'])
                        || !$import_result['product'] instanceof \WC_Product
                    ) {
                        throw new \UnexpectedValueException(
                            'Импортёр вернул некорректный результат.'
                        );
                    }

                    $action = $import_result['action'];

                    if (
                        !in_array(
                            $action,
                            ['created', 'updated', 'skipped'],
                            true
                        )
                    ) {
                        throw new \UnexpectedValueException(
                            'Импортёр вернул неизвестное действие.'
                        );
                    }

                    if ($on_success !== null) {
                        $on_success(
                            $import_result['product'],
                            $product_data,
                            $action
                        );
                    }

                    if ($action === 'created') {
                        $result['created']++;
                    } elseif ($action === 'updated') {
                        $result['updated']++;
                    } else {
                        $result['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $result['errors']++;

                    $result['items'][] = [
                        'index'   => $index,
                        'action'  => 'error',
                        'sku'     => $sku,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            $result['fatal_error'] = $e->getMessage();

            \Supplier_Importer\Core\Logger::error(
                'Критическая ошибка потокового импорта.',
                [
                    'message' => $e->getMessage(),
                ]
            );
        } finally {
            if ($reader_opened) {
                try {
                    $reader->close();
                } catch (\Throwable $e) {
                    \Supplier_Importer\Core\Logger::error(
                        'Ошибка закрытия читателя потокового импорта.',
                        [
                            'message' => $e->getMessage(),
                        ]
                    );

                    if ($result['fatal_error'] === '') {
                        $result['fatal_error'] = $e->getMessage();
                    }
                }
            }
        }

        return $result;
    }
}
