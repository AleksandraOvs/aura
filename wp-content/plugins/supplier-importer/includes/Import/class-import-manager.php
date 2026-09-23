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
     * Импортировать товары.
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
}
