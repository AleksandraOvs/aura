<?php

/**
 * Product attributes
 */

defined('ABSPATH') || exit;

if (!$product || (!$product->has_attributes() && !apply_filters('wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions()))) {
    return;
}

$attributes = array_filter($product->get_attributes(), function ($attribute) {
    return $attribute->get_visible();
});
?>

<div class="woocommerce-product-attributes">

    <?php if (!empty($attributes)) : ?>
        <div class="woocommerce-product-attributes__list">

            <?php foreach ($attributes as $attribute) : ?>

                <?php
                $attribute_name = $attribute->get_name();
                $attribute_label = wc_attribute_label($attribute_name);

                if ($attribute->is_taxonomy()) {
                    $attribute_values = wc_get_product_terms(
                        $product->get_id(),
                        $attribute_name,
                        ['fields' => 'names']
                    );
                } else {
                    $attribute_values = $attribute->get_options();
                }

                $attribute_value = implode(', ', $attribute_values);
                ?>

                <div class="woocommerce-product-attributes__item">
                    <div class="woocommerce-product-attributes__label">
                        <?php echo esc_html($attribute_label); ?>
                    </div>

                    <div class="woocommerce-product-attributes__value">
                        <?php echo esc_html($attribute_value); ?>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    <?php endif; ?>

</div>