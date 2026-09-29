<?php

/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_orders', $has_orders); ?>

<?php if ($has_orders) : ?>
    <h3>Заказы</h3>
    <div class="my-account-orders">

        <?php foreach ($customer_orders->orders as $customer_order) :
            $order = wc_get_order($customer_order);
            // Доставка
            // Способ доставки
            $shipping_method = '';
            $shipping_items  = $order->get_items('shipping');

            if (!empty($shipping_items)) {
                foreach ($shipping_items as $shipping_item) {
                    $shipping_method = $shipping_item->get_name();
                    break;
                }
            }

            // Дата доставки (если заказ выполнен)
            $delivery_date = '';

            if ($order->has_status('completed')) {
                $completed_date = $order->get_date_completed();
                if ($completed_date) {
                    $delivery_date = wc_format_datetime($completed_date);
                }
            }
            $items = $order->get_items();
            $item_count = $order->get_item_count() - $order->get_item_count_refunded();

            // Общий вес
            $total_weight = 0;
            foreach ($items as $item) {
                $product = $item->get_product();
                if ($product && $product->get_weight()) {
                    $total_weight += $product->get_weight() * $item->get_quantity();
                }
            }

            // Сумма скидок
            $discount_total = $order->get_total_discount();
        ?>
            <div class="my-order">
                <div class="my-order__title">
                    <h3>
                        <?php printf(
                            esc_html__('Заказ %s', 'woocommerce'),
                            '<a href="' . esc_url($order->get_view_order_url()) . '">№' . esc_html($order->get_order_number()) . '</a>'
                        ); ?>
                        <span class="my-order__date"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></span>

                    </h3>


                    <div class="my-order__title__right">
                        <span class="my-order__delivery">
                            <?php if ($order->has_status('completed') && $shipping_method && $delivery_date) : ?>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        '%s от %s',
                                        $shipping_method,
                                        $delivery_date
                                    )
                                );
                                ?>
                            <?php else : ?>
                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                            <?php endif; ?>
                        </span>

                        <svg width="16" height="10" viewBox="0 0 16 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.9506 1.26192L14.7587 0.0515046C14.6908 -0.0167611 14.5813 -0.0171077 14.5137 0.0501185L8.02863 6.48581L1.50406 0.0589549C1.437 -0.00688513 1.32906 -0.00723165 1.26183 0.0589549L0.0502043 1.25135C-0.0170212 1.31892 -0.016674 1.42843 0.0508976 1.49565L7.88188 9.28886C7.9491 9.35574 8.05808 9.35591 8.12548 9.28886L15.9483 1.5064C16.0164 1.43865 16.0173 1.32967 15.9506 1.26192Z" fill="#28A745" />
                        </svg>

                    </div>

                </div>

                <div class="my-order__items">
                    <?php foreach ($items as $item_id => $item) :
                        $product = $item->get_product();
                        if (! $product) continue;

                        $product_name = $product->get_name();
                        $product_sku  = $product->get_sku();
                        $product_img  = $product->get_image('thumbnail');
                        $quantity     = $item->get_quantity();
                        $weight       = $product->get_weight() ? wc_format_weight($product->get_weight() * $quantity) : '';
                        $product_link = get_permalink($product->get_id());
                    ?>
                        <div class="my-order__item">

                            <div class="my-order__item-img">
                                <a href="<?php echo esc_url($product_link); ?>">
                                    <?php echo $product_img; ?>
                                </a>
                            </div>

                            <div class="my-order__item-right">
                                <div class="my-order__item-info">
                                    <div class="my-order__item-name"><a href="<?php echo esc_url($product_link); ?>">
                                            <?php echo esc_html($product_name); ?>
                                        </a></div>
                                    <?php if ($product_sku) : ?>
                                        <div class="my-order__item-sku"><?php echo esc_html('SKU: ' . $product_sku); ?></div>
                                    <?php endif; ?>
                                </div>



                                <div class="my-order__item-right__item _qty">

                                    <div class="_qty">Кол-во: <?php echo esc_html($quantity); ?></div>
                                    <?php
                                    if ($quantity > 0) {
                                        echo '<div class="_qty">' . wp_kses_post(wc_price($item->get_total() / $quantity)) . '/&nbsp;шт.</div>';
                                    }
                                    ?>

                                </div>
                                <div class="my-order__item-right__item _total">
                                    <p class="label">Сумма:</p>
                                    <?php echo wp_kses_post(wc_price($item->get_total())); ?>
                                </div>
                                <!-- <div class="my-order__item-right__item _item-view">
									<a href="<?php //echo esc_url($order->get_view_order_url()); 
                                                ?>"><?php //esc_html_e('View', 'woocommerce'); 
                                                    ?></a>
								</div> -->
                            </div>


                        </div>
                    <?php endforeach; ?>

                    <!-- Финальный блок заказа -->
                    <!-- <div class="my-order__summary">
                        <div class="my-order__summary-item _status">
                            <?php //if ($order->has_status('completed')) : 
                            ?>
                                <svg width="25" height="17" viewBox="0 0 25 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.80197 10.6692C7.47019 11.3408 8.69775 12.573 9.28206 12.9743L20.7333 1.45849L20.9854 1.18071C21.3947 0.709402 21.9635 0.0551611 22.6363 0.00451539C22.8615 -0.0121145 23.0732 0.0177393 23.2641 0.0850149C23.5222 0.176857 23.7418 0.341262 23.9017 0.552916C24.06 0.763058 24.1609 1.02423 24.1821 1.31148C24.1995 1.53863 24.1678 1.78428 24.0778 2.03487C23.8911 2.54397 18.1005 8.25448 16.0713 10.2561L15.437 10.8839C14.3939 11.9244 13.5635 12.7839 12.8745 13.4959C11.1715 15.2572 10.2908 16.1669 9.52773 16.3978C8.59948 16.6783 8.02575 16.0717 6.84427 14.8229C6.54266 14.5039 6.19192 14.1335 5.73385 13.6754L5.09284 13.0484C3.51602 11.5177 0.0596547 8.16338 0.00409559 7.4328C-0.0117784 7.2117 0.0192093 7.00422 0.0845951 6.8194C0.179461 6.55748 0.348034 6.33748 0.563089 6.17987C0.776255 6.0234 1.0378 5.92627 1.32051 5.90737C1.5401 5.89187 1.77518 5.92288 2.01215 6.00754C2.39578 6.14549 3.03187 6.80617 3.4359 7.22419L3.66116 7.45587C4.14418 7.93322 4.61889 8.4287 5.09284 8.92457C5.53051 9.38265 5.96707 9.84075 6.50112 10.3691L6.80197 10.6692Z"
                                        fill="#B6713D" />
                                </svg>
                            <?php //endif; 
                            ?>

                            <span><?php //echo esc_html(wc_get_order_status_name($order->get_status())); 
                                    ?></span>
                        </div>
                        <div class="my-order__summary-item">
                            <p class="label"><?php //esc_html_e('Позиций:', 'woocommerce'); 
                                                ?></p>
                            <span><?php //echo esc_html($item_count); 
                                    ?></span>
                        </div>
                        <div class="my-order__summary-item">
                            <p class="label"><?php //esc_html_e('Вес:', 'woocommerce'); 
                                                ?></p>
                            <span><?php //echo $total_weight ? esc_html(wc_format_weight($total_weight)) : '-'; 
                                    ?></span>
                        </div>

                        <div class="my-order__summary-item _discount">
                            <p class="label"><?php //esc_html_e('Скидка:', 'woocommerce'); 
                                                ?></p>

                            <?php //if ($discount_total > 0) { 
                            ?>
                                <span><?php //echo wp_kses_post(wc_price($discount_total)); 
                                        ?></span>
                            <?php //} else {
                            // echo '—';
                            //} 
                            ?>
                        </div>

                        <div class="my-order__summary-item">
                            <strong><?php //esc_html_e('Total:', 'woocommerce'); 
                                    ?></strong>
                            <span><?php //echo wp_kses_post($order->get_formatted_order_total()); 
                                    ?></span>
                        </div>
                    </div> -->
                </div>



            </div>
        <?php endforeach; ?>

    </div>
<?php else : ?>
    <?php wc_print_notice(
        esc_html__('No order has been made yet.', 'woocommerce') .
            ' <a class="woocommerce-Button wc-forward button" href="' . esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))) . '">' . esc_html__('Browse products', 'woocommerce') . '</a>',
        'notice'
    ); ?>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const firstOrder = document.querySelector('.my-order');
        if (firstOrder) {
            firstOrder.classList.add('is-open');
        }
    });

    document.addEventListener('click', function(e) {
        const title = e.target.closest('.my-order__title');
        if (!title) return;

        const order = title.closest('.my-order');
        if (!order) return;

        order.classList.toggle('is-open');
    });
</script>