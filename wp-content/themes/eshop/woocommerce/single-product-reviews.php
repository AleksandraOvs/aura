<?php

/**
 * Отзывы о товаре WooCommerce.
 * Список отзывов отделён от формы добавления.
 */

defined('ABSPATH') || exit;

global $product;

if (! $product instanceof WC_Product) {
    $product = wc_get_product(get_queried_object_id());
}

if (! $product) {
    return;
}
?>

<div id="reviews" class="woocommerce-Reviews">

    <div id="comments" class="product-reviews">

        <h2 class="product-reviews__title">
            <?php
            $count = $product->get_review_count();

            if ($count && wc_review_ratings_enabled()) {
                printf(
                    esc_html(
                        _n(
                            '%s отзыв',
                            '%s отзывов',
                            $count,
                            'woocommerce'
                        )
                    ),
                    esc_html($count)
                );
            } else {
                esc_html_e('Отзывы', 'woocommerce');
            }
            ?>
        </h2>

        <?php if (have_comments()) : ?>

            <div class="product-reviews__list">
                <?php
                wp_list_comments(
                    array(
                        'callback' => 'pwvr_render_product_review',
                        'type'     => 'review',
                        'style'    => 'div',
                    )
                );
                ?>
            </div>

            <?php
            if (get_comment_pages_count() > 1 && get_option('page_comments')) :
            ?>
                <nav class="product-reviews__pagination woocommerce-pagination">
                    <?php
                    paginate_comments_links(
                        apply_filters(
                            'woocommerce_comment_pagination_args',
                            array(
                                'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
                                'next_text' => is_rtl() ? '&larr;' : '&rarr;',
                                'type'      => 'list',
                            )
                        )
                    );
                    ?>
                </nav>
            <?php endif; ?>

        <?php else : ?>

            <p class="product-reviews__empty">
                Пока нет опубликованных отзывов.
            </p>

        <?php endif; ?>

    </div>

    <div class="clear"></div>

</div>