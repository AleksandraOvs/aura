<?php
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

add_action('woocommerce_after_shop_loop_item', 'my_custom_more_link', 10);

function my_custom_more_link()
{
    global $product;

    if (!$product) return;
?>
    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="button product-more">
        Подробнее
    </a>
<?php
}

/* ---------- Cart count fragment ---------- */
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    ob_start();
?>
    <span class="cart-count"><?= WC()->cart->get_cart_contents_count(); ?></span>
<?php
    $fragments['span.cart-count'] = ob_get_clean();
    return $fragments;
});

//формат цены для вариативных товаров от...
add_filter('woocommerce_get_price_html', 'custom_variable_price_from', 10, 2);

function custom_variable_price_from($price, $product)
{

    if ($product->is_type('variable')) {

        // Минимальная цена вариации
        $min_price = $product->get_variation_price('min', true);

        if ($min_price) {
            $price = 'От ' . wc_price($min_price);
        }
    }

    return $price;
}

add_filter('gettext', function ($translated, $text, $domain) {

    // WooCommerce Blocks
    if ($text === 'Estimated total' || $text === 'estimated total') {
        return 'Итого';
    }

    return $translated;
}, 20, 3);


add_filter('gettext', 'change_coupon_text', 20, 3);
function change_coupon_text($translated, $text, $domain)
{

    if ($domain === 'woocommerce') {

        if ($text === 'Have a coupon?') {
            return 'Есть промокод?';
        }

        if ($text === 'Have a coupon? Click here to enter your code') {
            return 'Введите промокод';
        }

        if ($text === 'Add a coupon') {
            return 'Добавить промокод';
        }

        if (stripos($text, 'coupon') !== false) {
            return 'Добавить промокод';
        }
    }


    return $translated;
}


//**
// * 2. КАСТОМИЗАЦИЯ ПОЛЕЙ (billing)
// * Управляем тем, какие поля показываются на checkout и как они выглядят

add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

add_filter('woocommerce_checkout_fields', function ($fields) {

    /** ===================================================
     * BILLING FIELDS
     * =================================================== */

    // ❌ убираем стандартные WooCommerce поля
    unset(
        //$fields['billing']['billing_first_name'],
        //$fields['billing']['billing_last_name'],
        $fields['billing']['billing_address_2'],
        $fields['billing']['billing_state'],
        $fields['billing']['billing_country']
    );

    /** 📌 ФИО */
    // $fields['billing']['billing_full_name'] = [
    //     'type'        => 'text',
    //     'priority'    => 10,
    //     'class'       => ['form-row-wide'],
    //     'placeholder' => 'Ф.И.О*',
    //     'required'    => true,
    // ];

    /** 📞 Телефон */
    $fields['billing']['billing_phone']['type'] = 'tel';
    $fields['billing']['billing_phone']['priority'] = 20;
    $fields['billing']['billing_phone']['placeholder'] = '+7 (___) ___-__-__*';
    $fields['billing']['billing_phone']['required'] = true;
    $fields['billing']['billing_phone']['custom_attributes'] = [
        'inputmode' => 'tel',
        'autocomplete' => 'tel',
        'pattern' => '\+7\s\(\d{3}\)\s\d{3}-\d{2}-\d{2}'
    ];

    /** 📧 Email */
    $fields['billing']['billing_email']['priority']    = 30;
    $fields['billing']['billing_email']['placeholder'] = 'E-mail*';
    $fields['billing']['billing_email']['required']    = true;

    /** 🌍 Страна (если нужна — оставляем select WooCommerce) */
    //$fields['billing']['billing_country']['priority'] = 40;

    /** 📮 Индекс */
    $fields['billing']['billing_postcode']['priority']    = 80;
    $fields['billing']['billing_postcode']['placeholder'] = 'Индекс';
    $fields['billing']['billing_postcode']['required']    = false;

    /** 🏙 Город */
    $fields['billing']['billing_city']['priority']    = 50;
    $fields['billing']['billing_city']['placeholder'] = 'Город*';
    $fields['billing']['billing_city']['required']    = true;

    /** 🏠 Улица */
    $fields['billing']['billing_address_1']['priority']    = 60;
    $fields['billing']['billing_address_1']['placeholder'] = 'Улица*';
    $fields['billing']['billing_address_1']['required']    = true;

    /** 🏢 Дом */
    $fields['billing']['billing_address_2'] = [
        'type'        => 'text',
        'priority'    => 70,
        'class'       => ['form-row-first'],
        'placeholder' => 'Дом*',
        'required'    => true,
    ];

    /** ===================================================
     * UI CLEANUP
     * =================================================== */
    foreach ($fields as $section_key => $section) {
        foreach ($section as $field_key => $field) {

            if (!empty($field['label']) && empty($field['placeholder'])) {
                $fields[$section_key][$field_key]['placeholder'] = $field['label'];
            }

            $fields[$section_key][$field_key]['label'] = '';
            $fields[$section_key][$field_key]['label_class'] = ['screen-reader-text'];
        }
    }

    return $fields;
});

