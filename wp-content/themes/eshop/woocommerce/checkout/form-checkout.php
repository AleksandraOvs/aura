<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

?>

<form
    name="checkout"
    method="post"
    class="checkout woocommerce-checkout"
    action="<?php echo esc_url(wc_get_checkout_url()); ?>"
    enctype="multipart/form-data">

    <?php do_action('woocommerce_checkout_before_customer_details'); ?>





    <!-- ==========================================================
         Контактные данные
    =========================================================== -->
    <section class="page-content">

        <div class="container">
            <div class="checkout-section__inner">

                <div class="checkout-contacts__fields">
                    <!-- ==========================================================
         Авторизация
    =========================================================== -->
                    <?php
                    /*
 * Если регистрация обязательна
 */

                    if (
                        ! $checkout->is_registration_enabled()
                        && $checkout->is_registration_required()
                        && ! is_user_logged_in()
                    ) {
                        echo esc_html(
                            apply_filters(
                                'woocommerce_checkout_must_be_logged_in_message',
                                __('You must be logged in to checkout.', 'woocommerce')
                            )
                        );

                        return;
                    }
                    ?>
                    <h2 class="checkout-section-title">
                        Контактные данные
                    </h2>
                    <?php
                    /*
             * Billing-поля WooCommerce.
             *
             * Здесь остаются стандартные поля WooCommerce,
             * поэтому плагины доставки и оплаты продолжают
             * нормально с ними работать.
             */
                    ?>

                    <?php do_action('woocommerce_checkout_billing'); ?>
                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>
                </div>


                <div
                    class="checkout-order__summary"
                    id="checkout-order-summary">
                    <div class="checkout-order__total">

                        <span class="checkout-order__total-label">
                            Итого:
                        </span>

                        <span class="checkout-order__total-value">
                            <?php echo WC()->cart->get_total(); ?>
                        </span>

                    </div>
                    <div class="checkout-order__products">

                        В корзине
                        <span class="checkout-order__products-count">
                            <?php echo esc_html(WC()->cart->get_cart_contents_count()); ?>
                        </span>
                        <?php echo esc_html(_n('товар', 'товаров', WC()->cart->get_cart_contents_count(), 'woocommerce')); ?>

                    </div>




                </div>




            </div>

        </div>
    </section>

    <!-- ==========================================================
         Доставка + оплата
    =========================================================== -->

    <div
        id="checkout-payment-wrapper"
        class="checkout-payment-wrapper">

        <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>


        <!-- ======================================================
             Доставка

             ВАЖНО:
             Не выводим shipping вручную.
             WooCommerce сам выведет доступные способы доставки.
             ====================================================== -->

        <section class="checkout-delivery">

            <h2 class="checkout-section-title">
                Доставка
            </h2>

            <div class="checkout-delivery__methods">

                <?php
                /*
                 * WooCommerce checkout/order-review hooks.
                 *
                 * Способы доставки будут выведены стандартным
                 * механизмом WooCommerce.
                 */
                ?>

                <?php do_action('woocommerce_checkout_shipping'); ?>

            </div>

        </section>


        <!-- ======================================================
             Оплата

             Отдельный визуальный блок.
             ====================================================== -->

        <section class="checkout-payment">

            <h2 class="checkout-section-title">
                Способ оплаты
            </h2>

            <div class="checkout-payment__methods">

                <?php
                /*
                 * WooCommerce payment gateways.
                 *
                 * Здесь будут автоматически появляться:
                 *
                 * - банковская карта;
                 * - СБП;
                 * - другие подключенные шлюзы.
                 */
                ?>

                <?php woocommerce_checkout_payment(); ?>

            </div>

        </section>

    </div>


</form>


<?php do_action('woocommerce_after_checkout_form', $checkout); ?>