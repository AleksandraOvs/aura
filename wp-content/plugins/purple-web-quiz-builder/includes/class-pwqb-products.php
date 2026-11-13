<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Products
{
    public static function init()
    {
        add_action('wp_ajax_pwqb_get_products', [self::class, 'ajax_get_products']);
        add_action('wp_ajax_nopriv_pwqb_get_products', [self::class, 'ajax_get_products']);
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

        $tax_query = [];

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
                array_map('sanitize_title', $values)
            )));

            if (empty($values)) {
                continue;
            }

            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $values,
                'operator' => 'IN',
                'include_children' => false,
            ];
        }

        if (empty($tax_query)) {
            wp_send_json_success([
                'count' => 0,
                'html' => '',
            ]);
        }

        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }

        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'no_found_rows' => false,
            'orderby' => [
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ],
            'tax_query' => $tax_query,
        ]);

        $count = min($query->found_posts, 10);

        $product_ids = wp_list_pluck($query->posts, 'ID');

        ob_start();

        if ($query->have_posts()) {

            $add_swiper_slide = static function ($classes) {
                $classes[] = 'swiper-slide';
                return $classes;
            };

            while ($query->have_posts()) {
                $query->the_post();

                add_filter('post_class', $add_swiper_slide);

                wc_get_template_part('content', 'product');

                remove_filter('post_class', $add_swiper_slide);
            }
        }

        wp_reset_postdata();

        $html = ob_get_clean();

        wp_send_json_success([
            'count' => $count,
            'html' => $html,
            'product_ids' => $product_ids,
        ]);
    }
}
