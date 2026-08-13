<?php

/**
 * WooCommerce main template
 *
 * Используется для:
 * – shop
 * – product category
 * – product tag
 * – single product
 * – cart / checkout / account
 */

defined('ABSPATH') || exit;

get_header('shop');
?>

<?php
/**
 * Хуки WooCommerce для уведомлений, хлебных крошек и открытия контейнера
 */
do_action('woocommerce_before_main_content');
?>

<div class="woocommerce-page__content">
    <?php get_template_part('template-parts/page-header') ?>

    <?php
    if (is_shop()) {


        echo '<div class="container">';
        // Получаем родительские категории (только верхний уровень)
        $categories = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => true,
        ]);

        if (!empty($categories) && !is_wp_error($categories)) {
            echo '<div class="categories-grid">';
            foreach ($categories as $cat) {
                $cat_link = get_term_link($cat);
                if (!is_wp_error($cat_link)) {
    ?>
                    <a class="category-item__link" href="<?php echo esc_url($cat_link); ?>">
                        <div class="category-title hover-effect"><?php echo esc_html($cat->name); ?></div>
                    </a>
            <?php
                }
            }
            echo '</div>';
        }
        echo do_shortcode('[shop_filters]');
        // Показываем все товары
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ];
        $products = new WP_Query($args);

        if ($products->have_posts()) : ?>
            <?php
            // Получаем количество колонок (2,3,4,5 и т.д.)
            $columns = wc_get_loop_prop('columns');

            // Фолбек (если вдруг не задано)
            if (!$columns) {
                $columns = 4;
            }
            ?>

            <ul class="products products-<?php echo esc_attr($columns); ?>">
                <?php while ($products->have_posts()) : $products->the_post(); ?>
                    <?php wc_get_template_part('content', 'product'); ?>
                <?php endwhile; ?>
            </ul>

            <?php endif;
        wp_reset_postdata();
        echo '</div>';
    } elseif (is_product_taxonomy()) {

        $current_cat = get_queried_object();
        $parent_id = $current_cat->term_id;

        // Получаем дочерние категории
        $categories = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => $parent_id,
            'hide_empty' => true,
        ]);
        echo '<div class="container">';
        echo do_shortcode('[shop_filters]');
        if (!empty($categories) && !is_wp_error($categories)) {
            echo '<div class="categories-grid">';
            foreach ($categories as $cat) {
                $cat_link = get_term_link($cat);
                if (!is_wp_error($cat_link)) {
            ?>
                    <a class="category-item__link" href="<?php echo esc_url($cat_link); ?>">
                        <div class="category-title hover-effect"><?php echo esc_html($cat->name); ?></div>
                    </a>
            <?php
                }
            }
            echo '</div>';
        }

        // Показываем товары текущей категории
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $current_cat->term_id,
                ]
            ],
        ];
        $products = new WP_Query($args);

        if ($products->have_posts()) : ?>
            <?php
            // Получаем количество колонок (2,3,4,5 и т.д.)
            $columns = wc_get_loop_prop('columns');

            // Фолбек (если вдруг не задано)
            if (!$columns) {
                $columns = 4;
            }
            ?>

            <ul class="products products-<?php echo esc_attr($columns); ?>">
                <?php while ($products->have_posts()) : $products->the_post(); ?>
                    <?php wc_get_template_part('content', 'product'); ?>
                <?php endwhile; ?>
            </ul>
    <?php endif;
        wp_reset_postdata();
        echo '</div>';
    } else {
        // Для других страниц WooCommerce (cart, checkout, account)
        woocommerce_content();
    }
    ?>

</div>

<?php
/**
 * Хуки WooCommerce для закрытия контейнера и других действий
 */
do_action('woocommerce_after_main_content');

get_footer('shop');
