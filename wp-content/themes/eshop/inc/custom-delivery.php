<?php
add_action('woocommerce_checkout_create_order', 'save_custom_delivery_data_to_order', 20, 2);

function save_custom_delivery_data_to_order($order, $data)
{
    /*
     * Выбранный способ доставки
     */
    if (!empty($_POST['custom_delivery_method'])) {

        $method_id = sanitize_text_field(
            wp_unslash($_POST['custom_delivery_method'])
        );

        $custom_delivery_regions = get_field('delivery_region', 'option');

        if (!empty($custom_delivery_regions)) {

            foreach ($custom_delivery_regions as $region_index => $region) {

                $methods = $region['custom_delivery_method'] ?? [];

                if (empty($methods)) {
                    continue;
                }

                foreach ($methods as $method_index => $method) {

                    $current_method_id = 'custom-delivery-method-' . $region_index . '-' . $method_index;

                    if ($method_id === $current_method_id) {

                        $method_name = $method['custom_delivery_method_name'] ?? '';

                        if ($method_name) {
                            $order->update_meta_data(
                                '_custom_delivery_method',
                                sanitize_text_field($method_name)
                            );
                        }

                        break 2;
                    }
                }
            }
        }
    }


    /*
     * Выбранные типы доставки
     */
    if (!empty($_POST['custom_delivery_type']) && is_array($_POST['custom_delivery_type'])) {

        $delivery_types = array_map(
            'sanitize_text_field',
            wp_unslash($_POST['custom_delivery_type'])
        );

        $delivery_types = array_filter($delivery_types);

        if (!empty($delivery_types)) {

            $order->update_meta_data(
                '_custom_delivery_types',
                $delivery_types
            );
        }
    }
}


// Отображение данных о доставке в карточке заказа:

add_action('woocommerce_admin_order_data_after_order_details', 'display_custom_delivery_data_in_admin');

function display_custom_delivery_data_in_admin($order)
{
    $delivery_method = $order->get_meta('_custom_delivery_method');
    $delivery_types  = $order->get_meta('_custom_delivery_types');

    if (!$delivery_method && empty($delivery_types)) {
        return;
    }

    echo '<div class="custom-delivery-order-data" style="float: left; width:100%; margin-top: 2em; background: #f8f8f8; padding: .5em;">';

    if ($delivery_method) {
        echo '<p>';
        echo '<strong>Способ доставки:</strong> ';
        echo esc_html($delivery_method);
        echo '</p>';
    }

    if (!empty($delivery_types) && is_array($delivery_types)) {

        echo '<p>';
        echo '<strong>Тип доставки: </strong>';

        foreach ($delivery_types as $type) {
            echo esc_html($type);
        }

        echo '</p>';
    }

    echo '</div>';
}
