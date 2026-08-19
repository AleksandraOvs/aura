<?php
defined('ABSPATH') || exit;

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {

    if (!WC()->cart) {
        return $fragments;
    }

    $count = WC()->cart->get_cart_contents_count();

    $fragments['.cart-count'] = sprintf(
        '<span class="cart-count">%d</span>',
        $count
    );

    return $fragments;
});
