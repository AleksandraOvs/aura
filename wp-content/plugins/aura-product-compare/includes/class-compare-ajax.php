<?php

if (!defined('ABSPATH')) {
    exit;
}

class AURA_Product_Compare_Ajax
{

    private $compare;


    public function __construct()
    {

        $this->compare = new AURA_Product_Compare();

        add_action(
            'wp_ajax_aura_compare_add',
            [$this, 'add_product']
        );

        add_action(
            'wp_ajax_nopriv_aura_compare_add',
            [$this, 'add_product']
        );


        add_action(
            'wp_ajax_aura_compare_remove',
            [$this, 'remove_product']
        );

        add_action(
            'wp_ajax_nopriv_aura_compare_remove',
            [$this, 'remove_product']
        );
    }


    /**
     * Добавление
     */
    public function add_product()
    {

        check_ajax_referer(
            'aura_compare_nonce',
            'nonce'
        );

        $product_id = isset($_POST['product_id'])
            ? absint($_POST['product_id'])
            : 0;

        if (!$product_id) {
            wp_send_json_error([
                'message' => 'Некорректный товар.'
            ]);
        }


        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error([
                'message' => 'Товар не найден.'
            ]);
        }


        $result = $this->compare->add_product($product_id);

        if (!$result['success']) {

            wp_send_json_error([
                'message' => $result['message'],
                'products' => $result['products'],
                'count' => count($result['products']),
                'max_products' => AURA_Product_Compare::MAX_PRODUCTS,
            ]);
        }


        wp_send_json_success([
            'products' => $result['products'],
            'count' => count($result['products']),
            'product_id' => $product_id,
            'max_products' => AURA_Product_Compare::MAX_PRODUCTS,
        ]);
    }


    /**
     * Удаление
     */
    public function remove_product()
    {

        check_ajax_referer(
            'aura_compare_nonce',
            'nonce'
        );

        $product_id = isset($_POST['product_id'])
            ? absint($_POST['product_id'])
            : 0;

        if (!$product_id) {
            wp_send_json_error([
                'message' => 'Некорректный товар.'
            ]);
        }


        $products = $this->compare->remove_product($product_id);


        wp_send_json_success([
            'products' => $products,
            'count'    => count($products),
            'product_id' => $product_id,
        ]);
    }
}
