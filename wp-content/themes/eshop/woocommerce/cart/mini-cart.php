<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_mini_cart'); ?>

<div class="widget_shopping_cart_content">
    <?php if (WC()->cart && ! WC()->cart->is_empty()) : ?>

        <ul class="woocommerce-mini-cart cart_list product_list_widget <?php echo esc_attr($args['list_class']); ?>">
            <?php
            do_action('woocommerce_before_mini_cart_contents');

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key)) {
                    $product_name      = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
            ?>
                    <li class="woocommerce-mini-cart-item">
                        <div class="product-details">
                            <h2 class="product-title">
                                <?php if (! empty($product_permalink)) : ?>
                                    <a href="<?php echo esc_url($product_permalink); ?>"><?php echo $product_name; ?></a>
                                <?php else : ?>
                                    <?php echo $product_name; ?>
                                <?php endif; ?>
                            </h2>

                            <div class="prod-cal d-flex flex-column">
                                <?php
                                $qty = (int) $cart_item['quantity'];

                                // 1. Базовая цена за штуку (витрина)
                                $base_unit_price = (float) $_product->get_price();

                                // 2. Цена за штуку с учётом всех скидок корзины
                                $discounted_unit_price = $qty > 0
                                    ? (float) $cart_item['line_total'] / $qty
                                    : 0;
                                ?>

                                <?php if ($discounted_unit_price !== $base_unit_price) { ?>


                                    <div class="price-discounted">
                                        <p>Цена:</p>

                                        <div class="price-base">
                                            <div class="screen-reader-text">
                                                Цена с учетом скидки
                                            </div> <?php echo wc_price($base_unit_price); ?>
                                            <div class="price-base _old"><?php echo wc_price($discounted_unit_price); ?>
                                            </div>



                                        </div>
                                    </div>


                                <?php } else {
                                ?>
                                    <div class="price-base">
                                        <span class="price"><?php echo wc_price($base_unit_price) . '/шт.'; ?></span>
                                        <!-- Количество -->
                                        <div class="quantity">
                                            Кол-во: <?php echo $qty; ?>
                                        </div>
                                    </div>



                                <?php
                                } ?>

                                <?php
                                // 3. Итог за количество

                                //echo '<div class="product-total">' . $total_price = (float) $cart_item['line_total'] . '</div>';
                                ?>


                                <!-- <span class="price-total">
									<?php //echo sprintf('%d × %s = %s', $qty, wc_price($discounted_unit_price), wc_price($total_price)); 
                                    ?>
								</span> -->
                            </div>
                        </div>

                        <?php echo wc_get_formatted_cart_item_data($cart_item); ?>
                    </li>
            <?php
                }
            }

            do_action('woocommerce_mini_cart_contents');
            ?>
        </ul>

        <div class="woocommerce-mini-cart__footer">


            <p class="woocommerce-mini-cart__total total">
                <?php do_action('woocommerce_widget_shopping_cart_total'); ?>
            </p>
            <div class="minicart-btn-group">
                <a href="<?php echo wc_get_checkout_url(); ?>" class="button">Оформление заказа</a>
                <a href="<?php echo wc_get_cart_url(); ?>" class="link">Просмотр корзины</a>
            </div>
        </div>

    <?php else : ?>

        <p class="woocommerce-mini-cart__empty-message"><?php esc_html_e('No products in the cart.', 'woocommerce'); ?></p>

    <?php endif; ?>

    <?php do_action('woocommerce_after_mini_cart'); ?>
</div>