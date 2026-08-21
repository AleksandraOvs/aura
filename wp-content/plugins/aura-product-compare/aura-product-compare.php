<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
/**
 * Plugin Name: AURA Product Compare
 * Description: Сравнение товаров WooCommerce
 * Version: 1.0.0
 * Author: AURA
 */



if (!defined('ABSPATH')) {
    exit;
}

define('AURA_COMPARE_VERSION', '1.0.0');
define('AURA_COMPARE_PATH', plugin_dir_path(__FILE__));
define('AURA_COMPARE_URL', plugin_dir_url(__FILE__));


require_once AURA_COMPARE_PATH . 'includes/class-compare.php';
require_once AURA_COMPARE_PATH . 'includes/class-compare-ajax.php';
require_once AURA_COMPARE_PATH . 'includes/class-compare-shortcode.php';


function aura_compare_init()
{

    new AURA_Product_Compare();
    new AURA_Product_Compare_Ajax();
    new AURA_Product_Compare_Shortcode();
}

add_action('plugins_loaded', 'aura_compare_init');
