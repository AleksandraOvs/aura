<?php

defined('ABSPATH') || exit;

/**
 * Отдельная нумерация заказов WooCommerce.
 *
 * Старые заказы не изменяются.
 * Новые заказы получают номера начиная с 1000.
 */


/**
 * Получаем следующий номер заказа.
 */
function aura_get_next_order_number()
{

    $option_name = 'aura_next_order_number';

    $next_number = get_option($option_name);

    // Первый новый заказ.
    if ($next_number === false) {
        $next_number = 1000;
    }

    // Сохраняем следующий свободный номер.
    update_option($option_name, (int) $next_number + 1, false);

    return (int) $next_number;
}


/**
 * Выдаём новый номер после создания заказа.
 */
add_action('woocommerce_new_order', function ($order_id) {

    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Если номер уже есть — ничего не делаем.
    if ($order->get_meta('_aura_order_number')) {
        return;
    }

    // Не трогаем старые заказы.
    if ($order_id < get_option('aura_order_number_start_id', PHP_INT_MAX)) {
        return;
    }

    $order_number = aura_get_next_order_number();

    $order->update_meta_data('_aura_order_number', $order_number);
    $order->save();
}, 10);


/**
 * Запоминаем ID первого нового заказа.
 *
 * Это выполняется один раз при первом создании заказа.
 */
add_action('woocommerce_new_order', function ($order_id) {

    if (get_option('aura_order_number_start_id') === false) {
        add_option('aura_order_number_start_id', $order_id, '', false);
    }
}, 5);


/**
 * Показываем новый номер вместо стандартного
 * только для новых заказов.
 */
add_filter('woocommerce_order_number', function ($order_number, $order) {

    if (!$order instanceof WC_Order) {
        return $order_number;
    }

    $custom_number = $order->get_meta('_aura_order_number');

    if ($custom_number) {
        return $custom_number;
    }

    return $order_number;
}, 10, 2);
