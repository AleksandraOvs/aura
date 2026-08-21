<?php
/*
Plugin Name: Custom WooCommerce Filters (Auto Detect, Full Compatible)
Description: AJAX фильтр WooCommerce с авто-определением атрибутов (полная совместимость с исходной версткой)
Version: 2.2
Author: PurpleWeb
*/

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------
 * Подключение JS и CSS
 * --------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_script('jquery-ui-slider');
    wp_enqueue_style(
        'jquery-ui-style',
        'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css'
    );

    wp_enqueue_style(
        'cwc-style',
        plugin_dir_url(__FILE__) . 'css/style.css'
    );

    wp_enqueue_script(
        'cwc-scripts',
        plugin_dir_url(__FILE__) . 'js/scripts.js',
        'jquery',
        '1.1',
        true
    );

    wp_enqueue_script(
        'cwc-ajax-filters',
        plugin_dir_url(__FILE__) . 'js/ajax-filters.js',
        ['jquery', 'jquery-ui-slider'],
        '2.2',
        true
    );

    wp_localize_script('cwc-ajax-filters', 'cwc_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
});

/* ---------------------------------------------------
 * Фильтр по брендам
 * --------------------------------------------------- */

function cwc_get_brand_filter($current_cat_id = 0)
{
    $taxonomy = 'product_brand';

    if (!taxonomy_exists($taxonomy)) {
        return '';
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    // 🔥 используем твою же функцию
    return cwc_render_attribute_filter(
        $taxonomy,
        'Бренд',
        $current_cat_id
    );
}

/* ---------------------------------------------------
 * Диапазон цен магазина и категорий
 * --------------------------------------------------- */
function cwc_get_category_price_range($category_id = 0)
{
    $args = [
        'status' => 'publish',
        'limit' => -1,
    ];

    if ($category_id) {
        $args['tax_query'] = [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ]];
    }

    $products = wc_get_products($args);
    $prices = [];

    foreach ($products as $product) {
        if ($product->is_type('variable')) {
            $prices[] = (float)$product->get_variation_price('min', true);
            $prices[] = (float)$product->get_variation_price('max', true);
        } else {
            $prices[] = (float)$product->get_price();
        }
    }

    if (!$prices) {
        return [0, 100000];
    }

    return [
        floor(min($prices)),
        ceil(max($prices)),
    ];
}

// Диапазон цен всего магазина
function cwc_get_store_price_range()
{
    return cwc_get_category_price_range(0);
}



/* ---------------------------------------------------
 * Все атрибуты WooCommerce
 * --------------------------------------------------- */
function cwc_get_all_product_attributes()
{
    $taxes = wc_get_attribute_taxonomies();
    $out = [];

    foreach ($taxes as $tax) {
        $out[] = 'pa_' . $tax->attribute_name;
    }

    return $out;
}

/* ---------------------------------------------------
 * Очистка заголовка
 * --------------------------------------------------- */
function cwc_clean_title($title)
{
    return preg_replace('/^Товар\s*[:\-–—]?\s*/ui', '', $title);
}

/* ---------------------------------------------------
 * ТЕКСТОВЫЙ АТРИБУТ
 * --------------------------------------------------- */
