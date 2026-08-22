<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="aura-compare">

    <?php if (empty($products)) : ?>

        <div class="aura-compare__empty">
            <p>В сравнении пока нет товаров.</p>
            <a href="/shop" class="button">В каталог</a>
        </div>

    <?php else : ?>

        <div class="aura-compare__products swiper">

            <div class="aura-compare__filter">

                <label class="aura-compare__filter-label">

                    <input
                        type="checkbox"
                        class="aura-compare__different">

                    <span class="aura-compare__filter-text">
                        Различающиеся характеристики
                    </span>

                </label>

            </div>
            <div class="swiper-wrapper">

                <?php foreach ($products as $index => $compare_product) : ?>

                    <?php
                    $product = $compare_product;
                    ?>

                    <div class="aura-compare__item swiper-slide">

                        <!-- Карточка товара -->

                        <div class="product-card aura-compare-product">

                            <div class="product-card__image">
                                <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo $product->get_image(); ?>
                                </a>
                            </div>

                            <div class="product-card__title">
                                <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </div>

                            <div class="product-card__bottom">

                                <?php
                                if ($product->is_type('variable')) {

                                    echo '<span class="product-card__details">Подробнее</span>';
                                } else {

                                    global $product;

                                    $old_product = $product;
                                    $product = $compare_product;

                                    woocommerce_template_loop_price();

                                    $product = $old_product;
                                }
                                ?>

                                <div class="product-card__cart">

                                    <?php
                                    global $product;

                                    $old_product = $product;
                                    $product = $compare_product;

                                    woocommerce_template_loop_add_to_cart();

                                    $product = $old_product;
                                    ?>

                                </div>

                            </div>

                            <button
                                type="button"
                                class="aura-compare__remove"
                                data-product-id="<?php echo esc_attr($compare_product->get_id()); ?>">
                                Удалить
                            </button>

                        </div>


                        <!-- Характеристики -->

                        <div class="aura-compare__attribute-values">

                            <?php foreach ($compare_attributes as $attribute) : ?>
                                <?php
                                /*
         * Проверяем, отличается ли эта характеристика
         * у сравниваемых товаров.
         */

                                $attribute_values = [];

                                foreach ($products as $product) {

                                    $product_id = $product->get_id();

                                    $attribute_values[] = isset($attribute['values'][$product_id])
                                        ? trim((string) $attribute['values'][$product_id])
                                        : '—';
                                }

                                $is_different = count(array_unique($attribute_values)) > 1;


                                /*
         * Получаем значение характеристики
         * текущего товара.
         */

                                $product_id = $compare_product->get_id();

                                $value = isset($attribute['values'][$product_id])
                                    ? trim((string) $attribute['values'][$product_id])
                                    : '—';
                                ?>

                                <?php
                                $product_id = $compare_product->get_id();

                                $value = isset($attribute['values'][$product_id])
                                    ? trim((string) $attribute['values'][$product_id])
                                    : '—';
                                ?>

                                <div class="aura-compare__attribute-value <?php echo $is_different ? 'is-different' : 'is-same'; ?>">

                                    <?php if ($index === 0) : ?>

                                        <div class="aura-compare__attribute-name">
                                            <?php echo esc_html($attribute['name']); ?>
                                        </div>

                                    <?php endif; ?>

                                    <div class="aura-compare__attribute-text">
                                        <?php echo esc_html($value); ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="aura-compare__products__controls">
                <div class="aura-compare__prev swiper-button-prev"></div>
                <div class="aura-compare__next swiper-button-next"></div>
            </div>


        </div>

    <?php endif; ?>

</div>