<?php

/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

defined('ABSPATH') || exit;
?>
<div class="review-order woocommerce-checkout-review-order-table">

    <div class="review-order__row">
        <div class="review-order__col">Итого:</div>
        <div class="review-order__col">
            <?php echo wc_price(WC()->cart->get_cart_contents_total()); ?>
        </div>
    </div>

    <?php
    $discount_total = WC()->cart->get_discount_total();
    ?>

    <?php if ($discount_total > 0) : ?>
        <div class="review-order__row">
            <div class="review-order__col">Скидка:</div>
            <div class="review-order__col">
                -<?php echo wc_price($discount_total); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="review-order__row">
        <div class="review-order__col">Позиций:</div>
        <div class="review-order__col"><?php echo count(WC()->cart->get_cart()) ?></div>
    </div>

    <div class="review-order__row order-total">
        <div class="review-order__col">К оплате:</div>
        <div class="review-order__col">
            <?php wc_cart_totals_order_total_html(); ?>
        </div>
    </div>

    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>

        <?php do_action('woocommerce_review_order_before_shipping'); ?>

        <?php //wc_cart_totals_shipping_html(); 
        ?>

        <?php do_action('woocommerce_review_order_after_shipping'); ?>

    <?php endif; ?>


</div>