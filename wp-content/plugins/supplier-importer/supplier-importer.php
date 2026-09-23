<?php

/**
 * Plugin Name: Supplier Importer
 * Description: Импорт товаров поставщиков в WooCommerce.
 * Version: 1.0.0
 * Author: Purple Web
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'config/constants.php';

require_once SUPPLIER_IMPORTER_PATH . 'includes/Core/class-loader.php';
require_once SUPPLIER_IMPORTER_PATH . 'includes/Core/class-plugin.php';

register_activation_hook(
    __FILE__,
    ['Supplier_Importer\Core\Plugin', 'activate']
);

Supplier_Importer\Core\Plugin::init();

add_action('admin_notices', function () {
    if (
        !isset($_GET['supplier_import_debug'])
        || !current_user_can('manage_woocommerce')
    ) {
        return;
    }

    echo '<div class="notice notice-info"><p>';
    echo '<strong>Supplier Import nonce:</strong> ';
    echo esc_html(
        wp_create_nonce('supplier_import')
    );
    echo '</p></div>';
});
