<?php

/**
 * Product quantity inputs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/quantity-input.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 4.0.0
 */

defined('ABSPATH') || exit;

if ($max_value && $min_value === $max_value) {
?>
    <div class="quantity hidden">
        <input type="hidden" id="<?php echo esc_attr($input_id); ?>" class="qty" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($min_value); ?>" />
    </div>
<?php
} else {
    /* translators: %s: Quantity. */
    $label = ! empty($args['product_name']) ? sprintf(esc_html__('%s quantity', 'woocommerce'), wp_strip_all_tags($args['product_name'])) : esc_html__('Quantity', 'woocommerce');
?>
    <div class="quantity pro-qty">
        <?php do_action('woocommerce_before_quantity_input_field'); ?>
        <button type="button" class="dec qty-btn"><svg width="11" height="2" viewBox="0 0 11 2" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.5801 0V1.30216H0V0H10.5801Z" fill="white" />
            </svg>
        </button>
        <input
            type="text"
            id="<?php echo esc_attr($input_id); ?>"
            class="<?php echo esc_attr(join(' ', (array) $classes)); ?>"
            data-step="<?php echo esc_attr($step); ?>"
            data-min="<?php echo esc_attr($min_value); ?>"
            data-max="<?php echo esc_attr(0 < $max_value ? $max_value : ''); ?>"
            name="<?php echo esc_attr($input_name); ?>"
            value="<?php echo esc_attr($input_value); ?>"
            title="<?php echo esc_attr_x('Qty', 'Product quantity input tooltip', 'woocommerce'); ?>"
            size="4"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            inputmode="<?php echo esc_attr($inputmode); ?>" />
        <button type="button" class="inc qty-btn"><svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5.96825 4.63896H10.5801V5.94112H5.96825V10.553H4.63896V5.94112H0V4.63896H4.63896V0H5.96825V4.63896Z" fill="white" />
            </svg>
        </button>

        <?php do_action('woocommerce_after_quantity_input_field'); ?>
    </div>
<?php
}
