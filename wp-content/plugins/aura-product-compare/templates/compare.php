<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="aura-compare">

    <?php if (empty($products)) : ?>

        <div class="aura-compare__empty">
            <p>В сравнении пока нет товаров.</p>
        </div>

    <?php else : ?>

        <div
            class="aura-compare__table"
            style="--compare-products-count: <?php echo esc_attr(count($products)); ?>;">

            <!-- Товары -->

            <div class="aura-compare__products">

                <?php foreach ($products as $compare_product) : ?>

                    <div class="aura-compare__item">

                        <?php
                        $product = $compare_product;
                        ?>

                        <div class="product-card aura-compare-product">

                            <!-- Изображение -->

                            <div class="product-card__image">

                                <a
                                    href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo $product->get_image(); ?>
                                </a>

                            </div>


                            <!-- Название -->

                            <div class="product-card__title">

                                <a
                                    href="<?php echo esc_url($product->get_permalink()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>

                            </div>


                            <!-- Цена + корзина -->

                            <div class="product-card__bottom">

                                <?php
                                if ($product->is_type('variable')) {

                                    echo '<span class="product-card__details">Подробнее</span>';
                                } else {

                                    // Временно передаём товар WooCommerce
                                    // для корректного вывода цены
                                    global $product;

                                    $compare_product_global = $product;

                                    $product = $compare_product;

                                    woocommerce_template_loop_price();

                                    $product = $compare_product_global;
                                }
                                ?>


                                <div class="product-card__cart">

                                    <?php
                                    global $product;

                                    $compare_product_global = $product;

                                    $product = $compare_product;

                                    woocommerce_template_loop_add_to_cart();

                                    $product = $compare_product_global;
                                    ?>

                                </div>

                            </div>


                            <!-- Удаление из сравнения -->

                            <button
                                type="button"
                                class="aura-compare__remove"
                                data-product-id="<?php echo esc_attr($compare_product->get_id()); ?>">
                                Удалить
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

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

            <!-- Характеристики -->

            <?php if (!empty($compare_attributes)) : ?>

                <div class="aura-compare__attributes">

                    <?php foreach ($compare_attributes as $attribute) : ?>

                        <?php

                        /*
             * Собираем значения характеристики
             * у всех товаров.
             */
                        $attribute_values = [];

                        foreach ($products as $product) {

                            $product_id = $product->get_id();

                            $value = isset($attribute['values'][$product_id])
                                ? trim((string) $attribute['values'][$product_id])
                                : '—';

                            $attribute_values[] = $value;
                        }


                        /*
             * Если уникальных значений больше одного,
             * значит характеристика отличается.
             */
                        $is_different = count(array_unique($attribute_values)) > 1;

                        ?>

                        <div
                            class="aura-compare__attribute-row <?php echo $is_different ? 'is-different' : 'is-same'; ?>">

                            <!-- Название характеристики -->

                            <div class="aura-compare__attribute-name">

                                <?php echo esc_html($attribute['name']); ?>

                            </div>


                            <!-- Значения -->

                            <div class="aura-compare__attribute-values">

                                <?php foreach ($products as $product) : ?>

                                    <div class="aura-compare__attribute-value">

                                        <?php

                                        $product_id = $product->get_id();

                                        if (isset($attribute['values'][$product_id])) {

                                            echo esc_html(
                                                $attribute['values'][$product_id]
                                            );
                                        } else {

                                            echo '—';
                                        }

                                        ?>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>