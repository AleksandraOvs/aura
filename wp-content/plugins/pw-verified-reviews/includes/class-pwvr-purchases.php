<?php

defined('ABSPATH') || exit;

class PWVR_Purchases
{

    /**
     * Проверяет, есть ли у пользователя выполненный заказ
     * с указанным товаром.
     */
    public static function user_purchased_product($user_id, $product_id)
    {

        $user_id    = absint($user_id);
        $product_id = absint($product_id);

        if (! $user_id || ! $product_id) {
            return false;
        }

        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status'      => array('completed'),
            'limit'       => -1,
            'return'      => 'objects',
        ));

        foreach ($orders as $order) {
            foreach ($order->get_items('line_item') as $item) {

                if (
                    (int) $item->get_product_id() === $product_id
                    || (int) $item->get_variation_id() === $product_id
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
