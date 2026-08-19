<?php

add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {

    ob_start();
?>
    <span class="cart-count">
        <?= WC()->cart->get_cart_contents_count(); ?>
    </span>
<?php

    $fragments['span.cart-count'] = ob_get_clean();

    return $fragments;
});
