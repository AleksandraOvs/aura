<?php

/**
 * Plugin Name: PW Verified Reviews
 * Description: Отзывы покупателей WooCommerce с проверкой покупки и модерацией.
 * Version: 1.0.0
 * Author: Purple Web
 * Text Domain: pw-verified-reviews
 */

defined('ABSPATH') || exit;

define('PWVR_VERSION', '1.0.0');
define('PWVR_PATH', plugin_dir_path(__FILE__));
define('PWVR_URL', plugin_dir_url(__FILE__));

/**
 * Подключает модули плагина.
 */
function pwvr_init_plugin()
{

    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', 'pwvr_woocommerce_notice');
        return;
    }

    require_once PWVR_PATH . 'includes/class-pwvr-purchases.php';
    require_once PWVR_PATH . 'includes/class-pwvr-review-validation.php';
    require_once PWVR_PATH . 'includes/class-pwvr-frontend.php';
    require_once PWVR_PATH . 'includes/class-pwvr-ajax.php';


    PWVR_Review_Validation::init();
    PWVR_Frontend::init();
    PWVR_Ajax::init();
}
add_action('plugins_loaded', 'pwvr_init_plugin');

/**
 * Уведомление при отключённом WooCommerce.
 */
function pwvr_woocommerce_notice()
{
    echo '<div class="notice notice-error"><p>';
    echo esc_html__(
        'PW Verified Reviews требует установленный и активный WooCommerce.',
        'pw-verified-reviews'
    );
    echo '</p></div>';
}
