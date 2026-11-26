<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use WC_Product;
use Supplier_Importer\Product\Product_Data;
use Supplier_Importer\Product\Product_Finder;

class Product_Importer
{
    private $finder;
    private $creator;
    private $updater;

    public function __construct(
        $process_categories = true,
        $process_attributes = true
    ) {
        $this->finder = new Product_Finder();

        $this->creator = new Product_Creator(
            $process_categories,
            $process_attributes
        );

        $this->updater = new Product_Updater(
            $process_categories,
            $process_attributes
        );
    }

    /**
     * Импортировать один товар.
     *
     * @param Product_Data $product_data
     *
     * @return array
     */
    public function import(Product_Data $product_data)
    {
        $existing_product = $this->finder->find(
            $product_data
        );

        if ($existing_product instanceof WC_Product) {
            $product = $this->updater->update(
                $existing_product,
                $product_data
            );

            return [
                'action'  => 'updated',
                'product' => $product,
            ];
        }

        $product = $this->creator->create(
            $product_data
        );

        return [
            'action'  => 'created',
            'product' => $product,
        ];
    }
}
