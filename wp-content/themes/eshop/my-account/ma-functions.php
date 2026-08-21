<?php
// 1️⃣ Добавляем вкладку в меню ЛК
add_filter('woocommerce_account_menu_items', function ($items) {
    $items['favorites'] = 'Избранное'; // добавляем вкладку
    return $items;
}, 20);

// 2️⃣ Регистрируем endpoint для вкладки
add_action('init', function () {
    add_rewrite_endpoint('favorites', EP_PAGES);
});

// 3️⃣ Выводим контент вкладки напрямую
add_action('woocommerce_account_favorites_endpoint', function () {

    echo '<h3>Избранное</h3>';
    echo do_shortcode('[custom_wishlist]'); // сюда вставьте ваш шорткод

});

// Поле ФИО в анкете пользователя
add_action('woocommerce_edit_account_form', function () {

    $user_id   = get_current_user_id();
    $full_name = get_user_meta($user_id, 'account_full_name', true);
?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="account_full_name">
            Имя и фамилия <span class="required">*</span>
        </label>
        <input
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="account_full_name"
            id="account_full_name"
            value="<?php echo esc_attr($full_name); ?>"
            placeholder="Ф.И.О."
            required />
    </p>
<?php
});

// Сохраняем ФИО пользователя
add_action('woocommerce_save_account_details', function ($user_id) {

    if (isset($_POST['account_full_name'])) {
        update_user_meta(
            $user_id,
            'account_full_name',
            sanitize_text_field($_POST['account_full_name'])
        );
    }
});

//Вывод ФИО на странице «Анкета» (просмотр)
// $user_id   = get_current_user_id();
// $full_name = get_user_meta($user_id, 'account_full_name', true);

// if ($full_name) {
//     echo '<p><strong>Имя и фамилия:</strong> ' . esc_html($full_name) . '</p>';
// }

// Подставляем ФИО из аккаунта в оформление заказа
add_filter('woocommerce_checkout_get_value', function ($value, $input) {

    if ($input === 'billing_full_name' && is_user_logged_in()) {
        $full_name = get_user_meta(get_current_user_id(), 'account_full_name', true);
        if ($full_name) {
            return $full_name;
        }
    }

    return $value;
}, 10, 2);


//----------МЕНЮ ЛИЧНОГО КАБИНЕТА--------------//

add_filter('woocommerce_account_menu_items', function ($items) {

    $ordered = [];

    // 1. Мои данные
    if (isset($items['edit-account'])) {
        $ordered['edit-account'] = 'Мои данные';
    }

    // 2. Избранное
    if (isset($items['favorites'])) {
        $ordered['favorites'] = 'Избранное';
    }

    // 3. Заказы
    if (isset($items['orders'])) {
        $ordered['orders'] = 'Заказы';
    }



    return $ordered;
}, 100);
