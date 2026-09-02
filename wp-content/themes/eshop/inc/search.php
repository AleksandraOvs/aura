<?php

/**
 * AJAX live search WooCommerce
 */
add_action('wp_ajax_live_search', 'aura_live_search');
add_action('wp_ajax_nopriv_live_search', 'aura_live_search');

function aura_live_search()
{

    global $wpdb;

    $query = isset($_POST['s'])
        ? sanitize_text_field(wp_unslash($_POST['s']))
        : '';

    $query = trim($query);

    if (mb_strlen($query) < 2) {
        wp_send_json_success([
            'results' => [],
            'total'   => 0,
        ]);
    }

    $results = [];
    $limit   = 10;

    /*
     * ==========================================================
     * 1. ПОИСК ПО SKU
     * ==========================================================
     */

    $lookup_table = $wpdb->wc_product_meta_lookup;

    $sku_ids = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT product_id
            FROM {$lookup_table}
            WHERE sku LIKE %s
            LIMIT %d
            ",
            '%' . $wpdb->esc_like($query) . '%',
            $limit
        )
    );

    if (!empty($sku_ids)) {

        foreach ($sku_ids as $product_id) {

            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $results[] = [
                'id'    => $product_id,
                'title' => get_the_title($product_id),
                'url'   => get_permalink($product_id),
                'type'  => 'Товар',
                'sku'   => $product->get_sku(),
                'image' => wp_get_attachment_image_url(
                    $product->get_image_id(),
                    'thumbnail'
                ),
            ];

            if (count($results) >= $limit) {
                break;
            }
        }
    }


    /*
     * ==========================================================
     * 2. ПОИСК ПО НАЗВАНИЮ ТОВАРА
     * ==========================================================
     */

    if (count($results) < $limit) {

        $remaining = $limit - count($results);

        $products = new WP_Query([
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => $remaining,
            's'                      => $query,
            'post__not_in'           => wp_list_pluck($results, 'id'),
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        if (!empty($products->posts)) {

            foreach ($products->posts as $product_id) {

                $product = wc_get_product($product_id);

                if (!$product) {
                    continue;
                }

                $results[] = [
                    'id'    => $product_id,
                    'title' => get_the_title($product_id),
                    'url'   => get_permalink($product_id),
                    'type'  => 'Товар',
                    'sku'   => $product->get_sku(),
                    'image' => wp_get_attachment_image_url(
                        $product->get_image_id(),
                        'thumbnail'
                    ),
                ];
            }
        }

        wp_reset_postdata();
    }


    /*
     * ==========================================================
     * 3. ПОИСК ПО КАТЕГОРИЯМ
     * ==========================================================
     */

    if (count($results) < $limit) {

        $remaining = $limit - count($results);

        $categories = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'search'     => $query,
            'number'     => $remaining,
        ]);

        if (!is_wp_error($categories)) {

            foreach ($categories as $category) {

                $thumbnail_id = get_term_meta(
                    $category->term_id,
                    'thumbnail_id',
                    true
                );

                $results[] = [
                    'id'    => 'cat-' . $category->term_id,
                    'title' => $category->name,
                    'url'   => get_term_link($category),
                    'type'  => 'Категория',
                    'sku'   => '',
                    'image' => $thumbnail_id
                        ? wp_get_attachment_image_url(
                            $thumbnail_id,
                            'thumbnail'
                        )
                        : '',
                ];
            }
        }
    }


    /*
     * ==========================================================
     * 4. УБИРАЕМ ДУБЛИКАТЫ
     * ==========================================================
     */

    $unique = [];

    foreach ($results as $item) {

        $unique[$item['id']] = $item;
    }

    $results = array_values($unique);

    /*
     * Ограничиваем итоговое количество
     */
    $results = array_slice($results, 0, $limit);


    /*
     * ==========================================================
     * 5. ОТВЕТ
     * ==========================================================
     */

    wp_send_json_success([
        'results' => $results,
        'total'   => count($results),
        'query'   => $query,
    ]);
}
