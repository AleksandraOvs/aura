<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product_Simple;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Product\Product_Meta;
use Supplier_Importer\Product\Product_Categories;
use Supplier_Importer\Product\Product_Attributes;
use Supplier_Importer\Product\Product_Images;

class Product_Creator
{
    /**
     * Мета-данные товара.
     *
     * @var Product_Meta
     */
    private $product_meta;
    private $product_categories;
    private $product_attributes;
    private $product_images;

    /**
     * Конструктор.
     */
    public function __construct()
    {
        $this->product_meta = new Product_Meta();
        $this->product_categories = new Product_Categories();
        $this->product_attributes = new Product_Attributes();
        $this->product_images = new Product_Images();
    }

    /**
     * Создать товар WooCommerce из Product_Data.
     *
     * @param Product_Data $product_data
     *
     * @return WC_Product_Simple
     */
    public function create(Product_Data $product_data)
    {
        if (!class_exists('WooCommerce')) {
            throw new InvalidArgumentException(
                'WooCommerce не активен.'
            );
        }

        $sku = $product_data->get_sku();

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: START SKU=' . $sku
        );

        $start = microtime(true);

        $product = new WC_Product_Simple();

        $this->set_basic_data(
            $product,
            $product_data
        );

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: basic_data SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        $start = microtime(true);

        $product->save();

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: save_1 SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        $start = microtime(true);

        $this->set_supplier_meta(
            $product,
            $product_data
        );

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: supplier_meta SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        $start = microtime(true);

        $this->product_categories->assign(
            $product,
            $product_data->get('category', '')
        );

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: categories SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        $start = microtime(true);

        $this->product_attributes->assign(
            $product,
            $product_data->get_attributes()
        );

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: attributes SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        $start = microtime(true);

        $this->product_images->assign(
            $product,
            $product_data->get_images()
        );

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: images SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        $start = microtime(true);

        $product->save();

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: save_2 SKU=' . $sku
                . ', time=' . round(microtime(true) - $start, 2) . ' sec'
        );

        \Supplier_Importer\Core\Logger::info(
            'CREATE DEBUG: END SKU=' . $sku
        );

        return $product;
    }

    /**
     * Заполнить основные данные товара.
     *
     * @param WC_Product_Simple $product
     * @param Product_Data      $product_data
     */
    private function set_basic_data(
        WC_Product_Simple $product,
        Product_Data $product_data
    ) {
        $product->set_name(
            $product_data->get_name()
        );

        $product->set_sku(
            $product_data->get_sku()
        );

        $description = $product_data->get(
            'description',
            ''
        );

        if ($description !== '') {
            $product->set_description(
                $description
            );
        }

        /**
         * Цену устанавливаем только если поставщик
         * действительно передал её.
         */
        if ($product_data->has_price()) {
            $product->set_regular_price(
                (string) $product_data->get_price()
            );
        }

        /**
         * Остаток устанавливаем только если поставщик
         * действительно передал его.
         */
        if ($product_data->has_stock()) {
            $product->set_manage_stock(true);

            $product->set_stock_quantity(
                $product_data->get_stock()
            );
        }

        /**
         * На первом этапе создаём товар как черновик.
         * Публикацию будем контролировать отдельно
         * на уровне импорта.
         */
        $product->set_status('draft');
    }

    /**
     * Сохранить данные поставщика.
     *
     * @param WC_Product_Simple $product
     * @param Product_Data      $product_data
     */
    private function set_supplier_meta(
        WC_Product_Simple $product,
        Product_Data $product_data
    ) {
        $this->product_meta->set_supplier(
            $product,
            $product_data->get_supplier()
        );

        $this->product_meta->set_supplier_id(
            $product,
            $product_data->get_supplier_id()
        );

        $this->product_meta->set_barcode(
            $product,
            $product_data->get('barcode', '')
        );
    }
}
