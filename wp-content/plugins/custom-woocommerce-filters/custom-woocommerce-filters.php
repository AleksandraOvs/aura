<?php
/*
Plugin Name: Custom WooCommerce Filters (Auto Detect, Full Compatible)
Description: AJAX фильтр WooCommerce с авто-определением атрибутов (полная совместимость с исходной версткой)
Version: 2.2
Author: PurpleWeb
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'admin-settings.php';

if (!function_exists('cwc_get_enabled_filter_attributes')) {
    function cwc_get_enabled_filter_attributes($category_id = 0)
    {
        // Все зарегистрированные атрибуты WooCommerce.
        $attributes = wc_get_attribute_taxonomies();
        $all_attributes = [];

        foreach ($attributes as $attribute) {
            $all_attributes[] = 'pa_' . $attribute->attribute_name;
        }

        // Если категория не выбрана — показываем все атрибуты.
        if (!$category_id) {
            return $all_attributes;
        }

        // Находим родительскую категорию верхнего уровня.
        $ancestors = get_ancestors(
            (int) $category_id,
            'product_cat',
            'taxonomy'
        );

        $root_category_id = $ancestors
            ? (int) end($ancestors)
            : (int) $category_id;

        // Настройки атрибутов из админки плагина.
        $settings = get_option('cwc_filter_attributes_by_category', []);

        // Если настройки ещё не сохранены, не скрываем все фильтры.
        if (
            !is_array($settings)
            || !array_key_exists($root_category_id, $settings)
        ) {
            return $all_attributes;
        }

        $enabled = $settings[$root_category_id];

        if (!is_array($enabled)) {
            return [];
        }

        // Возвращаем только существующие атрибуты, разрешённые в настройках.
        return array_values(
            array_intersect($all_attributes, $enabled)
        );
    }
}

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

function cwc_get_brand_filter($category_id = 0)
{
    $taxonomy = 'product_brand';

    if (!taxonomy_exists($taxonomy)) {
        return '';
    }

    return cwc_render_attribute_filter(
        $taxonomy,
        'Бренд',
        $category_id
    );
}

/* ---------------------------------------------------
 * Диапазон цен магазина и категорий
 * --------------------------------------------------- */
