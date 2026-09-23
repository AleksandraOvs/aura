<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;
use WC_Product;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Product\Product_Meta;
use Supplier_Importer\Product\Product_Categories;
use Supplier_Importer\Product\Product_Attributes;
use Supplier_Importer\Product\Product_Images;

class Product_Updater
{
    private $product_meta;
    private $product_categories;
    private $product_attributes;
    private $product_images;

    public function __construct()
    {
        $this->product_meta = new Product_Meta();
        $this->product_categories = new Product_Categories();
        $this->product_attributes = new Product_Attributes();
        $this->product_images = new Product_Images();
    }

    /**
     * Обновить существующий товар.
     *
     * @param WC_Product  $product
     * @param Product_Data $product_data
     *
     * @return WC_Product
     */
    public function update(
        WC_Product $product,
        Product_Data $product_data
    ) {
        if (!$product instanceof WC_Product) {
            throw new InvalidArgumentException(
                'Product_Updater ожидает объект WC_Product.'
            );
        }

        $this->update_basic_data(
            $product,
            $product_data
        );

        $this->product_categories->assign(
            $product,
            $product_data->get('category', '')
        );

        $this->product_attributes->assign(
            $product,
            $product_data->get_attributes()
        );

        $product->save();

        $this->update_supplier_meta(
            $product,
            $product_data
        );

        $this->product_images->assign(
            $product,
            $product_data->get_images()
        );

        $product->save();

        return $product;
    }

    /**
     * Обновить основные данные товара.
     */
    private function update_basic_data(
        WC_Product $product,
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

        /*
         * Цена обновляется только если поставщик
         * действительно передал её.
         */
        if ($product_data->has_price()) {
            $product->set_regular_price(
                (string) $product_data->get_price()
            );
        }

        /*
         * Остаток обновляется только если поставщик
         * действительно передал его.
         */
        if ($product_data->has_stock()) {
            $product->set_manage_stock(true);

            $product->set_stock_quantity(
                $product_data->get_stock()
            );
        }
    }

    /**
     * Обновить мета-данные поставщика.
     */
    private function update_supplier_meta(
        WC_Product $product,
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
