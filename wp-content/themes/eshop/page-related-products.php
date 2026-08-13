<?php

/**
 * Template Name: Related products page
 */

defined('ABSPATH') || exit;
get_header();
?>

<!-- Получаем товар-источник -->
<?php
$product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;

if (! $product_id) {
    echo '<p>Товар не найден.</p>';
    get_footer();
    return;
}

$product = wc_get_product($product_id);

if (! $product) {
    echo '<p>Товар не найден.</p>';
    get_footer();
    return;
}

// Апсейлы
$upsell_ids = $product->get_upsell_ids();

// Related (все)
$related_ids = wc_get_related_products(
    $product->get_id(),
    -1
);

// Убираем дубли: если товар есть в upsells — не показываем его в related
if ($upsell_ids && $related_ids) {
    $related_ids = array_diff($related_ids, $upsell_ids);
}

// Если вообще есть что показывать
if ($upsell_ids || $related_ids) :

    wc_set_loop_prop('columns', 4);
?>

    <div class="container">
        <h1>Похожие товары</h1>

        <div class="single-product__related">
            <div class="products-on-column">

                <?php
                // ===== 1. UPSALES =====
                if ($upsell_ids) :
                    wc_set_loop_prop('name', 'upsells');

                    foreach ($upsell_ids as $product_id) :
                        $post_object = get_post($product_id);
                        setup_postdata($GLOBALS['post'] = $post_object);

                        wc_get_template_part('content', 'product');
                    endforeach;
                endif;
                ?>

                <?php
                // ===== 2. RELATED =====
                if ($related_ids) :
                    wc_set_loop_prop('name', 'related');

                    foreach ($related_ids as $product_id) :
                        $post_object = get_post($product_id);
                        setup_postdata($GLOBALS['post'] = $post_object);

                        wc_get_template_part('content', 'product');
                    endforeach;
                endif;
                ?>

            </div>
        </div>


        <?php wp_reset_postdata(); ?>
    </div>

<?php else : ?>

    <p>Похожих товаров не найдено.</p>

<?php endif; ?>

<?php get_footer() ?>