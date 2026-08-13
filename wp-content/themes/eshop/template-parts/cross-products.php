<!-- ================= Cross-sell products (Swiper) ================= -->
<?php

global $product;

if (!is_a($product, 'WC_Product')) {
    $product = wc_get_product(get_the_ID());
}

if (!$product) return;

// Получаем ID кроссейлов
$cross_sell_ids = $product->get_cross_sell_ids();

if (!empty($cross_sell_ids)) {

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'post__in'       => $cross_sell_ids,
        'orderby'        => 'post__in', // сохраняем порядок из админки
    ];

    $products = new WP_Query($args);

    if ($products->have_posts()) : ?>
        <div class="single-product__cross-sells">
            <div class="container">
                <div class="relative-products__head">
                    <h2>С этим товаром также покупают</h2>
                </div>

                <div class="swiper cross-sells-products-slider">
                    <div class="swiper-wrapper">

                        <?php while ($products->have_posts()) : $products->the_post(); ?>
                            <div class="swiper-slide cross-product__slide">
                                <a href="<?php the_permalink() ?>" class="cross-product__slide__image">
                                    <?php do_action('woocommerce_before_shop_loop_item_title'); ?>
                                </a>

                                <div class="cross-product__slide__content">
                                    <a href="<?php the_permalink(); ?>" class="woocommerce-LoopProduct-link">
                                        <?php the_title(); ?>
                                    </a>
                                    <div class="cross-product__slide__content__atc">
                                        <?php do_action('woocommerce_after_shop_loop_item_title'); ?>
                                        <?php do_action('woocommerce_after_shop_loop_item'); ?>
                                    </div>

                                </div>

                                <?php //wc_get_template_part('content', 'product'); 
                                ?>
                            </div>
                        <?php endwhile; ?>

                    </div>

                    <div class="swiper-controls">
                        <div class="swiper-arrows">
                            <div class="swiper-button-prev">
                                <svg width="7" height="12" viewBox="0 0 7 12" fill="none">
                                    <path d="M5.2929 11.7068C5.68344 12.0972 6.31649 12.0973 6.70696 11.7068C7.09727 11.3163 7.09727 10.6832 6.70696 10.2928L2.41399 5.99979L6.70696 1.70682C7.09727 1.31634 7.09727 0.683242 6.70696 0.292759C6.31649 -0.0977125 5.68344 -0.0976037 5.2929 0.292759L0.2929 5.29276C-0.0976234 5.68328 -0.0976234 6.3163 0.292901 6.70682L5.2929 11.7068Z" fill="#fff" />
                                </svg>
                            </div>
                            <div class="swiper-button-next">
                                <svg width="7" height="12" viewBox="0 0 7 12" fill="none">
                                    <path d="M1.70679 11.7068C1.31626 12.0972 0.683201 12.0973 0.292731 11.7068C-0.097577 11.3163 -0.0975769 10.6832 0.292731 10.2928L4.5857 5.99979L0.292732 1.70682C-0.0975758 1.31634 -0.0975764 0.683242 0.292732 0.292759C0.683202 -0.0977125 1.31626 -0.0976037 1.70679 0.292759L6.70679 5.29276C7.09732 5.68328 7.09732 6.3163 6.70679 6.70682L1.70679 11.7068Z" fill="#fff" />
                                </svg>
                            </div>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>

            <?php wp_reset_postdata(); ?>
        </div>

<?php
    endif;
}
?>