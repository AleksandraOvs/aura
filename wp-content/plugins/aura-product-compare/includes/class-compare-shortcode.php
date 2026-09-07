<?php

if (!defined('ABSPATH')) {
    exit;
}

class AURA_Product_Compare_Shortcode
{

    private $compare;


    public function __construct()
    {

        $this->compare = new AURA_Product_Compare();

        add_shortcode(
            'aura_compare',
            [$this, 'render']
        );
    }


    public function render()
    {

        $product_ids = $this->compare->get_products();

        $products = [];

        foreach ($product_ids as $product_id) {

            $product = wc_get_product($product_id);

            if ($product) {
                $products[] = $product;
            }
        }

        $compare_attributes = $this->compare->get_compare_attributes($products);

        /*
         * Характеристики для сравнения
         */
        $compare_attributes = $this->compare->get_compare_attributes($products);

        /*
         * Категории товаров, находящихся в сравнении
         */
        $compare_categories = $this->compare->get_compare_categories($products);

        ob_start();

        include AURA_COMPARE_PATH . 'templates/compare.php';

        return ob_get_clean();
    }
}
