<?php

/**
 * Шаблон сопутствующих и рекомендуемых товаров
 *
 * Используется на странице товара.
 *
 * Блоки:
 * 1. Cross-sells — "Сопутствующие товары"
 * 2. Upsells    — "Рекомендуем"
 */

defined('ABSPATH') || exit;

global $product;

/**
 * ---------------------------------------------------------
 * Получаем текущий товар
 * ---------------------------------------------------------
 */

if (!is_a($product, 'WC_Product')) {
    $product = wc_get_product(get_the_ID());
}

if (!$product) {
    return;
}


/**
 * =========================================================
 * CROSS-SELLS
 * Сопутствующие товары
 * =========================================================
 */

$cross_sell_ids = $product->get_cross_sell_ids();

$prev = '<svg width="21" height="35" viewBox="0 0 21 35" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M17.6194 0.107826L20.2597 2.70776C20.4086 2.85592 20.4094 3.09479 20.2628 3.24219L6.22408 17.3886L20.2435 31.6211C20.3871 31.7674 20.3879 32.0029 20.2435 32.1495L17.6424 34.7926C17.495 34.9392 17.2561 34.9384 17.1095 34.791L0.109559 17.7087C-0.0363307 17.5621 -0.0367086 17.3243 0.109559 17.1773L17.0861 0.112739C17.2338 -0.0357963 17.4716 -0.037686 17.6194 0.107826Z" fill="#1D1D1F"/>
</svg>
';

$next = '<svg width="21" height="35" viewBox="0 0 21 35" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M2.75273 0.107826L0.112349 2.70776C-0.0365641 2.85592 -0.0373207 3.09479 0.109325 3.24219L14.148 17.3886L0.128601 31.6211C-0.0150211 31.7674 -0.015777 32.0029 0.128601 32.1495L2.72967 34.7926C2.87707 34.9392 3.11594 34.9384 3.26259 34.791L20.2625 17.7087C20.4084 17.5621 20.4088 17.3243 20.2625 17.1773L3.28602 0.112739C3.13824 -0.0357963 2.90051 -0.037686 2.75273 0.107826Z" fill="#1D1D1F"/>
</svg>
';

if (!empty($cross_sell_ids)) {

    $cross_sells_query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'post__in'       => $cross_sell_ids,
        'orderby'        => 'post__in',
        'post_status'    => 'publish',
    ]);

    if ($cross_sells_query->have_posts()) :
?>

        <section class="single-product__cross-sells">

            <div class="container">

                <div class="relative-products__head">
                    <h2 class="small-heading">Сопутствующие товары</h2>
                </div>

                <div class="swiper cross-sells-products-slider">

                    <div class="swiper-wrapper">

                        <?php
                        while ($cross_sells_query->have_posts()) :
                            $cross_sells_query->the_post();
                        ?>

                            <div class="swiper-slide">

                                <?php
                                /**
                                 * Используем стандартную карточку товара.
                                 *
                                 * Файл:
                                 * woocommerce/content-product.php
                                 */
                                wc_get_template_part('content', 'product');
                                ?>

                            </div>

                        <?php endwhile; ?>

                    </div>

                    <!-- Управление слайдером -->
                    <div class="swiper-controls">

                        <div class="swiper-arrows">

                            <div class="swiper-button-prev">
                                <?php echo $prev ?>
                            </div>

                            <div class="swiper-button-next">
                                <?php echo $next ?>
                            </div>

                        </div>



                    </div>

                </div>

            </div>

        </section>

    <?php
    endif;

    wp_reset_postdata();
}


/**
 * =========================================================
 * UPSELLS / ПОХОЖИЕ ТОВАРЫ
 * =========================================================
 *
 * 1. Если Upsells указаны вручную — используем их.
 * 2. Если Upsells не указаны — автоматически
 *    подбираем товары из категории текущего товара.
 */

$upsell_ids = $product->get_upsell_ids();

/**
 * ---------------------------------------------------------
 * Вариант 1. Есть вручную выбранные Upsells
 * ---------------------------------------------------------
 */

if (!empty($upsell_ids)) {

    $upsells_query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'post__in'       => $upsell_ids,
        'orderby'        => 'post__in',
        'post_status'    => 'publish',
    ]);
} else {

    /**
     * ---------------------------------------------------------
     * Вариант 2. Upsells не выбраны
     *
     * Берём товары из категории текущего товара.
     * ---------------------------------------------------------
     */

    $category_ids = wp_get_post_terms(
        $product->get_id(),
        'product_cat',
        [
            'fields' => 'ids',
        ]
    );

    if (empty($category_ids) || is_wp_error($category_ids)) {
        return;
    }

    $upsells_query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'post__not_in'   => [$product->get_id()],
        'post_status'    => 'publish',

        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_ids,
            ],
        ],

        /**
         * Сначала более новые товары.
         * Если нужен другой порядок — можем изменить.
         */
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);
}


/**
 * ---------------------------------------------------------
 * Вывод блока
 * ---------------------------------------------------------
 */

if ($upsells_query->have_posts()) :
    ?>

    <section class="single-product__related">

        <div class="container">

            <div class="relative-products__head">
                <h2 class="small-heading">Похожие товары</h2>
            </div>

            <div class="swiper related-products-slider">

                <div class="swiper-wrapper">

                    <?php
                    while ($upsells_query->have_posts()) :
                        $upsells_query->the_post();
                    ?>

                        <div class="swiper-slide">

                            <?php
                            /**
                             * Используем ту же карточку товара,
                             * что и в каталоге.
                             */
                            wc_get_template_part('content', 'product');
                            ?>

                        </div>

                    <?php endwhile; ?>

                </div>

                <!-- Управление слайдером -->
                <div class="swiper-controls">

                    <div class="swiper-arrows">

                        <div class="swiper-button-prev">
                            <?php echo $prev ?>
                        </div>

                        <div class="swiper-button-next">
                            <?php echo $next ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

<?php
endif;

wp_reset_postdata();