function cwc_get_category_price_range($category_id = 0)
{
    global $wpdb;

    $where = "
        pm.meta_key = '_price'
        AND pm.meta_value <> ''
        AND pm.meta_value >= 0
    ";

    $join = "
        INNER JOIN {$wpdb->postmeta} pm
            ON pm.post_id = p.ID
    ";

    $params = [];

    if ($category_id) {
        $join .= "
            INNER JOIN {$wpdb->term_relationships} tr
                ON tr.object_id = p.ID
            INNER JOIN {$wpdb->term_taxonomy} tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id
        ";

        $where .= "
            AND tt.taxonomy = 'product_cat'
            AND tt.term_id = %d
        ";

        $params[] = (int) $category_id;
    }

    $sql = "
        SELECT
            MIN(CAST(pm.meta_value AS DECIMAL(20,4))) AS min_price,
            MAX(CAST(pm.meta_value AS DECIMAL(20,4))) AS max_price
        FROM {$wpdb->posts} p
        {$join}
        WHERE
            p.post_type = 'product'
            AND p.post_status = 'publish'
            AND {$where}
    ";

    if ($params) {
        $sql = $wpdb->prepare($sql, $params);
    }

    $result = $wpdb->get_row($sql);

    if (
        !$result ||
        $result->min_price === null ||
        $result->max_price === null
    ) {
        return [0, 100000];
    }

    return [
        floor((float) $result->min_price),
        ceil((float) $result->max_price),
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

/**
 * Получает значения атрибута только для товаров выбранной категории
 * и её дочерних категорий, не загружая ID всех товаров в PHP.
 *
 * @param string $taxonomy    Таксономия атрибута или бренда.
 * @param int    $category_id ID текущей категории.
 *
 * @return array
 */
function cwc_get_category_filter_terms($taxonomy, $category_id = 0)
{
    global $wpdb;

    if (!taxonomy_exists($taxonomy)) {
        return [];
    }

    // На странице магазина или другой странице показываем все значения.
    if (!$category_id) {
        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ]);

        return is_wp_error($terms) ? [] : $terms;
    }

    // Получаем ID дочерних категорий, а не ID товаров.
    $children = get_term_children(
        (int) $category_id,
        'product_cat'
    );

    if (is_wp_error($children)) {
        $children = [];
    }

    $category_ids = array_unique(array_map(
        'absint',
        array_merge([(int) $category_id], $children)
    ));

    $placeholders = implode(
        ', ',
        array_fill(0, count($category_ids), '%d')
    );

    // Ищем только термины, назначенные опубликованным товарам
    // из выбранной категории или её потомков.
    $sql = "
        SELECT DISTINCT filter_tt.term_id
        FROM {$wpdb->term_relationships} AS filter_tr
        INNER JOIN {$wpdb->term_taxonomy} AS filter_tt
            ON filter_tt.term_taxonomy_id = filter_tr.term_taxonomy_id
        INNER JOIN {$wpdb->posts} AS p
            ON p.ID = filter_tr.object_id
        INNER JOIN {$wpdb->term_relationships} AS cat_tr
            ON cat_tr.object_id = p.ID
        INNER JOIN {$wpdb->term_taxonomy} AS cat_tt
            ON cat_tt.term_taxonomy_id = cat_tr.term_taxonomy_id
        WHERE filter_tt.taxonomy = %s
          AND cat_tt.taxonomy = 'product_cat'
          AND cat_tt.term_id IN ($placeholders)
          AND p.post_type = 'product'
          AND p.post_status = 'publish'
    ";

    $params = array_merge([$taxonomy], $category_ids);

    $term_ids = $wpdb->get_col(
        $wpdb->prepare($sql, $params)
    );

    if (empty($term_ids)) {
        return [];
    }

    // Получаем полноценные WP_Term без выборки всех товаров.
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'include'    => array_map('absint', $term_ids),
        'hide_empty' => false,
    ]);

    return is_wp_error($terms) ? [] : $terms;
}

/* ---------------------------------------------------
 * ТЕКСТОВЫЙ АТРИБУТ
 * --------------------------------------------------- */
/* ---------------------------------------------------
 * ФИЛЬТР АТРИБУТА / БРЕНДА
 * --------------------------------------------------- */
