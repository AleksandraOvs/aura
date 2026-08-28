<?php

/**
 * Function describe for E-Shop 
 * 
 * @package e-shop
 */
if (! defined('_S_VERSION')) {
    // Replace the version number of the theme on each release.
    define('_S_VERSION', '1.0.0');
}

include_once(trailingslashit(get_stylesheet_directory()) . 'lib/e-shop-metaboxes.php');
include_once(trailingslashit(get_stylesheet_directory()) . 'lib/custom-config.php');

add_action('wp_enqueue_scripts', 'e_shop_enqueue_styles');

function mytheme_add_woocommerce_support()
{
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'mytheme_add_woocommerce_support');

//add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');

function e_shop_enqueue_styles()
{

    wp_enqueue_style('normalize-stylesheet', get_template_directory_uri() . '/css/normalize.css');
    wp_enqueue_style('eshop-stylesheet', get_template_directory_uri() . '/css/main-styles.css');
    wp_enqueue_style('eshop-animations', get_template_directory_uri() . '/css/animations.css');
    // wp_enqueue_style('eshop-header', get_template_directory_uri() . '/assets/css/header-style.css');
    // wp_enqueue_style('patterns-styles', get_template_directory_uri() . '/assets/css/patterns.css');
    // wp_enqueue_style('toggle-contacts-styles', get_template_directory_uri() . '/assets/css/toggle-contacts.css');
    // wp_enqueue_style('mob-menu-styles', get_template_directory_uri() . '/assets/css/mobile-menu.css');
    wp_enqueue_style('mini-cart-styles', get_template_directory_uri() . '/css/mini-cart.css');
    wp_enqueue_style('projects-styles', get_template_directory_uri() . '/css/projects.css');
    wp_enqueue_style('cooperation-styles', get_template_directory_uri() . '/css/cooperation.css');
    wp_enqueue_style('template-styles', get_template_directory_uri() . '/css/templates.css');
    wp_enqueue_style('woo-styles', get_template_directory_uri() . '/css/woo-styles.css');
    wp_enqueue_style('filter-styles', get_template_directory_uri() . '/css/filter-styles.css');
    wp_enqueue_style('my-account-styles', get_template_directory_uri() . '/css/myaccount.css');
    wp_enqueue_style('cart-styles', get_template_directory_uri() . '/css/cart.css');

    wp_enqueue_style('popups-styles', get_template_directory_uri() . '/css/popups.css');
    if (is_product()) {
        wp_enqueue_style('product-tabs-styles', get_template_directory_uri() . '/css/woo-product-tabs-styles.css');
    }
    //wp_enqueue_style('e-shop-styles', get_stylesheet_directory_uri());
    wp_enqueue_script('animations-script', get_stylesheet_directory_uri() . '/js/animations.js', array(), _S_VERSION, true);
    wp_enqueue_script('woo-scripts', get_stylesheet_directory_uri() . '/js/woo-scripts.js', array(), _S_VERSION, true);
    wp_enqueue_script('sliders-script', get_stylesheet_directory_uri() . '/js/sliders.js', array(), _S_VERSION, true);
    wp_enqueue_script('minicart-script', get_stylesheet_directory_uri() . '/js/mini-cart-scripts.js', array(), _S_VERSION, true);
    wp_enqueue_script('header-search-script', get_stylesheet_directory_uri() . '/js/header-search.js', array(), _S_VERSION, true);
    wp_enqueue_script('header-menu-script', get_stylesheet_directory_uri() . '/js/header-catalog-menu.js', array(), _S_VERSION, true);
    wp_enqueue_script('popups-script', get_stylesheet_directory_uri() . '/js/popup.js', array(), _S_VERSION, true);



    if (is_product()) {

        wp_enqueue_script('cross-us-sales-sliders-script', get_stylesheet_directory_uri() . '/js/cross-up-sales-sliders.js', array(), _S_VERSION, true);
    };


    //CHECKOUT
    wp_enqueue_script('chtckout-script', get_stylesheet_directory_uri() . '/js/checkout.js', array(), _S_VERSION, true);
    wp_enqueue_style('checkout-styles', get_template_directory_uri() . '/css/checkout.css');
}

if (!function_exists('e_shop_theme_setup')) {
    function e_shop_theme_setup()
    {
        // Локализация темы
        load_child_theme_textdomain('e-shop', get_stylesheet_directory() . '/languages');

        // Поддержка стилей редактора
        add_theme_support('editor-styles');

        // Настройка размеров изображений
        add_image_size('eshop-slider', 1140, 488, true);

        // Поддержка кастомного логотипа
        add_theme_support('custom-logo', [
            'height'      => 100,
            'width'       => 400,
            'flex-height' => true,
            'flex-width'  => true,
        ]);

        // Поддержка кастомного фона
        add_theme_support('custom-background', [
            'default-color' => 'ffffff',
        ]);
    }
}
add_action('after_setup_theme', 'e_shop_theme_setup');

