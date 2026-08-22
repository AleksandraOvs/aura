<?php
/* подключение стилей и скриптов */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'wl_styles',
        get_stylesheet_directory_uri() . '/wishlist/wl-style.css',
        array(),
        time()
    );

    wp_enqueue_script(
        'wl_scripts',
        get_template_directory_uri() . '/wishlist/wl-scripts.js',
        array('jquery'),
        null,
        true
    );

    wp_localize_script('wl_scripts', 'wl_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
});

// --------------------------------------------------
// КНОПКА ДОБАВИТЬ В ИЗБРАННОЕ
// --------------------------------------------------
add_action('woocommerce_after_shop_loop_item', 'custom_add_to_wishlist_button', 15);
add_action('woocommerce_single_product_summary', 'custom_add_to_wishlist_button', 32);

function custom_add_to_wishlist_button()
{
    global $product;
    if (!$product) return;

    $product_id = $product->get_id();

    // Получаем текущий вишлист
    $wishlist = [];
    if (is_user_logged_in()) {
        $wishlist = get_user_meta(get_current_user_id(), 'custom_wishlist', true) ?: [];
    } elseif (!empty($_COOKIE['custom_wishlist'])) {
        $wishlist = json_decode(stripslashes($_COOKIE['custom_wishlist']), true);
        if (!is_array($wishlist)) $wishlist = [];
    }

    $added = in_array($product_id, $wishlist) ? 'added' : '';
    $wishlist_icon = '<svg width="29" height="26" viewBox="0 0 29 26" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.9834 1C21.8169 1.00007 23.4892 1.6382 24.75 2.66895L24.9971 2.87988C26.3343 4.08409 27.1426 5.74037 27.1426 7.56152C27.1425 10.2866 25.8123 12.9936 23.6045 15.7686L23.1514 16.3242C20.8576 19.0761 17.913 21.7265 14.9033 24.4307V24.4316C14.4245 24.8621 13.7016 24.8523 13.2363 24.4287L13.2314 24.4238L10.9971 22.4043C8.79358 20.3905 6.70955 18.386 4.99023 16.3242L4.53711 15.7686C2.32944 12.9937 1.00007 10.2866 1 7.56152C1 5.73956 1.8084 4.08228 3.14648 2.88184L3.14844 2.88086C4.4316 1.72608 6.20631 1 8.15918 1C9.84003 1.00005 11.0995 1.37513 12.1807 2.09082L12.3945 2.23828C12.733 2.48229 13.0498 2.75975 13.3506 3.07227L14.0713 3.82129L14.792 3.07129C15.0925 2.75878 15.4094 2.48179 15.749 2.23828H15.75C16.8785 1.42685 18.1926 1 19.9834 1Z" stroke="#b6713d" stroke-width="2"/>
</svg>';

    echo '<button class="custom-wishlist-btn ' . esc_attr($added) . '" data-product_id="' . esc_attr($product_id) . '">
            <span class="wishlist-icon">' . $wishlist_icon . '</span>
            <span class="wishlist-text">' . ($added ? 'В избранном' : 'Добавить в избранное') . '</span>
          </button>';
}

// --------------------------------------------------
// AJAX ДОБАВЛЕНИЕ / УДАЛЕНИЕ
// --------------------------------------------------
add_action('wp_ajax_custom_toggle_wishlist', 'custom_toggle_wishlist');
add_action('wp_ajax_nopriv_custom_toggle_wishlist', 'custom_toggle_wishlist');

function custom_toggle_wishlist()
{
    if (empty($_POST['product_id'])) {
        wp_send_json_error();
    }

    $product_id = intval($_POST['product_id']);
    $wishlist = [];

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $wishlist = get_user_meta($user_id, 'custom_wishlist', true) ?: [];
    } elseif (!empty($_COOKIE['custom_wishlist'])) {
        $wishlist = json_decode(stripslashes($_COOKIE['custom_wishlist']), true);
        if (!is_array($wishlist)) $wishlist = [];
    }

    if (in_array($product_id, $wishlist)) {
        $wishlist = array_values(array_diff($wishlist, [$product_id]));
        $status = 'removed';
    } else {
        $wishlist[] = $product_id;
        $wishlist = array_unique($wishlist);
        $status = 'added';
    }

    // сохраняем
    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), 'custom_wishlist', $wishlist);
    } else {
        setcookie('custom_wishlist', wp_json_encode(array_values($wishlist)), time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }

    wp_send_json_success(['status' => $status]);
}

// --------------------------------------------------
// ШОРТКОД [custom_wishlist]
// --------------------------------------------------
add_shortcode('custom_wishlist', 'custom_wishlist_shortcode');

