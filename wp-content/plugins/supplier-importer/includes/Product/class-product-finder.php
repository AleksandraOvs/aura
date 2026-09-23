<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use WC_Product;

class Product_Finder
{
    /**
     * Мета-поле поставщика.
     */
    private const SUPPLIER_META = '_supplier';

    /**
     * Мета-поле ID товара у поставщика.
     */
    private const SUPPLIER_ID_META = '_supplier_id';

    /**
     * Мета-поле штрихкода.
     */
    private const BARCODE_META = '_barcode';

    /**
     * Найти товар по SKU.
     *
     * @param string $sku
     *
     * @return WC_Product|null
     */
    public function find_by_sku($sku)
    {
        $sku = trim((string) $sku);

        if ($sku === '') {
            return null;
        }

        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            return null;
        }

        $product = wc_get_product($product_id);

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }

    /**
     * Найти товар по поставщику и ID товара у поставщика.
     *
     * @param string $supplier
     * @param string $supplier_id
     *
     * @return WC_Product|null
     */
    public function find_by_supplier($supplier, $supplier_id)
    {
        $supplier = trim((string) $supplier);
        $supplier_id = trim((string) $supplier_id);

        if ($supplier === '' || $supplier_id === '') {
            return null;
        }

        $query = new \WP_Query([
            'post_type'      => [
                'product',
                'product_variation',
            ],
            'post_status'    => [
                'publish',
                'draft',
                'pending',
                'private',
            ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,

            'meta_query'     => [
                'relation' => 'AND',

                [
                    'key'     => self::SUPPLIER_META,
                    'value'   => $supplier,
                    'compare' => '=',
                ],

                [
                    'key'     => self::SUPPLIER_ID_META,
                    'value'   => $supplier_id,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return null;
        }

        $product_id = (int) $query->posts[0];

        $product = wc_get_product($product_id);

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }

    /**
     * Найти товар по штрихкоду.
     *
     * @param string $barcode
     *
     * @return WC_Product|null
     */
    public function find_by_barcode($barcode)
    {
        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            return null;
        }

        $query = new \WP_Query([
            'post_type'      => [
                'product',
                'product_variation',
            ],
            'post_status'    => [
                'publish',
                'draft',
                'pending',
                'private',
            ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,

            'meta_query'     => [
                [
                    'key'     => self::BARCODE_META,
                    'value'   => $barcode,
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return null;
        }

        $product_id = (int) $query->posts[0];

        $product = wc_get_product($product_id);

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }

    /**
     * Найти товар по данным поставщика.
     *
     * Порядок поиска:
     *
     * 1. SKU
     * 2. Поставщик + ID товара у поставщика
     * 3. Штрихкод
     *
     * @param Product_Data $product_data
     *
     * @return WC_Product|null
     */
    public function find(Product_Data $product_data)
    {
        /*
         * 1. Поиск по SKU.
         */
        $sku = $product_data->get_sku();

        if ($sku !== '') {
            $product = $this->find_by_sku($sku);

            if ($product) {
                return $product;
            }
        }

        /*
         * 2. Поиск по поставщику и ID товара.
         */
        $supplier = $product_data->get_supplier();
        $supplier_id = $product_data->get_supplier_id();

        if ($supplier !== '' && $supplier_id !== '') {
            $product = $this->find_by_supplier(
                $supplier,
                $supplier_id
            );

            if ($product) {
                return $product;
            }
        }

        /*
         * 3. Поиск по штрихкоду.
         */
        $barcode = $product_data->get('barcode');

        if ($barcode !== '') {
            $product = $this->find_by_barcode($barcode);

            if ($product) {
                return $product;
            }
        }

        /*
         * Товар не найден.
         */
        return null;
    }

    /**
     * Проверить существование товара по SKU.
     *
     * @param string $sku
     *
     * @return bool
     */
    public function exists_by_sku($sku)
    {
        return $this->find_by_sku($sku) !== null;
    }

    /**
     * Проверить существование товара по поставщику.
     *
     * @param string $supplier
     * @param string $supplier_id
     *
     * @return bool
     */
    public function exists_by_supplier($supplier, $supplier_id)
    {
        return $this->find_by_supplier(
            $supplier,
            $supplier_id
        ) !== null;
    }

    /**
     * Проверить существование товара по штрихкоду.
     *
     * @param string $barcode
     *
     * @return bool
     */
    public function exists_by_barcode($barcode)
    {
        return $this->find_by_barcode($barcode) !== null;
    }
}
