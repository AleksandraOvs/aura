<?php

add_action('init', function () {

    remove_action(
        'woocommerce_after_shop_loop_item',
        'woocommerce_template_loop_add_to_cart',
        10
    );
});

add_action('wp_enqueue_scripts', function () {

    //if (is_shop() || is_product_category() || is_product_tag() || is_product() || is_cart()) {

    wp_enqueue_script(
        'product-quantity',
        get_stylesheet_directory_uri() . '/js/product-quantity.js',
        ['jquery'],
        '1.0.0',
        true
    );
    // }
});

//КНОПКА В КОРЗИНУ - ИКОНКОЙ

add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {

    $svg = '
        <svg width="15" height="16" viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M5.37647 12.9107C5.37647 12.9107 5.43301 12.9673 5.54611 13.0804C5.6592 13.1935 5.71575 13.4048 5.71575 13.7143C5.71575 14.0238 5.60266 14.2917 5.37647 14.5179C5.15027 14.744 4.88242 14.8571 4.57289 14.8571C4.26337 14.8571 3.99551 14.744 3.76932 14.5179C3.54313 14.2917 3.43004 14.0238 3.43004 13.7143C3.43004 13.4048 3.54313 13.1369 3.76932 12.9107C3.99551 12.6845 4.26337 12.5714 4.57289 12.5714C4.88242 12.5714 5.15027 12.6845 5.37647 12.9107ZM13.3765 12.9107C13.3765 12.9107 13.433 12.9673 13.5461 13.0804C13.6592 13.1935 13.7158 13.4048 13.7158 13.7143C13.7158 14.0238 13.6027 14.2917 13.3765 14.5179C13.1503 14.744 12.8824 14.8571 12.5729 14.8571C12.2634 14.8571 11.9955 14.744 11.7693 14.5179C11.5431 14.2917 11.43 14.0238 11.43 13.7143C11.43 13.4048 11.5431 13.1369 11.7693 12.9107C11.9955 12.6845 12.2634 12.5714 12.5729 12.5714C12.8824 12.5714 13.1503 12.6845 13.3765 12.9107ZM14.8586 4V8.57143C14.8586 8.71429 14.8095 8.84077 14.7113 8.95089C14.6131 9.06101 14.4925 9.125 14.3497 9.14286L5.02825 10.2321C5.10563 10.5893 5.14432 10.7976 5.14432 10.8571C5.14432 10.9524 5.07289 11.1429 4.93004 11.4286H13.1443C13.2991 11.4286 13.433 11.4851 13.5461 11.5982C13.6592 11.7113 13.7158 11.8452 13.7158 12C13.7158 12.1548 13.6592 12.2887 13.5461 12.4018C13.433 12.5149 13.2991 12.5714 13.1443 12.5714H4.00146C3.8467 12.5714 3.71277 12.5149 3.59968 12.4018C3.48658 12.2887 3.43004 12.1548 3.43004 12C3.43004 11.9345 3.45385 11.8408 3.50147 11.7188C3.54908 11.5967 3.5967 11.4896 3.64432 11.3973C3.69194 11.3051 3.75593 11.186 3.83629 11.0402C3.91664 10.8943 3.96277 10.8065 3.97468 10.7768L2.39432 3.42857H0.572893C0.418132 3.42857 0.284203 3.37202 0.171108 3.25893C0.0580125 3.14583 0.00146484 3.0119 0.00146484 2.85714C0.00146484 2.70238 0.0580125 2.56845 0.171108 2.45536C0.284203 2.34226 0.418132 2.28571 0.572893 2.28571H2.85861C2.95385 2.28571 3.03867 2.30506 3.11307 2.34375C3.18748 2.38244 3.24551 2.42857 3.28718 2.48214C3.32885 2.53571 3.36754 2.60863 3.40325 2.70089C3.43897 2.79315 3.46277 2.87054 3.47468 2.93304C3.48658 2.99554 3.50295 3.08333 3.52379 3.19643C3.54462 3.30952 3.55801 3.3869 3.56397 3.42857H14.2872C14.4419 3.42857 14.5759 3.48512 14.689 3.59821C14.8021 3.71131 14.8586 3.84524 14.8586 4Z" fill="white"/>
</svg>';

    $span = '<span>В корзину</span>';

    return preg_replace(
        '/(<a\b[^>]*>).*?(<\/a>)/s',
        '$1' . $svg . $span . '$2',
        $html
    );
}, 10, 2);


/* удаляем заголовок товара из стандартного блока на странице товара */
remove_action(
    'woocommerce_single_product_summary',
    'woocommerce_template_single_title',
    5
);
// add_action('wp_enqueue_scripts', function () {
//     wp_enqueue_script(
//         'product-quantity',
//         get_stylesheet_directory_uri() . '/js/product-quantity.js',
//         [],
//         '1.0.0',
//         true
//     );
// });

// add_filter('woocommerce_quantity_input_html', function ($html, $product) {

//     $minus = '<button type="button" class="minus" aria-label="Уменьшить количество">−</button>';

//     $plus = '<button type="button" class="plus" aria-label="Увеличить количество">+</button>';

