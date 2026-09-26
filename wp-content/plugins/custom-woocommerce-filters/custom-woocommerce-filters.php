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
    <div class="filter-price">

        <div class="filter-price-title">
            Ценовой диапазон
        </div>


        <div class="price-range-wrap">
            <div id="price-slider" class="price-range" data-min="<?php echo $min; ?>" data-max="<?php echo $max; ?>"></div>
            <div class="range-inputs">
                <div class="price-input"><span class="price-prefix">От</span><input type="number" id="min_price" value="<?php echo $min; ?>"></div>
                <div class="price-input"><span class="price-prefix">До</span><input type="number" id="max_price" value="<?php echo $max; ?>"></div>
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
            Скрыть фильтры
        </div>
    </div>


    <div class="sidebar-area-wrapper _filters opened" data-current-cat="<?php echo esc_attr($current_cat_id); ?>">

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
