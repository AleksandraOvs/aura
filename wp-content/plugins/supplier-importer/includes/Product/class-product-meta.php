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
     * Метка последнего запуска импорта, в котором встретился товар.
     */
    private const FEED_RUN_META = '_supplier_feed_run';

    /**
     * Причина снятия товара с публикации.
     */
    private const UNPUBLISHED_BY_SYNC_META = '_supplier_unpublished_by_sync';

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
            return;
        }

        update_post_meta(
            $product_id,
            self::BARCODE_META,
            $barcode
        );
    }

    /**
     * Получить метку последнего запуска фида.
     *
     * @param WC_Product|int $product
     *
     * @return string
     */
    public function get_feed_run($product)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return '';
        }

        return (string) get_post_meta(
            $product_id,
            self::FEED_RUN_META,
            true
        );
    }

    /**
     * Сохранить метку запуска фида.
     *
     * @param WC_Product|int $product
     * @param string         $run_id
     *
     * @return void
     */
    public function set_feed_run($product, $run_id)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return;
        }

        $run_id = trim((string) $run_id);

        if ($run_id === '') {
            delete_post_meta($product_id, self::FEED_RUN_META);
            return;
        }

        update_post_meta(
            $product_id,
            self::FEED_RUN_META,
            $run_id
        );
    }

    /**
     * Проверить, снял ли импортёр товар с публикации.
     *
     * @param WC_Product|int $product
     *
     * @return bool
     */
    public function was_unpublished_by_sync($product)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return false;
        }

        return get_post_meta(
            $product_id,
            self::UNPUBLISHED_BY_SYNC_META,
            true
        ) === 'yes';
    }

    /**
     * Установить или удалить отметку о снятии с публикации.
     *
     * @param WC_Product|int $product
     * @param bool           $value
     *
     * @return bool
     */
    public function set_unpublished_by_sync($product, $value)
    {
        $product_id = $this->get_product_id($product);

        if (!$product_id) {
            return false;
        }

        if ($value) {
            update_post_meta(
                $product_id,
                self::UNPUBLISHED_BY_SYNC_META,
                'yes'
            );

            return $this->was_unpublished_by_sync($product_id);
        }

        delete_post_meta(
            $product_id,
            self::UNPUBLISHED_BY_SYNC_META
        );

        return !$this->was_unpublished_by_sync($product_id);
    }
}