add_action('woocommerce_checkout_create_order', function ($order, $data) {

    if (!empty($_POST['billing_full_name'])) {
        $order->update_meta_data(
            '_billing_full_name',
            sanitize_text_field($_POST['billing_full_name'])
        );
    }
}, 10, 2);


/**
 * ❗ ВАЖНО: ОТКЛЮЧАЕМ SHIPPING ПРАВИЛЬНО
 */
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');


/**
 * скрываем UI "доставка на другой адрес"
 */
add_action('wp_head', function () {
    if (is_checkout()) {
        echo '<style>
            #ship-to-different-address {
                display: none !important;
            }
        </style>';
    }
});

/**
 * 5. Убираем чекбокс "доставка по другому адресу"
 * (скрывает возможность ввода отдельного shipping-адреса)
 */
//add_filter('woocommerce_checkout_show_shipping', '__return_true');

// 🎨 дополнительно скрываем блок через CSS на странице checkout
add_action('wp_head', function () {
    if (is_checkout()) {
        echo '<style>
            #ship-to-different-address {
                display: none !important;
            }
        </style>';
    }
});

// ❌ полностью удаляем shipping-поля из формы оформления заказа
add_filter('woocommerce_checkout_fields', function ($fields) {

    unset($fields['shipping']); // убираем весь блок доставки

    return $fields;
});




/**
 * 6. Меняем текст "Billing details" на "Данные заказа"
 * (локализация/переименование заголовка блока checkout)
 */
add_filter('gettext', function ($translated, $text, $domain) {

    // заменяем только текст WooCommerce блока billing
    if ($domain === 'woocommerce' && ($text === 'Billing details' || $translated === 'Платёжные реквизиты')) {
        return 'Данные заказа';
    }

    return $translated;
}, 20, 3);

/**
 * отключаем автосогласие с политикой конфиденциальности
 */
add_filter('woocommerce_privacy_policy_checkbox_default_checked', '__return_false');

/**
 * UI-логика кнопки оформления заказа:
 * блокируем кнопку "Оформить заказ", пока не принят чекбокс условий
 */
add_action('wp_footer', function () {
    if (!is_checkout()) return;
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.querySelector('#terms'); // чекбокс согласия с условиями
            const placeOrderBtn = document.querySelector('#place_order'); // кнопка оформления заказа

            if (!checkbox || !placeOrderBtn) return;

            // включает/выключает состояние кнопки
            function togglePlaceOrderClass() {
                if (checkbox.checked) {
                    placeOrderBtn.classList.remove('disabled');
                } else {
                    placeOrderBtn.classList.add('disabled');
                }
            }

            // состояние при загрузке страницы
            togglePlaceOrderClass();

            // реакция на изменение чекбокса
            checkbox.addEventListener('change', togglePlaceOrderClass);
        });
    </script>
    <style>
        /* визуальное состояние неактивной кнопки */
        #place_order.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>
