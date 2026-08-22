<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="aura-compare">

    <?php if (empty($products)) : ?>

        <div class="aura-compare__empty">

            <p>В сравнении пока нет товаров.</p>

            <a href="/shop" class="button">
                В каталог
            </a>

        </div>

    <?php else : ?>


        <!-- Фильтр -->

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


        <!-- Товары -->

        <div class="aura-compare__products swiper">

            <div class="swiper-wrapper">


                <?php foreach ($products as $index => $compare_product) : ?>

                    <?php

                    global $product;

                    $old_product = $product;

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

                                    woocommerce_template_loop_price();
                                }

                                ?>


                                <div class="product-card__cart">

                                    <?php
                                    woocommerce_template_loop_add_to_cart();
                                    ?>

                                </div>

                            </div>


                            <button
                                type="button"
                                class="aura-compare__remove"
                                data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                Удалить
                            </button>

                        </div>


                        <!-- Характеристики -->

                        <div class="aura-compare__attribute-values">


                            <?php foreach ($compare_attributes as $attribute) : ?>

                                <?php

                                /*
                                 * Проверяем, отличается ли характеристика.
                                 */

                                $attribute_values = [];

                                foreach ($products as $compare_item) {

                                    $compare_product_id = $compare_item->get_id();

                                    $attribute_values[] = isset($attribute['values'][$compare_product_id])
                                        ? trim((string) $attribute['values'][$compare_product_id])
                                        : '—';
                                }

                                $is_different = count(array_unique($attribute_values)) > 1;


                                /*
                                 * Значение текущего товара.
                                 */

                                $product_id = $product->get_id();

                                $value = isset($attribute['values'][$product_id])
                                    ? trim((string) $attribute['values'][$product_id])
                                    : '—';

                                ?>


                                <div
                                    class="aura-compare__attribute-value <?php echo $is_different ? 'is-different' : 'is-same'; ?>">

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


                    <?php

                    $product = $old_product;

                    ?>

                <?php endforeach; ?>


            </div>


            <div class="aura-compare__products__controls">

                <div class="aura-compare__prev swiper-button-prev"></div>

                <div class="aura-compare__next swiper-button-next"></div>

            </div>


        </div>

    <?php endif; ?>

</div>