function theme_widgets_init()
{

    register_sidebar(array(
        'name'          => esc_html__('Footer Sidebar 1', 'eshop'),
        'id'            => 'footer-sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'eshop'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer Sidebar 2', 'eshop'),
        'id'            => 'footer-sidebar-2',
        'description'   => esc_html__('Add widgets here.', 'eshop'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer Sidebar 3', 'eshop'),
        'id'            => 'footer-sidebar-3',
        'description'   => esc_html__('Add widgets here.', 'eshop'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'theme_widgets_init');

// Подключение стилей редактора
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_style(
        'e-shop-editor-style',
        get_stylesheet_directory_uri() . '/css/editor.css',
        ['wp-edit-blocks'], // <-- зависимость от базовых стилей Gutenberg
        '1.0'
    );
});

register_nav_menus(
    array(
        'catalog_menu' => 'Меню Каталог',
        'customers_menu' => 'Покупателям',
        'docs_menu' => 'Документы'
    )
);

add_action('after_setup_theme', 'e_shop_theme_setup');

function e_shop_custom_remove($wp_customize)
{

    $wp_customize->remove_control('header-logo');
    $wp_customize->remove_section('site_bg_section');
}

add_action('customize_register', 'e_shop_custom_remove', 100);

// Remove parent theme homepage style.
// function e_shop_remove_page_templates($templates)
// {
//     unset($templates['template-home.php']);
//     return $templates;
// }

// add_filter('theme_page_templates', 'e_shop_remove_page_templates');

// Load theme info page.
// if (is_admin()) {
//     include_once(trailingslashit(get_template_directory()) . 'lib/welcome/welcome-screen.php');
// }

//разрешить загрузку свг только админам
function allow_svg_upload_for_admins($mimes)
{
    if (current_user_can('administrator')) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
}
add_filter('upload_mimes', 'allow_svg_upload_for_admins');

add_filter('template_include', 'var_template_include', 1000);
function var_template_include($t)
{
    $GLOBALS['current_theme_template'] = basename($t);
    return $t;
}

function get_current_template($echo = false)
{

    if (!isset($GLOBALS['current_theme_template']))
        return false;
    if ($echo)
        echo $GLOBALS['current_theme_template'];
    else
        return $GLOBALS['current_theme_template'];
}

require get_template_directory() . '/inc/walker.php';
require get_template_directory() . '/inc/customizer.php';
require get_stylesheet_directory() . '/inc/breadcrumbs.php';
require get_stylesheet_directory() . '/inc/views.php';
require get_stylesheet_directory() . '/inc/woo.php';
require get_stylesheet_directory() . '/inc/woo-minicart.php';
//require get_stylesheet_directory() . '/inc/woo-filter.php';
//require get_stylesheet_directory() . '/inc/woo-atts.php';
require get_stylesheet_directory() . '/inc/catalog-menu.php';
require get_stylesheet_directory() . '/inc/cpt.php';
require get_template_directory() . '/wishlist/wl.php';
require get_template_directory() . '/my-account/ma-functions.php';


add_action('init', function () {
    $patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    //error_log(print_r(array_keys($patterns), true));
});

// Добавляем новый столбец "ID"
add_filter('manage_edit-product_cat_columns', function ($columns) {
    // Вставляем после названия категории
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'name') {
            $new_columns['cat_id'] = 'ID';
        }
    }
    return $new_columns;
});

// Заполняем столбец значением ID
add_action('manage_product_cat_custom_column', function ($content, $column, $term_id) {
    if ($column === 'cat_id') {
        $content = $term_id;
    }
    return $content;
}, 10, 3);


//убираем хлебные крошки из функции - крошки используются свои - inc/breadcrumbs.php
add_action('init', function () {
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
});


// Меняем надпись "On Sale" на свою
add_filter('woocommerce_sale_flash', function ($html, $post, $product) {
    // Новый текст
    $new_text = '%'; // <- здесь меняем надпись
    return '<span class="onsale">' . esc_html($new_text) . '</span>';
}, 10, 3);

add_filter('woocommerce_get_order_address', function ($address, $type, $order) {
    if (is_admin() === false) {
        if ($type === 'shipping') {
            return [];
        }
    }
    return $address;
}, 10, 3);

/**
 * Получает дочерние страницы текущей страницы
 * или дочерние страницы её родителя.
 *
 * @return WP_Post[]
 */
function get_page_siblings()
{
    $current_id = get_queried_object_id();

    if (!$current_id) {
        return [];
    }

    // Если текущая страница дочерняя —
    // берем ID родительской страницы.
    $parent_id = wp_get_post_parent_id($current_id);

    // Если родителя нет — текущая страница является родительской.
    if (!$parent_id) {
        $parent_id = $current_id;
    }

    return get_children([
        'post_parent' => $parent_id,
        'post_type'   => 'page',
        'post_status' => 'publish',
        'orderby'     => 'menu_order',
        'order'       => 'ASC',
    ]);
}
