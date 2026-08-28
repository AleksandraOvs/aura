<?php

defined('ABSPATH') || exit;

$cart = WC()->cart;

if (! $cart) {
    return;
}

?>

<div class="cart-totals">

    <!-- Итого -->

    <div class="cart-totals__total">

        <span class="cart-totals__total-label">
            Итого:
        </span>

        <span class="cart-totals__total-value">
            <?php
            echo wp_kses_post(
                $cart->get_cart_total()
            );
            ?>
        </span>

    </div>


    <!-- Количество товаров -->

    <div class="cart-totals__count">

        <span class="cart-totals__count-label">
            Товаров в корзине:
        </span>

        <span class="cart-totals__count-value">
            <?php
            echo esc_html(
                $cart->get_cart_contents_count()
            );
            ?>
        </span>

    </div>


    <!-- Купон -->

    <?php if (wc_coupons_enabled()) : ?>

        <div class="cart-totals__coupon">

            <div class="coupon">
                <?php
                $arrow = '<svg width="27" height="18" viewBox="0 0 27 18" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M0.670254 8.32939C0.300007 8.32939 -0.0001297 8.62476 -0.0001297 8.98913C-0.0001297 9.35351 0.300007 9.64888 0.670254 9.64888H23.5447L16.148 16.8495C15.8848 17.1057 15.8825 17.5234 16.1427 17.7825C16.4031 18.0415 16.8276 18.0438 17.0908 17.7876L26.1289 8.98913L17.0908 0.190649C16.8276 -0.0655506 16.4031 -0.0632363 16.1427 0.195792C15.8825 0.454821 15.8848 0.872592 16.148 1.12879L23.5447 8.32939H0.670254Z" fill="#6C757D"/>
</svg>
';
                ?>

                <label
                    for="coupon_code"
                    class="screen-reader-text">
                    <?php esc_html_e('Coupon:', 'woocommerce'); ?>
                </label>

                <input
                    type="text"
                    name="coupon_code"
                    class="input-text"
                    id="coupon_code"
                    value=""
                    placeholder="Промокод" />

                <button
                    type="submit"
                    class="button"
                    name="apply_coupon"
                    value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>">
                    <?php echo $arrow ?>
                </button>

                <?php do_action('woocommerce_cart_coupon'); ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- Информация -->

    <div class="cart-totals__notice">

        Доступные способы оплаты и доставки
        можно выбрать при оформлении заказа.

    </div>


    <!-- Оформление заказа -->

    <div class="cart-totals__checkout">

        <a
            href="<?php echo esc_url(wc_get_checkout_url()); ?>"
            class="button button-black checkout-button">
            Оформить заказ
        </a>

    </div>

</div>