function cwc_render_attribute_filter($taxonomy, $title, $category_id = 0)
{
    // Получаем термины без загрузки ID всех товаров в PHP.
    $terms = cwc_get_category_filter_terms(
        $taxonomy,
        $category_id
    );

    if (empty($terms)) {
        return '';
    }

    usort($terms, function ($a, $b) {
        $a_num = is_numeric($a->name);
        $b_num = is_numeric($b->name);

        if ($a_num && $b_num) {
            return (float) $a->name <=> (float) $b->name;
        }

        return strnatcasecmp($a->name, $b->name);
    });

    ob_start();
?>
    <div class="filter">
        <div class="filter-item__title">
            <?php echo esc_html(cwc_clean_title($title)); ?>

            <div class="filter-item-title__toggle">
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="filter-item__content">
            <ul class="sidebar-list"
                data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                <?php foreach ($terms as $term) : ?>
                    <li>
                        <a
                            href="#"
                            class="filter-item"
                            data-slug="<?php echo esc_attr($term->slug); ?>">
                            <?php echo esc_html($term->name); ?>
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

    error_log('CWC START ' . microtime(true));
    $current_cat_id = is_product_category() ? get_queried_object_id() : 0;



    $text_filters = [];
    $brand_filter = cwc_get_brand_filter($current_cat_id);
    error_log('CWC BRAND ' . microtime(true));

    $filters = [];

    $enabled_attributes = cwc_get_enabled_filter_attributes(
        $current_cat_id
    );

    foreach ($enabled_attributes as $taxonomy) {
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

    $initial_count_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ];

    if ($current_cat_id) {
        $initial_count_args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $current_cat_id,
            ]
        ];
    }

    $initial_count_query = new WP_Query($initial_count_args);

    $initial_count = $initial_count_query->found_posts;
    error_log('CWC COUNT ' . microtime(true));
    ob_start(); ?>

    <div class="filters-head">
        <div class="filter-toggle"></div>
    </div>


    <div class="sidebar-area-wrapper _filters opened" data-current-cat="<?php echo esc_attr($current_cat_id); ?>">

        <div class="filters-wrapper">

            <div class="filters-wrapper__header">
                <div class="filter-wrapper__back">
                    <svg width="30" height="27" viewBox="0 0 30 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.1947 26.6173C13.6926 27.1275 12.8787 27.1275 12.3766 26.6173L0.376576 14.4237C-0.125527 13.9135 -0.125527 13.0865 0.376576 12.5763L12.3766 0.382653C12.8787 -0.127552 13.6926 -0.127552 14.1947 0.382653C14.6968 0.892856 14.6968 1.71987 14.1947 2.23007L4.38942 12.1935L28.7143 12.1935C29.4244 12.1935 30 12.7785 30 13.5C30 14.2215 29.4244 14.8065 28.7143 14.8065L4.38942 14.8065L14.1947 24.7699C14.6968 25.2801 14.6968 26.1071 14.1947 26.6173Z" fill="#979797" />
                    </svg>

                </div>
                <h3>Фильтры</h3>
                <div class="filter-wrapper__close">
                    <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M27.1551 0.488159C27.8059 -0.16272 28.861 -0.16272 29.5118 0.488159C30.1627 1.13904 30.1627 2.19407 29.5118 2.84495L17.3568 15L29.5118 27.1551C30.1627 27.8059 30.1627 28.861 29.5118 29.5118C28.861 30.1627 27.8059 30.1627 27.1551 29.5118L15 17.3568L2.84495 29.5118C2.19407 30.1627 1.13904 30.1627 0.488159 29.5118C-0.16272 28.861 -0.16272 27.8059 0.488159 27.1551L12.6432 15L0.488159 2.84495C-0.16272 2.19407 -0.16272 1.13904 0.488159 0.488159C1.13904 -0.16272 2.19407 -0.16272 2.84495 0.488159L15 12.6432L27.1551 0.488159Z" fill="#979797" />
                    </svg>

                </div>
            </div>
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
                <div class="cwc-products-count">
                    Найдено товаров:
                    <span id="cwc-products-count">
                        <?php echo esc_html($initial_count); ?>
                    </span>
                </div>
                <button id="cwc-apply-filters">Показать товары ( <?php echo esc_html($initial_count); ?>) </button>
                <button id="cwc-reset-filters" class="cwc-reset-button">Сброс</button>
            </div>
        </div>



    </div>
<?php
    return ob_get_clean();
}
add_shortcode('shop_filters', 'cwc_shop_filters_shortcode');

/* ---------------------------------------------------
 * AJAX: фильтрация + загрузка товаров
 * --------------------------------------------------- */