//     return $minus . $html . $plus;
// }, 10, 2);

// remove_action(
//     'woocommerce_after_shop_loop_item_title',
//     'woocommerce_template_loop_price',
//     10
// );

// remove_action(
//     'woocommerce_after_shop_loop_item',
//     'woocommerce_template_loop_add_to_cart',
//     10
// );


/**
 * ACF: список глобальных атрибутов WooCommerce
 */
add_filter(
    'acf/load_field/name=attribute',
    function ($field) {

        if (!function_exists('wc_get_attribute_taxonomies')) {
            return $field;
        }

        $field['choices'] = [];

        $attributes = wc_get_attribute_taxonomies();

        if (!$attributes) {
            return $field;
        }

        foreach ($attributes as $attribute) {

            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

            $field['choices'][$taxonomy] = $attribute->attribute_label;
        }

        return $field;
    }
);

/**
 * QUANTITY
 */

function aura_product_quantity($product, $args = [])
{
    if (!$product instanceof WC_Product) {
        return;
    }

    $defaults = [
        'input_name'  => 'quantity',
        'input_value' => $product->get_min_purchase_quantity(),
    ];

    $args = wp_parse_args($args, $defaults);

    $min_value = $product->is_sold_individually()
        ? 1
        : $product->get_min_purchase_quantity();

    $max_value = $product->is_sold_individually()
        ? 1
        : $product->get_max_purchase_quantity();

?>

    <div class="product-quantity">

        <button
            type="button"
            class="quantity-minus"
            aria-label="Уменьшить количество">
            −
        </button>

        <?php
        woocommerce_quantity_input(
            [
                'input_name'  => $args['input_name'],
                'input_value' => $args['input_value'],
                'min_value'   => $min_value,
                'max_value'   => $max_value,
                'product_name' => $product->get_name(),
            ],
            $product
        );
        ?>

        <button
            type="button"
            class="quantity-plus"
            aria-label="Увеличить количество">
            +
        </button>

    </div>

<?php
}

/* УДАЛИЛ БЛОК "ВАС МОЖЕТ ЗАИНТЕРЕСОВАТЬ" НА СТРАНИЦЕ КОРЗИНА */
add_action('wp', function () {
    if (is_cart()) {
        remove_action(
            'woocommerce_cart_collaterals',
            'woocommerce_cross_sell_display'
        );
    }
});

/* CHECKOUT FIELDS */
add_filter('woocommerce_checkout_fields', function ($fields) {

    /*
     * Billing
     */

    // Имя
    $fields['billing']['billing_first_name']['label'] = 'Имя';
    $fields['billing']['billing_first_name']['placeholder'] = 'Имя';
    $fields['billing']['billing_first_name']['required'] = true;

    // Фамилия
    $fields['billing']['billing_last_name']['label'] = 'Фамилия';
    $fields['billing']['billing_last_name']['placeholder'] = 'Фамилия';
    $fields['billing']['billing_last_name']['required'] = true;

    // Телефон
    $fields['billing']['billing_phone']['label'] = 'Номер телефона';
    $fields['billing']['billing_phone']['placeholder'] = '+7 (___) ___-__-__';
    $fields['billing']['billing_phone']['required'] = true;

    // E-mail
    $fields['billing']['billing_email']['label'] = 'E-mail';
    $fields['billing']['billing_email']['placeholder'] = 'E-mail';
    $fields['billing']['billing_email']['required'] = true;

    // Адрес
    $fields['billing']['billing_address_1']['label'] = 'Адрес';
    $fields['billing']['billing_address_1']['placeholder'] = 'Адрес';
    $fields['billing']['billing_address_1']['required'] = true;

    /*
     * Убираем ненужные billing-поля
     */

    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);

    /*
     * Если страна уже определена/не нужна пользователю,
     * её можно скрыть из формы.
     */
    // unset($fields['billing']['billing_country']);


    /*
     * Shipping нам здесь пока не нужен.
     */

    $fields['shipping'] = [];


    /*
     * Юридическое лицо
     */

    $fields['billing']['billing_legal_entity'] = [
        'type'     => 'checkbox',
        'label'    => 'Юридическое лицо',
        'required' => false,
        'class'    => ['form-row-wide'],
        'priority' => 80,
    ];


    /*
     * Примечание к заказу
     */

    $fields['order']['order_comments']['label'] = 'Примечание к заказу';
    $fields['order']['order_comments']['placeholder'] = 'Примечание к заказу';
    $fields['order']['order_comments']['required'] = false;
    $fields['order']['order_comments']['class'] = ['form-row-wide'];
    $fields['order']['order_comments']['priority'] = 90;


    return $fields;
});

add_action('woocommerce_checkout_create_order', function ($order, $data) {

    $legal_entity = ! empty($_POST['billing_legal_entity']) ? 'yes' : 'no';

    $order->update_meta_data(
        '_billing_legal_entity',
        $legal_entity
    );
}, 10, 2);

remove_action(
    'woocommerce_before_checkout_form',
    'woocommerce_checkout_coupon_form',
    10
);