function cwc_render_attribute_filter($taxonomy, $title, $current_cat_id = 0)
{
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    if (!$terms || is_wp_error($terms)) return '';

    list($store_min, $store_max) = cwc_get_store_price_range();

    // Отфильтруем термы, у которых нет товаров
    $filtered_terms = [];
    foreach ($terms as $term) {
        $args = [
            'status' => 'publish',
            'limit'  => -1,
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ],
            ],
            'meta_query' => [
                [
                    'key'     => '_price',
                    'value'   => [$store_min, $store_max],
                    'compare' => 'BETWEEN',
                    'type'    => 'NUMERIC',
                ]
            ]
        ];

        if ($current_cat_id) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $current_cat_id,
            ];
        }

        $count = count(wc_get_products($args));

        if ($count > 0) {
            $term->count = $count; // добавим количество для вывода
            $filtered_terms[] = $term;
        }
    }

    if (!$filtered_terms) {
        return '';
    }

    usort($filtered_terms, function ($a, $b) {

        $a_num = is_numeric($a->name);
        $b_num = is_numeric($b->name);

        // оба числовые
        if ($a_num && $b_num) {
            return (float)$a->name <=> (float)$b->name;
        }

        // иначе по алфавиту
        return strnatcasecmp($a->name, $b->name);
    });

    ob_start(); ?>
    <div class="filter">
        <div class="filter-item__title">
            <?php echo esc_html(cwc_clean_title($title)); ?>
            <div class="filter-item-title__toggle">
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="filter-item__content">
            <ul class="sidebar-list" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                <?php foreach ($filtered_terms as $term): ?>
                    <li>
                        <a href="#" class="filter-item" data-slug="<?php echo esc_attr($term->slug); ?>">
                            <?php echo esc_html($term->name); ?> <?php //echo $term->count; 
                                                                    ?>
                        </a>

                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php
    return ob_get_clean();
}



/* ---------------------------------------------------
 * ФИЛЬТР ЦЕНЫ
 * --------------------------------------------------- */