function cwc_filter_products_callback()
{
    error_log('CWC POST: ' . print_r($_POST, true));

    if (
        !isset($_POST['action']) ||
        $_POST['action'] !== 'cwc_filter_products'
    ) {
        wp_send_json_error('Неверный запрос');
    }

    /*
     * -----------------------------------------------
     * PAGE
     * -----------------------------------------------
     */

    $page = isset($_POST['page'])
        ? max(1, absint($_POST['page']))
        : 1;

    /*
     * Первая загрузка / после фильтра:
     * 18 товаров
     *
     * Infinite scroll:
     * по 9 товаров
     */

    if ($page === 1) {
        $posts_per_page = 18;
        $offset = 0;
    } else {
        $posts_per_page = 9;
        $offset = 18 + (($page - 2) * 9);
    }

    /*
     * -----------------------------------------------
     * TAX QUERY
     * -----------------------------------------------
     */

    $tax_query = [];

    foreach ($_POST as $key => $value) {

        if (strpos($key, 'filter_') !== 0) {
            continue;
        }

        if ($key === 'filter_current_cat_id') {
            continue;
        }

        $taxonomy = str_replace('filter_', '', $key);

        $terms = is_array($value)
            ? array_map('sanitize_text_field', $value)
            : [sanitize_text_field($value)];

        if (!$terms) {
            continue;
        }

        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $terms,
            'operator' => 'IN',
        ];
    }

    /*
     * -----------------------------------------------
     * PRICE / META QUERY
     * -----------------------------------------------
     */

    $meta_query = [
        'relation' => 'AND',
    ];

    if (isset($_POST['min_price'], $_POST['max_price'])) {

        $min_price = floatval($_POST['min_price']);
        $max_price = floatval($_POST['max_price']);

        $meta_query[] = [
            'relation' => 'OR',

            // Простые товары
            [
                'key'     => '_price',
                'value'   => [$min_price, $max_price],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ],

            // Вариативные товары
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

    /*
     * -----------------------------------------------
     * CATEGORY
     * -----------------------------------------------
     */

    if (!empty($_POST['current_cat_id'])) {
        $tax_query[] = [
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => absint($_POST['current_cat_id']),
            'include_children' => true,
        ];
    }

    /*
     * -----------------------------------------------
     * SORT
     * -----------------------------------------------
     */

    $orderby = isset($_POST['orderby'])
        ? sanitize_text_field($_POST['orderby'])
        : 'menu_order';

    $order = 'ASC';

    switch ($orderby) {

        case 'date':
            $orderby = 'date';
            $order = 'DESC';
            break;

        case 'price':
            $orderby = 'meta_value_num';
            $order = 'ASC';

            $meta_query[] = [
                'key'  => '_price',
                'type' => 'NUMERIC',
            ];
            break;

        case 'price-desc':
            $orderby = 'meta_value_num';
            $order = 'DESC';

            $meta_query[] = [
                'key'  => '_price',
                'type' => 'NUMERIC',
            ];
            break;

        case 'title':
            $orderby = 'title';
            $order = 'ASC';
            break;

        case 'menu_order':
        default:
            $orderby = 'menu_order';
            $order = 'ASC';
            break;
    }

    /*
     * -----------------------------------------------
     * STOCK
     * -----------------------------------------------
     */

    if (!empty($_POST['instock'])) {

        $meta_query[] = [
            'key'     => '_stock_status',
            'value'   => 'instock',
            'compare' => '=',
        ];
    }

    /*
     * -----------------------------------------------
     * QUERY
     * -----------------------------------------------
     */

    $query_args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => $posts_per_page,
        'offset'              => $offset,
        'tax_query'           => $tax_query,
        'meta_query'          => count($meta_query) > 1
            ? $meta_query
            : [],
        'orderby'             => $orderby,
        'order'               => $order,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => false,
    ];

    $query = new WP_Query($query_args);

    /*
     * -----------------------------------------------
     * HTML
     * -----------------------------------------------
     */

    ob_start();

    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();

            wc_get_template_part('content', 'product');
        }
    }

    $html = ob_get_clean();

    /*
     * -----------------------------------------------
     * HAS MORE
     * -----------------------------------------------
     */

    $loaded_until = $offset + $query->post_count;

    $has_more = $loaded_until < $query->found_posts;

    wp_reset_postdata();

    wp_send_json_success([
        'html'      => $html,
        'has_more'  => $has_more,
        'count'     => $query->post_count,
        'found'     => $query->found_posts,
        'page'      => $page,
        'offset'    => $offset,
    ]);
}

add_action(
    'wp_ajax_cwc_filter_products',
    'cwc_filter_products_callback'
);

add_action(
    'wp_ajax_nopriv_cwc_filter_products',
    'cwc_filter_products_callback'
);
