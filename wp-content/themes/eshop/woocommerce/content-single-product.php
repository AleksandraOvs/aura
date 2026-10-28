<?php
defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}
?>


<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>
    <!-- <h1 class="product-title"><?php //the_title(); 
                                    ?></h1> -->

    <div class="container">
        <div class="product-title-block">
            <h1 class="product-title">
                <?php the_title(); ?>
            </h1>
        </div>

        <div class="single-product__inner">

            <!-- 1. Галерея -->
            <div class="product-inner__images">
                <?php do_action('woocommerce_before_single_product_summary'); ?>

                <div class="product-inner__images__buttons">
                    <?php custom_add_to_wishlist_button();
                    echo do_shortcode('[aura_compare_button]'); ?>
                </div>

                <?php aura_product_tags(); ?>


            </div>

            <div class="single-product__inner__content">



                <!-- <div class="product-inner__content__col__info _info-col"> -->
                <!-- </div> -->
                <div class="product-inner__content__col">
                    <div class="product-inner__content__col__buy">
                        <div class="product-card__price">
                            <?php echo $product->get_price_html(); ?>
                        </div>


                        <?php aura_product_quantity($product); ?>


                        <?php if ($product->is_type('simple')) : ?>

                            <form
                                class="cart product-buy-form"
                                action="<?php echo esc_url($product->get_permalink()); ?>"
                                method="post"
                                enctype="multipart/form-data">

                                <input
                                    type="hidden"
                                    name="quantity"
                                    value="<?php echo esc_attr($product->get_min_purchase_quantity()); ?>"
                                    class="product-buy-quantity">

                                <button
                                    type="submit"
                                    name="add-to-cart"
                                    value="<?php echo esc_attr($product->get_id()); ?>"
                                    class="button single_add_to_cart_button button">
                                    Купить
                                </button>

                            </form>

                        <?php endif; ?>

                        <button
                            type="button"
                            class="button button-black buy-one-click"
                            data-popup="buy-one-click-popup"
                            data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                            data-product-name="<?php echo esc_attr($product->get_name()); ?>"
                            data-product-sku="<?php echo esc_attr($product->get_sku() ?: '—'); ?>"
                            data-product-price="<?php echo esc_attr(wp_strip_all_tags($product->get_price_html())); ?>"
                            data-product-url="<?php echo esc_url($product->get_permalink()); ?>">
                            Купить в один клик
                        </button>
                    </div>

                    <div class="product-info">
                        <?php render_product_stock($product); ?>

                        <div class="product-sku">
                            Код товара:
                            <?php echo esc_html($product->get_sku() ?: '—'); ?>
                        </div>

                        <?php
                        $brands = wp_get_post_terms(
                            $product->get_id(),
                            'product_brand'
                        );

                        $brand_name = '—';

                        if (!is_wp_error($brands) && !empty($brands)) {
                            $brand_name = $brands[0]->name;
                        }
                        ?>

                        <div class="product-brand">
                            Бренд: <?php echo esc_html($brand_name); ?>
                        </div>
                    </div>
                </div>



                <?php
                $order_benefits = get_field('order_benefits', 'option');
                ?>

                <?php if ($order_benefits): ?>
                    <div class="product-inner__content__col__benefits _benefits-col">
                        <h3>Преимущества заказа</h3>
                        <ul class="order-benefits">

                            <?php foreach ($order_benefits as $benefit): ?>

                                <?php
                                $icon = $benefit['benefit_icon'] ?? '';
                                $text = $benefit['benefit_text'] ?? '';
                                ?>

                                <li class="order-benefits__item">

                                    <?php if ($icon): ?>

                                        <?php
                                        if (is_array($icon)) {
                                            $icon_url = $icon['url'] ?? '';
                                            $icon_alt = $icon['alt'] ?? '';
                                        } else {
                                            $icon_url = wp_get_attachment_image_url($icon, 'full');
                                            $icon_alt = get_post_meta(
                                                $icon,
                                                '_wp_attachment_image_alt',
                                                true
                                            );
                                        }
                                        ?>

                                        <?php if ($icon_url): ?>

                                            <img
                                                src="<?= esc_url($icon_url); ?>"
                                                alt="<?= esc_attr($icon_alt); ?>"
                                                class="order-benefits__item__icon">

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <svg
                                            class="order-benefits__item__icon"
                                            width="19"
                                            height="16"
                                            viewBox="0 0 19 16"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M6.21 15.54L0 9.33L2.83 6.5L6.21 9.89L16.09 0L18.92 2.83L6.21 15.54Z"
                                                fill="#FFCD1A" />
                                        </svg>

                                    <?php endif; ?>


                                    <?php if ($text): ?>

                                        <span class="order-benefits__item__text">
                                            <?= esc_html($text); ?>
                                        </span>

                                    <?php endif; ?>

                                </li>

                            <?php endforeach; ?>

                        </ul>
                    </div>
                <?php endif; ?>

            </div>


        </div>
    </div>


    <?php get_template_part('woocommerce/single-product/product-tabs') ?>
    <?php get_template_part('template-parts/cross-up-sales') ?>

    <?php get_template_part('sections/contacts')
    ?>
</div>