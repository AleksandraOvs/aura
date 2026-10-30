<?php

/**
 * Single Product Image — Swiper gallery.
 *
 * @package WooCommerce
 */

use Automattic\WooCommerce\Enums\ProductType;

defined('ABSPATH') || exit;

if (! function_exists('wc_get_gallery_image_html')) {
	return;
}

global $product;

if (! $product instanceof WC_Product) {
	return;
}

$main_image_id = $product->get_image_id();
$gallery_ids   = $product->get_gallery_image_ids();

$image_ids = array_values(
	array_unique(
		array_filter(
			array_merge(
				$main_image_id ? array($main_image_id) : array(),
				$gallery_ids
			)
		)
	)
);

$columns = apply_filters('woocommerce_product_thumbnails_columns', 4);

$wrapper_classes = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ($main_image_id ? 'with-images' : 'without-images'),
		'woocommerce-product-gallery--columns-' . absint($columns),
		'images',
	)
);
?>

<div
	class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?> aura-product-gallery"
	data-columns="<?php echo esc_attr($columns); ?>">
	<?php if ($image_ids) : ?>
		<button
			class="slider-next"
			type="button"
			aria-label="<?php esc_attr_e('Next image', 'woocommerce'); ?>"></button>

		<div class="swiper aura-product-gallery__main">

			<div class="swiper-wrapper">

				<?php foreach ($image_ids as $index => $image_id) : ?>
					<div class="swiper-slide">
						<?php
						$html = wc_get_gallery_image_html($image_id, $index === 0);

						echo apply_filters(
							'woocommerce_single_product_image_thumbnail_html',
							$html,
							$image_id
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				<?php endforeach; ?>

			</div>
		</div>

		<button
			class="slider-prev"
			type="button"
			aria-label="<?php esc_attr_e('Previous image', 'woocommerce'); ?>"></button>

		<?php if (count($image_ids) > 1) : ?>
			<div class="swiper aura-product-gallery__thumbs">
				<div class="swiper-wrapper">

					<?php foreach ($image_ids as $index => $image_id) : ?>
						<div class="swiper-slide">
							<?php
							echo wp_get_attachment_image(
								$image_id,
								'woocommerce_gallery_thumbnail',
								false,
								array(
									'class' => 'aura-product-gallery__thumb',
									'alt'   => sprintf(
										/* translators: %d: image number */
										esc_attr__('Product image %d', 'woocommerce'),
										$index + 1
									),
								)
							);
							?>
						</div>
					<?php endforeach; ?>

				</div>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<div class="aura-product-gallery__placeholder">
			<?php
			echo wp_kses_post(
				wc_placeholder_img('woocommerce_single')
			);
			?>
		</div>

	<?php endif; ?>
</div>