<?php
defined('ABSPATH') || exit;

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {

    if (!WC()->cart) {
        return $fragments;
    }

    /*
     * Количество товаров в корзине
     */
    $count = WC()->cart->get_cart_contents_count();

    $fragments['.cart-count'] = sprintf(
        '<span class="cart-count">%d</span>',
        $count
    );


    /*
     * Mini-cart
     */
    ob_start();

    woocommerce_mini_cart();

    $fragments['.widget_shopping_cart_content'] = ob_get_clean();


    return $fragments;
});
