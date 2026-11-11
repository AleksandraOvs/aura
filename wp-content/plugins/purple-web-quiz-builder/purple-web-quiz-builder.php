<?php

/**
 * Plugin Name: Purple Web Quiz Builder
 * Description: Конструктор товарных квизов для WordPress и WooCommerce.
 * Version: 1.1.0
 * Author: Purple Web
 * Text Domain: purple-web-quiz-builder
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PWQB_VERSION', '1.1.0');
define('PWQB_FILE', __FILE__);
define('PWQB_PATH', plugin_dir_path(__FILE__));
define('PWQB_URL', plugin_dir_url(__FILE__));

// Основные классы.
require_once PWQB_PATH . 'includes/class-pwqb-activator.php';
require_once PWQB_PATH . 'includes/class-pwqb-quiz.php';
require_once PWQB_PATH . 'includes/class-pwqb-admin.php';
require_once PWQB_PATH . 'includes/class-pwqb-shortcode.php';
require_once PWQB_PATH . 'includes/class-pwqb-submissions.php';
require_once PWQB_PATH . 'includes/class-pwqb-submissions-admin.php';

// Конструктор квизов.
require_once PWQB_PATH . 'includes/class-pwqb-builder.php';

// Активация и деактивация.
register_activation_hook(
    __FILE__,
    ['PWQB_Activator', 'activate']
);

register_deactivation_hook(
    __FILE__,
    ['PWQB_Activator', 'deactivate']
);

// Инициализация плагина.
add_action('plugins_loaded', static function () {

    PWQB_Quiz::init();

    PWQB_Admin::init();

    PWQB_Builder::init();

    PWQB_Shortcode::init();

    PWQB_Submissions_Admin::init();
});
