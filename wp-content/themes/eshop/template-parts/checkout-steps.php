<nav class="checkout-steps">
    <ul class="checkout-steps__list">
        <li>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>"
                class="<?php echo is_cart() ? 'active' : ''; ?>">
                Корзина
            </a>
        </li>

        <li>
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>"
                class="<?php echo (is_checkout() && ! is_order_received_page()) ? 'active' : ''; ?>">
                Оформление заказа
            </a>
        </li>

        <li>
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>"
                class="<?php echo is_wc_endpoint_url('order-pay') ? 'active' : ''; ?>">
                Оплата
            </a>
        </li>
    </ul>
</nav>