function cwc_render_price_filter()
{
    $current_cat_id = is_product_category() ? get_queried_object_id() : 0;
    list($min, $max) = cwc_get_category_price_range($current_cat_id);

    ob_start(); ?>
    <div class="filter">

        <div class="filter-item__title">
            Цена
            <div class="filter-item-title__toggle">
                <span></span>
                <span></span>
            </div>

        </div>

        <div class="filter-item__content">
            <div class="price-range-wrap">
                <div id="price-slider" class="price-range" data-min="<?php echo $min; ?>" data-max="<?php echo $max; ?>"></div>
                <div class="range-inputs">
                    <div class="price-input"><span class="price-prefix">От</span><input type="number" id="min_price" value="<?php echo $min; ?>"></div>
                    <div class="price-input"><span class="price-prefix">До</span><input type="number" id="max_price" value="<?php echo $max; ?>"></div>
                </div>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/* ---------------------------------------------------
 * ШОРТКОД
 * --------------------------------------------------- */
function cwc_shop_filters_shortcode()
{
    $current_cat_id = is_product_category() ? get_queried_object_id() : 0;

    $text_filters = [];
    $brand_filter = cwc_get_brand_filter($current_cat_id);

    $filters = [];

    foreach (cwc_get_all_product_attributes() as $taxonomy) {

        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $tax = get_taxonomy($taxonomy);

        $filters[] = cwc_render_attribute_filter(
            $taxonomy,
            $tax->label ?? $taxonomy,
            $current_cat_id
        );
    }

    ob_start(); ?>

    <div class="filters-head">
        <div class="filter-toggle">
            <svg width="20" height="19" viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.0713 13.2139C11.7821 13.2139 12.4642 13.4964 12.9668 13.999C13.3496 14.3819 13.6042 14.8689 13.7041 15.3936H19C19.276 15.3936 19.4998 15.6176 19.5 15.8936C19.5 16.1697 19.2761 16.3936 19 16.3936H13.7041C13.6043 16.9188 13.3499 17.4068 12.9668 17.79C12.4642 18.2927 11.7821 18.5751 11.0713 18.5752C10.3599 18.5752 9.67796 18.2912 9.17578 17.7891C8.79268 17.4059 8.53909 16.9184 8.43945 16.3936H0.5C0.223858 16.3936 0 16.1697 0 15.8936C0.000240539 15.6176 0.224006 15.3936 0.5 15.3936H8.43945C8.5392 14.8691 8.79291 14.3819 9.17578 13.999C9.67838 13.4964 10.3605 13.2139 11.0713 13.2139ZM11.0713 14.2139C10.6257 14.2139 10.1979 14.391 9.88281 14.7061C9.56793 15.021 9.39074 15.4482 9.39062 15.8936C9.39062 16.3391 9.5678 16.767 9.88281 17.082C10.1983 17.3975 10.6263 17.5752 11.0713 17.5752C11.5169 17.5751 11.9447 17.3981 12.2598 17.083C12.575 16.7677 12.752 16.339 12.752 15.8936C12.7518 15.4485 12.575 15.0214 12.2598 14.7061C11.9447 14.391 11.5169 14.2139 11.0713 14.2139ZM4.46387 6.60742C5.17464 6.60742 5.85678 6.88901 6.35938 7.3916C6.74245 7.77468 6.99602 8.26237 7.0957 8.78711H19C19.2761 8.78711 19.5 9.01097 19.5 9.28711C19.4999 9.5632 19.2761 9.78711 19 9.78711H7.0957C6.99603 10.3116 6.7422 10.7987 6.35938 11.1816C5.85678 11.6842 5.17465 11.9668 4.46387 11.9668C3.75313 11.9668 3.0719 11.6842 2.56934 11.1816C2.1863 10.7986 1.93173 10.3118 1.83203 9.78711H0.5C0.223894 9.78711 5.92621e-05 9.5632 0 9.28711C0 9.01097 0.223858 8.78711 0.5 8.78711H1.83203C1.9317 8.26235 2.18625 7.77469 2.56934 7.3916C3.07187 6.88927 3.75331 6.60746 4.46387 6.60742ZM4.46387 7.60742C4.01852 7.60746 3.59136 7.78383 3.27637 8.09863C2.96131 8.41369 2.78418 8.84155 2.78418 9.28711C2.78421 9.73263 2.96134 10.1596 3.27637 10.4746C3.5914 10.7896 4.01835 10.9668 4.46387 10.9668C4.90943 10.9668 5.33728 10.7897 5.65234 10.4746C5.96715 10.1596 6.14353 9.73246 6.14355 9.28711C6.14355 8.84155 5.9674 8.41369 5.65234 8.09863C5.33728 7.78358 4.90942 7.60742 4.46387 7.60742ZM13.7139 0C14.0657 0 14.4142 0.069487 14.7393 0.204102C15.0644 0.338784 15.3605 0.536295 15.6094 0.785156C15.8581 1.03396 16.0558 1.32926 16.1904 1.6543C16.2607 1.82389 16.3116 2.00022 16.3457 2.17969H19C19.276 2.17969 19.4998 2.40369 19.5 2.67969C19.5 2.95583 19.2761 3.17969 19 3.17969H16.3467C16.3126 3.35945 16.2608 3.5362 16.1904 3.70605C16.0558 4.03107 15.8581 4.32641 15.6094 4.5752C15.3605 4.82406 15.0644 5.02157 14.7393 5.15625C14.4142 5.29083 14.0657 5.36035 13.7139 5.36035C13.0033 5.36032 12.3219 5.0776 11.8193 4.5752C11.4362 4.19206 11.1817 3.70452 11.082 3.17969H0.5C0.223858 3.17969 0 2.95583 0 2.67969C0.000168848 2.40369 0.223962 2.17969 0.5 2.17969H11.082C11.1818 1.65509 11.4363 1.16815 11.8193 0.785156C12.3219 0.282646 13.0032 3.51019e-05 13.7139 0ZM13.7139 1C13.2684 1.00004 12.8414 1.17721 12.5264 1.49219C12.2114 1.80717 12.0343 2.23424 12.0342 2.67969C12.0342 3.12525 12.2113 3.5531 12.5264 3.86816C12.8414 4.18303 13.2685 4.36032 13.7139 4.36035C13.9343 4.36035 14.1528 4.31671 14.3564 4.23242C14.5603 4.14799 14.7463 4.02417 14.9023 3.86816C15.0583 3.71221 15.1822 3.52698 15.2666 3.32324C15.351 3.11942 15.3936 2.90031 15.3936 2.67969C15.3935 2.45924 15.3509 2.24079 15.2666 2.03711C15.1822 1.8334 15.0582 1.64813 14.9023 1.49219C14.7463 1.33619 14.5603 1.21236 14.3564 1.12793C14.1527 1.0436 13.9344 1 13.7139 1Z" fill="#332233" />
            </svg>
        </div>
    </div>


    <div class="sidebar-area-wrapper _filters" data-current-cat="<?php echo esc_attr($current_cat_id); ?>">

        <div class="filters-wrapper">
            <?php
            // 🔥 БРЕНДЫ (сразу после цены)
            if (!empty($brand_filter)) {
                echo $brand_filter;
            }
            ?>

            <?php echo cwc_render_price_filter(); ?>

            <!-- <div class="single-sidebar-wrap">
                <div class="sidebar-body">
                    <ul class="sidebar-list" data-taxonomy="instock_filter">
                        <li>
                            <a href="#" class="filter-item" data-slug="instock">
                                <span class="filter-checkbox"></span> Есть в наличии
                            </a>
                        </li>
                    </ul>
                </div>
            </div> -->

            <?php
            echo implode('', $filters);
            ?>

            <div class="cwc-filter-actions">
                <button id="cwc-apply-filters" class="cwc-apply-button">Показать результаты</button>
                <button id="cwc-reset-filters" class="cwc-reset-button">Сброс</button>
            </div>
        </div>



    </div>
<?php
    return ob_get_clean();
}
add_shortcode('shop_filters', 'cwc_shop_filters_shortcode');

/* ---------------------------------------------------
 * AJAX: фильтрация товаров
 * --------------------------------------------------- */
function cwc_filter_products_callback()
{

    error_log('CWC POST: ' . print_r($_POST, true));
    if (!isset($_POST['action']) || $_POST['action'] !== 'cwc_filter_products') {
        wp_send_json_error('Неверный запрос');
    }

    $tax_query  = [];
    $meta_query = ['relation' => 'AND'];

    /* -------------------------
     * Атрибуты
     * ------------------------- */
    foreach ($_POST as $key => $value) {

        if (strpos($key, 'filter_') !== 0) continue;
        if ($key === 'filter_current_cat_id') continue;

        $taxonomy = str_replace('filter_', '', $key);

        $terms = is_array($value)
            ? array_map('sanitize_text_field', $value)
            : [sanitize_text_field($value)];

        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $terms,
            'operator' => 'IN',
        ];
    }

    /* -------------------------
     * Цена (ПРАВИЛЬНО ДЛЯ ВАРИАЦИЙ)
     * ------------------------- */
    if (isset($_POST['min_price'], $_POST['max_price'])) {

        $min_price = floatval($_POST['min_price']);
        $max_price = floatval($_POST['max_price']);

        $meta_query[] = [
            'relation' => 'OR',

            // простые товары
            [
                'key'     => '_price',
                'value'   => [$min_price, $max_price],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ],

            // вариативные: диапазоны пересекаются
            [
                'key'     => '_min_variation_price',
                'value'   => $max_price,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => '_max_variation_price',
                'value'   => $min_price,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ];
    }

    /* -------------------------
     * Категория
     * ------------------------- */
    if (!empty($_POST['current_cat_id'])) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => intval($_POST['current_cat_id']),
        ];
    }

    /* -------------------------
     * WP_Query (ВМЕСТО wc_get_products)
     * ------------------------- */
    $query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'tax_query'      => $tax_query ?: [],
        'meta_query'     => count($meta_query) > 1 ? $meta_query : [],
    ]);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
    } else {
        echo '<p class="no-products">Товары не найдены</p>';
    }

    wp_reset_postdata();

    wp_send_json_success([
        'html' => ob_get_clean()
    ]);
}

add_action('wp_ajax_cwc_filter_products', 'cwc_filter_products_callback');
add_action('wp_ajax_nopriv_cwc_filter_products', 'cwc_filter_products_callback');
