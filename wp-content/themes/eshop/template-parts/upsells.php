<?php
defined('ABSPATH') || exit;

global $product;

if (!$product) return;

$upsell_ids = $product->get_upsell_ids();

if (empty($upsell_ids)) return;

$query = new WP_Query([
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'post__in'       => $upsell_ids,
    'orderby'        => 'post__in',
    'post_status'    => 'publish',
]);

if (!$query->have_posts()) return;
?>

<div class="single-product__related">
    <div class="container">
        <div class="relative-products__head">
            <h2>Рекомендуем</h2>
        </div>
    </div>

    <div class="container">
        <div class="swiper related-products-slider">

            <div class="swiper-wrapper">

                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="swiper-slide">
                        <?php wc_get_template_part('content', 'product'); ?>
                    </div>
                <?php endwhile; ?>

            </div>

            <div class="swiper-controls">
                <div class="swiper-arrows">
                    <div class="swiper-button-prev">
                        <svg width="7" height="12" viewBox="0 0 7 12" fill="none">
                            <path d="M5.2929 11.7068C5.68344 12.0972 6.31649 12.0973 6.70696 11.7068C7.09727 11.3163 7.09727 10.6832 6.70696 10.2928L2.41399 5.99979L6.70696 1.70682C7.09727 1.31634 7.09727 0.683242 6.70696 0.292759C6.31649 -0.0977125 5.68344 -0.0976037 5.2929 0.292759L0.2929 5.29276C-0.0976234 5.68328 -0.0976234 6.3163 0.292901 6.70682L5.2929 11.7068Z" fill="black" />
                        </svg>
                    </div>

                    <div class="swiper-button-next">
                        <svg width="7" height="12" viewBox="0 0 7 12" fill="none">
                            <path d="M1.70679 11.7068C1.31626 12.0972 0.683201 12.0973 0.292731 11.7068C-0.097577 11.3163 -0.0975769 10.6832 0.292731 10.2928L4.5857 5.99979L0.292732 1.70682C-0.0975758 1.31634 -0.0975764 0.683242 0.292732 0.292759C0.683202 -0.0977125 1.31626 -0.0976037 1.70679 0.292759L6.70679 5.29276C7.09732 5.68328 7.09732 6.3163 6.70679 6.70682L1.70679 11.7068Z" fill="black" />
                        </svg>
                    </div>
                </div>

                <div class="swiper-pagination"></div>
            </div>

        </div>
    </div>
</div>

<?php wp_reset_postdata(); ?>