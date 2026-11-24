<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Products
{
    private const PRODUCTS_LIMIT = 10;

    public static function init()
    {
        add_action('wp_ajax_pwqb_get_products', [self::class, 'ajax_get_products']);
        add_action('wp_ajax_nopriv_pwqb_get_products', [self::class, 'ajax_get_products']);

        add_action('wp_ajax_pwqb_get_available_attributes', [self::class, 'ajax_get_available_attributes']);
        add_action('wp_ajax_nopriv_pwqb_get_available_attributes', [self::class, 'ajax_get_available_attributes']);
    }

    /**
     * Получение доступных значений атрибутов товаров выбранных категорий.
     */
    public static function ajax_get_available_attributes(): void
    {
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'pwqb_get_products'
            )
        ) {
            wp_send_json_error([
                'message' => 'Ошибка проверки безопасности.',
            ], 403);
        }

        $categories = isset($_POST['categories'])
            ? json_decode(wp_unslash($_POST['categories']), true)
            : [];

        if (!is_array($categories)) {
            wp_send_json_error([
                'message' => 'Некорректные категории.',
            ], 400);
        }

        $categories = array_values(array_unique(array_filter(
            array_map('absint', $categories)
        )));

        if (empty($categories)) {
            wp_send_json_success([
                'attributes' => [],
            ]);
        }

        $valid_categories = [];

        foreach ($categories as $category_id) {
            $term = get_term($category_id, 'product_cat');

            if ($term && !is_wp_error($term)) {
                $valid_categories[] = $category_id;
            }
        }

        if (empty($valid_categories)) {
            wp_send_json_success([
                'attributes' => [],
            ]);
        }

        $products = get_posts([
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $valid_categories,
                    'operator'         => 'IN',
                    'include_children' => true,
                ],
            ],
        ]);

        if (empty($products)) {
            wp_send_json_success([
                'attributes' => [],
            ]);
        }

        $available_attributes = [];

        $taxonomies = wc_get_attribute_taxonomies();

        foreach ($taxonomies as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $terms = wp_get_object_terms(
                $products,
                $taxonomy,
                ['fields' => 'slugs']
            );

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $available_attributes[$taxonomy] = array_values(
                array_unique($terms)
            );
        }

        wp_send_json_success([
            'attributes' => $available_attributes,
        ]);
    }

    public static function ajax_get_products()
    {
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'pwqb_get_products'
            )
        ) {
            wp_send_json_error([
                'message' => 'Ошибка проверки безопасности.',
            ], 403);
        }

        $quiz_id = isset($_POST['quiz_id'])
            ? absint($_POST['quiz_id'])
            : 0;

        $attributes = isset($_POST['attributes'])
            ? json_decode(wp_unslash($_POST['attributes']), true)
            : [];

        if (!$quiz_id || get_post_type($quiz_id) !== PWQB_Quiz::POST_TYPE) {
            wp_send_json_error([
                'message' => 'Некорректный квиз.',
            ], 400);
        }

        if (get_post_status($quiz_id) !== 'publish') {
            wp_send_json_error([
                'message' => 'Квиз недоступен.',
            ], 403);
        }

        if (!is_array($attributes)) {
            wp_send_json_error([
                'message' => 'Некорректные атрибуты.',
            ], 400);
        }

        $categories = isset($_POST['categories'])
            ? json_decode(wp_unslash($_POST['categories']), true)
            : [];

        if (!is_array($categories)) {
            wp_send_json_error([
                'message' => 'Некорректные категории.',
            ], 400);
        }

        $categories = array_values(array_unique(array_filter(
            array_map('absint', $categories)
        )));

        /*
         * 1. Получаем товары, выбранные вручную.
         */
        $priority_ids = self::get_priority_product_ids($quiz_id);

        // Ручные товары уже занимают часть общего лимита.
        $remaining = self::PRODUCTS_LIMIT - count($priority_ids);

        /*
         * 2. Формируем критерии автоматического подбора.
         */
        $tax_query = [];

        if (!empty($categories)) {
            $valid_categories = [];

            foreach ($categories as $category_id) {
                $term = get_term($category_id, 'product_cat');

                if ($term && !is_wp_error($term)) {
                    $valid_categories[] = $category_id;
                }
            }

            if (!empty($valid_categories)) {
                $tax_query[] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $valid_categories,
                    'operator'         => 'IN',
                    'include_children' => true,
                ];
            }
        }

        foreach ($attributes as $taxonomy => $values) {

            if (
                !is_string($taxonomy) ||
                !str_starts_with($taxonomy, 'pa_') ||
                !taxonomy_exists($taxonomy) ||
                !is_array($values) ||
                empty($values)
            ) {
                continue;
            }

            $values = array_values(array_unique(array_filter(
                array_map(
                    static function ($value) {
                        return is_scalar($value)
                            ? sanitize_title((string) $value)
                            : '';
                    },
                    $values
                )
            )));

            if (empty($values)) {
                continue;
            }

            $tax_query[] = [
                'taxonomy'         => $taxonomy,
                'field'            => 'slug',
                'terms'            => $values,
                'operator'         => 'IN',
                'include_children' => false,
            ];
        }

        /*
         * 3. Автоматически подбираем товары,
         *    исключая уже выбранные вручную.
         */
        $automatic_ids = [];

        if ($remaining > 0 && !empty($tax_query)) {

            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }

            error_log('PWQB PRODUCTS: tax_query');
            error_log(print_r($tax_query, true));

            $query = new WP_Query([
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => $remaining,
                'no_found_rows'  => true,
                'orderby'        => [
                    'menu_order' => 'ASC',
                    'title'      => 'ASC',
                ],
                'post__not_in'   => $priority_ids,
                'tax_query'      => $tax_query,
            ]);

            error_log('PWQB PRODUCTS: найдено товаров — ' . $query->found_posts);
            error_log('PWQB PRODUCTS: ID товаров — ' . print_r(
                wp_list_pluck($query->posts, 'ID'),
                true
            ));

            $automatic_ids = wp_list_pluck($query->posts, 'ID');
        }

        /*
         * 4. Объединяем списки.
         *    Ручные товары всегда остаются первыми.
         */
        $product_ids = array_slice(
            array_values(array_unique(array_merge(
                $priority_ids,
                $automatic_ids
            ))),
            0,
            self::PRODUCTS_LIMIT
        );

        /*
         * 5. Формируем HTML карточек товаров.
         */
        ob_start();

        $add_swiper_slide = static function ($classes) {
            $classes[] = 'swiper-slide';
            return $classes;
        };

        foreach ($product_ids as $product_id) {
            $product_post = get_post($product_id);

            if (!$product_post) {
                continue;
            }

            $GLOBALS['post'] = $product_post;

            setup_postdata($product_post);

            add_filter('post_class', $add_swiper_slide);

            wc_get_template_part('content', 'product');

            remove_filter('post_class', $add_swiper_slide);
        }

        wp_reset_postdata();

        $html = ob_get_clean();

        wp_send_json_success([
            'count'       => count($product_ids),
            'html'        => $html,
            'product_ids' => $product_ids,
        ]);
    }

    /**
     * Получение вручную выбранных товаров квиза.
     */
    private static function get_priority_product_ids(int $quiz_id): array
    {
        $settings = get_post_meta(
            $quiz_id,
            PWQB_Quiz::META_SETTINGS,
            true
        );

        if (
            !is_array($settings) ||
            !isset($settings['priority_products']) ||
            !is_array($settings['priority_products'])
        ) {
            return [];
        }

        $product_ids = [];

        foreach ($settings['priority_products'] as $product_id) {
            $product_id = absint($product_id);

            if (
                !$product_id ||
                get_post_type($product_id) !== 'product' ||
                get_post_status($product_id) !== 'publish'
            ) {
                continue;
            }

            $product_ids[] = $product_id;
        }

        return array_slice(
            array_values(array_unique($product_ids)),
            0,
            self::PRODUCTS_LIMIT
        );
    }
}