<?php
});


/**
 * 7. Убираем блок "Спасибо за заказ" (order details)
 * после успешного оформления заказа
 */
add_action('wp', function () {

    // выполняем только на странице завершения заказа
    if (!is_wc_endpoint_url('order-received')) {
        return;
    }

    // убираем таблицу с деталями заказа
    remove_action(
        'woocommerce_thankyou',
        'woocommerce_order_details_table',
        10
    );

    // убираем блок с данными покупателя
    remove_action(
        'woocommerce_thankyou',
        'woocommerce_order_details_customer_details',
        20
    );
});

add_action('wp_footer', function () {
    if (!is_checkout()) return;
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const input = document.querySelector('#billing_phone');
            if (!input) return;

            function getDigits(value) {
                return value.replace(/\D/g, '');
            }

            function formatPhone(digits) {
                if (digits.startsWith('8')) digits = '7' + digits.slice(1);
                if (!digits.startsWith('7')) digits = '7' + digits;

                let result = '+7';

                if (digits.length > 1) result += ' (' + digits.substring(1, 4);
                if (digits.length >= 5) result += ') ' + digits.substring(4, 7);
                if (digits.length >= 8) result += '-' + digits.substring(7, 9);
                if (digits.length >= 10) result += '-' + digits.substring(9, 11);

                return result;
            }

            input.addEventListener('input', function() {

                let digits = getDigits(this.value);

                // ограничение длины
                digits = digits.substring(0, 11);

                const formatted = formatPhone(digits);

                this.value = formatted;
            });

        });
    </script>
<?php
});

// add_filter('gettext', function ($translated, $text, $domain) {

//     if ($text === 'Add coupon') {
//         return 'Добавить промокод';
//     }

//     if ($text === 'Estimated total') {
//         return 'Итог:';
//     }

//     return $translated;
// }, 20, 3);

add_filter('woocommerce_blocks_translate_text', function ($translation, $text, $domain) {

    if ($text === 'privacy policy') {
        return 'политике конфиденциальности';
    }

    return $translation;
}, 10, 3);



add_action('wp_footer', function () {
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const replaceText = (node) => {
                node.childNodes.forEach(child => {
                    if (child.nodeType === Node.TEXT_NODE) {
                        if (child.nodeValue.trim() === 'Добавить купоны') {
                            child.nodeValue = 'Добавить промокод';
                        }
                    }
                });
            };

            document.querySelectorAll('*').forEach(el => {
                replaceText(el);
            });

        });
    </script>
<?php
});

add_filter('woocommerce_thankyou_order_received_text', function ($text, $order) {
    return '<p class="thankyou-text" style="font-size: 28px; margin-bottom: 10px;">Благодарим Вас за заказ.</p> <p style="margin-bottom: 30px;">В ближайшее время наш менеджер свяжется с Вами для уточнения деталей доставки.</p>';
}, 10, 2);

add_filter('woocommerce_checkout_required_field_notice', function ($message, $field_label) {

    // убираем "Выставление счёта "
    $message = str_replace('Выставление счёта ', '', $message);

    return $message;
}, 10, 2);

add_filter('woocommerce_checkout_fields', function ($fields) {

    // Имя
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['placeholder'] = 'Имя*';
    }

    // Фамилия
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['placeholder'] = 'Фамилия*';
    }

    return $fields;
});

add_action('wp_footer', function () {
?>
    <script>
        (function() {

            function updateLabel() {

                document.querySelectorAll('.wc-block-components-totals-item').forEach(function(row) {

                    const label = row.querySelector('.wc-block-components-totals-item__label');

                    if (!label) return;

                    if (label.textContent.trim() === 'Скидка') {
                        label.textContent = 'Скидка по промокоду';
                    }

                });

            }

            const observer = new MutationObserver(updateLabel);

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(updateLabel, 500);
            });

        })();
    </script>
<?php
});
