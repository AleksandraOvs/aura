<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use WC_Product;

class Product_Meta
{
    /**
     * Meta key для идентификатора поставщика.
     */
    private const SUPPLIER_ID_META = '_supplier_id';

    /**
     * Meta key для идентификатора поставщика-системы.
     */
    private const SUPPLIER_META = '_supplier';

    /**
     * Meta key для штрихкода.
     */
    private const BARCODE_META = '_barcode';

    /**
     * Получить ID поставщика у товара.
     *
     * @param WC_Product|int $product
     *
     * @return string
     */
    public function get_supplier_id($product)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return '';
        }

        return (string) get_post_meta(
            $product_id,
            self::SUPPLIER_ID_META,
            true
        );
    }

    /**
     * Получить поставщика у товара.
     *
     * @param WC_Product|int $product
     *
     * @return string
     */
    public function get_supplier($product)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return '';
        }

        return (string) get_post_meta(
            $product_id,
            self::SUPPLIER_META,
            true
        );
    }

    /**
     * Сохранить ID поставщика.
     *
     * На данном этапе метод не вызывается автоматически.
     *
     * @param WC_Product|int $product
     * @param string         $supplier_id
     */
    public function set_supplier_id($product, $supplier_id)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return;
        }

        $supplier_id = trim((string) $supplier_id);

        if ($supplier_id === '') {
            delete_post_meta(
                $product_id,
                self::SUPPLIER_ID_META
            );

            return;
        }

        update_post_meta(
            $product_id,
            self::SUPPLIER_ID_META,
            $supplier_id
        );
    }

    /**
     * Сохранить поставщика.
     *
     * @param WC_Product|int $product
     * @param string         $supplier
     */
    public function set_supplier($product, $supplier)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return;
        }

        $supplier = trim((string) $supplier);

        if ($supplier === '') {
            delete_post_meta(
                $product_id,
                self::SUPPLIER_META
            );

            return;
        }

        update_post_meta(
            $product_id,
            self::SUPPLIER_META,
            $supplier
        );
    }

    /**
     * Получить ID товара.
     *
     * @param WC_Product|int $product
     *
     * @return int
     */
    private function get_product_id($product)
    {
        if ($product instanceof WC_Product) {
            return $product->get_id();
        }

        $product_id = absint($product);

        return $product_id > 0
            ? $product_id
            : 0;
    }

    /**
     * Получить штрихкод товара.
     *
     * @param WC_Product|int $product
     *
     * @return string
     */
    public function get_barcode($product)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return '';
        }

        return (string) get_post_meta(
            $product_id,
            self::BARCODE_META,
            true
        );
    }

    /**
     * Сохранить штрихкод товара.
     *
     * @param WC_Product|int $product
     * @param string         $barcode
     */
    public function set_barcode($product, $barcode)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return;
        }

        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            delete_post_meta(
                $product_id,
                self::BARCODE_META
            );

            return;
        }

        update_post_meta(
            $product_id,
            self::BARCODE_META,
            $barcode
        );
    }
}