function custom_wishlist_shortcode()
{
    $wishlist = [];

    if (is_user_logged_in()) {
        $wishlist = get_user_meta(get_current_user_id(), 'custom_wishlist', true) ?: [];
    } elseif (!empty($_COOKIE['custom_wishlist'])) {
        $wishlist = json_decode(stripslashes($_COOKIE['custom_wishlist']), true);
        if (!is_array($wishlist)) {
            $wishlist = [];
        }
    }

    if (empty($wishlist)) {
        return '
            <div class="empty-wl"><p>Ваш список избранного пуст</p>

            <p class="return-to-shop">
                <a href="' . esc_url(site_url('shop')) . '" class="button wc-backward">В каталог</a>
            </p>
            </div>
        ';
    }

    $clean_wishlist = [];

    foreach ($wishlist as $product_id) {

        if (get_post_status($product_id) !== 'publish') {
            continue;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            continue;
        }

        $clean_wishlist[] = $product_id;
    }

    // Если пользователь авторизован — очищаем wishlist от удалённых/невалидных товаров
    if (is_user_logged_in() && $clean_wishlist !== $wishlist) {
        update_user_meta(
            get_current_user_id(),
            'custom_wishlist',
            $clean_wishlist
        );
    }

    if (empty($clean_wishlist)) {
        return '
            <p class="empty-wl">Ваш список избранного пуст</p>
            <p class="return-to-shop">
                <a href="' . esc_url(site_url('shop')) . '" class="button wc-backward">В магазин</a>
            </p>
        ';
    }

    ob_start();
?>

    <div class="cart-flex woocommerce-cart-form__contents">

        <?php foreach ($clean_wishlist as $product_id) :

            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $sku = $product->get_sku() ?: '—';
            $price = (float) $product->get_price();
            $price_html = wc_price($price);

            $in_cart = false;

            if (function_exists('WC') && WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    if ((int) $cart_item['product_id'] === (int) $product_id) {
                        $in_cart = true;
                        break;
                    }
                }
            }
        ?>

            <div class="cart-flex__row wishlist-item">

                <div class="cart-flex__col cart-flex__col--product">
                    <div class="cart-product-item">

                        <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="product-thumb">
                            <?php echo $product->get_image(); ?>
                        </a>

                        <div class="cart-product-item__summary">

                            <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="product-name">
                                <?php echo esc_html($product->get_name()); ?>
                            </a>

                            <div class="sku">
                                Артикул: <?php echo esc_html($sku); ?>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="cart-flex__col cart-flex__col--price">

                    <div class="cart-flex__col__label">
                        Цена:
                    </div>

                    <span
                        class="price"
                        data-raw-price="<?php echo esc_attr($price); ?>">
                        <?php echo $price_html; ?>
                    </span>

                </div>

                <div class="cart-flex__col cart-flex__col--total">

                    <div class="wishlist-cart-actions">

                        <?php if ($in_cart) : ?>

                            <a
                                href="<?php echo esc_url(wc_get_cart_url()); ?>"
                                class="button wishlist-in-cart">
                                В корзине
                            </a>

                        <?php else : ?>

                            <a
                                href="<?php echo esc_url($product->add_to_cart_url()); ?>"
                                class="button add_to_cart_button ajax_add_to_cart wishlist-add-to-cart"
                                data-product_id="<?php echo esc_attr($product_id); ?>"
                                data-quantity="1"
                                rel="nofollow">
                                В корзину
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="product-remove">

                    <button
                        class="custom-wishlist-btn added"
                        data-product_id="<?php echo esc_attr($product_id); ?>"
                        aria-label="Удалить из избранного">

                        <span class="wishlist-icon">

                            <svg
                                width="29"
                                height="26"
                                viewBox="0 0 29 26"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M19.9834 1C21.8169 1.00007 23.4892 1.6382 24.75 2.66895L24.9971 2.87988C26.3343 4.08409 27.1426 5.74037 27.1426 7.56152C27.1425 10.2866 25.8123 12.9936 23.6045 15.7686L23.1514 16.3242C20.8576 19.0761 17.913 21.7265 14.9033 24.4307V24.4316C14.4245 24.8621 13.7016 24.8523 13.2363 24.4287L13.2314 24.4238L10.9971 22.4043C8.79358 20.3905 6.70955 18.386 4.99023 16.3242L4.53711 15.7686C2.32944 12.9937 1.00007 10.2866 1 7.56152C1 5.73956 1.8084 4.08228 3.14648 2.88184L3.14844 2.88086C4.4316 1.72608 6.20631 1 8.15918 1C9.84003 1.00005 11.0995 1.37513 12.1807 2.09082L12.3945 2.23828C12.733 2.48229 13.0498 2.75975 13.3506 3.07227L14.0713 3.82129L14.792 3.07129C15.0925 2.75878 15.4094 2.48179 15.749 2.23828H15.75C16.8785 1.42685 18.1926 1 19.9834 1Z"
                                    fill="#DC3545"
                                    stroke="none" />
                            </svg>

                        </span>

                    </button>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php
    return ob_get_clean();
}
